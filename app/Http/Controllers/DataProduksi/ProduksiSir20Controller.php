<?php

namespace App\Http\Controllers\DataProduksi;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduksiSir20Controller extends Controller
{
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
        $data = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])->findOrFail($id);
        // 🔥 PENTING: Saat Edit, load opsi bak KHUSUS untuk tanggal produksi data tersebut!
        $tgl_produksi = Carbon::parse($data->tanggal_produksi)->startOfDay();
        $data->opsi_maturasi = $this->getBakAktifUntukTanggal($tgl_produksi);
        
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
                $request->nomor_end
            );

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
    private function generatePalletsOtomatis($tanggal, $totalKg, $totalPallet, $nomorStart, $nomorEnd)
    {
        if ($totalPallet <= 0) return;

        // 1. Buat atau Update Header Laporan Harian
        $header = ProduksiSir::firstOrCreate(
            ['tanggal_produksi' => $tanggal],
            ['kg' => 0, 'pallet' => 0, 'keterangan' => 'Generate Otomatis dari Laporan Pabrik']
        );

        $header->increment('kg', $totalKg);
        $header->increment('pallet', $totalPallet);

        // 2. Ambil Default Lokasi & Mutu
        $lokasiAwal = Lokasi::firstOrCreate(['nama' => 'Di Gudang SIR']);
        $mutuPrima  = Mutu::firstOrCreate(['uraian' => 'Mutu Prima (siap jual)']);

        // 3. Hitung Berat Rata-rata per Pallet
        $kgPerPallet = $totalKg / $totalPallet;
        
        // Ambil 2 digit tahun (Contoh: "26" dari 2026)
        $tahunSingkat = Carbon::parse($tanggal)->format('y'); 

        // 4. 🔥 LOOPING DARI NOMOR START SAMPAI NOMOR END! 🔥
        for ($i = $nomorStart; $i <= $nomorEnd; $i++) {
            // Hasil: PLT-26-0001
            $noPalletFix = 'PLT-' . $tahunSingkat . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            $pallet = Pallet::create([
                'id_produksi_sir'  => $header->id_produksi_sir,
                'no_pallet'        => $noPalletFix,
                'berat'            => $kgPerPallet,
                'tanggal_produksi' => $tanggal,
            ]);

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
            
            // 🔥 1. AMBIL DATA LAMA SEBELUM DI-UPDATE 🔥
            $oldDate      = $produksi->tanggal_produksi; 
            $oldKgPress   = $produksi->kg_yang_dipress;
            $oldJmlPallet = $produksi->jumlah_pallet;
            $oldStart     = $produksi->nomor_start;
            $oldEnd       = $produksi->nomor_end;

            // 2. Update Header Laporan Pabrik
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

            // 3. Update Remahan & TRIGGER MATURASI (Revert & Add)
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

            // 4. Update Lainnya (Temp & BB)
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
            // 🔥 5. SINKRONISASI GUDANG (REGENERATE PALLET JIKA ADA PERUBAHAN FISIK) 🔥
            // =========================================================================
            
            $newDate      = $produksi->tanggal_produksi;
            $newKgPress   = $produksi->kg_yang_dipress;
            $newJmlPallet = $produksi->jumlah_pallet;
            $newStart     = $produksi->nomor_start;
            $newEnd       = $produksi->nomor_end;

            // 🔥 RADAR: Format Nomor PLT-YY-XXXX
            $tahunLama = Carbon::parse($oldDate)->format('y');
            $prefixLama  = 'PLT-' . $tahunLama . '-';
            $noStartLama = $prefixLama . str_pad($oldStart, 4, '0', STR_PAD_LEFT);
            $noEndLama   = $prefixLama . str_pad($oldEnd, 4, '0', STR_PAD_LEFT);
            $palletFisikAda = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])->exists();

            if (!$palletFisikAda || $oldDate != $newDate || $oldKgPress != $newKgPress || $oldJmlPallet != $newJmlPallet || $oldStart != $newStart || $oldEnd != $newEnd) {

                // A. Kurangi Rekap Header
                $oldHeader = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
                if ($oldHeader) {
                    $oldHeader->decrement('kg', $oldKgPress);
                    $oldHeader->decrement('pallet', $oldJmlPallet);
                    if ($oldHeader->pallet <= 0) $oldHeader->delete();
                }

                // B. Hapus Fisik Pallet LAMA
                $palletsLama = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])->get();
                foreach($palletsLama as $pLama) {
                    LokasiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    KondisiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    $pLama->delete();
                }

                // C. Generate Ulang Pallet BARU
                $this->generatePalletsOtomatis($newDate, $newKgPress, $newJmlPallet, $newStart, $newEnd);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi Diperbarui & Gudang Tersinkronisasi!');

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

            // 1. Kembalikan Stok Maturasi (Revert)
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach ($oldRemahan as $old) {
                // Aksi 'kurang' akan menambah stok master dan mengurangi kolom diolah di log
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }

            // 2. 🔥 BERSIHKAN DATA GUDANG (PALLET) 🔥
            // Kita cari Pallet berdasarkan range nomor yang ada di data produksi ini
            $tahunPallet = Carbon::parse($oldDate)->format('y');
            $prefix      = 'PLT-' . $tahunPallet . '-';
            $noStart     = $prefix . str_pad($produksi->nomor_start, 4, '0', STR_PAD_LEFT);
            $noEnd       = $prefix . str_pad($produksi->nomor_end, 4, '0', STR_PAD_LEFT);

            // Cari Header Produksi Gudang (ProduksiSir) untuk sinkronisasi rekap
            $headerGudang = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
            if ($headerGudang) {
                $headerGudang->decrement('kg', $produksi->kg_yang_dipress);
                $headerGudang->decrement('pallet', $produksi->jumlah_pallet);
                // Jika setelah dikurangi jadi 0, hapus headernya
                if ($headerGudang->pallet <= 0) $headerGudang->delete();
            }

            // Ambil pallet fisik untuk dihapus riwayatnya
            $pallets = Pallet::whereBetween('no_pallet', [$noStart, $noEnd])
                        ->where('tanggal_produksi', $oldDate)
                        ->get();

            foreach ($pallets as $p) {
                // Hapus riwayat lokasi dan kondisi sebelum hapus palletnya
                LokasiPallet::where('id_pallet', $p->id_pallet)->delete();
                KondisiPallet::where('id_pallet', $p->id_pallet)->delete();
                $p->delete();
            }

            // 3. Hapus Data Detail Produksi Pabrik
            RemahanSir20::where('id_produksi_sir20', $id)->delete();
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            
            // 4. Hapus Header Produksi Pabrik
            $produksi->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Produksi Dibatalkan: Stok kembali ke Maturasi & Pallet dihapus dari Gudang!');
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
            $log = PengolahanMaturasi::firstOrCreate(
                ['id_maturasi' => $maturasi->id_maturasi, 'tgl_laporan' => $tanggal],
                ['masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0, 'keterangan' => 'Auto Produksi']
            );

            if ($aksi === 'tambah') {
                $log->increment('diolah', $berat);
                $maturasi->decrement('stok_akhir', $berat); 
            } else {
                $log->decrement('diolah', min($log->diolah, $berat));
                $maturasi->increment('stok_akhir', $berat); 
            }

            // 🔥 RESET TOTAL HANYA JIKA BAK KOSONG
            if ($maturasi->stok_akhir <= 0.01) {
                $maturasi->update([
                    'stok_akhir' => 0, 
                    'keterangan' => 'KOSONG', 
                    'asal_bokar' => null,
                    'tgl_masuk'  => null
                ]);
            } 
            else {
                // Update string identitas (Bisa berubah jadi CMP jika ada Masuk HI baru)
                $newIdentitas = $this->getDetailedAsalBokarString($maturasi, $maturasi->stok_akhir, $maturasi->stok_akhir, Carbon::parse($tanggal));
                $maturasi->update(['asal_bokar' => $newIdentitas]);
                
                // 🔥 JANGAN reset tgl_masuk ke hari ini di sini agar umur Batch Lama tidak hilang
            }
        }
    }

    // =========================================================================
    // HELPER: HITUNG SALDO AWAL (SEBELUM TANGGAL TERTENTU)
    // =========================================================================
    protected function getNetBeforeDate(int $id_maturasi, Carbon $date): float
    {
        $sums = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->select(
                DB::raw('COALESCE(SUM(masuk_hi),0) as sum_masuk'),
                DB::raw('COALESCE(SUM(diolah),0) as sum_diolah'),
                DB::raw('COALESCE(SUM(mutasi),0) as sum_mutasi')
            )->first();

        if (!$sums) return 0;
        
        $net = ($sums->sum_masuk - $sums->sum_diolah - $sums->sum_mutasi);
        return (float) $net;
    }

    private function getDetailedAsalBokarString($maturasi, float $stokAkhir, float $stokAwal, Carbon $filterDate)
    {
        // Jika Stok Awal KOSONG DAN Stok Akhir KOSONG, baru return '-'
        if ($stokAkhir <= 0.01 && $stokAwal <= 0.01) return '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            // Ambil log mundur
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = ($stokAkhir > 0.01) ? $stokAkhir : ($stokAwal + 0.1);
            $hasKeluar = false; // 🔥 Penanda apakah sudah ada barang yang digiling

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // Tandai jika kita menemukan aktivitas KELUAR (Diolah/Mutasi Keluar)
                // Ini berarti barang lama sudah mulai dikuras
                if ($keluar > 0.01) {
                    $hasKeluar = true;
                }

                // 🔥 LOGIKA RESET MUTLAK (TERBARU)
                // Jika kita mundur dan menemukan hari dimana ada MASUK BARU,
                // DAN sejak hari itu (atau pada hari itu) sudah ada KELUAR (Diolah),
                // Maka SISA STOK LAMA DIABAIKAN! Sistem berhenti melacak masa lalu.
                if (($masuk + $mutasiMasuk) > 0.01 && $hasKeluar) {
                    break; 
                }

                // Reset pelacakan jika stok benar-benar habis
                if ($prevStock <= 0.01) break;
                
                $currentTracingStock = $prevStock;
            }

            // Cari Jenis di Lab Bokar berdasarkan Range Tanggal yang ditemukan
            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function($v) { return strtoupper(trim($v)); })
                ->unique()->filter()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1 ? 'CMP (' . implode(', ', $jenisList) . ')' : $jenisList[0];
            }

            return $maturasi->asal_bokar ?? '-';

        } catch (\Exception $e) {
            return $maturasi->asal_bokar ?? '-';
        }
    }
}