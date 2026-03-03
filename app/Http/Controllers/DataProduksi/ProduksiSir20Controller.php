<?php

namespace App\Http\Controllers\DataProduksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DataPengolahan\BahanProsesController;
use App\Models\AktualTemperatureSir20;
use App\Models\BahanBakarSir20;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\KondisiPallet;
use App\Models\Lokasi;
use App\Models\LokasiPallet;
use App\Models\Maturasi;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\PengolahanMaturasi;
use App\Models\ProduksiSir20;
use App\Models\ProduksiSir;
use App\Models\RemahanSir20;
use App\Models\User;
use App\Traits\MaturasiSyncTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduksiSir20Controller extends Controller
{

    use MaturasiSyncTrait; // 🔥 Tambahkan baris ini

    // =========================================================================
    // 🔥 HELPER BARU: TARIK STOK & UMUR KHUSUS UNTUK TANGGAL TERTENTU
    // =========================================================================
    private function getBakAktifUntukTanggal(Carbon $hari_ini)
    {
        $bak_aktif_raw = Maturasi::orderBy('uraian', 'asc')->get();
        $bak_aktif = collect(); 

        foreach ($bak_aktif_raw as $bak) {
            // 1. HITUNG STOK MURNI SIAP GILING (H-1)
            $sums = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tgl_laporan', '<', $hari_ini->toDateString())
                ->select(
                    DB::raw('COALESCE(SUM(masuk_hi),0) as sum_masuk'),
                    DB::raw('COALESCE(SUM(diolah),0) as sum_diolah'),
                    DB::raw('COALESCE(SUM(mutasi),0) as sum_mutasi')
                )->first();

            $stok_kemarin = ($sums->sum_masuk ?? 0) - ($sums->sum_diolah ?? 0) - ($sums->sum_mutasi ?? 0);
            
            if (!PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->exists()) {
                $stok_kemarin = $bak->stok_awal;
            }

            if ($stok_kemarin > 0.01) {
                // 2. LOGIKA UMUR (SINKRON 100% DENGAN MATURASI)
                $tgl_basis = null;
                $logsBeforeToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', '<', $hari_ini->toDateString())
                    ->orderBy('tgl_laporan', 'asc')->get();

                $running_stock = 0;
                foreach($logsBeforeToday as $log) {
                    $logDate = Carbon::parse($log->tgl_laporan);
                    $in_fresh = $log->masuk_hi;
                    $mutasi_in = $log->mutasi < -0.01 ? abs($log->mutasi) : 0;
                    $out = $log->diolah + ($log->mutasi > 0.01 ? $log->mutasi : 0);

                    // 🔥 PERBAIKAN: Pokoknya setiap ada MASUK FRESH, umur untuk besok otomatis reset!
                    if ($in_fresh > 0.01) {
                        $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                            ->whereDate('tanggal', '<=', $logDate->toDateString())->orderBy('tanggal', 'desc')->first();
                        $tgl_basis = $lab ? Carbon::parse($lab->tanggal) : $logDate;
                    } 
                    elseif ($running_stock <= 0.01 && $mutasi_in > 0.01) {
                        $tgl_basis = $logDate;
                    }

                    $running_stock = $running_stock + $in_fresh + $mutasi_in - $out;
                    if ($running_stock <= 0.01) $tgl_basis = null;
                }

                if (!$tgl_basis) {
                    $historyDate = $this->getHistoryDateFromLog($bak->id_maturasi, $hari_ini);
                    $tgl_basis = $historyDate ? $historyDate : (!empty($bak->tgl_masuk) ? Carbon::parse($bak->tgl_masuk) : Carbon::parse($bak->created_at));
                }

                $tgl_basis = $tgl_basis->startOfDay();
                
                // 3. SET DATA FINAL
                $bak->umur = (int) $tgl_basis->diffInDays($hari_ini); 
                $bak->stok_akhir = round($stok_kemarin, 2);
                $bak_aktif->push($bak);
            }
        }
        return $bak_aktif;
    }

    public function index(Request $request)
    {
        // 🔥 JIKA INI PANGGILAN AJAX DARI FORM UBAH TANGGAL, KEMBALIKAN JSON!
        if ($request->has('ajax_date')) {
            $hari_ini = Carbon::parse($request->ajax_date)->startOfDay();
            return response()->json($this->getBakAktifUntukTanggal($hari_ini));
        }

        $filter_tgl = $request->input('filter_tanggal');
        $hari_ini = $filter_tgl ? Carbon::parse($filter_tgl)->startOfDay() : Carbon::today()->startOfDay();

        $history = ProduksiSir20::with(['remahan'])
            ->when($filter_tgl, fn($q) => $q->whereDate('tanggal_produksi', $hari_ini))
            ->orderBy('tanggal_produksi', 'desc')
            ->get();
            
        $bak_aktif = $this->getBakAktifUntukTanggal($hari_ini);

        $lastProduction = ProduksiSir20::orderBy('id_produksi_sir20', 'desc')->first();
        $users = User::orderBy('fullname', 'asc')->get();
        $lastNomorAkhir = $lastProduction ? $lastProduction->total_nomor_akhir : 0;
        
        return view('DataProduksi.produksi-sir20', [
            'history' => $history, 'bak_aktif' => $bak_aktif, 'lastNomorAkhir' => $lastNomorAkhir, 'users' => $users, 'selected_date' => $hari_ini->format('Y-m-d')
        ]);
    }

    public function show($id)
    {
        // 1. Ambil data utama produksi
        $data = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])->findOrFail($id);
        
        // 2. Load opsi maturasi sesuai tanggal produksi
        $tgl_produksi = Carbon::parse($data->tanggal_produksi)->startOfDay();
        $data->opsi_maturasi = $this->getBakAktifUntukTanggal($tgl_produksi);

        // 3. 🔥 AMBIL DATA PALLET DARI DATABASE (Nomor & Jenis)
        // Kita cari pallet yang diproduksi pada tanggal dan range nomor tersebut
        $tahunPallet = Carbon::parse($data->tanggal_produksi)->format('y');
        // UBAH BAGIAN INI: Hilangkan 'PLT-'
        $prefix = $tahunPallet . '-';
        $noStart = $prefix . str_pad($data->nomor_start, 4, '0', STR_PAD_LEFT);
        $noEnd = $prefix . str_pad($data->nomor_end, 4, '0', STR_PAD_LEFT);

        $data->details_pallets = Pallet::whereBetween('no_pallet', [$noStart, $noEnd])
            ->where('tanggal_produksi', $data->tanggal_produksi)
            ->orderBy('no_pallet', 'asc')
            ->get(['no_pallet', 'jenis_pallet']);

        return response()->json($data);
    }

    // =========================================================================
    // HELPER DARI MATURASI (DITAMBAHKAN AGAR UMUR SINKRON 100%)
    // =========================================================================
    protected function getHistoryDateFromLog(int $id_maturasi, Carbon $reportDate)
    {
        $lastEntry = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $reportDate->toDateString())
            ->where(function($q) {
                $q->where('masuk_hi', '>', 0.01)->orWhere('mutasi', '<', -0.01); 
            })->orderBy('tgl_laporan', 'desc')->first();

        if ($lastEntry) {
            if ($lastEntry->masuk_hi > 0.01) {
                $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
                    ->whereDate('tanggal', '<=', $lastEntry->tgl_laporan)->orderBy('tanggal', 'desc')->first();
                return $lab ? Carbon::parse($lab->tanggal) : Carbon::parse($lastEntry->tgl_laporan);
            }
            return Carbon::parse($lastEntry->tgl_laporan);
        }

        $logLab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', '<', $reportDate->toDateString())->orderBy('tanggal', 'desc')->first();
        return $logLab ? Carbon::parse($logLab->tanggal) : null;
    }

    // =========================================================================
    // 🔥 STORE FINAL (SIMPAN DATA PABRIK + GENERATE PALLET GUDANG)
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        DB::beginTransaction(); 

        try {
            // 1. Simpan Header Produksi (Laporan Pabrik)
            $produksi = ProduksiSir20::create([
                'tanggal_produksi'      => $request->tanggal_produksi,
                'shift_kerja'           => $request->shift_kerja,
                'jam_start_dryer'       => $request->jam_start_dryer,
                'jumlah_trolly_masuk'   => $this->cleanNumber($request->trolly_masuk),
                'jumlah_trolly_keluar'  => $this->cleanNumber($request->trolly_keluar),
                'jam_stop_dryer'        => $request->jam_stop_dryer,
                'jumlah_jam_dryer'      => $this->cleanNumber($request->jam_jalan_dryer),
                'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales),
                'kg_yang_dipress'       => $this->cleanNumber($request->kg_press),
                'capacity_per_jam'      => $this->cleanNumber($request->input('capacity_per_jam', 0)),
                'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
                'produktivitas'         => $this->cleanNumber($request->input('produktivitas', 0)),
                'kg_cake'               => $this->cleanNumber($request->kg_sir20),
                'bales_terkontaminasi'  => $this->cleanNumber($request->bales_kontamin),
                'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
                'jam_operasional_genset'=> $this->cleanNumber($request->jam_genset),
                'pemakaian_listrik_pln' => $this->cleanNumber($request->pln_kwh),
                'jumlah_pallet'         => $this->cleanNumber($request->jml_pallet),
                'total_nomor'           => $this->cleanNumber($request->total_nomor),
                'mc_val'                => $this->cleanNumber($request->mc_val),
                'nomor_start'           => $request->nomor_start,
                'nomor_end'             => $request->nomor_end,
                'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
                'petugas'               => $request->petugas,
            ]);

            // 2. Simpan Remahan & Trigger Maturasi
            if ($request->has('maturasi')) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) || !empty($item['berat'])) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $produksi->id_produksi_sir20,
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur']),
                        ]);

                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 3. Simpan Temperature
            $tempData = [
                ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
                ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
                ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
            ];

            foreach ($tempData as $temp) {
                if ($temp['start'] || $temp['end']) {
                    AktualTemperatureSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20,
                        'jenis'             => $temp['jenis'],
                        'nilai_start'       => $this->cleanNumber($temp['start']),
                        'nilai_end'         => $this->cleanNumber($temp['end']),
                    ]);
                }
            }

            // 4. Simpan Bahan Bakar
            $bbData = [
                ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
                ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
                ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
            ];

            foreach ($bbData as $bb) {
                $cleanJumlah = $this->cleanNumber($bb['jumlah']);
                if ($cleanJumlah > 0) {
                    BahanBakarSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20,
                        'bahan_bakar'       => $bb['nama'],
                        'digunakan'         => $cleanJumlah,
                    ]);
                }
            }

            // -----------------------------------------------------------------
            // 🔥 PERBAIKAN: PANGGIL HELPER DENGAN PARAMETER NOMOR START & END 🔥
            // -----------------------------------------------------------------
            $this->generatePalletsOtomatis(
                $request->tanggal_produksi, 
                $this->cleanNumber($request->kg_press), 
                $this->cleanNumber($request->jml_pallet),
                $request->nomor_start,
                $request->nomor_end,
                $request->jenis_pallets // 🔥 Tambahkan parameter ini
            );

                // 🔥 TAMBAHKAN PEMICU KE BAHAN PROSES
                // Panggil controller BahanProses secara internal untuk sinkronisasi otomatis
                $bahanController = new BahanProsesController();
                
                // Kita butuh akses ke method syncChainData. 
                // Pastikan di BahanProsesController, method syncChainData diubah dari 'private' menjadi 'public'.
                $bahanController->syncChainData($request->tanggal_produksi);

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi Berhasil Disimpan & Pallet Masuk ke Gudang!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 HELPER BARU: GENERATE PALLET SESUAI NOMOR START & END INPUTAN OPERATOR 🔥
    // =========================================================================
    private function generatePalletsOtomatis($tanggal, $totalKg, $totalPallet, $nomorStart, $nomorEnd, $jenisArray = [])
    {
        if ($totalPallet <= 0) return;

        // 1. Header Laporan Harian (Gudang)
        $header = ProduksiSir::firstOrCreate(
            ['tanggal_produksi' => $tanggal],
            ['kg' => 0, 'pallet' => 0, 'keterangan' => 'Generate Otomatis dari Laporan Pabrik']
        );
        $header->increment('kg', $totalKg);
        $header->increment('pallet', $totalPallet);

        $kgPerPallet = $totalKg / $totalPallet;
        $tahunSingkat = Carbon::parse($tanggal)->format('y'); 

        // 2. AMBIL DAFTAR HUTANG (FIFO)
        $hutangManual = DB::table('penjualan_manual_sir20')
            ->where('status', 'Pending')
            ->orderBy('tanggal', 'asc')
            ->get();

        $idxHutang = 0;
        $sisaKebutuhanHutang = $hutangManual->count() > 0 ? $hutangManual[$idxHutang]->pallet_manual : 0;

        // 3. 🔥 LOOPING GENERATE PALLET FISIK
        for ($i = $nomorStart; $i <= $nomorEnd; $i++) {
            
            // --- 🚀 BAGIAN PENYESUAIAN JENIS PALLET 🚀 ---
            // $i adalah nomor pallet (misal 1001), nomorStart adalah 1001.
            // Maka index perulangan pertama adalah 0.
            $idxArr = $i - $nomorStart;
            
            // Ambil dari array yang dikirim radio button, jika tidak ada default ke 'SW'
            $jenisFix = isset($jenisArray[$idxArr]) ? $jenisArray[$idxArr] : 'SW';
            // ----------------------------------------------

            $noPalletFix = $tahunSingkat . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            // --- 🛡️ LOGIKA PELUNASAN OTOMATIS 🛡️ ---
            $tglPenjualanOtomatis = null;
            if ($hutangManual->count() > 0 && $idxHutang < $hutangManual->count()) {
                $tglPenjualanOtomatis = $hutangManual[$idxHutang]->tanggal;
                $sisaKebutuhanHutang--;

                if ($sisaKebutuhanHutang <= 0) {
                    DB::table('penjualan_manual_sir20')
                        ->where('id_penjualan_manual', $hutangManual[$idxHutang]->id_penjualan_manual)
                        ->update(['status' => 'Settled']);
                    
                    $idxHutang++;
                    if ($idxHutang < $hutangManual->count()) {
                        $sisaKebutuhanHutang = $hutangManual[$idxHutang]->pallet_manual;
                    }
                }
            }

            // 4. SIMPAN DATA KE TABEL PALLET
            $pallet = Pallet::create([
                'id_produksi_sir'   => $header->id_produksi_sir,
                'no_pallet'         => $noPalletFix,
                'berat'             => $kgPerPallet,
                'jenis_pallet'      => $jenisFix, // 🔥 Sekarang sudah dinamis (MB5 atau SW)
                'tanggal_produksi'  => $tanggal,
                'tanggal_penjualan' => $tglPenjualanOtomatis
            ]);

            // ... simpan lokasi & kondisi tetap sama ...
            $lokasiAwal = Lokasi::firstOrCreate(['nama' => 'Di Gudang SIR']);
            $mutuPrima  = Mutu::firstOrCreate(['uraian' => 'Mutu Prima (siap jual)']);

            LokasiPallet::create([
                'id_lokasi' => $lokasiAwal->id_lokasi,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggal
            ]);

            KondisiPallet::create([
                'id_mutu'   => $mutuPrima->id_mutu,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggal
            ]);
        }
    }

   // =========================================================================
    // 🔥 UPDATE (EDIT DATA + SINKRONISASI STOK MATURASI + REGENERATE PALLET)
    // =========================================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        DB::beginTransaction();

        try {
            $produksi = ProduksiSir20::findOrFail($id);
            
            // 1. AMBIL DATA LAMA UNTUK REVERT STOK
            $oldDate      = $produksi->tanggal_produksi; 
            $oldKgPress   = $produksi->kg_yang_dipress;
            $oldJmlPallet = $produksi->jumlah_pallet;
            $oldStart     = $produksi->nomor_start;
            $oldEnd       = $produksi->nomor_end;

            // 2. UPDATE HEADER PRODUKSI
            $produksi->update([
                'tanggal_produksi'      => $request->tanggal_produksi,
                'shift_kerja'           => $request->shift_kerja,
                'jam_start_dryer'       => $request->jam_start_dryer,
                'jumlah_trolly_masuk'   => $this->cleanNumber($request->trolly_masuk),
                'jumlah_trolly_keluar'  => $this->cleanNumber($request->trolly_keluar),
                'jam_stop_dryer'        => $request->jam_stop_dryer,
                'jumlah_jam_dryer'      => $this->cleanNumber($request->jam_jalan_dryer),
                'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales),
                'kg_yang_dipress'       => $this->cleanNumber($request->kg_press),
                'capacity_per_jam'      => $this->cleanNumber($request->input('capacity_per_jam', 0)),
                'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
                'produktivitas'         => $this->cleanNumber($request->input('produktivitas', 0)),
                'kg_cake'               => $this->cleanNumber($request->kg_sir20),
                'bales_terkontaminasi'  => $this->cleanNumber($request->bales_kontamin),
                'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
                'jam_operasional_genset'=> $this->cleanNumber($request->jam_genset),
                'pemakaian_listrik_pln' => $this->cleanNumber($request->pln_kwh),
                'jumlah_pallet'         => $this->cleanNumber($request->jml_pallet),
                'total_nomor'           => $this->cleanNumber($request->total_nomor),
                'mc_val'                => $this->cleanNumber($request->mc_val),
                'nomor_start'           => $request->nomor_start,
                'nomor_end'             => $request->nomor_end,
                'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
                'petugas'               => $request->petugas,
            ]);

            // 3. REVERT STOK MATURASI LAMA & SIMPAN YANG BARU
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }
            RemahanSir20::where('id_produksi_sir20', $id)->delete();

            if ($request->has('maturasi')) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) || !empty($item['berat'])) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        RemahanSir20::create([
                            'id_produksi_sir20' => $id, 
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur']),
                        ]);
                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 4. UPDATE TEMPERATURE & BAHAN BAKAR
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            $tempData = [
                ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
                ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
                ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
            ];
            foreach ($tempData as $temp) {
                if ($temp['start'] || $temp['end']) {
                    AktualTemperatureSir20::create([
                        'id_produksi_sir20' => $id,
                        'jenis'             => $temp['jenis'],
                        'nilai_start'       => $this->cleanNumber($temp['start']),
                        'nilai_end'         => $this->cleanNumber($temp['end']),
                    ]);
                }
            }

            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $bbData = [
                ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
                ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
                ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
            ];
            foreach ($bbData as $bb) {
                $cleanJumlah = $this->cleanNumber($bb['jumlah']);
                if ($cleanJumlah > 0) {
                    BahanBakarSir20::create([
                        'id_produksi_sir20' => $id,
                        'bahan_bakar'       => $bb['nama'],
                        'digunakan'         => $cleanJumlah,
                    ]);
                }
            }

            // =========================================================================
            // 🔥 5. SINKRONISASI GUDANG (REGENERATE PALLET)
            // =========================================================================
            
            $newDate      = $produksi->tanggal_produksi;
            $newKgPress   = $produksi->kg_yang_dipress;
            $newJmlPallet = $produksi->jumlah_pallet;
            $newStart     = $produksi->nomor_start;
            $newEnd       = $produksi->nomor_end;

            // Ambil data jenis pallet yang sudah ada di DB untuk dibandingkan
            $tahunPallet = Carbon::parse($oldDate)->format('y');
            // GANTI MENJADI:
            $prefixLama  = $tahunPallet . '-';
            $noStartLama = $prefixLama . str_pad($oldStart, 4, '0', STR_PAD_LEFT);
            $noEndLama   = $prefixLama . str_pad($oldEnd, 4, '0', STR_PAD_LEFT);
            
            $currentPalletTypes = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])
                                    ->where('tanggal_produksi', $oldDate)
                                    ->orderBy('no_pallet', 'asc')
                                    ->pluck('jenis_pallet')
                                    ->toArray();

            // 🔥 CEK PERUBAHAN: Jika Tanggal/Jumlah/Nomor berubah OR Jenis Pallet ada yang diganti
            $isJenisChanged = $currentPalletTypes !== ($request->jenis_pallets ?? []);

            if ($oldDate != $newDate || $oldKgPress != $newKgPress || $oldJmlPallet != $newJmlPallet || 
                $oldStart != $newStart || $oldEnd != $newEnd || $isJenisChanged) {

                // A. Kurangi Rekap Header Lama
                $oldHeader = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
                if ($oldHeader) {
                    $oldHeader->decrement('kg', $oldKgPress);
                    $oldHeader->decrement('pallet', $oldJmlPallet);
                    if ($oldHeader->pallet <= 0) $oldHeader->delete();
                }

                // B. Hapus Fisik Pallet LAMA
                $palletsLama = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])
                                    ->where('tanggal_produksi', $oldDate)->get();
                foreach($palletsLama as $pLama) {
                    LokasiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    KondisiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    $pLama->delete();
                }

                // C. Generate Ulang Pallet Baru dengan Jenis Terbaru
                $this->generatePalletsOtomatis(
                    $newDate, 
                    $newKgPress, 
                    $newJmlPallet, 
                    $newStart, 
                    $newEnd,
                    $request->jenis_pallets // 🔥 Kirim array jenis terbaru
                );
            }

            // 🔥 6. SINKRONISASI WIP (BAHAN PROSES) 🔥
            $bahanController = new BahanProsesController();
            $bahanController->syncChainData($newDate);
            // Jika tanggal berubah, sinkronkan juga tanggal lamanya untuk update saldo pindahan
            if ($oldDate != $newDate) {
                $bahanController->syncChainData($oldDate);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi & WIP Berhasil Diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Update: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 DELETE (HAPUS DATA + KEMBALIKAN STOK MATURASI)
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiSir20::findOrFail($id);
            $oldDate  = $produksi->tanggal_produksi;

            // 1. KEMBALIKAN STOK KE MATURASI (REVERT)
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach ($oldRemahan as $old) {
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }

            // 2. BERSIHKAN DATA GUDANG (PALLET)
            $tahunPallet = Carbon::parse($oldDate)->format('y');
            // GANTI MENJADI:
            $prefix      = $tahunPallet . '-';
            $noStart     = $prefix . str_pad($produksi->nomor_start, 4, '0', STR_PAD_LEFT);
            $noEnd       = $prefix . str_pad($produksi->nomor_end, 4, '0', STR_PAD_LEFT);

            // --- 🛡️ LOGIKA PENGEMBALIAN STATUS HUTANG MANUAL 🛡️ ---
            // Cari pallet yang akan dihapus yang ternyata punya 'tanggal_penjualan' 
            // (artinya pallet ini dipakai melunasi hutang manual saat store tadi)
            $palletsUsedForDebt = Pallet::whereBetween('no_pallet', [$noStart, $noEnd])
                ->where('tanggal_produksi', $oldDate)
                ->whereNotNull('tanggal_penjualan')
                ->get();

            foreach ($palletsUsedForDebt as $p) {
                // Kita kembalikan status hutang manual yang bersangkutan menjadi 'Pending'
                // agar bisa dilunasi lagi oleh produksi di masa depan
                DB::table('penjualan_manual_sir20')
                    ->where('id_penjualan_sir20', function($query) use ($p) {
                        $query->select('id_penjualan_sir20')
                            ->from('penjualan_sir20')
                            ->where('tanggal', $p->tanggal_penjualan)
                            ->limit(1);
                    })
                    ->where('status', 'Settled')
                    ->update(['status' => 'Pending']);
            }

            // Kurangi Header Rekap ProduksiSir (Gudang)
            $headerGudang = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
            if ($headerGudang) {
                $headerGudang->decrement('kg', $produksi->kg_yang_dipress);
                $headerGudang->decrement('pallet', $produksi->jumlah_pallet);
                if ($headerGudang->pallet <= 0) $headerGudang->delete();
            }

            // Hapus Fisik Pallet & Riwayatnya (Lokasi & Kondisi)
            $pallets = Pallet::whereBetween('no_pallet', [$noStart, $noEnd])
                            ->where('tanggal_produksi', $oldDate)->get();

            foreach ($pallets as $p) {
                LokasiPallet::where('id_pallet', $p->id_pallet)->delete();
                KondisiPallet::where('id_pallet', $p->id_pallet)->delete();
                $p->delete();
            }

            // 3. HAPUS DATA DETAIL & HEADER PRODUKSI SIR 20
            RemahanSir20::where('id_produksi_sir20', $id)->delete();
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $produksi->delete();

            // 4. SINKRONISASI WIP (BAHAN PROSES)
            $bahanController = new BahanProsesController();
            $bahanController->syncChainData($oldDate);

            DB::commit();
            return redirect()->back()->with('success', 'Produksi Dibatalkan: Stok kembali ke Maturasi, WIP dibersihkan, dan Hutang Manual dibuka kembali!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Membatalkan Produksi: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // HELPER FUNCTIONS
    // =========================================================================
    
    private function cleanNumber($value)
    {
        if (empty($value)) return 0;

        // 🔥 CEK 1: Apakah nilainya sudah berupa angka standar? (Contoh: "2250.00" atau 2250)
        // Input dari <input type="number"> selalu mengirim format ini.
        // Jika iya, langsung kembalikan sebagai float, JANGAN hapus titiknya.
        if (is_numeric($value)) {
            return (float) $value;
        }

        // 🔥 CEK 2: Jika format Indonesia (ada koma sebagai desimal, misal "2.250,50")
        // Ini biasanya dari input text manual atau plugin masking
        $string = (string) $value;
        $string = str_replace('.', '', $string); // Hapus titik ribuan
        $string = str_replace(',', '.', $string); // Ganti koma jadi titik desimal
        return (float) $string;
    }

    // 🔥 LOGIKA SINKRONISASI KE TABEL MATURASI
    private function triggerUpdateMaturasi($namaRuang, $tanggal, $berat, $aksi)
    {
        if ($berat <= 0) return;
        $maturasi = Maturasi::where('uraian', $namaRuang)->first();
        
        if ($maturasi) {
            // 1. Cari atau buat log harian
            $log = PengolahanMaturasi::firstOrCreate(
                ['id_maturasi' => $maturasi->id_maturasi, 'tgl_laporan' => $tanggal],
                ['masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0, 'keterangan' => 'Auto Produksi']
            );

            // 2. Update kolom 'diolah' (Barang keluar dari maturasi ke gilingan)
            if ($aksi === 'tambah') {
                $log->increment('diolah', $berat);
            } else {
                $log->decrement('diolah', min($log->diolah, $berat));
            }

            // 3. 🔥 PANGGIL TRAIT UNTUK UPDATE STOK AKHIR & LABEL 🔥
            // Ini akan menggantikan puluhan baris logika manual yang sebelumnya ada di sini
            $this->syncMaturasi($maturasi->id_maturasi, $tanggal);
        }
    }

    // Tambahkan di dalam class ProduksiSir20Controller
    // =========================================================================
    // 🔥 CETAK PDF PER ID (SATU SHIFT)
    // =========================================================================
    public function cetakPdf($id)
    {
        // Menggunakan relasi yang sudah Maswi definisikan
        $data = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])->findOrFail($id);

        // Kirim dalam array agar seragam dengan view cetak teman
        $dataProduksi = [$data->shift_kerja => $data];
        $tanggal = $data->tanggal_produksi;

        // Siapkan wadah suhu & bahan bakar
        $temps = [$data->shift_kerja => [
            'b1_start' => '', 'b1_end' => '', 'b2_start' => '', 'b2_end' => '', 'cycle_start' => '', 'cycle_end' => ''
        ]];
        $bbs = [$data->shift_kerja => ['solar' => 0, 'batubara' => 0, 'cangkang' => 0]];

        foreach ($data->aktualTemperature as $t) {
            if ($t->jenis == 'Burner 1') { $temps[$data->shift_kerja]['b1_start'] = $t->nilai_start; $temps[$data->shift_kerja]['b1_end'] = $t->nilai_end; }
            if ($t->jenis == 'Burner 2') { $temps[$data->shift_kerja]['b2_start'] = $t->nilai_start; $temps[$data->shift_kerja]['b2_end'] = $t->nilai_end; }
            if ($t->jenis == 'Cycle Time') { $temps[$data->shift_kerja]['cycle_start'] = $t->nilai_start; $temps[$data->shift_kerja]['cycle_end'] = $t->nilai_end; }
        }

        foreach ($data->bahanBakar as $b) {
            if ($b->bahan_bakar == 'Solar') $bbs[$data->shift_kerja]['solar'] = $b->digunakan;
            if ($b->bahan_bakar == 'Batu Bara') $bbs[$data->shift_kerja]['batubara'] = $b->digunakan;
            if ($b->bahan_bakar == 'Cangkang') $bbs[$data->shift_kerja]['cangkang'] = $b->digunakan;
        }

        return view('Cetak.cetak-pdf-sir20', compact('dataProduksi', 'tanggal', 'temps', 'bbs'));
    }

    // =========================================================================
    // 🔥 CETAK PDF REKAP HARIAN (3 SHIFT SEKALIGUS)
    // =========================================================================
    public function cetakHarian(Request $request)
    {
        $tanggal = $request->tanggal;

        $produksi = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])
                    ->whereDate('tanggal_produksi', $tanggal)
                    ->get();

        if ($produksi->isEmpty()) {
            return redirect()->back()->with('error', 'Data produksi pada tanggal ' . date('d-m-Y', strtotime($tanggal)) . ' tidak ditemukan.');
        }

        $dataProduksi = $produksi->keyBy('shift_kerja');
        $temps = [];
        $bbs = [];

        foreach ($dataProduksi as $shift => $data) {
            $temps[$shift] = [
                'b1_start' => $data->aktualTemperature->where('jenis', 'Burner 1')->first()->nilai_start ?? '',
                'b1_end'   => $data->aktualTemperature->where('jenis', 'Burner 1')->first()->nilai_end ?? '',
                'b2_start' => $data->aktualTemperature->where('jenis', 'Burner 2')->first()->nilai_start ?? '',
                'b2_end'   => $data->aktualTemperature->where('jenis', 'Burner 2')->first()->nilai_end ?? '',
                'cycle_start' => $data->aktualTemperature->where('jenis', 'Cycle Time')->first()->nilai_start ?? '',
                'cycle_end'   => $data->aktualTemperature->where('jenis', 'Cycle Time')->first()->nilai_end ?? '',
            ];

            $bbs[$shift] = [
                'solar'    => $data->bahanBakar->where('bahan_bakar', 'Solar')->first()->digunakan ?? 0,
                'batubara' => $data->bahanBakar->where('bahan_bakar', 'Batu Bara')->first()->digunakan ?? 0,
                'cangkang' => $data->bahanBakar->where('bahan_bakar', 'Cangkang')->first()->digunakan ?? 0,
            ];
        }

        return view('Cetak.cetak-pdf-sir20', compact('dataProduksi', 'tanggal', 'temps', 'bbs'));
    }
}