<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Lokasi;
use App\Models\Pallet;
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
            
            // Tentukan Tanggal Awal Bulan & Akhir Bulan Lalu
            $startOfMonth = $selectedDate->copy()->startOfMonth()->format('Y-m-d');
            $dateAkhirBulanLalu = $selectedDate->copy()->startOfMonth()->subDay()->format('Y-m-d');

            // 1. Ambil Master Data
            $lokasiList = Lokasi::all();
            $mutuList = Mutu::all();
            
            // 2. Ambil SEMUA Pallet Aktif & Terjual (Filter Histori Akurat)
            $allActivePallets = Pallet::where(function($q) use ($formattedDate) {
                $q->whereNull('tanggal_penjualan')
                  ->orWhereDate('tanggal_penjualan', '>=', $formattedDate);
            })->get();
            
            $soldPalletsToday = Pallet::whereDate('tanggal_penjualan', $formattedDate)->get();
            $soldPalletsSdKemarin = Pallet::whereBetween(DB::raw('DATE(tanggal_penjualan)'), [
                $startOfMonth, 
                Carbon::parse($formattedDate)->subDay()->format('Y-m-d')
            ])->get();

            // =================================================================
            // 🔥 LOGIKA SALDO AWAL (SUBQUERY DB)
            // =================================================================
            // A. Saldo Awal Kemarin
            $subQueryKemarin = DB::table('lokasi_pallet')
                ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
                ->whereDate('tanggal', '<', $formattedDate)
                ->groupBy('id_pallet');

            $saldoAwalList = DB::table('lokasi_pallet as lp')
                ->joinSub($subQueryKemarin, 'latest', function ($join) {
                    $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
                })
                ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
                ->where(function($q) use ($formattedDate) {
                    $q->whereNull('p.tanggal_penjualan')
                      ->orWhereDate('p.tanggal_penjualan', '>=', $formattedDate);
                })
                ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
                ->groupBy('lp.id_lokasi')
                ->pluck('total_berat', 'id_lokasi');

            // B. Saldo Awal Bulan
            $subQueryAwalBulan = DB::table('lokasi_pallet')
                ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
                ->whereDate('tanggal', '<=', $dateAkhirBulanLalu)
                ->groupBy('id_pallet');

            $saldoAwalBulanList = DB::table('lokasi_pallet as lp')
                ->joinSub($subQueryAwalBulan, 'latest', function ($join) {
                    $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
                })
                ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
                ->where(function($q) use ($startOfMonth) {
                    $q->whereNull('p.tanggal_penjualan')
                      ->orWhereDate('p.tanggal_penjualan', '>=', $startOfMonth);
                })
                ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
                ->groupBy('lp.id_lokasi')
                ->pluck('total_berat', 'id_lokasi');

            // =================================================================
            // 🔥 SUSUN TABEL IV (GUDANG / LOKASI) FULL NET MOVEMENT
            // =================================================================
            $listGudang = [];
            $saldoGudangUtama = 0;
            $no = 1;

            $lainnya_saldo_awal = 0;
            $lainnya_masuk = 0;
            $lainnya_sd_hi = 0;
            $lainnya_yg_lalu = 0;
            $lainnya_keluar = 0;
            $lainnya_saldo_akhir = 0;

            foreach ($lokasiList as $lokasi) {
                $saldo_awal_kg = $saldoAwalList->get($lokasi->id_lokasi, 0);
                $saldo_awal_bulan_kg = $saldoAwalBulanList->get($lokasi->id_lokasi, 0);

                // Saldo Akhir Real-time
                $saldo_akhir_kg = 0;
                foreach($allActivePallets as $p) {
                    $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)
                                ->whereDate('tanggal', '<=', $formattedDate)
                                ->orderBy('id_lokasi_pallet', 'desc')
                                ->first();
                    if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                        $saldo_akhir_kg += $p->berat;
                    }
                }

                // Pengiriman Hari Ini
                $keluar_kg = 0; 
                foreach($soldPalletsToday as $sold) {
                    $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)
                                ->whereDate('tanggal', '<=', $formattedDate)
                                ->orderBy('id_lokasi_pallet', 'desc')
                                ->first();
                    if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                        $keluar_kg += $sold->berat;
                    }
                }

                // Pengiriman s/d Kemarin
                $pengiriman_sd_kemarin_kg = 0;
                foreach($soldPalletsSdKemarin as $sold) {
                    $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)
                                ->whereDate('tanggal', '<=', $formattedDate)
                                ->orderBy('id_lokasi_pallet', 'desc')
                                ->first();
                    if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                        $pengiriman_sd_kemarin_kg += $sold->berat;
                    }
                }

                // RUMUS NET MATEMATIKA (Anti Minus & Double Count)
                $net_masuk_kg = $saldo_akhir_kg - $saldo_awal_kg + $keluar_kg;
                $masuk_kg = $net_masuk_kg > 0 ? $net_masuk_kg : 0; 

                $net_yg_lalu_kg = $saldo_awal_kg - $saldo_awal_bulan_kg + $pengiriman_sd_kemarin_kg;
                $yg_lalu_kg = $net_yg_lalu_kg > 0 ? $net_yg_lalu_kg : 0; 

                $sd_hi_kg = $yg_lalu_kg + $masuk_kg;

                if ($lokasi->nama == 'Di Gudang SIR') {
                    $saldoGudangUtama = $saldo_akhir_kg;
                }

                if (in_array($lokasi->id_lokasi, [1, 2])) {
                    $listGudang[] = [
                        'no'            => '4.'.$no++,
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
                } else {
                    $lainnya_saldo_awal += $saldo_awal_kg;
                    $lainnya_masuk += $masuk_kg;
                    $lainnya_yg_lalu += $yg_lalu_kg;
                    $lainnya_sd_hi += $sd_hi_kg;
                    $lainnya_keluar += $keluar_kg;
                    $lainnya_saldo_akhir += $saldo_akhir_kg;
                }
            }

            if (count($lokasiList) > 2) {
                $listGudang[] = [
                    'no'            => '4.'.$no++,
                    'uraian'        => 'Lainnya',
                    'saldo_awal'    => (float)$lainnya_saldo_awal,
                    'masuk'         => (float)$lainnya_masuk,
                    'total'         => (float)($lainnya_saldo_awal + $lainnya_masuk),
                    'prod_bln_lalu' => (float)$lainnya_yg_lalu,
                    'prod_sd_hi'    => (float)$lainnya_sd_hi,
                    'pengiriman'    => (float)$lainnya_keluar,
                    'saldo_akhir'   => (float)$lainnya_saldo_akhir,
                    'keterangan'    => '-'
                ];
            }

            // =================================================================
            // 🔥 SUSUN TABEL VI (MUTU) DARI TRACKING PALLET
            // =================================================================
            $listMutu = [];
            $noMutu = 1;

            foreach ($mutuList as $mutu) {
                $kg = 0;
                $palletCount = 0;

                foreach($allActivePallets as $p) {
                    $lastMutu = KondisiPallet::where('id_pallet', $p->id_pallet)
                                ->whereDate('tanggal', '<=', $formattedDate)
                                ->orderBy('id_kondisi_pallet', 'desc')
                                ->first();
                    if ($lastMutu && $lastMutu->id_mutu == $mutu->id_mutu) {
                        $kg += $p->berat;
                        $palletCount++;
                    }
                }

                $listMutu[] = [
                    'no'         => '6.'.$noMutu++,
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

    // =========================================================================
    // API: AMBIL MASTER DATA LOKASI UNTUK DROPDOWN
    // =========================================================================
    public function getAllLokasi()
    {
        try {
            $lokasi = Lokasi::orderBy('id_lokasi', 'asc')->get();
            return response()->json(['success' => true, 'data' => $lokasi], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}