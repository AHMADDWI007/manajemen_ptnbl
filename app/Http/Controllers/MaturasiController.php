<?php
namespace App\Http\Controllers;

use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiBokarDiolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Controller;

class MaturasiController extends Controller
{
    // ===================================================================
    // FUNGSI HELPER ASLI ANDA (TETAP DIPAKAI)
    // ===================================================================

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
    
    protected function hitungSnapshot(Maturasi $maturasi, Carbon $selectedDate): array
    {
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $maturasi->uraian, $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1];
        }

        // jika tidak ada log sampai tanggal ini -> default kosong
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

        // ===== RULE: hari pertama masuk -> jangan tampilkan nilai 'diolah' pada hari yang sama
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
                        } catch (\Exception $e) { }
                    }
                }
            } else {
                $tgl_masuk = null;
                $umur = 0;
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


    // ===================================================================
    // ⬇️ HELPER BARU 1: Logika Kalkulasi Inti (Mengembalikan Array) ⬇️
    // ===================================================================

    /**
     * [HELPER BARU] Mengambil daftar (array) asal bokar yang stoknya 
     * masih aktif (> 0) pada tanggal filter.
     */
    protected function getActiveAsalBokarList(string $bakMaturasi, string $filterTanggal): array
    {
        $maturasi = Maturasi::where('uraian', $bakMaturasi)->first();
        if (!$maturasi) {
            return []; // Return array kosong jika bak tidak ditemukan
        }

        // Ambil semua data masuk/keluar sampai tanggal filter
        $dataMasuk = PengolahanMaturasi::where('maturasi_id', $maturasi->id)
            ->where('tgl_laporan', '<=', $filterTanggal)
            ->orderBy('tgl_laporan')
            ->get();

        if ($dataMasuk->isEmpty()) {
             // Fallback ke Uji Bokar jika log pengolahan kosong
             $ujiBokarData = HasilUjiBokarDiolah::join('maturasis', 'maturasis.uraian', '=', DB::raw("CONCAT('Di ', hasil_uji_bokar_diolah.bak_maturasi)"))
                ->where('maturasis.uraian', $bakMaturasi)
                ->where('hasil_uji_bokar_diolah.tanggal', '<=', $filterTanggal)
                ->orderBy('hasil_uji_bokar_diolah.tanggal', 'desc')
                ->select('hasil_uji_bokar_diolah.jenis') 
                ->first();
             
             // Pastikan tidak null dan bukan string kosong sebelum dimasukkan ke array
             return ($ujiBokarData && !empty($ujiBokarData->jenis)) ? [$ujiBokarData->jenis] : [];
        }

        $stokPerAsal = [];
        foreach ($dataMasuk as $item) {
            $asal = $item->asal_bokar;
            
            if (empty($asal) && $item->masuk_hi > 0) {
                 $ujiBokarAsal = HasilUjiBokarDiolah::where(DB::raw("CONCAT('Di ', bak_maturasi)"), $maturasi->uraian)
                    ->whereDate('tanggal', $item->tgl_laporan) 
                    ->value('jenis');
                 $asal = $ujiBokarAsal ?? 'UNKNOWN';
            } elseif (empty($asal)) {
                $asal = 'UNKNOWN';
            }

            if (!isset($stokPerAsal[$asal])) {
                $stokPerAsal[$asal] = 0;
            }
            $stokPerAsal[$asal] += (float)$item->masuk_hi - (float)$item->diolah - (float)$item->mutasi;
        }

        // Filter stok > 0
        $stokAktif = array_filter($stokPerAsal, fn($stok) => $stok > 0.01); 

        // Hapus UNKNOWN jika masih ada stok lain yang valid
        if (count($stokAktif) > 1 && isset($stokAktif['UNKNOWN'])) {
             unset($stokAktif['UNKNOWN']);
        }
        
        // Hanya kembalikan nama-nama (keys) dari stok yang aktif
        $activeKeys = array_keys($stokAktif);
        
        // Pastikan kita tidak mengembalikan 'UNKNOWN' sendirian
        if (count($activeKeys) === 1 && $activeKeys[0] === 'UNKNOWN') {
            return [];
        }

        return $activeKeys; 
    }


    // ===================================================================
    // ⬇️ HELPER BARU 2: Formatter untuk Tampilan Detail (Modal) ⬇️
    // ===================================================================

    /**
     * [FUNGSI BARU] Mengambil string asal bokar yang sudah diformat untuk TAMPILAN DETAIL.
     * Mengembalikan "PT", "INHUT", or "CMP (PT, INHUT)"
     */
    protected function getFormattedAsalBokarDetail(string $bakMaturasi, string $filterTanggal): string
    {
        $activeList = $this->getActiveAsalBokarList($bakMaturasi, $filterTanggal);

        if (count($activeList) === 0) {
            return '-';
        } elseif (count($activeList) === 1) {
            return $activeList[0]; // "PT"
        } else {
            // Ada lebih dari 1, format sebagai CMP (...)
            return 'CMP (' . implode(', ', $activeList) . ')'; // "CMP (PT, INHUT)"
        }
    }


    // ===================================================================
    // ⬇️ FUNGSI LAMA (DIPERBARUI): Untuk Tampilan Index (Simple) ⬇️
    // ===================================================================

    /**
     * [FUNGSI DIPERBARUI] Fungsi untuk hitung asal bokar (SIMPLE) untuk tampilan INDEX.
     * Mengembalikan "PT", "CMP", or null.
     */
    protected function getAsalBokarByFilterTanggal(string $bakMaturasi, string $filterTanggal): ?string
    {
        // Panggil helper kalkulasi inti
        $activeList = $this->getActiveAsalBokarList($bakMaturasi, $filterTanggal);

        if (count($activeList) === 1) {
            // Hanya satu asal bokar stok > 0
            return $activeList[0]; // "PT"

        } elseif (count($activeList) > 1) {
            // Lebih dari satu asal bokar stok > 0 berarti campuran
            return 'CMP';
        } else {
            // STOK HABIS: Ambil asal bokar terakhir yang masuk
            // (Logika fallback ini kita pertahankan jika stok = 0)
            
            $maturasi = Maturasi::where('uraian', $bakMaturasi)->first();
            if (!$maturasi) return null;

            $dataMasuk = PengolahanMaturasi::where('maturasi_id', $maturasi->id)
                ->where('tgl_laporan', '<=', $filterTanggal)
                ->get();
                
            $lastEntryWithAsal = $dataMasuk->whereNotNull('asal_bokar')->where('asal_bokar', '!=', 'UNKNOWN')->last();
            if ($lastEntryWithAsal) {
                return $lastEntryWithAsal->asal_bokar;
            }

            // Fallback terakhir jika tidak ada di PengolahanMaturasi
            $ujiBokarData = HasilUjiBokarDiolah::join('maturasis', 'maturasis.uraian', '=', DB::raw("CONCAT('Di ', hasil_uji_bokar_diolah.bak_maturasi)"))
                ->where('maturasis.uraian', $bakMaturasi)
                ->where('hasil_uji_bokar_diolah.tanggal', '<=', $filterTanggal)
                ->orderBy('hasil_uji_bokar_diolah.tanggal', 'desc')
                ->select('hasil_uji_bokar_diolah.jenis') 
                ->first();
            
            return $ujiBokarData->jenis ?? null;
        }
    }


    // ===================================================================
    // ⬇️ FUNGSI INDEX (HANYA MEMANGGIL FUNGSI DI ATAS) ⬇️
    // ===================================================================

   public function index(Request $request): View
    {
        $selectedDate = $request->has('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        // 1. Ambil Data Utama
        $data_maturasi_db = Maturasi::with(['hasilUjiMaturasi', 'hasilUjiBokarDiolah'])
            ->orderBy('id')
            ->get();

        // 2. [BARU] Hitung Total Diolah s/d Kemarin (Sebelum Tanggal Filter)
        // Ini diperlukan untuk footer tabel bagian "s/d Kemarin"
        $total_diolah_sd_kemarin = \App\Models\PengolahanMaturasi::whereDate('tgl_laporan', '<', $selectedDate)
            ->sum('diolah');

        $dataTampilan = new Collection();
        $bak_aktif_list = [];

        foreach ($data_maturasi_db as $bak) {
            $snap = $this->hitungSnapshot($bak, $selectedDate);
            $row = clone $bak;
            foreach ($snap as $k => $v) $row->$k = $v;
            $row->updated_at = $selectedDate;

            // 🔹 Ambil nomor bak dari uraian
            $nomorBak = null;
            if (preg_match('/Di Bak Maturasi-(\d+)/', $bak->uraian, $matches)) {
                $nomorBak = "Bak Maturasi " . $matches[1];
            }

            // 🔹 Ambil hasil uji K3 masuk
            $ujiK3 = null;
            if ($nomorBak) {
                $ujiK3 = \App\Models\HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                    ->whereDate('tanggal', '<=', $selectedDate)
                    ->latest('tanggal')
                    ->first();
            }

            // Default nilai
            $row->k3_masuk = $ujiK3->k3 ?? 0;
            $row->asal_bokar = '-';

            // 🔹 Tentukan apakah stok masih dianggap aktif untuk hari ini
            $stokMasihAktif = $row->stok_akhir > 0;

            // 🔹 Kalau stok 0 tapi tanggal pengolahan terakhir == tanggal filter, maka hari ini masih tampil
            if (!$stokMasihAktif) {
                $tglOlahTerakhir = \App\Models\HasilUjiMaturasi::where('id', $bak->id_hasil_uji_maturasi)
                    ->value('tanggal');
                
                if ($tglOlahTerakhir && Carbon::parse($tglOlahTerakhir)->isSameDay($selectedDate)) {
                    $stokMasihAktif = true;
                }
            }
            
            // 🔹 Tampilkan asal bokar HANYA jika stok masih aktif
            if ($stokMasihAktif) {
                // Gunakan fungsi (SIMPLE) untuk tampilan index
                $row->asal_bokar = $this->getAsalBokarByFilterTanggal($bak->uraian, $selectedDate->toDateString()) ?? '-';
            } else {
                $row->asal_bokar = '-';
            }

            // =========================================================
            // BAGIAN UTAMA: ambil hasil uji maturasi sesuai tanggal
            // =========================================================

            $ujiTerakhir = null;
            if ($bak->id_hasil_uji_maturasi) {
                $ujiTerakhir = \App\Models\HasilUjiMaturasi::where('id', '<=', $bak->id_hasil_uji_maturasi)
                    ->whereDate('tanggal', '<=', $selectedDate)
                    ->orderBy('tanggal', 'desc')
                    ->first();
            }
            
            if ($ujiTerakhir) {
                $tanggalUji = Carbon::parse($ujiTerakhir->tanggal);

                if ($row->stok_akhir > 0) {
                    $row->k3_olah = $ujiTerakhir->k3 ?? 0;
                    $row->po      = $ujiTerakhir->po ?? 0;
                    $row->pri     = $ujiTerakhir->pri ?? 0;
                    $row->tgl_uji = $ujiTerakhir->tanggal;
                }
                elseif ($selectedDate->isSameDay($tanggalUji)) {
                    $row->k3_olah = $ujiTerakhir->k3 ?? 0;
                    $row->po      = $ujiTerakhir->po ?? 0;
                    $row->pri     = $ujiTerakhir->pri ?? 0;
                    $row->tgl_uji = $ujiTerakhir->tanggal;
                }
                else {
                    $row->k3_olah = 0;
                    $row->po      = 0;
                    $row->pri     = 0;
                    $row->tgl_uji = null;
                }
            } else {
                $row->k3_olah = 0;
                $row->po      = 0;
                $row->pri     = 0;
                $row->tgl_uji = null;
            }

            if ($row->stok_akhir <= 0 && !$stokMasihAktif) {
                $row->asal_bokar = '-';
            }

            $dataTampilan->push($row);

            if ($row->stok_akhir > 0) {
                $bak_aktif_list[] = $row->uraian;
            }
        }

        return view('Pengolahan.data_maturasi', [
            'data_maturasi' => $dataTampilan,
            'bak_aktif_list' => $bak_aktif_list,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'total_diolah_sd_kemarin' => $total_diolah_sd_kemarin // <--- KIRIM VARIABEL INI KE VIEW
        ]);
    }


    // ===================================================================
    // ⬇️ FUNGSI getPreviousData (DIPERBARUI UNTUK MODAL DETAIL) ⬇️
    // ===================================================================

    public function getPreviousData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'tanggal_filter' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Input tidak valid'], 400);
        }

        $uraian = $request->input('uraian');
        $tanggalFilter = Carbon::parse($request->input('tanggal_filter'));

        $maturasi = Maturasi::where('uraian', $uraian)->first();
        if (!$maturasi) {
            return response()->json(['error' => 'Data Bak tidak ditemukan'], 404);
        }

        $snap = $this->hitungSnapshot($maturasi, $tanggalFilter);

        // ✅ Jika benar-benar kosong (tidak ada aktivitas/log sama sekali)
        if (
            ($snap['stok_awal'] ?? 0) == 0 &&
            ($snap['stok_akhir'] ?? 0) == 0 &&
            ($snap['masuk_hi'] ?? 0) == 0 &&
            ($snap['diolah'] ?? 0) == 0 &&
            ($snap['mutasi'] ?? 0) == 0 &&
            empty($snap['tgl_masuk'])
        ) {
            return response()->json([
                'stok_awal' => 0,
                'umur' => 0,
                'netto_kering_hi' => 0,
                'diolah' => 0,
                'mutasi' => 0,
                'stok_akhir' => 0,
                'tgl_masuk' => null,
                'keterangan' => '-',
                'k3_masuk' => 0,
                'k3_olah' => 0,
                'po' => 0,
                'pri' => 0,
                'tgl_uji' => null,
                'asal_bokar' => '-', // <-- default
            ]);
        }

        // --- kalau tidak kosong, lanjut seperti biasa ---
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $maturasi->uraian, $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1];
        }

        // ... (Kode untuk $k3_masuk, $k3_olah, $po, $pri, $tgl_uji tetap sama) ...
        $ujiK3 = null;
        if ($nomorBak) {
            $ujiK3 = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', '<=', $tanggalFilter)
                ->latest('tanggal')
                ->first();
        }
        $k3_masuk = $ujiK3->k3 ?? 0;

        $ujiTerakhir = null;
        if ($maturasi->id_hasil_uji_maturasi) {
            $ujiTerakhir = \App\Models\HasilUjiMaturasi::whereDate('tanggal', '<=', $tanggalFilter)
                ->where('id', '<=', $maturasi->id_hasil_uji_maturasi)
                ->orderBy('tanggal', 'desc')
                ->first();
        }

        $k3_olah = $ujiTerakhir->k3 ?? 0;
        $po      = $ujiTerakhir->po ?? 0;
        $pri     = $ujiTerakhir->pri ?? 0;
        $tgl_uji = $ujiTerakhir->tanggal ?? null;

        // =========================================================
        // 🔧 BAGIAN YANG DIPERBARUI (MENGGUNAKAN LOGIKA DETAIL) 🔧
        // =========================================================
        
        // Replikasi logika $stokMasihAktif dari 'index' agar konsisten
        $stokMasihAktif = $snap['stok_akhir'] > 0;
        if (!$stokMasihAktif) {
            $tglOlahTerakhir = \App\Models\HasilUjiMaturasi::where('id', $maturasi->id_hasil_uji_maturasi)
                ->value('tanggal');
            
            if ($tglOlahTerakhir && Carbon::parse($tglOlahTerakhir)->isSameDay($tanggalFilter)) {
                $stokMasihAktif = true;
            }
        }

        $asal_bokar = '-';
        if ($stokMasihAktif) {
             // Gunakan fungsi BARU (Langkah 2) untuk format detail "CMP (PT, INHUT)"
            $asal_bokar = $this->getFormattedAsalBokarDetail(
                $maturasi->uraian, 
                $tanggalFilter->toDateString()
            );
        }
        
        // =========================================================
        // 🔧 AKHIR BAGIAN YANG DIPERBARUI 🔧
        // =========================================================

        return response()->json([
            'stok_awal' => $snap['stok_awal'],
            'umur' => $snap['umur'],
            'netto_kering_hi' => $snap['masuk_hi'],
            'diolah' => $snap['diolah'],
            'mutasi' => $snap['mutasi'],
            'stok_akhir' => $snap['stok_akhir'],
            'tgl_masuk' => $snap['tgl_masuk'],
            'keterangan' => $snap['keterangan'],
            'k3_masuk' => $k3_masuk,
            'k3_olah' => $k3_olah,
            'po' => $po,
            'pri' => $pri,
            'tgl_uji' => $tgl_uji,
            'asal_bokar' => $asal_bokar, // ⬅️ Gunakan variabel baru
        ]);
    }


    // ===================================================================
    // SISA FUNGSI (STORE, UPDATE, RESET, DLL) - TIDAK BERUBAH
    // ===================================================================

    public function store(Request $request): RedirectResponse
    {
        $cleanNumber = function ($value) {
            if (empty($value)) return 0;
            return str_replace(',', '.', str_replace('.', '', $value));
        };
        $request->merge([
            'stok_awal' => $cleanNumber($request->input('stok_awal')),
            'masuk_hi' => $cleanNumber($request->input('masuk_hi')),
            'diolah' => $cleanNumber($request->input('diolah')),
            'mutasi' => $cleanNumber($request->input('mutasi')),
        ]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasis,uraian',
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_input_harian' => 'required|date',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = $validator->validated();
        $tanggalInput = Carbon::parse($data['tanggal_input_harian']);
        $maturasi = Maturasi::where('uraian', $data['uraian'])->firstOrFail();

        // update tgl_masuk jika sebelumnya 0 dan ada masuk_hi
        $tgl_masuk_stok = $maturasi->tgl_masuk;
        if (($data['stok_awal'] ?? 0) <= 0 && ($data['masuk_hi'] ?? 0) > 0) {
            $tgl_masuk_stok = $maturasi->tgl_masuk; // tetap apa adanya (biasanya null)
        }

        $stok_akhir_baru = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);
        if ($stok_akhir_baru <= 0) {
            $tgl_masuk_stok = null;
        }

        PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $tanggalInput,
            'diolah' => $data['diolah'],
            'mutasi' => $data['mutasi'],
            'masuk_hi' => $data['masuk_hi'],
            'asal_bokar' => $data['asal_bokar'] ?? null, // FIX
            'keterangan' => $data['keterangan'] ?? null, // FIX
        ]);

        // tetap update tabel maturasi sebagai "status terakhir"
        $maturasi->update([
            'stok_awal' => $data['stok_awal'],
            'tgl_masuk' => $tgl_masuk_stok,
            'umur' => $data['umur'],
            'diolah' => $data['diolah'],
            'mutasi' => $data['mutasi'],
            'masuk_hi' => $data['masuk_hi'],
            'stok_akhir' => $stok_akhir_baru,
            'asal_bokar' => $data['asal_bokar'] ?? null, // FIX
            'keterangan' => $data['keterangan'] ?? null, // FIX
            'updated_at' => $tanggalInput
        ]);

        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tanggalInput->format('Y-m-d')
        ])->with('success', 'Data ' . $maturasi->uraian . ' berhasil ditambahkan.');
    }

    public function show(Maturasi $maturasi): JsonResponse { return response()->json($maturasi); }
    public function edit(Maturasi $maturasi): JsonResponse { return response()->json($maturasi); }

    public function update(Request $request, Maturasi $maturasi): RedirectResponse
    {
        $cleanNumber = function ($value) {
            if (empty($value)) return 0;
            return str_replace(',', '.', str_replace('.', '', $value));
        };
        $request->merge([
            'stok_awal' => $cleanNumber($request->input('stok_awal')),
            'masuk_hi' => $cleanNumber($request->input('masuk_hi')),
            'diolah' => $cleanNumber($request->input('diolah')),
            'mutasi' => $cleanNumber($request->input('mutasi')),
        ]);

        $validator = Validator::make($request->all(), [
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'tgl_masuk' => 'nullable|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_input' => 'required|date',
        ]);
        $tanggalInput = Carbon::parse($request->tanggal_input);
        if ($validator->fails()) {
            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalInput->format('Y-m-d')])
                ->withErrors($validator)
                ->withInput()
                ->with(['edit_error' => true, 'edit_id' => $maturasi->id]);
        }
        $data = $validator->validated();

        $stok_akhir_baru = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);
        $tgl_masuk_stok = $data['tgl_masuk'];
        if ($data['stok_awal'] <= 0 && $data['masuk_hi'] > 0) {
            $tgl_masuk_stok = $maturasi->tgl_masuk;
        }
        if ($stok_akhir_baru <= 0) {
            $tgl_masuk_stok = null;
        }

        PengolahanMaturasi::create([
                'maturasi_id' => $maturasi->id,
                'tgl_laporan' => $tanggalInput,
                'diolah' => $data['diolah'],
                'mutasi' => $data['mutasi'],
                'masuk_hi' => $data['masuk_hi'],
                'asal_bokar' => $data['asal_bokar'] ?? null, // FIX
                'keterangan' => $data['keterangan'] ?? null, // FIX
            ]);


        $maturasi->update([
            'stok_awal' => $data['stok_awal'],
            'tgl_masuk' => $tgl_masuk_stok,
            'umur' => $data['umur'],
            'diolah' => $data['diolah'],
            'mutasi' => $data['mutasi'],
            'masuk_hi' => $data['masuk_hi'],
            'stok_akhir' => $stok_akhir_baru,
            'asal_bokar' => $data['asal_bokar'] ?? null, // FIX
            'keterangan' => $data['keterangan'] ?? null, // FIX
            'updated_at' => $tanggalInput
        ]);

        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tanggalInput->format('Y-m-d')
        ])->with('success', 'Data ' . $maturasi->uraian . ' berhasil diperbarui.');
    }

    public function reset(Maturasi $maturasi): RedirectResponse
    {
        $maturasi->riwayatPengolahan()->delete();
        $maturasi->update([
            'stok_awal' => 0,
            'tgl_masuk' => null,
            'umur' => 0,
            'diolah' => 0,
            'mutasi' => 0,
            'masuk_hi' => 0,
            'stok_akhir' => 0,
            'asal_bokar' => null,
            'keterangan' => 'KOSONG',
            'updated_at' => Carbon::now()
        ]);
        return redirect()->route('maturasi.index', ['filter_tanggal' => Carbon::today()->format('Y-m-d')])
            ->with('success', 'Data ' . $maturasi->uraian . ' telah di-reset.');
    }

    public function destroy(Maturasi $maturasi): RedirectResponse
    {
        return redirect()->route('maturasi.index')->with('error', 'Fungsi hapus tidak diizinkan.');
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
}