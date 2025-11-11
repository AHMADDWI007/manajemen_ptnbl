<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maturasi; // Model utama
use App\Models\PengolahanMaturasi; // Untuk histori
use App\Models\HasilUjiBokarDiolah; // Untuk K3 Masuk
use App\Models\HasilUjiMaturasi; // ✅ PERBAIKAN: Import model ini
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon; 
use Illuminate\Database\Eloquent\Collection; 


class MaturasiApiController extends Controller
{
    /**
     * ✅ PERBAIKAN: [TABEL MATURASI] Mengambil data status maturasi
     * Logika disamakan dengan MaturasiController@index (Web)
     * Endpoint: GET /pengolahan-maturasi
     */
    public function index(Request $request)
    {
        try {
            // Mobile tidak mengirim filter, jadi kita selalu gunakan HARI INI
            $selectedDate = Carbon::today();

            // ✅ PERBAIKAN: Ubah 'hasilUji' ke relasi yang benar 'hasilUjiMaturasi'
            $data_maturasi_db = Maturasi::with(['hasilUjiMaturasi'])->orderBy('id')->get();
            
            $dataTampilan = new Collection();

            // Loop setiap Bak dan hitung snapshot-nya
            foreach ($data_maturasi_db as $bak) {
                // Panggil helper hitungSnapshot (dicopy dari web controller)
                $snap = $this->hitungSnapshot($bak, $selectedDate);
                
                $row = clone $bak;
                foreach ($snap as $k => $v) $row->$k = $v;
                $row->updated_at = $selectedDate;

                // --- Logika K3 Masuk (dari HasilUjiBokarDiolah) ---
                $nomorBak = null;
                if (preg_match('/Di Bak Maturasi-(\d+)/', $bak->uraian, $matches)) {
                    $nomorBak = "Bak Maturasi " . $matches[1];
                }
                
                $ujiK3 = null;
                if ($nomorBak) {
                    $ujiK3 = \App\Models\HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                        ->whereDate('tanggal', '<=', $selectedDate)
                        ->latest('tanggal')
                        ->first();
                }
                $row->k3_masuk = $ujiK3->k3 ?? 0;
                // --- Akhir Logika K3 Masuk ---

                // ✅ PERBAIKAN: TAMBAHKAN BLOK INI (untuk K3 Olah, PO, PRI)
                // (Disalin dari MaturasiController.php)
                // ==========================================================
                // Ambil nilai dari hasil uji maturasi jika ada
                $ujiTerakhir = $bak->hasilUjiMaturasi() // Gunakan relasi
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
                // ==============================================================
                // ✅ AKHIR BLOK TAMBAHAN
                
                // ✅ PERBAIKAN: Sempurnakan Logika Asal Bokar (dari web controller)
                // (Logika $snap['tgl_masuk'] sudah dihitung oleh hitungSnapshot)
                if ($row->stok_akhir > 0 && $row->tgl_masuk && $selectedDate->gte(Carbon::parse($row->tgl_masuk)->startOfDay())) {
                    // asal_bokar tetap tampil (nilainya sudah ada dari $row = clone $bak)
                } else {
                    $row->asal_bokar = null; // Sembunyikan
                }
                // --- Akhir Perbaikan Asal Bokar ---

                // Masukkan hasil kalkulasi ke data
                $dataTampilan->push($row);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Data Pengolahan Maturasi berhasil diambil.',
                'data' => $dataTampilan // Kirim data yang sudah dikalkulasi
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error index MaturasiApi: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // ✅ AKHIR PERBAIKAN FUNGSI INDEX

    /**
     * ✅ [FORM 2 - SPINNER] Mengambil daftar Bak Maturasi yang stoknya > 0
     * Endpoint: GET /maturasi/list-available
     */
    public function getListAvailable()
    {
        try {
            // ✅ PERBAIKAN: Kita ambil snapshot hari ini untuk cek stok
            // Ini lebih akurat daripada $bak->stok_akhir di DB
            $selectedDate = Carbon::today();
            $bak_aktif_list = [];
            $data_maturasi_db = Maturasi::orderBy('id')->get();

            foreach ($data_maturasi_db as $bak) {
                 $snap = $this->hitungSnapshot($bak, $selectedDate);
                 if ($snap['stok_akhir'] > 0) {
                    // Hanya kirim data yang perlu untuk spinner
                    $bak_aktif_list[] = [
                        'id' => $bak->id,
                        'uraian' => $bak->uraian
                    ];
                 }
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar Bak Maturasi (stok > 0) berhasil diambil.',
                'data' => $bak_aktif_list // Kirim daftar yang sudah difilter
            ], 200);

        } catch (\Exception $e) {
             Log::error('Error getListAvailable MaturasiApi: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ SEMUA FUNGSI HELPER DI BAWAH INI SUDAH BENAR
    // (Fungsi-fungsi ini disalin dari MaturasiController web Anda
    // dan penting untuk kalkulasi snapshot)
    // -------------------------------------------------------------------
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
        
        // ✅ PERBAIKAN: Pastikan memanggil helper yang benar
        $masuk_hi_today = $this->getMasukHiOnDateFromSources($maturasi->id, $selectedDate, $nomorBak);
        
        $diolah_today = $diolah_today_raw;
        if ($stok_awal == 0 && $masuk_hi_today > 0) {
            $diolah_today = 0;
            $mutasi_today = 0;
        }

        $stok_akhir = $stok_awal - $diolah_today - $mutasi_today + $masuk_hi_today;

        $last_before = $this->getLastMasukHiDateBefore($maturasi->id, $selectedDate, $nomorBak);

        $tgl_masuk = null;
        $umur = 0;

        // (Logika tgl_masuk dan umur disalin dari web controller)
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
                        } catch (\Exception $e) {}
                    }
                }
            } else {
                $tgl_masuk = null;
                $umur = 0;
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
            } else {
                $tgl_masuk = null;
                $umur = 0;
            }
        }
        
        // (Logika keterangan disalin dari web controller)
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
    // ✅ AKHIR SALINAN FUNGSI HELPER
}