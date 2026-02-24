<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Lokasi;
use App\Models\Pallet;
use App\Models\BahanProses;
use App\Models\ProduksiSir;
use App\Models\LokasiPallet;
use Illuminate\Http\Request;
use App\Models\KondisiPallet;
use App\Models\ProduksiSir20;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GudangSirApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($dateStr);
            $formattedDate = $selectedDate->format('Y-m-d');
            $startOfMonth = $selectedDate->copy()->startOfMonth()->format('Y-m-d');

            // 1. Ambil Master Data
            $lokasiList = Lokasi::all();
            $mutuList = Mutu::all();
            
            // 2. Ambil SEMUA Pallet Aktif & Terjual Hari Ini
            $allActivePallets = Pallet::whereNull('tanggal_penjualan')->get();
            $soldPalletsToday = Pallet::whereDate('tanggal_penjualan', $formattedDate)->get();

            // =================================================================
            // 🔥 SUSUN TABEL IV (GUDANG / LOKASI) DARI TRACKING PALLET
            // =================================================================
            $listGudang = [];
            $saldoGudangUtama = 0;

            foreach ($lokasiList as $lokasi) {
                $saldo_akhir_kg = 0;
                
                // Saldo Akhir Real-time
                foreach($allActivePallets as $p) {
                    $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_lokasi_pallet', 'desc')->first();
                    if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                        $saldo_akhir_kg += $p->berat;
                    }
                }

                // Masuk Hari Ini
                $masuk_kg = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                                ->whereDate('tanggal', $formattedDate)
                                ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                                ->sum('pallet.berat');

                // Produksi s/d HI
                $sd_hi_kg = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                                ->whereBetween('tanggal', [$startOfMonth, $formattedDate])
                                ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                                ->sum('pallet.berat');

                $yg_lalu_kg = $sd_hi_kg - $masuk_kg;
                $keluar_kg = 0; 

                // Pengiriman
                foreach($soldPalletsToday as $sold) {
                    $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)->orderBy('id_lokasi_pallet', 'desc')->first();
                    if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                        $keluar_kg += $sold->berat;
                    }
                }

                $saldo_awal_kg = $saldo_akhir_kg - $masuk_kg + $keluar_kg;

                if ($lokasi->nama == 'Di Gudang SIR') {
                    $saldoGudangUtama = $saldo_akhir_kg;
                }

                $listGudang[] = [
                    'no'            => '4.'.$lokasi->id_lokasi,
                    'uraian'        => $lokasi->nama,
                    'saldo_awal'    => (float)$saldo_awal_kg,
                    'masuk'         => (float)$masuk_kg,
                    'total'         => (float)($saldo_awal_kg + $masuk_kg),
                    'prod_bln_lalu' => (float)$yg_lalu_kg,
                    'prod_sd_hi'    => (float)$sd_hi_kg,
                    'pengiriman'    => (float)$keluar_kg,
                    'saldo_akhir'   => (float)$saldo_akhir_kg,
                    'keterangan'    => '-'
                ];
            }

            // =================================================================
            // 🔥 SUSUN TABEL VI (MUTU) DARI TRACKING PALLET
            // =================================================================
            $listMutu = [];
            $no = 1;

            foreach ($mutuList as $mutu) {
                $kg = 0;
                $palletCount = 0;

                foreach($allActivePallets as $p) {
                    $lastMutu = KondisiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_kondisi_pallet', 'desc')->first();
                    if ($lastMutu && $lastMutu->id_mutu == $mutu->id_mutu) {
                        $kg += $p->berat;
                        $palletCount++;
                    }
                }

                $listMutu[] = [
                    'no'         => '6.'.$no++,
                    'uraian'     => $mutu->uraian,
                    'kg'         => (float)$kg,
                    'pallet'     => (int)$palletCount,
                    'keterangan' => '-'
                ];
            }

            // Info Produksi (Untuk Modal Input di HP)
            $produksiHariIni = ProduksiSir20::whereDate('tanggal_produksi', $selectedDate)->sum('kg_yang_dipress');

            return response()->json([
                'success'    => true,
                'dataGudang' => $listGudang,
                'dataMutu'   => $listMutu,
                'info'       => [
                    'wipHariIni'     => (float)$produksiHariIni,
                    'saldoGudangSir' => (float)$saldoGudangUtama
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian'  => 'required',
        ]);

        $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
        DB::beginTransaction();

        try {
            // ==========================================================
            // 1. UPDATE TABEL IV (GUDANG / LOKASI)
            // ==========================================================
            
            // Ambil Saldo Awal (H-1)
            $prevGudang = ProduksiSir::where('uraian', $request->uraian)
                ->whereDate('tanggal_produksi', '<', $tgl)
                ->orderBy('tanggal_produksi', 'desc')->first();

            $saldoAwal = $prevGudang ? $prevGudang->saldo_akhir : 0;
            $prodLalu  = $prevGudang ? $prevGudang->prod_sd_hi : 0;

            $masuk = $request->input('masuk', 0);
            $pengiriman = $request->input('pengiriman', 0);
            
            $total = $saldoAwal + $masuk;
            $prodSdHi = $prodLalu + $masuk;
            $saldoAkhir = $total - $pengiriman;

            ProduksiSir::updateOrCreate(
                ['uraian' => $request->uraian, 'tanggal_produksi' => $tgl],
                [
                    'saldo_awal'    => $saldoAwal,
                    'masuk'         => $masuk,
                    'total'         => $total,
                    'prod_bln_lalu' => $prodLalu,
                    'prod_sd_hi'    => $prodSdHi,
                    'pengiriman'    => $pengiriman,
                    'saldo_akhir'   => $saldoAkhir,
                    'keterangan'    => $request->keterangan,
                    'pallet'        => $request->input('pallet_gudang', 0) 
                ]
            );

            // ==========================================================
            // 2. UPDATE TABEL VI (MUTU)
            // ==========================================================
            if ($request->uraian == 'Di Gudang SIR') {
                $mutuItems = [
                    'Mutu Prima (siap jual)' => ['kg' => 'mutu_prima', 'pal' => 'pal_prima'],
                    'PO / PRI Low'           => ['kg' => 'po_pri',     'pal' => 'pal_po'],
                    'WhiteSpot (WS)'         => ['kg' => 'ws',         'pal' => 'pal_ws'],
                    'Kontaminasi'            => ['kg' => 'kontaminasi','pal' => 'pal_kontam'],
                    'Repacking On Hold'      => ['kg' => 'repacking',  'pal' => 'pal_repack'],
                ];

                foreach ($mutuItems as $namaMutu => $field) {
                    $kgVal = $request->input($field['kg'], 0);
                    $palVal = $request->input($field['pal'], 0);

                    ProduksiSir::updateOrCreate(
                        ['uraian' => $namaMutu, 'tanggal_produksi' => $tgl],
                        [
                            'kg'         => $kgVal,
                            'pallet'     => $palVal,
                            'keterangan' => '-'
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data Gudang & Mutu Tersimpan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: AMBIL DAFTAR PALLET UNTUK MUTASI (MOBILE)
    // =========================================================================
    public function getPalletsByLocation(Request $request)
    {
        try {
            $id_lokasi = $request->query('id_lokasi');
            $pallets = Pallet::whereNull('tanggal_penjualan')->get();
            $result = [];

            foreach($pallets as $p) {
                $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_lokasi_pallet', 'desc')->first();

                if ($lastLoc && $lastLoc->id_lokasi == $id_lokasi) {
                    $lastMutuRow = KondisiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_kondisi_pallet', 'desc')->first();
                    
                    $namaMutu = 'Unknown';
                    $idMutuNow = null;

                    if($lastMutuRow) {
                        $m = Mutu::find($lastMutuRow->id_mutu);
                        if($m) {
                            $namaMutu = $m->uraian;
                            $idMutuNow = $m->id_mutu;
                        }
                    }

                    $result[] = [
                        'id_pallet'   => $p->id_pallet,
                        'no_pallet'   => $p->no_pallet,
                        'berat'       => number_format($p->berat, 0, ',', '.'),
                        'mutu'        => $namaMutu,
                        'id_mutu_now' => $idMutuNow
                    ];
                }
            }

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: PROSES PINDAH LOKASI (MOBILE)
    // =========================================================================
    public function pindahLokasi(Request $request)
    {
        DB::beginTransaction();
        try {
            $count = 0;
            $mutuBaruArr = $request->input('mutu_baru'); // Tangkap array dari Android

            if($request->selected_pallets) {
                foreach($request->selected_pallets as $idPallet) {
                    
                    // 1. Pindah Lokasi
                    LokasiPallet::create([
                        'id_lokasi' => $request->id_lokasi_tujuan,
                        'id_pallet' => $idPallet,
                        'tanggal'   => $request->tanggal_pindah
                    ]);

                    // 2. Update Mutu (Cari ID berdasarkan Nama String)
                    if (is_array($mutuBaruArr) && isset($mutuBaruArr[$idPallet])) {
                        $namaMutu = $mutuBaruArr[$idPallet];
                        $mutuModel = Mutu::where('uraian', $namaMutu)->first();

                        if ($mutuModel) {
                            $newMutuId = $mutuModel->id_mutu;
                            $lastMutu = KondisiPallet::where('id_pallet', $idPallet)
                                        ->orderBy('id_kondisi_pallet', 'desc')->first();
                            
                            if (!$lastMutu || $lastMutu->id_mutu != $newMutuId) {
                                KondisiPallet::create([
                                    'id_pallet' => $idPallet,
                                    'id_mutu'   => $newMutuId,
                                    'tanggal'   => $request->tanggal_pindah // Samakan dgn tgl pindah
                                ]);
                            }
                        }
                    }
                    $count++;
                }
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => "$count Pallet berhasil dipindahkan."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // API: UPDATE MUTU SAJA (MOBILE)
    // =========================================================================
    public function updateStatusMutu(Request $request)
    {
        DB::beginTransaction();
        try {
            $count = 0;
            $mutuBaruArr = $request->input('mutu_baru'); // Tangkap array dari Android

            if($request->selected_pallets && is_array($mutuBaruArr)) {
                foreach($request->selected_pallets as $idPallet) {
                    if (isset($mutuBaruArr[$idPallet])) {
                        $namaMutu = $mutuBaruArr[$idPallet];
                        $mutuModel = Mutu::where('uraian', $namaMutu)->first();

                        if ($mutuModel) {
                            $newMutuId = $mutuModel->id_mutu;
                            $lastMutu = KondisiPallet::where('id_pallet', $idPallet)
                                        ->orderBy('id_kondisi_pallet', 'desc')->first();
                            
                            if (!$lastMutu || $lastMutu->id_mutu != $newMutuId) {
                                KondisiPallet::create([
                                    'id_pallet' => $idPallet,
                                    'id_mutu'   => $newMutuId,
                                    'tanggal'   => Carbon::now()->format('Y-m-d')
                                ]);
                                $count++; // Hitung yg benar-benar berubah
                            }
                        }
                    }
                }
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => "$count Pallet berhasil diperbarui mutunya."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}