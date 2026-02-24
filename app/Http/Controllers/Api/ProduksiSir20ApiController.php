<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AktualTemperatureSir20;
use App\Models\BahanBakarSir20;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\KondisiPallet;

// --- IMPORT MODEL ---
use App\Models\Lokasi;
use App\Models\LokasiPallet;
use App\Models\Maturasi;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\PengolahanMaturasi;
// Model Gudang & Mutu
use App\Models\ProduksiSir20;
use App\Models\ProduksiSir;
use App\Models\RemahanSir20;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProduksiSir20ApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])
                ->orderBy('tanggal_produksi', 'desc');

            if ($request->has('date')) {
                $query->whereDate('tanggal_produksi', $request->query('date'));
            }

            $data = $query->get();
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 🔥 GET ACTIVE MATURASI (UNTUK DROPDOWN DI MOBILE APP)
    // =========================================================================
    public function getActiveMaturasi(Request $request)
    {
        try {
            // 1. Ambil Parameter Tanggal (Sama seperti Web)
            $dateInput = $request->query('date');
            $hari_ini = $dateInput ? Carbon::parse($dateInput)->startOfDay() : Carbon::today()->startOfDay();
            
            // 2. Tarik semua bak (Web menggunakan get() tanpa filter stok_akhir di query awal agar bisa simulasi)
            $bak_aktif_raw = Maturasi::orderBy('uraian', 'asc')->get();
            $bak_aktif = collect(); 

            foreach ($bak_aktif_raw as $bak) {
                // --- LOGIKA HITUNG STOK H-1 (SINKRON WEB) ---
                $sums = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', '<', $hari_ini->toDateString())
                    ->select(
                        DB::raw('COALESCE(SUM(masuk_hi),0) as sum_masuk'),
                        DB::raw('COALESCE(SUM(diolah),0) as sum_diolah'),
                        DB::raw('COALESCE(SUM(mutasi),0) as sum_mutasi')
                    )->first();

                $stok_kemarin = ($sums->sum_masuk ?? 0) - ($sums->sum_diolah ?? 0) - ($sums->sum_mutasi ?? 0);
                
                // Fallback stok awal jika belum ada log sama sekali
                if (!PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->exists()) {
                    $stok_kemarin = $bak->stok_awal;
                }

                // Hanya tampilkan jika stok pada tanggal tersebut > 0
                if ($stok_kemarin > 0.01) {
                    // --- LOGIKA UMUR (SINKRON 100% DENGAN WEB) ---
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

                        // RESET UMUR JIKA ADA MASUK FRESH (Sesuai Logika Web Anda)
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

                    // Fallback jika tidak ditemukan tgl_basis di log
                    if (!$tgl_basis) {
                        $historyDate = $this->getHistoryDateFromLog($bak->id_maturasi, $hari_ini);
                        $tgl_basis = $historyDate ? $historyDate : (!empty($bak->tgl_masuk) ? Carbon::parse($bak->tgl_masuk) : Carbon::parse($bak->created_at));
                    }

                    $tgl_basis = $tgl_basis->startOfDay();
                    
                    // Set Data untuk Mobile
                    $bak->umur = (int) $tgl_basis->diffInDays($hari_ini); 
                    $bak->stok_akhir = round($stok_kemarin, 2);
                    $bak_aktif->push($bak);
                }
            }

            return response()->json(['success' => true, 'data' => $bak_aktif], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Jangan lupa tambahkan method helper ini di API Controller juga (Copy dari Web)
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
        return null;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        if ($validator->fails()) return response()->json(['success'=>false, 'errors'=>$validator->errors()], 422);

        DB::beginTransaction();
        try {
            // 1. Simpan Header Produksi
            $produksi = ProduksiSir20::create($this->filterRequest($request));

            // 2. Simpan Remahan (Maturasi) & Potong Stok
            if ($request->filled('maturasi') && is_array($request->maturasi)) {
                foreach ($request->maturasi as $item) {
                    $namaRuang = $item['ruang_maturasi'] ?? $item['ruang'] ?? null;
                    $berat = $item['berat'] ?? 0;
                    $umur = $item['umur'] ?? 0;

                    if (!empty($namaRuang) && $berat > 0) {
                        $beratBersih = $this->cleanNumber($berat);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $produksi->id_produksi_sir20,
                            'ruang_maturasi'    => $namaRuang,
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($umur),
                        ]);

                        $this->triggerUpdateMaturasi($namaRuang, $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 3. Simpan Suhu & BB
            $this->saveTemperatures($produksi->id_produksi_sir20, $request);
            $this->saveBahanBakar($produksi->id_produksi_sir20, $request);

            // 4. 🔥 GENERATE PALLET MENGGUNAKAN START & END DARI MOBILE 🔥
            $this->generatePalletsOtomatis(
                $request->tanggal_produksi, 
                $this->cleanNumber($request->kg_yang_dipress), 
                $this->cleanNumber($request->jumlah_pallet),
                $request->nomor_start,
                $request->nomor_end
            );

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produksi berhasil disimpan & Pallet masuk Gudang.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 🔥 UPDATE (EDIT DATA + SINKRONISASI STOK MATURASI + REGENERATE PALLET)
    // =========================================================================
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiSir20::findOrFail($id);
            
            // 1. AMBIL DATA LAMA SEBELUM DI-UPDATE 
            $oldDate      = $produksi->tanggal_produksi; 
            $oldKgPress   = $produksi->kg_yang_dipress;
            $oldJmlPallet = $produksi->jumlah_pallet;
            $oldStart     = $produksi->nomor_start;
            $oldEnd       = $produksi->nomor_end;

            // 2. Update Header Laporan Pabrik
            $produksi->update($this->filterRequest($request));

            // 3. Revert Stok Maturasi Lama
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }
            RemahanSir20::where('id_produksi_sir20', $id)->delete();

            // 4. Simpan Remahan Baru
            if ($request->filled('maturasi') && is_array($request->maturasi)) {
                foreach ($request->maturasi as $item) {
                    $namaRuang = $item['ruang_maturasi'] ?? $item['ruang'] ?? null;
                    $berat = $item['berat'] ?? 0;
                    $umur = $item['umur'] ?? 0;

                    if (!empty($namaRuang) && $berat > 0) {
                        $beratBersih = $this->cleanNumber($berat);
                        RemahanSir20::create([
                            'id_produksi_sir20' => $id, 
                            'ruang_maturasi'    => $namaRuang,
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($umur),
                        ]);
                        $this->triggerUpdateMaturasi($namaRuang, $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 5. Update Suhu & BB
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            $this->saveTemperatures($id, $request);

            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $this->saveBahanBakar($id, $request);

            // =========================================================================
            // 🔥 6. SINKRONISASI GUDANG (REGENERATE PALLET JIKA ADA PERUBAHAN FISIK) 🔥
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

                // A. Kurangi Rekap Header Harian
                $oldHeader = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
                if ($oldHeader) {
                    $oldHeader->decrement('kg', $oldKgPress);
                    $oldHeader->decrement('pallet', $oldJmlPallet);
                    if ($oldHeader->pallet <= 0) {
                        $oldHeader->delete();
                    }
                }

                // B. HAPUS FISIK PALLET LAMA
                $palletsLama = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])->get();
                foreach($palletsLama as $pLama) {
                    LokasiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    KondisiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                    $pLama->delete();
                }

                // C. 🔥 GENERATE ULANG PALLET MENGGUNAKAN HELPER 🔥
                $this->generatePalletsOtomatis($newDate, $newKgPress, $newJmlPallet, $newStart, $newEnd);
            }

            // 🔥 SINKRONISASI KE BAHAN PROSES (WIP)
            app(BahanProsesApiController::class)->recalculateAndSaveFlow(Carbon::parse($oldDate));
            if ($oldDate != $newDate) {
                app(BahanProsesApiController::class)->recalculateAndSaveFlow(Carbon::parse($newDate));
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produksi diperbarui & Gudang disinkronkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 🔥 DESTROY (HAPUS DATA + KEMBALIKAN STOK MATURASI + HAPUS PALLET)
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiSir20::findOrFail($id);
            $oldDate = $produksi->tanggal_produksi;
            $oldKgPress = $produksi->kg_yang_dipress;
            $oldJmlPallet = $produksi->jumlah_pallet;
            $oldStart = $produksi->nomor_start;
            $oldEnd = $produksi->nomor_end;

            // 1. Revert Stok Maturasi
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }

            // 2. Hapus Rekap Gudang Harian
            $oldHeader = ProduksiSir::where('tanggal_produksi', $oldDate)->first();
            if ($oldHeader) {
                $oldHeader->decrement('kg', $oldKgPress);
                $oldHeader->decrement('pallet', $oldJmlPallet);
                if ($oldHeader->pallet <= 0) {
                    $oldHeader->delete();
                }
            }

            // 3. 🔥 HAPUS FISIK PALLET BERDASARKAN FORMAT PLT-YY-XXXX 🔥
            $tahunLama = Carbon::parse($oldDate)->format('y');
            $prefixLama = 'PLT-' . $tahunLama . '-';
            $noStartLama = $prefixLama . str_pad($oldStart, 4, '0', STR_PAD_LEFT);
            $noEndLama   = $prefixLama . str_pad($oldEnd, 4, '0', STR_PAD_LEFT);

            $palletsLama = Pallet::whereBetween('no_pallet', [$noStartLama, $noEndLama])->get();
            foreach($palletsLama as $pLama) {
                LokasiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                KondisiPallet::where('id_pallet', $pLama->id_pallet)->delete();
                $pLama->delete();
            }

            // 4. Hapus Data Induk dan Relasi
            RemahanSir20::where('id_produksi_sir20', $id)->delete();
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $produksi->delete();

            // 🔥 SINKRONISASI KE BAHAN PROSES (WIP)
            app(BahanProsesApiController::class)->recalculateAndSaveFlow(Carbon::parse($oldDate));

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data produksi dan pallet gudang berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPER FUNCTIONS 
    // =========================================================================

    private function filterRequest(Request $request)
    {
        return [
            'tanggal_produksi'      => $request->tanggal_produksi,
            'shift_kerja'           => $request->shift_kerja,
            'jam_start_dryer'       => $request->jam_start_dryer,
            'jam_stop_dryer'        => $request->jam_stop_dryer,
            'jumlah_jam_dryer'      => $this->cleanNumber($request->jumlah_jam_dryer),
            'jumlah_trolly_masuk'   => $this->cleanNumber($request->jumlah_trolly_masuk),
            'jumlah_trolly_keluar'  => $this->cleanNumber($request->jumlah_trolly_keluar),
            'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales_dipress),
            'kg_yang_dipress'       => $this->cleanNumber($request->kg_yang_dipress),
            'capacity_per_jam'      => $this->cleanNumber($request->capacity_per_jam),
            'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
            'produktivitas'         => $this->cleanNumber($request->produktivitas),
            'kg_cake'               => $this->cleanNumber($request->kg_cake),
            'bales_terkontaminasi'  => $this->cleanNumber($request->bales_terkontaminasi),
            'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
            'jam_operasional_genset'=> $this->cleanNumber($request->jam_operasional_genset),
            'pemakaian_listrik_pln' => $this->cleanNumber($request->pemakaian_listrik_pln),
            'jumlah_pallet'         => $this->cleanNumber($request->jumlah_pallet),
            'total_nomor'           => $this->cleanNumber($request->total_nomor),
            'mc_val'                => $this->cleanNumber($request->mc_val),
            'nomor_start'           => $request->nomor_start,
            'nomor_end'             => $request->nomor_end,
            'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
            'petugas'               => $request->petugas,
            'keterangan'            => $request->keterangan
        ];
    }

    private function saveTemperatures($id, $request)
    {
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
    }

    private function saveBahanBakar($id, $request)
    {
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
    }

    private function generatePalletsOtomatis($tanggal, $totalKg, $totalPallet, $nomorStart, $nomorEnd)
    {
        if ($totalPallet <= 0) return;

        // 1. Buat Header Produksi Sir (Gudang)
        $header = ProduksiSir::firstOrCreate(
            ['tanggal_produksi' => $tanggal],
            ['kg' => 0, 'pallet' => 0, 'keterangan' => 'Generate Otomatis dari API Mobile']
        );

        $header->increment('kg', $totalKg);
        $header->increment('pallet', $totalPallet);

        // 2. Ambil Default Lokasi & Mutu
        $lokasiAwal = Lokasi::firstOrCreate(['nama' => 'Di Gudang SIR']);
        $mutuPrima  = Mutu::firstOrCreate(['uraian' => 'Mutu Prima (siap jual)']);

        // 3. Hitung Berat Rata-rata per Pallet
        $kgPerPallet = $totalKg / $totalPallet;

        // Ambil 2 digit tahun
        $tahunSingkat = Carbon::parse($tanggal)->format('y'); 

        // 4. 🔥 LOOPING DARI NOMOR START SAMPAI NOMOR END! 🔥
        for ($i = $nomorStart; $i <= $nomorEnd; $i++) {
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
                if ($log->diolah >= $berat) $log->decrement('diolah', $berat);
                else $log->update(['diolah' => 0]);
                $maturasi->increment('stok_akhir', $berat); 
            }
            
            // 🔥 RESET TOTAL HANYA JIKA BAK KOSONG
            if ($maturasi->stok_akhir <= 0.01) {
                $maturasi->update([
                    'stok_akhir' => 0, 
                    'keterangan' => 'KOSONG', 
                    'asal_bokar' => null,
                    'tgl_masuk'  => null // Dikosongkan karena habis
                ]);
            } else {
                // Update string identitas (Bisa berubah jadi CMP jika ada Masuk HI baru)
                $newIdentitas = $this->getDetailedAsalBokarString($maturasi, $maturasi->stok_akhir, $maturasi->stok_akhir, Carbon::parse($tanggal));
                $maturasi->update(['asal_bokar' => $newIdentitas]);
                
                // ❌ DILARANG KERAS update tgl_masuk dan umur di sini!
            }
        }
    }

    // =========================================================================
    // HELPER: PENCARIAN ASAL BOKAR (CMP TRACING) SINKRON WEB
    // =========================================================================
    // =========================================================================
    // HELPER: PENCARIAN ASAL BOKAR (CMP TRACING) ANTI-GABUNG
    // =========================================================================
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
            $isFirstLog = true;

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                // Saldo Sebelumnya
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // 🔥 LOGIKA BARU ANTI-GABUNG: 
                // Jika di hari ini (log pertama), stok warisan kemarin ternyata 
                // habis terpakai oleh gilingan hari ini (prevStock <= keluar), 
                // maka BERHENTI melacak ke belakang! Ini adalah batch baru murni.
                if ($isFirstLog) {
                    if ($prevStock <= ($keluar + 0.01) && ($masuk + $mutasiMasuk) > 0.01) {
                        break; 
                    }
                    $isFirstLog = false;
                }

                if ($prevStock <= 0.01) break;
                $currentTracingStock = $prevStock;
            }

            // Cari Jenis di Lab Bokar berdasarkan Range Tanggal yang ditemukan
            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(fn($v) => strtoupper(trim($v)))
                ->unique()->filter()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1 ? 'CMP (' . implode(', ', $jenisList) . ')' : $jenisList[0];
            }

            return $maturasi->asal_bokar ?? '-';

        } catch (\Exception $e) {
            return $maturasi->asal_bokar ?? '-';
        }
    }

    private function cleanNumber($value)
    {
        if (empty($value)) return 0;
        if (is_numeric($value)) return (float) $value;
        
        $string = (string) $value;
        $string = str_replace('.', '', $string);
        $string = str_replace(',', '.', $string);
        return (float) $string;
    }

    public function getLastNumber(Request $request)
    {
        // 1. Tentukan Tahun yang mau dicek (Default: Tahun Hari Ini)
        // Kalau Android kirim parameter ?date=2025-12-31, kita cek tahun 2025.
        $dateInput = $request->query('date');
        $year = $dateInput ? Carbon::parse($dateInput)->year : Carbon::now()->year;

        // 2. Cari Data Terakhir HANYA di Tahun Tersebut
        $last = ProduksiSir20::whereYear('tanggal_produksi', $year)
                             ->orderBy('total_nomor_akhir', 'desc') // Pastikan ambil urutan angka tertinggi
                             ->first();

        // 3. Jika ketemu, ambil nomornya. Jika tidak (awal tahun), kembalikan 0.
        $num = $last ? $last->total_nomor_akhir : 0;
        
        return response()->json(['success' => true, 'data' => $num]);
    }
}