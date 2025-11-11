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

    /**
     * Fungsi bantu agar semua tampilan (index + detail) konsisten.
     * Implementasi aturan:
     * - Jika stok_awal == 0 dan ada masuk_hi hari itu: hari pertama -> tampilkan diolah = 0, tgl_masuk = null, umur=0, keterangan = selectedDate
     * - Hari berikutnya: show tgl_masuk = last_before dan umur = selisih hari
     * - Jika stok habis hari ini (stok_awal>0 dan stok_akhir<=0): tampilkan tgl_masuk last_before dan umur
     */
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
        // (untuk tampilan detail/tabel). Diolah sebenarnya bisa dicatat di DB, tetapi UI menampilkan 0.
        $diolah_today = $diolah_today_raw;
        if ($stok_awal == 0 && $masuk_hi_today > 0) {
            // hari pertama: override tampilan diolah menjadi 0
            $diolah_today = 0;
            // juga mengabaikan mutasi pada hari pertama untuk tampilan (jika ada)
            $mutasi_today = 0;
        }

        $stok_akhir = $stok_awal - $diolah_today - $mutasi_today + $masuk_hi_today;

        // last masuk hi strictly before selectedDate
        $last_before = $this->getLastMasukHiDateBefore($maturasi->id, $selectedDate, $nomorBak);

        $tgl_masuk = null;
        $umur = 0;

        // Jika stok akhir > 0:
        // - jika hari pertama masuk (stok_awal == 0 && masuk_hi_today > 0) => tgl_masuk tetap null, umur 0
        // - else gunakan last_before (atau fallback ke maturasi->tgl_masuk jika tersedia)
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
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
            } else {
                // hari pertama -> tgl_masuk null, umur 0 (sengaja)
                $tgl_masuk = null;
                $umur = 0;
            }
        } else {
            // stok_akhir <= 0 : kalau sebelumnya ada stok_awal > 0, show tgl_masuk terakhir dan umur
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
                        } catch (\Exception $e) {
                            // ignore
                        }
                    }
                }
            } else {
                // stok_awal == 0 && stok_akhir <= 0 => nothing to show
                $tgl_masuk = null;
                $umur = 0;
            }
        }

        // Keterangan:
        // - jika stok_akhir <= 0 -> '-'
        // - else jika ada masuk hari ini -> tampilkan tanggal selectedDate (karena ada masuk)
        // - else fallback ke last_before or maturasi->tgl_masuk
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

   public function index(Request $request): View
{
   $selectedDate = $request->has('filter_tanggal')
    ? Carbon::parse($request->input('filter_tanggal'))
    : Carbon::today();


    // pastikan relasi hasilUjiMaturasi ikut di-load
  $data_maturasi_db = Maturasi::with(['hasilUjiMaturasi', 'hasilUjiBokarDiolah'])->orderBy('id')->get();
    $dataTampilan = new Collection();
    $bak_aktif_list = [];

    foreach ($data_maturasi_db as $bak) {
    $snap = $this->hitungSnapshot($bak, $selectedDate);
    $row = clone $bak;
    foreach ($snap as $k => $v) $row->$k = $v;
    $row->updated_at = $selectedDate;

    
        // 1. Dapatkan $nomorBak yang cocok (string "Bak Maturasi 1")
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $bak->uraian, $matches)) {
        $nomorBak = "Bak Maturasi " . $matches[1];
        }

        // 2. Query manual menggunakan $nomorBak, BUKAN pakai relasi
        $ujiK3 = null;
        if ($nomorBak) { // Hanya query jika $nomorBak ditemukan
        $ujiK3 = \App\Models\HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
        ->whereDate('tanggal', '<=', $selectedDate)
         ->latest('tanggal')
        ->first();
         }

        // 3. Logika if-else Anda sekarang akan berfungsi
            
            $row->k3_masuk = $ujiK3->k3 ?? 0;
            
        // ==========================================================

        // ==========================================================



    // Ambil nilai dari hasil uji maturasi jika ada
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

// ==============================================================
        // ✅ Sembunyikan asal_bokar jika stok habis atau belum masuk bak
        // ============================================================== 
        if ($row->stok_akhir > 0 && $selectedDate->gte(Carbon::parse($row->tgl_masuk)->startOfDay())) {
            // asal_bokar tetap tampil
        } else {
            $row->asal_bokar = null; // atau '-' di view
        }

        // push ke tampilan
        $dataTampilan->push($row);

        if ($row->stok_akhir > 0) $bak_aktif_list[] = $row->uraian;
    }

    return view('Pengolahan.data_maturasi', [
        'data_maturasi' => $dataTampilan,
        'bak_aktif_list' => $bak_aktif_list,
        'selected_date' => $selectedDate->format('Y-m-d')
    ]);
}

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

// k3_masuk
$nomorBak = null;
if (preg_match('/Di Bak Maturasi-(\d+)/', $maturasi->uraian, $matches)) {
    $nomorBak = "Bak Maturasi " . $matches[1];
}
$ujiK3 = null;
if ($nomorBak) {
    $ujiK3 = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
        ->whereDate('tanggal', '<=', $tanggalFilter)
        ->latest('tanggal')
        ->first();
}
$k3_masuk = $ujiK3->k3 ?? 0;

// k3_olah, po, pri, tgl_uji
$ujiTerakhir = $maturasi->hasilUjiMaturasi()
    ->whereDate('tanggal', '<=', $tanggalFilter)
    ->latest('tanggal')
    ->first();
$k3_olah = $ujiTerakhir->k3 ?? 0;
$po      = $ujiTerakhir->po ?? 0;
$pri     = $ujiTerakhir->pri ?? 0;
$tgl_uji = $ujiTerakhir->tanggal ?? null;

// asal_bokar
    if ($snap['stok_akhir'] > 0 && $snap['tgl_masuk'] && $tanggalFilter->gte(Carbon::parse($snap['tgl_masuk'])->startOfDay())) {
        $asal_bokar = $maturasi->asal_bokar;
    } else {
        $asal_bokar = null;
    }

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
    'asal_bokar' => $asal_bokar,
]);
    }

    // --- store, update, reset, dll tetap seperti sebelumnya (salin dari implementasimu) ---

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
            // jangan langsung set tgl_masuk ke tanggal yang sama; biarkan null pada hari input
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
            'keterangan' => $data['keterangan'],
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
            'asal_bokar' => $data['asal_bokar'],
            'keterangan' => $data['keterangan'],
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
            // jangan set tgl_masuk otomatis ke tanggal input pada update juga
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
            'keterangan' => $data['keterangan'],
        ]);

        $maturasi->update([
            'stok_awal' => $data['stok_awal'],
            'tgl_masuk' => $tgl_masuk_stok,
            'umur' => $data['umur'],
            'diolah' => $data['diolah'],
            'mutasi' => $data['mutasi'],
            'masuk_hi' => $data['masuk_hi'],
            'stok_akhir' => $stok_akhir_baru,
            'asal_bokar' => $data['asal_bokar'],
            'keterangan' => $data['keterangan'],
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

    /**
     * Cek apakah ada log apapun sampai termasuk $date (pengolahan_maturasi atau hasil uji)
     * Mengembalikan true jika ada event (masuk_hi/diolah/mutasi atau netto_kering) pada atau sebelum tanggal.
     */
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