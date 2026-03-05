<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// Import Model
use App\Models\Pallet;
use App\Models\Lokasi;
use App\Models\Mutu;
use App\Models\LokasiPallet;
use App\Models\KondisiPallet;

class DataProduksiSir20Controller extends Controller
{
    // =========================================================================
    // 1. DASHBOARD UTAMA (TABLE IV & VI)
    // =========================================================================
    public function index(Request $request)
    {
        // A. Filter Tanggal
        $dateInput = $request->input('filter_tanggal');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        $formattedDate = $date->format('Y-m-d');

        // B. Tentukan Tanggal Awal Bulan & Akhir Bulan Lalu
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d');
        $dateAkhirBulanLalu = $date->copy()->startOfMonth()->subDay()->format('Y-m-d');

        // C. Ambil Master Data
        $lokasiList = Lokasi::all();
        $mutuList = Mutu::all();
        
        // D. Ambil Pallet (Aktif, Terjual Hari Ini, Terjual s/d Kemarin)
        $allActivePallets = Pallet::whereNull('tanggal_penjualan')->get();
        $soldPalletsToday = Pallet::whereDate('tanggal_penjualan', $formattedDate)->get();
        $soldPalletsSdKemarin = Pallet::whereBetween(DB::raw('DATE(tanggal_penjualan)'), [
            $startOfMonth, 
            Carbon::parse($formattedDate)->subDay()->format('Y-m-d')
        ])->get();

        // ---------------------------------------------------------------------
        // LOGIKA TABEL IV (GUDANG / LOKASI) - FULL NET MOVEMENT (HARIAN & BULANAN)
        // ---------------------------------------------------------------------
        
        // 🔥 1. SALDO AWAL (Kondisi Stok Pada Akhir Hari Kemarin)
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

        // 🔥 2. SALDO AWAL BULAN (Kondisi Stok Pada Tanggal 1 pagi / Akhir Bulan Lalu)
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

        $tabelIV = collect();
        $no = 1;

        // Variabel penampung untuk baris "Lainnya"
        $lainnya_saldo_awal = 0;
        $lainnya_masuk = 0;
        $lainnya_sd_hi = 0;
        $lainnya_yg_lalu = 0;
        $lainnya_keluar = 0;
        $lainnya_saldo_akhir = 0;
        $lainnya_id_lokasi = [];

        foreach ($lokasiList as $lokasi) {
            
            // A. Saldo Awal Hari Ini & Saldo Awal Bulan
            $saldo_awal_kg = $saldoAwalList->get($lokasi->id_lokasi, 0);
            $saldo_awal_bulan_kg = $saldoAwalBulanList->get($lokasi->id_lokasi, 0);

            // B. Saldo Akhir (Realtime Hari Ini Sesuai Filter Tanggal)
            $saldo_akhir_kg = 0;
            foreach($allActivePallets as $p) {
                // 🔥 FIX: Tambahkan filter tanggal agar histori tidak bocor ke masa depan
                $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $formattedDate)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $saldo_akhir_kg += $p->berat;
                }
            }

            // C. Pengiriman Hari Ini
            $keluar_kg = 0; 
            foreach($soldPalletsToday as $sold) {
                // 🔥 FIX: Tambahkan filter tanggal
                $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->whereDate('tanggal', '<=', $formattedDate)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $keluar_kg += $sold->berat;
                }
            }

            // D. Pengiriman s/d Kemarin (Bulan Ini)
            $pengiriman_sd_kemarin_kg = 0;
            foreach($soldPalletsSdKemarin as $sold) {
                // 🔥 FIX: Tambahkan filter tanggal
                $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->whereDate('tanggal', '<=', $formattedDate)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $pengiriman_sd_kemarin_kg += $sold->berat;
                }
            }

            // 🔥 RUMUS NET MATEMATIKA (Anti Double-Count & Anti Minus) 🔥
            // 1. Hitung Net Masuk Hari Ini (Akhir - Awal + Keluar Penjualan)
            $net_masuk_kg = $saldo_akhir_kg - $saldo_awal_kg + $keluar_kg;
            $masuk_kg = $net_masuk_kg > 0 ? $net_masuk_kg : 0; 

            // 2. Hitung Produksi Yg Lalu (Bulan Ini s/d Kemarin)
            $net_yg_lalu_kg = $saldo_awal_kg - $saldo_awal_bulan_kg + $pengiriman_sd_kemarin_kg;
            $yg_lalu_kg = $net_yg_lalu_kg > 0 ? $net_yg_lalu_kg : 0; 

            // 3. Produksi s/d HI (Akumulasi yang sudah bersih dari minus)
            $sd_hi_kg = $yg_lalu_kg + $masuk_kg;

            // PISAHKAN LOGIKA TAMPILAN
            if (in_array($lokasi->id_lokasi, [1, 2])) {
                $tabelIV->push((object)[
                    'id_lokasi'       => $lokasi->id_lokasi, 
                    'no'              => '4.'.$no++,
                    'uraian'          => $lokasi->nama,
                    'saldo_awal'      => $saldo_awal_kg,
                    'masuk'           => $masuk_kg,
                    'total'           => $saldo_awal_kg + $masuk_kg,
                    'prod_bln_lalu'   => $yg_lalu_kg,
                    'prod_sd_hi'      => $sd_hi_kg,
                    'pengiriman'      => $keluar_kg,
                    'saldo_akhir'     => $saldo_akhir_kg,
                    'keterangan'      => '-'
                ]);
            } else {
                $lainnya_saldo_awal += $saldo_awal_kg;
                $lainnya_masuk += $masuk_kg;
                $lainnya_yg_lalu += $yg_lalu_kg;
                $lainnya_sd_hi += $sd_hi_kg;
                $lainnya_keluar += $keluar_kg;
                $lainnya_saldo_akhir += $saldo_akhir_kg;
                $lainnya_id_lokasi[] = $lokasi->id_lokasi;
            }
        }

        if (count($lainnya_id_lokasi) > 0) {
            $tabelIV->push((object)[
                'id_lokasi'       => implode(',', $lainnya_id_lokasi), 
                'no'              => '4.'.$no,
                'uraian'          => 'Lainnya',
                'saldo_awal'      => $lainnya_saldo_awal,
                'masuk'           => $lainnya_masuk,
                'total'           => $lainnya_saldo_awal + $lainnya_masuk,
                'prod_bln_lalu'   => $lainnya_yg_lalu,
                'prod_sd_hi'      => $lainnya_sd_hi,
                'pengiriman'      => $lainnya_keluar,
                'saldo_akhir'     => $lainnya_saldo_akhir,
                'keterangan'      => '-'
            ]);
        }

        // =====================================================================
        // 🔥 PERHITUNGAN GRAND TOTAL (TOTAL I s/d IV)
        // =====================================================================
        $totalGudang = $tabelIV->sum('saldo_akhir');

        $bokarMasuk = \App\Models\TransaksiApiBokar::whereDate('tanggal', '<=', $formattedDate)
                        ->whereIn('kode_api', ['petani', 'ptpn', 'inhut'])
                        ->sum('masuk_hi');

        $bokarKeluar = \App\Models\PengolahanBasah::whereDate('tanggal', '<=', $formattedDate)
                        ->whereIn('jenis', ['PT', 'DS', 'INHUT'])
                        ->sum('netto_kering');

        $bokarRektif = \App\Models\RektifikasiStok::whereDate('tanggal', '<=', $formattedDate)
                        ->whereIn('jenis', ['PT', 'DS', 'INHUT'])
                        ->sum('berat');
        
        $stokBasah = ($bokarMasuk - $bokarKeluar) + $bokarRektif;
        if($stokBasah < 0) $stokBasah = 0;

        $maturasiStats = \App\Models\PengolahanMaturasi::whereDate('tgl_laporan', '<=', $formattedDate)
                            ->selectRaw('SUM(masuk_hi) as in_val, SUM(diolah) as out_val, SUM(mutasi) as mut_val')
                            ->first();
        
        $stokMaturasi = 0;
        if($maturasiStats) {
            $stokMaturasi = $maturasiStats->in_val - $maturasiStats->out_val - $maturasiStats->mut_val;
        }

        $stokWIP = \App\Models\BahanProses::whereDate('tanggal', $formattedDate)->sum('saldo_akhir');
        $totalBahanProses = $stokMaturasi + $stokWIP;
        $grandTotal = $stokBasah + $totalBahanProses + $totalGudang;
    
        // ---------------------------------------------------------------------
        // LOGIKA TABEL VI (MUTU)
        // ---------------------------------------------------------------------
        $tabelVI = collect();
        $no = 1;

        foreach ($mutuList as $mutu) {
            $kg = 0;
            $palletCount = 0;

            foreach($allActivePallets as $p) {
                // 🔥 FIX: Tambahkan filter tanggal mutu
                $lastMutu = KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $formattedDate)
                            ->orderBy('id_kondisi_pallet', 'desc')
                            ->first();
                
                if ($lastMutu && $lastMutu->id_mutu == $mutu->id_mutu) {
                    $kg += $p->berat;
                    $palletCount++;
                }
            }

            $tabelVI->push((object)[
                'no'              => $no++,
                'uraian'          => $mutu->uraian,
                'kg'              => $kg,
                'pallet'          => $palletCount,
                'keterangan'      => '-'
            ]);
        }

        // ---------------------------------------------------------------------
        // BUAT LIST KHUSUS UNTUK DROPDOWN DI MODAL EDIT
        // ---------------------------------------------------------------------
        $dropdownLokasi = collect();
        
        foreach ($lokasiList as $loc) {
            if (in_array($loc->id_lokasi, [1, 2])) {
                $dropdownLokasi->push($loc);
            }
        }
        
        // PENTING: Karena Anda sudah merubah ID 3 menjadi "Lainnya" di Database, 
        // kita bisa langsung memanggilnya dari database tanpa perlu bikin object manual lagi!
        $lokasiLainnya = Lokasi::where('id_lokasi', 3)->first();
        if ($lokasiLainnya) {
            $dropdownLokasi->push($lokasiLainnya);
        }

        return view('DataProduksi.data-produksi-sir20', [
            'tabelIV'        => $tabelIV,
            'tabelVI'        => $tabelVI,
            'lokasiList'     => $lokasiList,
            'dropdownLokasi' => $dropdownLokasi, 
            'mutuList'       => $mutuList, 
            'selected_date'  => $formattedDate,
            'grandTotal'     => $grandTotal, 
        ]);
    }

    // =========================================================================
    // API: AMBIL INFO PRODUKSI HARI INI (Untuk Modal Input Data Gudang)
    // =========================================================================
    public function getProductionToday(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        // 1. Ambil Data Produksi dari Pabrik (Tabel produksi_sir20)
        // Kita perlu tahu total KG dan Pallet yang diproduksi hari ini di pabrik
        $produksiPabrik = \App\Models\ProduksiSir20::whereDate('tanggal_produksi', $date)
                            ->selectRaw('SUM(kg_yang_dipress) as total_kg, SUM(jumlah_pallet) as total_pallet')
                            ->first();

        $totalKg = $produksiPabrik->total_kg ?? 0;
        $totalPallet = $produksiPabrik->total_pallet ?? 0;

        // 2. Info Lab (Opsional: Jika ingin menampilkan warning mutu rendah)
        // Misal ambil jumlah pallet dengan PRI rendah dari data lab hari ini
        $lowPriCount = \App\Models\HasilUjiLabSIR20::whereDate('tanggal', '<=', $date)
                        ->where('pri', '<', 50) // Ambang batas contoh
                        ->count();

        return response()->json([
            'total_target_kg'     => $totalKg,      // Akan mengisi #lblTotalKg
            'total_target_pallet' => $totalPallet,  // Akan mengisi #lblTotalPallet
            'masuk_kg'            => $totalKg,      // Sama dengan total produksi
            'low_pri'             => $lowPriCount
        ]);
    }

    // =========================================================================
    // 2. API: AMBIL DAFTAR PALLET (Untuk Isi Modal Mutasi)
    // =========================================================================
    // Dipanggil via AJAX saat tombol "Pindah" diklik
    public function getPalletsByLocation(Request $request)
    {
        $id_lokasi = $request->id_lokasi;
        $pallets = Pallet::whereNull('tanggal_penjualan')->get();
        $result = [];

        foreach($pallets as $p) {
            $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_lokasi_pallet', 'desc')->first();

            if ($lastLoc && $lastLoc->id_lokasi == $id_lokasi) {
                
                $lastMutuRow = KondisiPallet::where('id_pallet', $p->id_pallet)->orderBy('id_kondisi_pallet', 'desc')->first();
                
                $namaMutu = 'Unknown';
                $idMutuNow = null; // Variable baru

                if($lastMutuRow) {
                    $m = Mutu::find($lastMutuRow->id_mutu);
                    if($m) {
                        $namaMutu = $m->uraian;
                        $idMutuNow = $m->id_mutu; // Simpan ID Mutu
                    }
                }

                $result[] = [
                    'id_pallet'   => $p->id_pallet,
                    'no_pallet'   => $p->no_pallet,
                    'berat'       => number_format($p->berat, 2, ',', '.'),
                    'mutu'        => $namaMutu,
                    'id_mutu_now' => $idMutuNow // Kirim ke JSON
                ];
            }
        }

        return response()->json($result);
    }

    // =========================================================================
    // 3. ACTION: PROSES MUTASI (PINDAH GUDANG)
    // =========================================================================
    public function pindahLokasi(Request $request)
    {
        $request->validate([
            'id_lokasi_tujuan' => 'required|exists:lokasi,id_lokasi',
            'tanggal_pindah'   => 'required|date',
            'selected_pallets' => 'required|array',
            'mutu_baru'        => 'array', // Array mutu baru [id_pallet => id_mutu]
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach($request->selected_pallets as $idPallet) {
                
                // 1. PINDAH LOKASI (Insert History Lokasi)
                LokasiPallet::create([
                    'id_lokasi' => $request->id_lokasi_tujuan,
                    'id_pallet' => $idPallet,
                    'tanggal'   => $request->tanggal_pindah
                ]);

                // 2. CEK & UPDATE MUTU (Jika user mengubah dropdown)
                if (isset($request->mutu_baru[$idPallet])) {
                    $newMutuId = $request->mutu_baru[$idPallet];

                    // Cek mutu terakhir di DB
                    $lastMutu = KondisiPallet::where('id_pallet', $idPallet)
                                ->orderBy('id_kondisi_pallet', 'desc')
                                ->first();
                    
                    // Hanya insert jika mutu BERBEDA dengan yang terakhir (Hemat DB)
                    if (!$lastMutu || $lastMutu->id_mutu != $newMutuId) {
                        KondisiPallet::create([
                            'id_pallet' => $idPallet,
                            'id_mutu'   => $newMutuId,
                            'tanggal'   => $request->tanggal_pindah // Tanggal perubahan mutu = tanggal pindah
                        ]);
                    }
                }

                $count++;
            }
            
            DB::commit();
            return back()->with('success', $count . ' Pallet berhasil dipindahkan & mutu diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memindahkan pallet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 4. ACTION: UPDATE MUTU SAJA (TANPA PINDAH)
    // =========================================================================
    public function updateStatusMutu(Request $request)
    {
        $request->validate([
            'selected_pallets' => 'required|array',
            'mutu_baru'        => 'array',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach($request->selected_pallets as $idPallet) {
                // Cek apakah ada perubahan mutu yang dikirim
                if (isset($request->mutu_baru[$idPallet])) {
                    $newMutuId = $request->mutu_baru[$idPallet];

                    // Cek mutu terakhir di DB
                    $lastMutu = KondisiPallet::where('id_pallet', $idPallet)
                                ->orderBy('id_kondisi_pallet', 'desc')
                                ->first();
                    
                    // Insert jika mutu beda atau belum ada
                    if (!$lastMutu || $lastMutu->id_mutu != $newMutuId) {
                        KondisiPallet::create([
                            'id_pallet' => $idPallet,
                            'id_mutu'   => $newMutuId,
                            'tanggal'   => Carbon::now() // Tanggal update = hari ini
                        ]);
                        $count++;
                    }
                }
            }
            
            DB::commit();
            
            if($count == 0) {
                return back()->with('info', 'Tidak ada perubahan mutu yang disimpan.');
            }
            return back()->with('success', $count . ' Status Mutu Pallet berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update mutu: ' . $e->getMessage());
        }
    }
}