<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use App\Models\PengolahanBasah;
use App\Models\TransaksiApiBokar;
use App\Models\RektifikasiStok;
use App\Models\ProduksiSir20; 
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BahanProsesController extends Controller
{
    // Urutan Proses Pabrik yang Baku
    private $masterUraian = [
        'Lantai Umpan Kering', 
        'Di Blending Tank 4', 
        'Di Lump Breaker-2 (Di Blending Tank-4)',
        'Di Pre Breaker-2 (Di Blending Tank-5)', 
        'Di Hammer Mill-2 (Di Blending Tank-6)',
        'Di Blending Tank-7', 
        'Di Trolley', 
        'Di Dalam Dryer/Press Bale', 
        'Di Reproses Ex WS.'
    ];

    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();
        
        // 🔥 STEP 1: JALANKAN KALKULASI & SIMPAN OTOMATIS
        // Ini akan memastikan semua baris terisi di database berdasarkan data Maturasi & Produksi terbaru
        $this->recalculateAndSaveFlow($selectedDate);

        // 🔥 STEP 2: AMBIL DATA YANG SUDAH DISIMPAN (Tinggal Tampil)
        $dataProduksi = BahanProses::whereDate('tanggal', $selectedDate)
                        ->orderBy('id') // Urutkan sesuai ID (urutan insert masterUraian)
                        ->get();

        // Hitung Total untuk Footer View
        $totals = [
            'saldo_awal' => $dataProduksi->sum('saldo_awal'),
            'wip_masuk' => $dataProduksi->sum('wip_masuk'),
            'wip_keluar' => $dataProduksi->sum('wip_keluar'),
            'produksi_sir20' => $dataProduksi->sum('produksi_sir20'),
            'rekfif' => $dataProduksi->sum('rekfif'),
            'saldo_akhir' => $dataProduksi->sum('saldo_akhir'),
        ];

        // Hitung Grand Total Neraca Massa (Data Pendukung Footer)
        $stokAkhirBokar = $this->getStokAkhirBokar($selectedDate);
        $stokAkhirMaturasi = $this->getStokAkhirMaturasi($selectedDate);
        
        $grandTotalSaldoAkhir = $stokAkhirBokar + $stokAkhirMaturasi + $totals['saldo_akhir'];
        $grandTotalKeterangan = $totals['saldo_akhir'] + $stokAkhirMaturasi;

        return view('DataPengolahan.bahan-proses', [
            'data_produksi' => $dataProduksi,
            'selectedDate' => $selectedDate,
            'totals' => $totals,
            'grandTotalSaldoAkhir' => $grandTotalSaldoAkhir,
            'grandTotalKeterangan' => $grandTotalKeterangan,
            'detail_bokar' => $stokAkhirBokar,
            'detail_maturasi' => $stokAkhirMaturasi,
            'detail_wip' => $totals['saldo_akhir']
        ]);
    }

    /**
     * FUNGSI UTAMA: MENGHITUNG DAN MENYIMPAN ALUR PROSES
     * Dipanggil saat Index (Buka Halaman) dan Store (Simpan Rektif)
     */
    private function recalculateAndSaveFlow($date)
    {
        // 1. Ambil Input Awal dari Maturasi (Total Diolah - Mutasi)
        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $date)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;

        // 2. Ambil Angka Produksi Real dari LHP (Menu Produksi SIR 20)
        $realProduction = ProduksiSir20::whereDate('tanggal', $date)->sum('kg_press'); 

        $prevWipKeluar = 0; // Variabel estafet

        foreach ($this->masterUraian as $index => $uraian) {
            
            // A. Ambil Data yang ada saat ini (untuk mempertahankan Rektif user)
            $existingRow = BahanProses::whereDate('tanggal', $date)
                            ->where('uraian', $uraian)->first();
            
            $rektifUser = $existingRow ? $existingRow->rekfif : 0;
            $ketUser = $existingRow ? $existingRow->keterangan : '-';

            // B. Cari Saldo Awal (Dari Saldo Akhir data terakhir sebelum hari ini)
            $lastData = BahanProses::where('uraian', $uraian)
                ->whereDate('tanggal', '<', $date)
                ->orderBy('tanggal', 'desc')
                ->first();
            $saldoAwal = $lastData ? $lastData->saldo_akhir : 0;

            // C. Tentukan WIP Masuk
            if ($index === 0) {
                // Tahap 1: Dari Maturasi
                $wipMasuk = $inputDariMaturasi;
            } else {
                // Tahap Selanjutnya: Dari Output Mesin Sebelumnya
                $wipMasuk = $prevWipKeluar;
            }

            // D. Hitung WIP Keluar, Produksi, dan Saldo Akhir
            $produksi = 0;
            $wipKeluar = 0;
            $saldoAkhir = 0;

            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                // --- DRYER (Barang Jadi) ---
                $produksi = $realProduction;
                $wipKeluar = $produksi; 
                // Saldo Akhir = (Sisa + Masuk) - (Produksi) + Rektif
                $saldoAkhir = $saldoAwal + $wipMasuk - $produksi + $rektifUser;
            } else {
                // --- MESIN PERANTARA (Continuous Flow) ---
                $produksi = 0;
                
                // Target Saldo Akhir = Saldo Awal + Rektif (Koreksi Fisik)
                $targetSaldoAkhir = $saldoAwal + $rektifUser;
                $saldoAkhir = max(0, $targetSaldoAkhir); 

                // Yang Keluar = (Yang Tersedia) - (Yang Ditahan di Akhir)
                $wipKeluar = max(0, ($saldoAwal + $wipMasuk) - $saldoAkhir);
            }

            // E. SIMPAN KE DATABASE (Update atau Create)
            BahanProses::updateOrCreate(
                ['tanggal' => $date->format('Y-m-d'), 'uraian' => $uraian],
                [
                    'saldo_awal' => $saldoAwal,
                    'wip_masuk' => $wipMasuk,
                    'wip_keluar' => $wipKeluar,
                    'produksi_sir20' => $produksi,
                    'rekfif' => $rektifUser, // Pastikan rektif user tidak hilang
                    'saldo_akhir' => $saldoAkhir,
                    'keterangan' => $ketUser
                ]
            );

            // Set nilai estafet untuk loop berikutnya
            $prevWipKeluar = $wipKeluar;
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'uraian'        => 'required|string',
            'rekfif'        => 'nullable|numeric',
            'keterangan'    => 'nullable|string',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator);

        // 1. Simpan Rektif User Terlebih Dahulu
        BahanProses::updateOrCreate(
            ['tanggal' => $request->tanggal_input, 'uraian' => $request->uraian],
            [
                'rekfif' => $request->rekfif ?? 0,
                'keterangan' => $request->keterangan
            ]
        );

        // 2. TRIGGER RE-CALCULATE (Chain Reaction)
        // Karena mengubah rektif di tahap 2 akan mengubah 'Masuk' di tahap 3, dst.
        $this->recalculateAndSaveFlow(Carbon::parse($request->tanggal_input));

        return redirect()->back()->with('success', 'Koreksi stok berhasil disimpan dan alur proses diperbarui.');
    }

    public function destroy($id)
    {
        $data = BahanProses::findOrFail($id);
        $date = Carbon::parse($data->tanggal);

        // Reset rektif user jadi 0
        $data->update([
            'rekfif' => 0,
            'keterangan' => null
        ]);

        // Hitung ulang lagi agar saldo kembali normal (default sistem)
        $this->recalculateAndSaveFlow($date);
        
        return redirect()->back()->with('success', 'Data Rektifikasi di-reset ke default sistem.');
    }

    // --- Helper Functions (Tidak Berubah) ---
    private function getStokAkhirBokar($date) {
        try {
            // A. Total Masuk (FILTER KODE API AGAR TIDAK DOUBLE COUNT)
            // Kita hanya ambil komponen pembentuknya, abaikan 'total'
            $total_masuk = TransaksiApiBokar::whereDate('tanggal', '<=', $date)
                ->whereIn('kode_api', ['petani', 'ptpn', 'inhut']) // <--- WAJIB ADA INI
                ->sum('masuk_hi');
            
            // B. Total Diolah (Pengurang Stok) -> Pakai Netto Kering
            $total_diolah = PengolahanBasah::whereDate('tanggal', '<=', $date)
                ->sum('netto_kering');
            
            // C. Total Rektif (Koreksi)
            $total_rektif = RektifikasiStok::whereDate('tanggal', '<=', $date)
                ->sum('berat');
            
            // Rumus: Masuk - Keluar + Rektif
            return max(0, $total_masuk - $total_diolah + $total_rektif);
            
        } catch (\Exception $e) { return 0; }
    }

    private function getStokAkhirMaturasi($date) {
        $sums = PengolahanMaturasi::whereDate('tgl_laporan', '<=', $date)
            ->selectRaw('SUM(masuk_hi) as in_total, SUM(diolah) as out_process, SUM(mutasi) as out_mutation')
            ->first();
        if (!$sums) return 0;
        return $sums->in_total - $sums->out_process - $sums->out_mutation;
    }
}