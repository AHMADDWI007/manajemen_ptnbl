<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Traits\MaturasiSyncTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaturasiApiController extends Controller
{

    use MaturasiSyncTrait; // 🔥 Tambahkan ini

    // =========================================================================
    // HELPER 1: HITUNGAN SNAPSHOT (COPY DARI WEB ADMIN)
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
        
        $hasil = $sums->sum_masuk - $sums->sum_diolah - $sums->sum_mutasi;
        
        // 🔥 PERBAIKAN 1: Bulatkan ke 2 desimal agar tidak ada sisa -0.00001
        return round((float) $hasil, 2);
    }

    protected function getSumsOnDate(int $id_maturasi, Carbon $date): array
    {
        $s = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', $date->toDateString())
            ->select(
                DB::raw('COALESCE(SUM(masuk_hi),0) as masuk_hi'),
                DB::raw('COALESCE(SUM(diolah),0) as diolah'),
                DB::raw('COALESCE(SUM(mutasi),0) as mutasi')
            )->first();

        return [
            'masuk_hi' => (float)($s->masuk_hi ?? 0),
            'diolah'   => (float)($s->diolah ?? 0),
            'mutasi'   => (float)($s->mutasi ?? 0),
        ];
    }

    protected function getLastMasukHiDateBefore(int $id_maturasi, Carbon $date)
    {
        $log = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->where('masuk_hi', '>', 0)
            ->orderBy('tgl_laporan', 'desc')
            ->first();

        return $log ? Carbon::parse($log->tgl_laporan) : null;
    }

    protected function hasAnyLogUpToDate(int $id_maturasi, Carbon $date): bool
    {
        return PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<=', $date->toDateString())
            ->exists();
    }

    protected function hitungSnapshot($maturasi, Carbon $selectedDate): array
    {
        if (! $this->hasAnyLogUpToDate($maturasi->id_maturasi, $selectedDate)) {
            return [
                'stok_awal' => 0, 'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
                'stok_akhir' => 0, 'tgl_masuk' => null, 'umur' => 0, 'keterangan' => '-'
            ];
        }

        // 1. Stok Awal & Transaksi Hari Ini
        $stok_awal = $this->getNetBeforeDate($maturasi->id_maturasi, $selectedDate);
        $transaksi = $this->getSumsOnDate($maturasi->id_maturasi, $selectedDate);
        
        // 2. Cek Masuk dari Uji Bokar (Backup)
        $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', $selectedDate)
            ->sum('netto_kering');

        $masuk_hi_today = max($transaksi['masuk_hi'], $masuk_from_uji);

        // 3. Hitung Stok Akhir
        $stok_akhir = $stok_awal + $masuk_hi_today - $transaksi['diolah'] - $transaksi['mutasi'];

        // 🔥 PERBAIKAN 2: Bulatkan hasil akhir ke 2 desimal
        $stok_akhir = round($stok_akhir, 2);

        // 🔥 PERBAIKAN 3: Cegah Minus (Jika kebijakan perusahaan stok tidak boleh minus)
        // Web Admin biasanya melakukan ini di view atau logic controller.
        if ($stok_akhir < 0) {
            $stok_akhir = 0;
        }

        // 4. Logika Tanggal Masuk & Umur
        $tgl_masuk = null;
        $umur = 0;
        $keterangan = '-';

        if ($stok_akhir > 0) {
            if ($masuk_hi_today > 0) {
                $tgl_masuk = $selectedDate->toDateString();
                $umur = 0;
                $keterangan = strtoupper($selectedDate->format('d M Y'));
            } else {
                $last_before = $this->getLastMasukHiDateBefore($maturasi->id_maturasi, $selectedDate);
                
                if ($last_before) {
                    $tgl_masuk = $last_before->toDateString();
                    $umur = $last_before->diffInDays($selectedDate);
                    $keterangan = strtoupper($last_before->format('d M Y'));
                } elseif ($maturasi->tgl_masuk) {
                    try {
                        $candidate = Carbon::parse($maturasi->tgl_masuk);
                        if ($candidate->lte($selectedDate)) {
                            $tgl_masuk = $candidate->toDateString();
                            $umur = $candidate->diffInDays($selectedDate);
                            $keterangan = strtoupper($candidate->format('d M Y'));
                        }
                    } catch (\Exception $e) {}
                }
            }
        }

        return [
            'stok_awal'  => round($stok_awal, 2),  // Pastikan dibulatkan
            'diolah'     => round($transaksi['diolah'], 2),
            'mutasi'     => round($transaksi['mutasi'], 2),
            'masuk_hi'   => round($masuk_hi_today, 2),
            'stok_akhir' => $stok_akhir, // Sudah dibulatkan di atas
            'tgl_masuk'  => $tgl_masuk,
            'umur'       => $umur,
            'keterangan' => $keterangan,
        ];
    }

    private function getDetailedAsalBokarString($maturasi, float $stokAkhir, float $stokAwal, Carbon $filterDate)
    {
        if ($stokAkhir <= 0.01 && $stokAwal <= 0.01) return '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = ($stokAkhir > 0.01) ? $stokAkhir : ($stokAwal + 0.1);
            $akumulasiKeluar = 0; // 🔥 SINKRON WEB

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $akumulasiKeluar += $keluar; 
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // 🔥 LOGIKA RESET ULTIMATE
                if (($masuk + $mutasiMasuk) > 0.01 && $akumulasiKeluar > 0.01) {
                    break; 
                }

                if ($prevStock <= 0.01) break;
                $currentTracingStock = $prevStock;
            }

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


    // =========================================================================
    // ENDPOINT API (SAMA SEPERTI ANDROID MINTA)
    // =========================================================================

    /**
     * [LIST TABLE] Mengambil status semua bak (Snapshot Harian)
     */
    public function index(Request $request)
    {
        try {
            // Default hari ini jika tidak ada filter
            $selectedDate = $request->has('date') ? Carbon::parse($request->query('date')) : Carbon::today();
            
            $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
            $dataTampilan = [];

            foreach ($data_maturasi_db as $bak) {
                // 1. Hitung Snapshot (LOGIKA WEB)
                $snap = $this->hitungSnapshot($bak, $selectedDate);
                
                // 🔥 PERBAIKAN DISINI: Cek tipe data sebelum toArray()
                if ($bak instanceof Maturasi) {
                    $row = $bak->toArray();
                } else {
                    // Jika stdClass, casting manual ke array
                    $row = (array) $bak;
                }

                $row['stok_awal']  = $snap['stok_awal'];
                $row['diolah']     = $snap['diolah'];
                $row['mutasi']     = $snap['mutasi'];
                $row['masuk_hi']   = $snap['masuk_hi'];
                $row['stok_akhir'] = $snap['stok_akhir'];
                $row['tgl_masuk']  = $snap['tgl_masuk'];
                $row['umur']       = $snap['umur'];
                $row['keterangan'] = $snap['keterangan'];

                // 2. Ambil K3 Masuk & Olah
                $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tanggal', '<=', $selectedDate)
                    ->orderBy('tanggal', 'desc')->first();
                
                $row['k3_olah'] = $ujiLab ? $ujiLab->k3 : null;
                $row['po']      = $ujiLab ? $ujiLab->po : null;
                $row['pri']     = $ujiLab ? $ujiLab->pri : null;
                
                // ID Hasil Uji untuk keperluan Edit/Hapus di Android
                $row['id_hasil_uji_maturasi'] = $ujiLab ? $ujiLab->id : null; 

                // 3. Logika Asal Bokar (CMP)
                $row['asal_bokar'] = $this->getDetailedAsalBokarString($bak, $snap['stok_akhir'], $snap['stok_awal'], $selectedDate);

                if ($snap['stok_akhir'] <= 0) {
                    $row['asal_bokar'] = '-';
                }

                $dataTampilan[] = $row;
            }

            return response()->json(['success' => true, 'data' => $dataTampilan]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [SPINNER] Mengambil daftar Bak yang Stoknya > 0 (Untuk Produksi & Olah)
     */
    public function getListAvailable(Request $request)
    {
        try {
            $dateInput = $request->query('date');
            $hari_ini = $dateInput ? Carbon::parse($dateInput)->startOfDay() : Carbon::today()->startOfDay();
            
            $bak_aktif_raw = Maturasi::orderBy('uraian', 'asc')->get();
            $available = []; 

            foreach ($bak_aktif_raw as $bak) {
                // 1. HITUNG STOK MURNI SIAP GILING (H-1) -> SAMA PERSIS WEB
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
                    // 2. LOGIKA UMUR (SINKRON 100% DENGAN MATURASI WEB ADMIN)
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

                        // Pokoknya setiap ada MASUK FRESH, umur untuk besok otomatis reset!
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
                    
                    // 3. SET DATA FINAL UNTUK ANDROID
                    $bak->umur = (int) $tgl_basis->diffInDays($hari_ini); 
                    $bak->stok_akhir = round($stok_kemarin, 2);
                    
                    // Variabel ekstra agar Java Android tidak gagal get
                    $bak->umur_real = $bak->umur; 
                    $bak->tgl_dasar_hitung = $tgl_basis->format('Y-m-d');
                    
                    $available[] = $bak;
                }
            }
            return response()->json(['success' => true, 'data' => $available], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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

    /**
     * [STORE] Simpan Input Harian (Mutasi / Diolah Manual)
     */
    public function store(Request $request)
    {
        $request->merge(['mutasi' => (float) $request->input('mutasi', 0)]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian',
            'tanggal_input_harian' => 'required|date',
            'mutasi' => 'nullable|numeric|min:0',
            'tujuan_mutasi' => 'nullable|exists:maturasi,id_maturasi',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $tglInput  = Carbon::parse($request->input('tanggal_input_harian'));
        $mutasiVal = (float) $request->input('mutasi');
        $bakAsal   = Maturasi::where('uraian', $request->input('uraian'))->first();

        DB::beginTransaction();
        try {
            // --- 1. SIMPAN LOG (Tetap perlu untuk database history) ---
            $existingAsal = PengolahanMaturasi::where('id_maturasi', $bakAsal->id_maturasi)
                ->whereDate('tgl_laporan', $tglInput)->first();

            $ketAsal = $request->input('keterangan') ?? 'Mutasi Keluar';
            if ($existingAsal && str_contains($existingAsal->keterangan, 'Terima dari')) {
                $ketAsal = $existingAsal->keterangan . ' & Mutasi';
            }

            PengolahanMaturasi::updateOrCreate(
                ['id_maturasi' => $bakAsal->id_maturasi, 'tgl_laporan' => $tglInput],
                [
                    'diolah' => $existingAsal->diolah ?? 0, 
                    'mutasi' => ($existingAsal->mutasi ?? 0) + $mutasiVal, 
                    'keterangan' => $ketAsal
                ]
            );

            if ($request->filled('tujuan_mutasi') && $mutasiVal > 0) {
                $bakTujuan = Maturasi::find($request->input('tujuan_mutasi'));
                if ($bakTujuan) {
                    $existingTujuan = PengolahanMaturasi::where('id_maturasi', $bakTujuan->id_maturasi)
                        ->whereDate('tgl_laporan', $tglInput)->first();

                    PengolahanMaturasi::updateOrCreate(
                        ['id_maturasi' => $bakTujuan->id_maturasi, 'tgl_laporan' => $tglInput],
                        [
                            'mutasi'     => ($existingTujuan->mutasi ?? 0) - $mutasiVal, 
                            'keterangan' => 'Terima dari ' . $bakAsal->uraian
                        ]
                    );
                }
            }

            DB::commit();

            // --- 2. 🔥 PERBAIKAN: SINKRONISASI TOTAL VIA TRAIT ---
            // Ini akan menangani Label CMP, Stok Akhir, dan status KOSONG secara otomatis
            $this->syncMaturasi($bakAsal->id_maturasi, $tglInput);
            if ($request->filled('tujuan_mutasi')) {
                $this->syncMaturasi($request->input('tujuan_mutasi'), $tglInput);
            }

            return response()->json(['success' => true, 'message' => 'Mutasi berhasil diproses.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal Server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [EDIT] Mengambil data detail mutasi untuk diisi ke Form Edit Mobile
     */
    public function edit(Request $request, $id_maturasi)
    {
        try {
            $maturasi = Maturasi::find($id_maturasi);
            if (!$maturasi) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

            $selectedDate = $request->has('filter_tanggal') ? Carbon::parse($request->input('filter_tanggal')) : Carbon::today();
            $snap = $this->hitungSnapshot($maturasi, $selectedDate);

            // Cari Tujuan Mutasi
            $tujuanId = null;
            $logAsal = PengolahanMaturasi::where('id_maturasi', $id_maturasi)->whereDate('tgl_laporan', $selectedDate)->first();
            
            if ($logAsal && $logAsal->mutasi > 0) {
                $namaBakAsal = trim($maturasi->uraian);
                $logTujuan = PengolahanMaturasi::whereDate('tgl_laporan', $selectedDate)
                    ->where('keterangan', 'LIKE', '%' . $namaBakAsal . '%')
                    ->where('mutasi', '<', 0)->first();
                if($logTujuan) $tujuanId = $logTujuan->id_maturasi;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id_maturasi' => $maturasi->id_maturasi,
                    'uraian'      => $maturasi->uraian,
                    'mutasi'      => abs((float) $snap['mutasi']), // Mutasi murni
                    'tujuan_mutasi_id' => $tujuanId,
                    'keterangan'  => $snap['keterangan']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [UPDATE] Menyimpan perubahan Mutasi (Sinkron 100% dengan Web Admin)
     */
    public function update(Request $request, $id_maturasi)
    {
        $request->merge(['mutasi' => (float) $request->input('mutasi', 0)]);
        
        $validator = Validator::make($request->all(), [
            'tanggal_input_harian' => 'required|date',
            'mutasi' => 'nullable|numeric|min:0',
            'tujuan_mutasi' => 'nullable|exists:maturasi,id_maturasi',
        ]);

        if ($validator->fails()) return response()->json(['success'=>false, 'message'=>$validator->errors()->first()], 422);

        $mutasiBaru = (float) $request->input('mutasi');
        $tglInput = Carbon::parse($request->input('tanggal_input_harian'));

        DB::beginTransaction();
        try {
            $bakSekarang = Maturasi::findOrFail($id_maturasi);
            $logSekarang = PengolahanMaturasi::where('id_maturasi', $id_maturasi)->whereDate('tgl_laporan', $tglInput)->first();
            
            if (!$logSekarang) return response()->json(['success'=>false, 'message'=>'Tidak ada mutasi di tanggal ini.'], 404);

            $logPasangan = null;
            $bakPasangan = null;

            // 1. CARI PASANGAN MUTASI
            if ($logSekarang->mutasi > 0) {
                $namaBakAsal = trim($bakSekarang->uraian);
                $logPasangan = PengolahanMaturasi::whereDate('tgl_laporan', $tglInput)
                    ->where('keterangan', 'LIKE', '%Terima dari ' . $namaBakAsal . '%')
                    ->where('mutasi', '<', 0)->first();
                if ($logPasangan) $bakPasangan = Maturasi::find($logPasangan->id_maturasi);

            } else if ($logSekarang->mutasi < 0) {
                if (preg_match('/Terima dari (.*)/', $logSekarang->keterangan, $matches)) {
                    $namaBakAsal = trim($matches[1]);
                    $bakPasangan = Maturasi::where('uraian', $namaBakAsal)->first();
                    if ($bakPasangan) {
                        $logPasangan = PengolahanMaturasi::where('id_maturasi', $bakPasangan->id_maturasi)
                            ->whereDate('tgl_laporan', $tglInput)
                            ->where('mutasi', '>', 0)->first();
                    }
                }
            }

            // 2. UPDATE LOG
            $nilaiSekarang = ($logSekarang->mutasi > 0) ? $mutasiBaru : -$mutasiBaru;
            $logSekarang->update(['mutasi' => $nilaiSekarang]);

            if ($logPasangan) {
                $nilaiPasangan = ($logSekarang->mutasi > 0) ? -$mutasiBaru : $mutasiBaru;
                $logPasangan->update(['mutasi' => $nilaiPasangan]);
            }

            // 3. SYNC STOK
            $isOriginSource = ($logSekarang->mutasi > 0); 
            $bakAsalObj     = $isOriginSource ? $bakSekarang : $bakPasangan;
            $bakTujuanObj   = $isOriginSource ? $bakPasangan : $bakSekarang;

            if ($bakAsalObj) {
                $snapA = $this->hitungSnapshot($bakAsalObj, $tglInput);
                $updateA = ['stok_akhir' => $snapA['stok_akhir'], 'updated_at' => $tglInput];
                if ($snapA['stok_akhir'] <= 0.01) {
                    $updateA += ['tgl_masuk' => null, 'asal_bokar' => null, 'keterangan' => 'KOSONG'];
                } else {
                     $histTgl = $this->getHistoryDateFromLog($bakAsalObj->id_maturasi, $tglInput);
                     if($histTgl) {
                        $updateA['tgl_masuk'] = $histTgl->toDateString();
                        $updateA['keterangan'] = strtoupper($histTgl->format('d M Y'));
                     }
                }
                $bakAsalObj->update($updateA);
            }

            if ($bakTujuanObj) {
                if ($bakAsalObj && $mutasiBaru > 0) {
                     $snapVisualAsal = $this->hitungSnapshot($bakAsalObj, $tglInput);
                     $stokBasisMutasi = $snapVisualAsal['stok_awal'] + $snapVisualAsal['masuk_hi'] - $snapVisualAsal['diolah'];
                     
                     if ($stokBasisMutasi > 0 && ($mutasiBaru / $stokBasisMutasi) >= 0.5) {
                        $asalBokarKirim = $this->getDetailedAsalBokarString($bakAsalObj, 100, 100, $tglInput); 
                        $histTgl = $this->getHistoryDateFromLog($bakAsalObj->id_maturasi, $tglInput);
                        $tglMasukKirim = $histTgl ? $histTgl->toDateString() : null;

                        $bakTujuanObj->update([
                            'tgl_masuk'  => $tglMasukKirim,
                            'asal_bokar' => $asalBokarKirim,
                            'keterangan' => $tglMasukKirim ? strtoupper(Carbon::parse($tglMasukKirim)->format('d M Y')) : '-',
                            'updated_at' => $tglInput
                        ]);
                     }
                }
                $snapT = $this->hitungSnapshot($bakTujuanObj, $tglInput);
                $bakTujuanObj->update(['stok_akhir' => $snapT['stok_akhir']]);
            }

            DB::commit();

            // 🔥 PERBAIKAN: SINKRONISASI OTOMATIS LEWAT TRAIT
            $this->syncMaturasi($id_maturasi, $tglInput);
            if ($bakPasangan) {
                $this->syncMaturasi($bakPasangan->id_maturasi, $tglInput);
            }

            return response()->json(['success' => true, 'message' => 'Mutasi berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [DROPDOWN ALL] Mengambil SEMUA daftar Bak (Untuk Timbang Bokar / Input Barang Masuk)
     * Tidak peduli stok ada atau nol.
     */
    public function getAll() {
        // Ambil semua kolom agar aman saat parsing di Android
        $data = Maturasi::orderBy('id_maturasi')->get(); 
        return response()->json(['success' => true, 'data' => $data]);
    }
}