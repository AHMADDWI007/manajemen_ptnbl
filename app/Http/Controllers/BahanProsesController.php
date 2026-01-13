<?php

namespace App\Http\Controllers;

use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use App\Models\PengolahanBasah;     // Model Bokar
use App\Models\TransaksiApiBokar;   // ✅ Model API Lokal (Wajib ada)
use App\Models\RektifikasiStok;     // Model Rektif Bokar
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class BahanProsesController extends Controller
{
    private $masterUraian = [
        'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
        'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
        'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
    ];

    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();
        
        $yesterday = $selectedDate->copy()->subDay();

        // 1. Ambil Data WIP
        $dataToday = BahanProses::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian'); 
        $dataYesterday = BahanProses::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');

        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $selectedDate)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;

        $finalData = collect([]);
        $prevWipKeluar = 0;
        $totals = ['saldo_awal' => 0, 'wip_masuk' => 0, 'wip_keluar' => 0, 'produksi_sir20' => 0, 'rekfif' => 0, 'saldo_akhir' => 0];

        foreach ($this->masterUraian as $uraian) {
            $row = $dataToday->get($uraian);

            if ($row) {
                $prevWipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $row->wip_keluar;
                $finalData->push($row);

                $totals['saldo_awal'] += $row->saldo_awal;
                $totals['wip_masuk'] += $row->wip_masuk;
                $totals['wip_keluar'] += $row->wip_keluar;
                $totals['produksi_sir20'] += $row->produksi_sir20;
                $totals['rekfif'] += $row->rekfif;
                $totals['saldo_akhir'] += $row->saldo_akhir;
            } else {
                $virtualRow = new BahanProses();
                $virtualRow->id = null;
                $virtualRow->tanggal = $selectedDate;
                $virtualRow->uraian = $uraian;
                
                $saldoAwal = isset($dataYesterday[$uraian]) ? $dataYesterday[$uraian]->saldo_akhir : 0;
                $virtualRow->saldo_awal = $saldoAwal;

                if ($uraian == 'Lantai Umpan Kering') {
                    $wipMasuk = $inputDariMaturasi;
                } else {
                    $wipMasuk = $prevWipKeluar;
                }
                $virtualRow->wip_masuk = $wipMasuk;

                if ($uraian == 'Di Dalam Dryer/Press Bale') {
                    $wipKeluar = 0; 
                    $produksiSIR20 = $wipMasuk; 
                    $prevWipKeluar = 0;
                } else {
                    $wipKeluar = $wipMasuk; 
                    $produksiSIR20 = 0;
                    $prevWipKeluar = $wipMasuk; 
                }
                $virtualRow->wip_keluar = $wipKeluar;
                $virtualRow->produksi_sir20 = $produksiSIR20;
                $virtualRow->rekfif = 0;
                $virtualRow->keterangan = '-';
                
                $virtualRow->saldo_akhir = $saldoAwal + $wipMasuk - $wipKeluar - $produksiSIR20;

                $finalData->push($virtualRow);

                $totals['saldo_awal'] += $virtualRow->saldo_awal;
                $totals['wip_masuk'] += $virtualRow->wip_masuk;
                $totals['wip_keluar'] += $virtualRow->wip_keluar;
                $totals['produksi_sir20'] += $virtualRow->produksi_sir20;
                $totals['rekfif'] += $virtualRow->rekfif;
                $totals['saldo_akhir'] += $virtualRow->saldo_akhir;
            }
        }

        // ==============================================================
        // ✅ HITUNG DATA EKSTERNAL UNTUK GRAND TOTAL FOOTER (VERSI REVISI)
        // ==============================================================

        // 1. Hitung Stok Akhir Bokar (Menggunakan DB Lokal agar SINKRON dengan Ringkasan Stok)
        $stokAkhirBokar = $this->getStokAkhirBokar($selectedDate);

        // 2. Hitung Stok Akhir Maturasi
        $stokAkhirMaturasi = $this->getStokAkhirMaturasi($selectedDate);

        // 3. Hitung Grand Total
        $grandTotalSaldoAkhir = $stokAkhirBokar + $stokAkhirMaturasi + $totals['saldo_akhir'];
        $grandTotalKeterangan = $totals['saldo_akhir'] + $stokAkhirMaturasi;

        return view('Pengolahan.bahan_proses', [
            'data_produksi' => $finalData,
            'selectedDate' => $selectedDate,
            'totals' => $totals,
            'grandTotalSaldoAkhir' => $grandTotalSaldoAkhir,
            'grandTotalKeterangan' => $grandTotalKeterangan,
            
            // Kirim detail untuk pengecekan (opsional)
            'detail_bokar' => $stokAkhirBokar,
            'detail_maturasi' => $stokAkhirMaturasi,
            'detail_wip' => $totals['saldo_akhir']
        ]);
    }

    // --- ✅ HELPER BARU: Hitung Stok Bokar dari DB Lokal (Bukan API Langsung) ---
    private function getStokAkhirBokar($date) {
        try {
            // 1. Total Masuk Kumulatif (Dari Tabel TransaksiApiBokar)
            // Kita ambil semua kode: 'petani', 'ptpn', 'inhut'
            $total_masuk = TransaksiApiBokar::whereDate('tanggal', '<=', $date)
                ->whereIn('kode_api', ['petani', 'ptpn', 'inhut'])
                ->sum('masuk_hi');

            // 2. Total Diolah Kumulatif (Dari Tabel PengolahanBasah - Netto Basah)
            $total_diolah = PengolahanBasah::whereDate('tanggal', '<=', $date)
                ->sum('netto_basah');

            // 3. Total Rektif Kumulatif (Dari Tabel RektifikasiStok)
            $total_rektif = 0;
            // Cek apakah tabel Rektifikasi ada (untuk keamanan)
            if (class_exists(RektifikasiStok::class)) {
                $total_rektif = RektifikasiStok::whereDate('tanggal', '<=', $date)
                    ->sum('berat');
            }

            // Rumus: Masuk - Keluar + Rektif
            return max(0, $total_masuk - $total_diolah + $total_rektif);

        } catch (\Exception $e) {
            return 0;
        }
    }

    // --- HELPER: Hitung Stok Akhir Maturasi ---
    private function getStokAkhirMaturasi($date) {
        // Stok Akhir Maturasi = Total Masuk (Kumulatif) - Total Diolah (Kumulatif) - Total Mutasi (Kumulatif)
        $sums = PengolahanMaturasi::whereDate('tgl_laporan', '<=', $date)
            ->selectRaw('SUM(masuk_hi) as in_total, SUM(diolah) as out_process, SUM(mutasi) as out_mutation')
            ->first();
        
        if (!$sums) return 0;

        return $sums->in_total - $sums->out_process - $sums->out_mutation;
    }

    // ... (Fungsi store, update, destroy, show, edit TETAP SAMA) ...
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'uraian'        => 'required|string|max:255',
            'rekfif'        => 'nullable|numeric',
            'keterangan'    => 'nullable|string',
            'wip_keluar'    => 'nullable|numeric',
            'produksi_sir20'=> 'nullable|numeric',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        $inputDate = Carbon::parse($request->tanggal_input);
        $uraian    = $request->uraian;
        $rektif    = $request->rekfif ?? 0;
        
        $inputWipKeluar = $request->filled('wip_keluar') ? $request->wip_keluar : 0;
        $inputProduksi  = $request->filled('produksi_sir20') ? $request->produksi_sir20 : 0;

        $yesterday = $inputDate->copy()->subDay();
        $dataKemarin = BahanProses::whereDate('tanggal', $yesterday)->where('uraian', $uraian)->first();
        $saldoAwal = $dataKemarin ? $dataKemarin->saldo_akhir : 0;

        $wipMasuk = 0;
        if ($uraian == 'Lantai Umpan Kering') {
            $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $inputDate)
                ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
            if ($maturasiToday) $wipMasuk = $maturasiToday->total_diolah - $maturasiToday->total_mutasi;
        } else {
            $urutan = array_search($uraian, $this->masterUraian);
            if ($urutan !== false && $urutan > 0) {
                $prevUraianName = $this->masterUraian[$urutan - 1];
                $prevData = BahanProses::whereDate('tanggal', $inputDate)->where('uraian', $prevUraianName)->first();
                $wipMasuk = $prevData ? $prevData->wip_keluar : 0;
            }
        }

        if ($uraian == 'Di Dalam Dryer/Press Bale') {
            $produksiSIR20 = $inputProduksi;
        } else {
            $produksiSIR20 = 0; 
        }

        if ($request->filled('wip_keluar')) {
             $wipKeluar = $inputWipKeluar;
        } else {
             $wipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $wipMasuk;
        }

        $saldoAkhir = $saldoAwal + $wipMasuk - $wipKeluar - $produksiSIR20 + $rektif;

        BahanProses::updateOrCreate(
            ['tanggal' => $inputDate->format('Y-m-d'), 'uraian' => $uraian],
            [
                'saldo_awal' => $saldoAwal, 'wip_masuk' => $wipMasuk, 'wip_keluar' => $wipKeluar,
                'produksi_sir20' => $produksiSIR20, 'rekfif' => $rektif, 'saldo_akhir' => $saldoAkhir,
                'keterangan' => $request->keterangan
            ]
        );

        $urutanSaatIni = array_search($uraian, $this->masterUraian);
        if ($urutanSaatIni !== false && $urutanSaatIni > 0) {
            $prevUraianName = $this->masterUraian[$urutanSaatIni - 1];
            $prevData = BahanProses::whereDate('tanggal', $inputDate)->where('uraian', $prevUraianName)->first();
            
            if ($prevData) {
                $prevData->wip_keluar = $wipMasuk;
                $prevData->saldo_akhir = $prevData->saldo_awal + $prevData->wip_masuk - $prevData->wip_keluar - $prevData->produksi_sir20 + $prevData->rekfif;
                $prevData->save();
            }
        }

        return redirect()->route('bahan-proses.index', ['filter_tanggal' => $inputDate->format('Y-m-d')])->with('success', 'Data berhasil disimpan!');
    }
    
    public function destroy($id)
    {
        $data = BahanProses::findOrFail($id);
        $data->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }
    
    public function show($id) { return response()->json(BahanProses::find($id)); }
    public function edit($id) { return response()->json(BahanProses::find($id)); }
    
    public function update(Request $request, $id) {
         $data = BahanProses::findOrFail($id);
         $input = $request->all();
         $saldoAwal = $input['saldo_awal'] ?? $data->saldo_awal;
         $masuk = $input['wip_masuk'] ?? $data->wip_masuk;
         $keluar = $input['wip_keluar'] ?? $data->wip_keluar;
         $rektif = $input['rekfif'] ?? $data->rekfif;
         
         if ($data->uraian == 'Di Dalam Dryer/Press Bale') {
             $prod = $input['produksi_sir20'] ?? $data->produksi_sir20;
         } else {
             $prod = 0;
         }

         $input['saldo_akhir'] = $saldoAwal + $masuk - $keluar - $prod + $rektif;
         $input['produksi_sir20'] = $prod; 
         
         $data->update($input);
         return redirect()->back()->with('success', 'Data diperbarui');
    }
}