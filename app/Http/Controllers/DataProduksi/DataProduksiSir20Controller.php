<?php

namespace App\Http\Controllers\DataProduksi;

use App\Http\Controllers\Controller;
use App\Models\ProduksiSir; // Model Sesuai Info
use App\Models\BahanProses;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
// Pastikan Controller Penjualan sudah di-import atau path-nya benar
use App\Http\Controllers\DataProduksi\PenjualanSir20Controller; 

class DataProduksiSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // Ambil Data Hari Ini (KeyBy Uraian agar mudah diakses)
        $dataDB = ProduksiSir::whereDate('created_at', $selectedDate)
                             ->get()
                             ->keyBy('uraian');
        
        // Ambil Data Terakhir Sebelumnya (Untuk Saldo Awal)
        // Ambil semua data < tanggal terpilih, urutkan descending, unique uraian
        $prevDataDB = ProduksiSir::whereDate('created_at', '<', $selectedDate)
                                 ->orderBy('created_at', 'desc')
                                 ->get()
                                 ->unique('uraian')
                                 ->keyBy('uraian');

        // Logic Ambil Total Bahan Proses (Saldo Akhir WIP)
        $totalBahanProses = BahanProses::whereDate('tanggal', $selectedDate)->sum('saldo_akhir');
        
        // Fallback: Jika hari ini kosong, ambil hari terakhir yang ada data
        if ($totalBahanProses == 0 && BahanProses::whereDate('tanggal', $selectedDate)->count() == 0) {
            $lastBPDate = BahanProses::whereDate('tanggal', '<', $selectedDate)->max('tanggal');
            if ($lastBPDate) {
                $totalBahanProses = BahanProses::whereDate('tanggal', $lastBPDate)->sum('saldo_akhir');
            }
        }

        // --- TABEL IV: GUDANG ---
        $masterGudang = [
            '4.1' => 'Di Gudang SIR',
            '4.2' => 'Di Areal Press Bale',
            '4.3' => 'Di Gudang TOH 1',
            '4.4' => 'Di Gudang TOH 2',
        ];

        $tabelIV = new Collection();
        $totalSaldoGudang = 0;

        foreach ($masterGudang as $no => $namaGudang) {
            $item = $dataDB[$namaGudang] ?? null;
            $prevItem = $prevDataDB[$namaGudang] ?? null;

            // Logic Estafet Saldo
            // Saldo Awal Hari Ini = Saldo Akhir Hari Sebelumnya
            $saldo_awal = $item ? $item->saldo_awal : ($prevItem ? $prevItem->saldo_akhir : 0);
            $prod_bln_lalu = $item ? $item->prod_bln_lalu : ($prevItem ? $prevItem->prod_sd_hi : 0);
            
            $masuk = $item->masuk ?? 0;
            $pengiriman = $item->pengiriman ?? 0;
            
            $total = $saldo_awal + $masuk;
            $prod_sd_hi = $prod_bln_lalu + $masuk; 
            $saldo_akhir = $total - $pengiriman;
            
            $totalSaldoGudang += $saldo_akhir;

            $tabelIV->push((object)[
                'id_produksi_sir' => $item->id_produksi_sir ?? null, // 🔥 PK Baru
                'no' => $no,
                'uraian' => $namaGudang,
                'saldo_awal' => $saldo_awal,
                'masuk' => $masuk,
                'total' => $total,
                'prod_bln_lalu' => $prod_bln_lalu,
                'prod_sd_hi' => $prod_sd_hi,
                'pengiriman' => $pengiriman,
                'saldo_akhir' => $saldo_akhir,
                'keterangan' => $item->keterangan ?? '-',
            ]);
        }

        $grandTotal = $totalBahanProses + $totalSaldoGudang;

        // --- TABEL VI: MUTU ---
        $masterMutu = [
            '6.1' => 'Mutu Prima (siap jual)',
            '6.2' => 'PO / PRI Low',
            '6.3' => 'WhiteSpot (WS)',
            '6.4' => 'Kontaminasi',
            '6.5' => 'Repacking On Hold',
        ];
        
        $tabelVI = new Collection();
        foreach ($masterMutu as $no => $uraian) {
            $item = $dataDB[$uraian] ?? null;
            $tabelVI->push((object)[
                'id_produksi_sir' => $item->id_produksi_sir ?? null, // 🔥 PK Baru
                'no' => $no,
                'uraian' => $uraian,
                'kg' => $item->kg ?? 0,
                'pallet' => $item->pallet ?? 0,
                'keterangan' => $item->keterangan ?? '-',
            ]);
        }

        return view('DataProduksi.data-produksi-sir20', [
            'tabelIV' => $tabelIV,
            'tabelVI' => $tabelVI,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'grandTotal' => $grandTotal
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['tanggal' => 'required|date']);
        $tgl = Carbon::parse($request->tanggal);
        
        DB::beginTransaction();
        
        try {
            $gudangSirUpdated = false;
            $pengirimanSir = 0;
            
            // --- SIMPAN DATA GUDANG (TABEL IV) ---
            if ($request->has('uraian') && !empty($request->uraian)) {
                // Cari Saldo Akhir hari sebelumnya untuk dijadikan Saldo Awal hari ini
                $prevData = ProduksiSir::where('uraian', $request->uraian)
                    ->whereDate('created_at', '<', $tgl)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $saldo_awal = $prevData ? $prevData->saldo_akhir : 0;
                $prod_bln_lalu = $prevData ? $prevData->prod_sd_hi : 0;
                
                $masuk = $request->masuk ?? 0;
                $pengiriman = $request->pengiriman ?? 0;
                
                $total = $saldo_awal + $masuk;
                $prod_sd_hi = $prod_bln_lalu + $masuk; 
                $saldo_akhir = $total - $pengiriman;

                ProduksiSir::updateOrCreate(
                    [
                        'uraian' => $request->uraian, 
                        'created_at' => $tgl->format('Y-m-d H:i:s') // Kunci Pencarian (Tanggal & Uraian)
                    ],
                    [
                        'saldo_awal' => $saldo_awal, 
                        'masuk' => $masuk, 
                        'total' => $total, 
                        'prod_bln_lalu' => $prod_bln_lalu, 
                        'prod_sd_hi' => $prod_sd_hi, 
                        'pengiriman' => $pengiriman, 
                        'saldo_akhir' => $saldo_akhir, 
                        'keterangan' => $request->keterangan, 
                        'updated_at' => Carbon::now()
                    ]
                );

                // Cek Trigger Sinkronisasi ke Penjualan
                if ($request->uraian == 'Di Gudang SIR') {
                    $gudangSirUpdated = true;
                    $pengirimanSir = $pengiriman;
                }
            }
            
            // --- SIMPAN DATA MUTU (TABEL VI) ---
            $mapMutu = [
                'mutu_prima' => 'Mutu Prima (siap jual)', 
                'po_pri' => 'PO / PRI Low', 
                'ws' => 'WhiteSpot (WS)', 
                'kontaminasi' => 'Kontaminasi', 
                'repacking' => 'Repacking On Hold'
            ];
            
            foreach ($mapMutu as $inputKey => $dbUraian) {
                if ($request->filled($inputKey)) {
                    ProduksiSir::updateOrCreate(
                        [
                            'uraian' => $dbUraian, 
                            'created_at' => $tgl->format('Y-m-d H:i:s')
                        ],
                        [
                            'kg' => $request->input($inputKey), 
                            'pallet' => ($inputKey == 'mutu_prima') ? $request->input('pallet', 0) : 0, 
                            'updated_at' => Carbon::now()
                        ]
                    );
                }
            }

            // --- TRIGGER SINKRONISASI KE PENJUALAN ---
            if ($gudangSirUpdated) {
                $penjualanController = new PenjualanSir20Controller();
                // Kirim Notifikasi/Log Update ke Penjualan
                $penjualanController->recalculateAndSave($tgl->format('Y-m-d'), 'SIR20 PTNBL', $pengirimanSir, 'Auto Sync dari Pengiriman Gudang');
                // Reset PTPN4 (Opsional, sesuai logika lama)
                $penjualanController->recalculateAndSave($tgl->format('Y-m-d'), 'SIR20 PTPN4', 0, 'Auto Sync dari Pengiriman Gudang (Diset 0)');
            }

            DB::commit();
            return redirect()->route('data-sir.index', ['filter_tanggal' => $tgl->format('Y-m-d')])
                             ->with('success', 'Data Gudang & Mutu berhasil disimpan!');
                             
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getProductionToday(Request $request) {
        $date = $request->date;
        
        // Ambil dari Bahan Proses (Dryer/Press Bale)
        $wipDryer = BahanProses::whereDate('tanggal', $date)
                               ->where('uraian', 'Di Dalam Dryer/Press Bale')
                               ->first();
        
        $produksiKg = $wipDryer ? $wipDryer->produksi_sir20 : 0;
        
        // Estimasi Pallet (1260 Kg per Pallet)
        $estPallet = ($produksiKg > 0) ? floor($produksiKg / 1260) : 0;
        
        return response()->json(['masuk' => $produksiKg, 'pallet' => $estPallet]);
    }

    public function destroy($id) {
        // 🔥 [PERBAIKAN] Cari berdasarkan PK Baru
        $data = ProduksiSir::find($id); 
        
        if ($data) { 
            $tgl = Carbon::parse($data->created_at)->format('Y-m-d');
            $uraian = $data->uraian;
            
            $data->delete(); 
            
            // Jika Gudang SIR dihapus, reset Penjualan juga
            if ($uraian == 'Di Gudang SIR') {
                $penjualanController = new PenjualanSir20Controller();
                $penjualanController->recalculateAndSave($tgl, 'SIR20 PTNBL', 0, 'Auto Sync setelah data Gudang di-reset/hapus');
                $penjualanController->recalculateAndSave($tgl, 'SIR20 PTPN4', 0, 'Auto Sync setelah data Gudang di-reset/hapus');
            }
            return back()->with('success', 'Data berhasil di-reset.'); 
        }
        return back()->with('error', 'Data tidak ditemukan.');
    }
}