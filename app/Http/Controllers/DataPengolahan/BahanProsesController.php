<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use App\Models\PengolahanBasah;
use App\Models\TransaksiApiBokar;
use App\Models\RektifikasiStok;
use App\Models\ProduksiSir20; // Pastikan model ini ada dan sesuai
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
        $this->recalculateAndSaveFlow($selectedDate);

        // 🔥 STEP 2: AMBIL DATA YANG SUDAH DISIMPAN
        // Order by PK baru (id_bahan_proses) agar urutannya sesuai saat insert
        $dataProduksi = BahanProses::whereDate('tanggal', $selectedDate)
                        ->orderBy('id_bahan_proses') // [PERBAIKAN PK]
                        ->get();

        // Hitung Total untuk Footer View
        $totals = [
            'saldo_awal'     => $dataProduksi->sum('saldo_awal'),
            'wip_masuk'      => $dataProduksi->sum('wip_masuk'),
            'wip_keluar'     => $dataProduksi->sum('wip_keluar'),
            'produksi_sir20' => $dataProduksi->sum('produksi_sir20'),
            'rekfif'         => $dataProduksi->sum('rekfif'),
            'saldo_akhir'    => $dataProduksi->sum('saldo_akhir'),
        ];

        // Hitung Grand Total Neraca Massa
        $stokAkhirBokar = $this->getStokAkhirBokar($selectedDate);
        $stokAkhirMaturasi = $this->getStokAkhirMaturasi($selectedDate);
        
        $grandTotalSaldoAkhir = $stokAkhirBokar + $stokAkhirMaturasi + $totals['saldo_akhir'];
        $grandTotalKeterangan = $totals['saldo_akhir'] + $stokAkhirMaturasi;

        return view('DataPengolahan.bahan-proses', [
            'data_produksi'       => $dataProduksi,
            'selectedDate'        => $selectedDate,
            'totals'              => $totals,
            'grandTotalSaldoAkhir'=> $grandTotalSaldoAkhir,
            'grandTotalKeterangan'=> $grandTotalKeterangan,
            'detail_bokar'        => $stokAkhirBokar,
            'detail_maturasi'     => $stokAkhirMaturasi,
            'detail_wip'          => $totals['saldo_akhir']
        ]);
    }

    /**
     * FUNGSI UTAMA: MENGHITUNG DAN MENYIMPAN ALUR PROSES
     */
    private function recalculateAndSaveFlow($date)
    {
        // ... (Bagian ambil data Maturasi & Produksi SIR20 Tetap Sama) ...
        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $date)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
        
        $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $date)->sum('kg_yang_dipress');
        $prevWipKeluar = 0;

        foreach ($this->masterUraian as $index => $uraian) {
            
            // A. Ambil Data Existing (Untuk melihat apa yang diinput user)
            $existingRow = BahanProses::whereDate('tanggal', $date)
                            ->where('uraian', $uraian)->first();
            
            // Ambil input user, kalau belum ada anggap 0
            $userWipKeluar = $existingRow ? $existingRow->wip_keluar : 0;
            $rektifUser    = $existingRow ? $existingRow->rekfif : 0;
            $ketUser       = $existingRow ? $existingRow->keterangan : '-';

            // B. Saldo Awal (H-1)
            $lastData = BahanProses::where('uraian', $uraian)
                ->whereDate('tanggal', '<', $date)
                ->orderBy('tanggal', 'desc')->first();
            $saldoAwal = $lastData ? $lastData->saldo_akhir : 0;

            // C. WIP Masuk (Estafet)
            if ($index === 0) {
                $wipMasuk = $inputDariMaturasi;
            } else {
                $wipMasuk = $prevWipKeluar;
            }

            // D. 🔥 RUMUS BARU (Sesuai Excel)
            // Rumus: Saldo Akhir = (Awal + Masuk + Rektif) - Keluar
            
            $produksi = 0;
            $finalWipKeluar = $userWipKeluar;

            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                // Khusus Dryer, Outputnya adalah Produksi Jadi
                $produksi = $realProduction;
                $finalWipKeluar = $produksi; 
            } else {
                $produksi = 0;
            }

            // Hitung Saldo Akhir
            $totalTersedia = $saldoAwal + $wipMasuk + $rektifUser;
            $saldoAkhir = $totalTersedia - $finalWipKeluar;

            // E. Simpan
            BahanProses::updateOrCreate(
                ['tanggal' => $date->format('Y-m-d'), 'uraian' => $uraian],
                [
                    'saldo_awal'     => $saldoAwal,
                    'wip_masuk'      => $wipMasuk,
                    'wip_keluar'     => $finalWipKeluar, // Disimpan sesuai input/produksi
                    'produksi_sir20' => $produksi,
                    'rekfif'         => $rektifUser,
                    'saldo_akhir'    => $saldoAkhir, // Hasil hitungan rumus
                    'keterangan'     => $ketUser
                ]
            );

            // ======================================================
            // 🔥 LOGIKA CUT-OFF (MEMUTUS ALIRAN)
            // ======================================================
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                // Jika sudah sampai Dryer, jangan oper ke 'Di Reproses Ex WS'
                // Set estafet jadi 0.
                $prevWipKeluar = 0; 
            } else {
                // Selain Dryer, lanjut estafet ke proses berikutnya
                $prevWipKeluar = $finalWipKeluar;
            }
        }
    }

    // 3. TAMBAHKAN HELPER BARU (Untuk AJAX di View)
    public function checkStock(Request $request)
    {
        $date = Carbon::parse($request->tanggal);
        $uraian = $request->uraian;

        // Cek apakah data hari ini sudah terbentuk (karena auto-calculate index)
        $data = BahanProses::whereDate('tanggal', $date)
                ->where('uraian', $uraian)->first();

        if ($data) {
            // Stok Tersedia = Saldo Awal + Masuk + Rektif
            $stok = $data->saldo_awal + $data->wip_masuk + $data->rekfif;
        } else {
            // Fallback ke H-1 jika data hari ini belum ada
            $lastData = BahanProses::where('uraian', $uraian)
                ->whereDate('tanggal', '<', $date)
                ->orderBy('tanggal', 'desc')->first();
            $stok = $lastData ? $lastData->saldo_akhir : 0;
        }

        return response()->json(['stok_tersedia' => $stok]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'uraian'        => 'required|string',
            // 🔥 PERUBAHAN: Wajib input WIP Keluar (Barang yang dipindah)
            'wip_keluar'    => 'required|numeric|min:0', 
            'rekfif'        => 'nullable|numeric',
            'keterangan'    => 'nullable|string',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator);

        // Simpan Inputan User
        BahanProses::updateOrCreate(
            ['tanggal' => $request->tanggal_input, 'uraian' => $request->uraian],
            [
                'wip_keluar' => $request->wip_keluar, // User yang menentukan ini
                'rekfif'     => $request->rekfif ?? 0,
                'keterangan' => $request->keterangan
            ]
        );

        // Hitung ulang saldo akhir berdasarkan input baru
        $this->recalculateAndSaveFlow(Carbon::parse($request->tanggal_input));

        return redirect()->back()->with('success', 'Data proses berhasil disimpan.');
    }

    public function destroy($id)
    {
        // [PERBAIKAN PK] Cari berdasarkan id_bahan_proses
        $data = BahanProses::where('id_bahan_proses', $id)->firstOrFail();
        $date = Carbon::parse($data->tanggal);

        // Reset rektif user jadi 0
        $data->update([
            'rekfif'     => 0,
            'keterangan' => null
        ]);

        // Hitung ulang
        $this->recalculateAndSaveFlow($date);
        
        return redirect()->back()->with('success', 'Data Rektifikasi di-reset ke default sistem.');
    }

    // --- Helper Functions ---
    private function getStokAkhirBokar($date) {
        try {
            $total_masuk = TransaksiApiBokar::whereDate('tanggal', '<=', $date)
                ->whereIn('kode_api', ['petani', 'ptpn', 'inhut'])
                ->sum('masuk_hi');
            
            $total_diolah = PengolahanBasah::whereDate('tanggal', '<=', $date)
                ->sum('netto_kering');
            
            $total_rektif = RektifikasiStok::whereDate('tanggal', '<=', $date)
                ->sum('berat');
            
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