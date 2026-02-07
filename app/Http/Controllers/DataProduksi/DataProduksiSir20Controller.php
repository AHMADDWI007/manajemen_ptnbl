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

        // 🔥 TAMBAHAN PENTING: Tentukan Tanggal Awal Bulan untuk perhitungan s/d HI
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d');

        // B. Ambil Master Data
        $lokasiList = Lokasi::all();
        $mutuList = Mutu::all();
        
        
        // C. Ambil SEMUA Pallet Aktif (Belum Terjual)
        $allActivePallets = Pallet::whereNull('tanggal_penjualan')->get();

        // 🔥 C.2. Ambil Pallet TERJUAL HARI INI -> Untuk Kolom Pengiriman 🔥
        $soldPalletsToday = Pallet::whereDate('tanggal_penjualan', $formattedDate)->get();

        // ---------------------------------------------------------------------
        // LOGIKA TABEL IV (GUDANG / LOKASI)
        // ---------------------------------------------------------------------
        $tabelIV = collect();

        foreach ($lokasiList as $lokasi) {
            $saldo_akhir_kg = 0;
            
            // 1. Hitung Saldo Akhir (Stok saat ini / Realtime di Gudang X)
            // Ini menghitung fisik barang yang "detik ini" ada di gudang
            foreach($allActivePallets as $p) {
                $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $saldo_akhir_kg += $p->berat;
                }
            }

            // 2. Hitung Masuk Hari Ini
            // Pallet yang record pindahnya TEPAT pada tanggal yang dipilih
            $masuk_kg = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                            ->whereDate('tanggal', $formattedDate)
                            ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                            ->sum('pallet.berat');

            // 3. 🔥 PERBAIKAN: Hitung Produksi s/d HI (Akumulasi Bulan Ini) 🔥
            // Rumus: Total Masuk dari Tanggal 1 s/d Tanggal yang dipilih
            $sd_hi_kg = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                            ->whereBetween('tanggal', [$startOfMonth, $formattedDate]) // <-- KUNCINYA DISINI
                            ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                            ->sum('pallet.berat');

            // 4. 🔥 PERBAIKAN: Hitung "Yg Lalu" (Stok kemarin s/d tgl 1) 🔥
            // Logika Matematika: Total Sampai Hari Ini - Masuk Hari Ini = Sisa Yang Lalu
            $yg_lalu_kg = $sd_hi_kg - $masuk_kg;

            // 5. Pengiriman (Placeholder - nanti ambil dari tabel penjualan)
            $keluar_kg = 0; 

            // Cek setiap pallet yang terjual hari ini, apakah lokasi terakhirnya di gudang ini?
            foreach($soldPalletsToday as $sold) {
                $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();

                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $keluar_kg += $sold->berat;
                }
            }

            // 6. Saldo Awal (Hitung Mundur dari Saldo Akhir)
            // Rumus: Saldo Awal = Saldo Akhir - Masuk + Keluar
            $saldo_awal_kg = $saldo_akhir_kg - $masuk_kg + $keluar_kg;

            // Push Data ke View
            $tabelIV->push((object)[
                'id_lokasi'       => $lokasi->id_lokasi, 
                'no'              => '4.'.$lokasi->id_lokasi,
                'uraian'          => $lokasi->nama,
                'saldo_awal'      => $saldo_awal_kg,
                
                'masuk'           => $masuk_kg,
                'total'           => $saldo_awal_kg + $masuk_kg, // Total = Awal + Masuk
                
                // 🔥 UPDATE DATA VIEW 🔥
                'prod_bln_lalu'   => $yg_lalu_kg,  // Kolom "Yg lalu"
                'prod_sd_hi'      => $sd_hi_kg,    // Kolom "s/d HI"
                
                'pengiriman'      => $keluar_kg,
                'saldo_akhir'     => $saldo_akhir_kg,
                'keterangan'      => '-'
            ]);
        }

        // ---------------------------------------------------------------------
        // LOGIKA TABEL VI (MUTU) - (Tidak ada perubahan, tetap sama)
        // ---------------------------------------------------------------------
        $tabelVI = collect();
        $no = 1;

        foreach ($mutuList as $mutu) {
            $kg = 0;
            $palletCount = 0;

            foreach($allActivePallets as $p) {
                $lastMutu = KondisiPallet::where('id_pallet', $p->id_pallet)
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

        return view('DataProduksi.data-produksi-sir20', [
            'tabelIV'       => $tabelIV,
            'tabelVI'       => $tabelVI,
            'lokasiList'    => $lokasiList,
            'mutuList'      => $mutuList, 
            'selected_date' => $formattedDate,
            'grandTotal'    => $tabelIV->sum('saldo_akhir'),
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