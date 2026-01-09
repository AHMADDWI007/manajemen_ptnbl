<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiBokarDiolah;
use App\Models\HasilUjiMaturasi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MaturasiApiController extends Controller
{
    // =========================================================================
    // 1. HELPER FUNCTIONS (COPY-PASTE DARI WEB ADMIN AGAR SINKRON)
    // =========================================================================

    protected function getNetBeforeDate(int $maturasi_id, Carbon $date): float
    {
        $sums = PengolahanMaturasi::where('maturasi_id', $maturasi_id)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->select(
                DB::raw('COALESCE(SUM(masuk_hi),0) as sum_masuk'),
                DB::raw('COALESCE(SUM(diolah),0) as sum_diolah'),
                DB::raw('COALESCE(SUM(mutasi),0) as sum_mutasi')
            )->first();

        if (!$sums) return 0;
        return (float)$sums->sum_masuk - (float)$sums->sum_diolah - (float)$sums->sum_mutasi;
    }

    protected function getSumsOnDate(int $maturasi_id, Carbon $date): array
    {
        $s = PengolahanMaturasi::where('maturasi_id', $maturasi_id)
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

    protected function getLastMasukHiDateBefore(int $maturasi_id, Carbon $date, ?string $nomorBak = null)
    {
        $log = PengolahanMaturasi::where('maturasi_id', $maturasi_id)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->select('tgl_laporan', DB::raw('COALESCE(SUM(masuk_hi),0) as total_masuk'))
            ->groupBy('tgl_laporan')
            ->havingRaw('COALESCE(SUM(masuk_hi),0) > 0')
            ->orderBy('tgl_laporan', 'desc')
            ->first();

        $dateLog = $log ? Carbon::parse($log->tgl_laporan) : null;

        $dateUji = null;
        if ($nomorBak) {
            $uji = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', '<', $date->toDateString())
                ->select('tanggal', DB::raw('COALESCE(SUM(netto_kering),0) as total'))
                ->groupBy('tanggal')
                ->havingRaw('COALESCE(SUM(netto_kering),0) > 0')
                ->orderBy('tanggal', 'desc')
                ->first();
            $dateUji = $uji ? Carbon::parse($uji->tanggal) : null;
        }

        if ($dateLog && $dateUji) return $dateLog->gte($dateUji) ? $dateLog : $dateUji;
        if ($dateLog) return $dateLog;
        if ($dateUji) return $dateUji;
        return null;
    }

    protected function getMasukHiOnDateFromSources(int $maturasi_id, Carbon $date, ?string $nomorBak = null): float
    {
        $s = PengolahanMaturasi::where('maturasi_id', $maturasi_id)
            ->whereDate('tgl_laporan', $date->toDateString())
            ->sum('masuk_hi');

        if ($nomorBak) {
            $fromUji = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', $date->toDateString())
                ->sum('netto_kering');
            return max($s, (float)$fromUji);
        }
        return (float)$s;
    }
    
    protected function hasAnyLogUpToDate(int $maturasi_id, Carbon $date, ?string $nomorBak = null): bool
    {
        $countLog = PengolahanMaturasi::where('maturasi_id', $maturasi_id)
            ->whereDate('tgl_laporan', '<=', $date->toDateString())
            ->where(function ($q) {
                $q->where('masuk_hi', '>', 0)
                    ->orWhere('diolah', '>', 0)
                    ->orWhere('mutasi', '>', 0);
            })->count();

        if ($countLog > 0) return true;

        if ($nomorBak) {
            $countUji = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', '<=', $date->toDateString())
                ->where('netto_kering', '>', 0)
                ->count();
            return $countUji > 0;
        }
        return false;
    }

    protected function hitungSnapshot(Maturasi $maturasi, Carbon $selectedDate): array
    {
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $maturasi->uraian, $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1];
        }

        if (! $this->hasAnyLogUpToDate($maturasi->id, $selectedDate, $nomorBak)) {
            return [
                'stok_awal' => 0, 'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
                'stok_akhir' => 0, 'tgl_masuk' => null, 'umur' => 0, 'keterangan' => '-'
            ];
        }

        $stok_awal = $this->getNetBeforeDate($maturasi->id, $selectedDate);
        $sumsToday = $this->getSumsOnDate($maturasi->id, $selectedDate);
        $diolah_today_raw = $sumsToday['diolah'];
        $mutasi_today = $sumsToday['mutasi'];
        $masuk_hi_today_from_pengolahan = $sumsToday['masuk_hi'];

        $masuk_from_uji = 0;
        if ($nomorBak) {
            $masuk_from_uji = (float) HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', $selectedDate)
                ->sum('netto_kering');
        }

        $masuk_hi_today = max($masuk_hi_today_from_pengolahan, $masuk_from_uji);

        $diolah_today = $diolah_today_raw;
        if ($stok_awal == 0 && $masuk_hi_today > 0) {
            $diolah_today = 0;
            $mutasi_today = 0;
        }

        $stok_akhir = $stok_awal - $diolah_today - $mutasi_today + $masuk_hi_today;

        $last_before = $this->getLastMasukHiDateBefore($maturasi->id, $selectedDate, $nomorBak);

        $tgl_masuk = null;
        $umur = 0;

        if ($stok_akhir > 0) {
            if (!($stok_awal == 0 && $masuk_hi_today > 0)) {
                if ($last_before) {
                    $tgl_masuk = $last_before->toDateString();
                    $umur = $last_before->diffInDays($selectedDate);
                } else {
                    if ($maturasi->tgl_masuk) {
                        try {
                            $candidate = Carbon::parse($maturasi->tgl_masuk);
                            if ($candidate->lte($selectedDate)) {
                                $tgl_masuk = $candidate->toDateString();
                                $umur = $candidate->diffInDays($selectedDate);
                            }
                        } catch (\Exception $e) { }
                    }
                }
            }
        } else {
             if ($stok_awal > 0) {
                if ($last_before) {
                    $tgl_masuk = $last_before->toDateString();
                    $umur = $last_before->diffInDays($selectedDate);
                } else {
                     if ($maturasi->tgl_masuk) {
                        try {
                            $candidate = Carbon::parse($maturasi->tgl_masuk);
                            if ($candidate->lte($selectedDate)) {
                                $tgl_masuk = $candidate->toDateString();
                                $umur = $candidate->diffInDays($selectedDate);
                            }
                        } catch (\Exception $e) {}
                    }
                }
            }
        }

        if ($stok_akhir <= 0) {
                $keterangan = '-';
            } else {
                if ($masuk_hi_today > 0) {
                    $keterangan = strtoupper($selectedDate->format('d M Y'));
                } else {
                    if ($last_before) {
                        $keterangan = strtoupper($last_before->format('d M Y'));
                    } elseif ($maturasi->tgl_masuk) {
                        try {
                            $candidate = Carbon::parse($maturasi->tgl_masuk);
                            $keterangan = strtoupper($candidate->format('d M Y'));
                        } catch (\Exception $e) {
                            $keterangan = '-';
                        }
                    } else {
                        $keterangan = '-';
                    }
                }
            }

        return [
            'stok_awal' => $stok_awal,
            'diolah' => $diolah_today,
            'mutasi' => $mutasi_today,
            'masuk_hi' => $masuk_hi_today,
            'stok_akhir' => $stok_akhir,
            'tgl_masuk' => $tgl_masuk,
            'umur' => $umur,
            'keterangan' => $keterangan,
        ];
    }

    // =========================================================================
    // 2. ENDPOINT UTAMA (INDEX, SPINNER, STORE)
    // =========================================================================

    /**
     * [LIST UTAMA] GET /pengolahan-maturasi
     * Mengembalikan data status maturasi dengan kalkulasi real-time (SNAPSHOT)
     */
    public function index(Request $request)
    {
        try {
            // Mobile selalu menampilkan status HARI INI
            $selectedDate = Carbon::today();

            // Load data Master dengan relasi ke Hasil Uji Maturasi
            // (Agar kita bisa mengambil data K3 Olah, Po, Pri)
            $data_maturasi_db = Maturasi::with(['hasilUjiMaturasi'])->orderBy('id')->get();
            
            $dataTampilan = new Collection();

            // Loop untuk hitung snapshot setiap bak
            foreach ($data_maturasi_db as $bak) {
                $snap = $this->hitungSnapshot($bak, $selectedDate);
                
                // Clone object agar tidak mengubah data asli di loop
                $row = clone $bak;
                foreach ($snap as $k => $v) $row->$k = $v;
                $row->updated_at = $selectedDate;

                // --- LOGIKA K3 MASUK (Dari Uji Bokar Diolah) ---
                $nomorBak = null;
                if (preg_match('/Di Bak Maturasi-(\d+)/', $bak->uraian, $matches)) {
                    $nomorBak = "Bak Maturasi " . $matches[1];
                }
                
                $ujiK3Masuk = null;
                if ($nomorBak) {
                    $ujiK3Masuk = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                        ->whereDate('tanggal', '<=', $selectedDate)
                        ->latest('tanggal')
                        ->first();
                }
                $row->k3_masuk = $ujiK3Masuk->k3 ?? 0;

                // --- LOGIKA K3 OLAH / HASIL UJI LAB (Dari Hasil Uji Maturasi) ---
                // Cari data uji terakhir
                $ujiTerakhir = $bak->hasilUjiMaturasi()
                    ->whereDate('tanggal', '<=', $selectedDate)
                    ->latest('tanggal')
                    ->first();

                if ($ujiTerakhir) {
                    $row->k3_olah = $ujiTerakhir->k3 ?? 0;
                    $row->po      = $ujiTerakhir->po ?? 0;
                    $row->pri     = $ujiTerakhir->pri ?? 0;
                    $row->tgl_uji = $ujiTerakhir->tanggal;
                } else {
                    $row->k3_olah = 0;
                    $row->po      = 0;
                    $row->pri     = 0;
                    $row->tgl_uji = null;
                }

                // --- LOGIKA ASAL BOKAR ---
                // Jika stok habis atau belum masuk hari ini, sembunyikan asal bokar
                if ($row->stok_akhir > 0 && $row->tgl_masuk && $selectedDate->gte(Carbon::parse($row->tgl_masuk)->startOfDay())) {
                    // Biarkan $row->asal_bokar apa adanya
                } else {
                    $row->asal_bokar = null; 
                }

                $dataTampilan->push($row);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Data Maturasi berhasil dihitung dan diambil.',
                'data' => $dataTampilan
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error index MaturasiApi: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [SPINNER] GET /maturasi/list-available
     * Mengambil daftar Bak yang STOK-nya > 0 (Untuk pilihan di Produksi)
     */
    public function getListAvailable()
    {
        try {
            $selectedDate = Carbon::today();
            $bak_aktif_list = [];
            $data_maturasi_db = Maturasi::orderBy('id')->get();

            foreach ($data_maturasi_db as $bak) {
                 $snap = $this->hitungSnapshot($bak, $selectedDate);
                 // Hanya ambil yang stok akhirnya positif
                 if ($snap['stok_akhir'] > 0) {
                    $bak_aktif_list[] = [
                        'id' => $bak->id,
                        'uraian' => $bak->uraian,
                        // Kirim juga stok & k3 kalau-kalau android butuh
                        'stok_akhir' => $snap['stok_akhir'], 
                    ];
                 }
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar Bak Aktif berhasil diambil.',
                'data' => $bak_aktif_list
            ], 200);

        } catch (\Exception $e) {
             Log::error('Error getListAvailable MaturasiApi: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [INPUT MANUAL] POST /pengolahan-maturasi/store
     * Menyimpan data DIOLAH / MUTASI dari Android
     */
    public function store(Request $request)
    {
        try {
            // Helper cleaning number (jika android kirim format indo 1.000,00)
            $cleanNumber = function ($value) {
                if (empty($value)) return 0;
                if (is_string($value)) {
                    return (float) str_replace(',', '.', str_replace('.', '', $value));
                }
                return (float) $value;
            };

            $validatedData = $request->validate([
                'uraian'        => 'required|string|exists:maturasis,uraian',
                'tanggal_input' => 'required|date',
                'diolah'        => 'nullable', // Bisa string/number
                'mutasi'        => 'nullable',
                'keterangan'    => 'nullable|string|max:255',
            ]);

            $tanggalInput = Carbon::parse($validatedData['tanggal_input']);
            $maturasi = Maturasi::where('uraian', $validatedData['uraian'])->firstOrFail();

            // Bersihkan input angka
            $diolah = $cleanNumber($request->diolah);
            $mutasi = $cleanNumber($request->mutasi);
            
            // Cek Stok Cukup atau Tidak (Optional Validation)
            // if ($maturasi->stok_akhir < ($diolah + $mutasi)) {
            //     return response()->json(['success' => false, 'message' => 'Stok tidak cukup!'], 400);
            // }

            // 1. Simpan/Update Log Harian (PengolahanMaturasi)
            // Hati-hati: Kita update log harian untuk tanggal tersebut
            $logHarian = PengolahanMaturasi::updateOrCreate(
                ['maturasi_id' => $maturasi->id, 'tgl_laporan' => $tanggalInput],
                [
                    // Kita gunakan DB::raw untuk increment jika mau, tapi replace lebih aman utk input manual
                    'diolah'   => $diolah,
                    'mutasi'   => $mutasi,
                    // Jangan ubah masuk_hi di sini (biarkan urusan lab), kecuali null
                    // 'masuk_hi' => DB::raw('masuk_hi'), 
                    'keterangan' => $validatedData['keterangan'] ?? 'Input Mobile',
                ]
            );

            // 2. Update Master Maturasi (Hitung Ulang Stok Akhir Master)
            // Ambil snapshot terakhir untuk tanggal ini agar akurat
            $snap = $this->hitungSnapshot($maturasi, $tanggalInput);
            
            // Update tabel master
            $maturasi->stok_awal = $snap['stok_awal'];
            $maturasi->diolah = $snap['diolah'];
            $maturasi->mutasi = $snap['mutasi'];
            $maturasi->masuk_hi = $snap['masuk_hi'];
            $maturasi->stok_akhir = $snap['stok_akhir'];
            $maturasi->umur = $snap['umur'];
            $maturasi->tgl_masuk = $snap['tgl_masuk'];
            $maturasi->keterangan = $snap['keterangan'];
            $maturasi->updated_at = $tanggalInput;
            $maturasi->save();

            return response()->json([
                'success' => true,
                'message' => 'Data Olah/Mutasi berhasil disimpan.',
                'data'    => $maturasi
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error store OlahMaturasi API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}