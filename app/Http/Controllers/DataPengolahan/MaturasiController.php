<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiBokarDiolah;
use App\Models\HasilUjiMaturasi; // Pastikan Model ini di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MaturasiController extends Controller
{
    // =========================================================================
    // HELPER FUNCTIONS
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

    // =========================================================================
    // FUNGSI UTAMA (GET DATA) - UPDATE LOGIKA K3 DI SINI
    // =========================================================================
    private function getMaturasiData($selectedDate)
    {
        $data_maturasi_db = Maturasi::with(['hasilUjiMaturasi', 'hasilUjiBokarDiolah'])->orderBy('id')->get();
        $dataTampilan = new Collection();
        $bak_aktif_list = [];

        foreach ($data_maturasi_db as $bak) {
            $snap = $this->hitungSnapshot($bak, $selectedDate);
            $row = clone $bak;
            foreach ($snap as $k => $v) $row->$k = $v;
            $row->updated_at = $selectedDate;

            // ================================================================
            // PERBAIKAN: AMBIL K3 DARI HASIL UJI MATURASI (BUKAN BOKAR)
            // ================================================================
            
            // Cari data uji maturasi terakhir untuk bak ini, pada atau sebelum tanggal filter
            $ujiMaturasi = HasilUjiMaturasi::where('no_kamar', $bak->uraian)
                            ->whereDate('tanggal', '<=', $selectedDate)
                            ->orderBy('tanggal', 'desc') // Ambil yang paling baru
                            ->first();

            if ($ujiMaturasi) {
                // Jika ada data uji, ambil nilainya
                $row->k3      = $ujiMaturasi->k3 ?? 0;
                $row->po      = $ujiMaturasi->po ?? 0;
                $row->pri     = $ujiMaturasi->pri ?? 0;
                $row->tgl_uji = $ujiMaturasi->tanggal;
            } else {
                // Jika belum ada uji, kosongkan
                $row->k3      = 0;
                $row->po      = 0;
                $row->pri     = 0;
                $row->tgl_uji = null;
            }
            
            // Kolom K3 Masuk (Opsional, tetap dari Bokar jika perlu referensi)
            // Tapi untuk tampilan tabel utama 'K3', kita pakai $row->k3 (dari Uji Maturasi)
            $row->k3_masuk = 0; 

            if ($row->stok_akhir > 0 && $row->tgl_masuk && $selectedDate->gte(Carbon::parse($row->tgl_masuk)->startOfDay())) {
                // tampilkan
            } else {
                $row->asal_bokar = null; 
            }

            $dataTampilan->push($row);

            if ($row->stok_akhir > 0) $bak_aktif_list[] = $row->uraian;
        }

        $total_stok_awal  = $dataTampilan->sum('stok_awal');
        $total_diolah     = $dataTampilan->sum('diolah');
        $total_mutasi     = $dataTampilan->sum('mutasi');
        $total_masuk_hi   = $dataTampilan->sum('masuk_hi');
        $total_stok_akhir = $dataTampilan->sum('stok_akhir');

        $maturasi_diolah_sd_kemarin = PengolahanMaturasi::whereDate('tgl_laporan', '<', $selectedDate)->sum('diolah');
        $maturasi_diolah_sd_hari_ini = $maturasi_diolah_sd_kemarin + $total_diolah;

        return [
            'data_maturasi'  => $dataTampilan,
            'bak_aktif_list' => $bak_aktif_list,
            'selected_date'  => $selectedDate->format('Y-m-d'),
            'formatted_date' => $selectedDate->isoFormat('D MMMM YYYY'),
            'footer_data' => [
                'total_stok_awal'  => $total_stok_awal,
                'total_diolah'     => $total_diolah,
                'total_mutasi'     => $total_mutasi,
                'total_masuk_hi'   => $total_masuk_hi,
                'total_stok_akhir' => $total_stok_akhir,
                'maturasi_diolah_sd_kemarin'  => $maturasi_diolah_sd_kemarin,
                'maturasi_diolah_hari_ini'    => $total_diolah,
                'maturasi_diolah_sd_hari_ini' => $maturasi_diolah_sd_hari_ini,
            ]
        ];
    }

    // =========================================================================
    // FUNGSI UTAMA
    // =========================================================================
    
    public function index(Request $request): View
    {
        $selectedDate = $request->has('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        $data = $this->getMaturasiData($selectedDate);

        return view('DataPengolahan.data-maturasi', $data);
    }

    public function cetakPdf(Request $request)
    {
        $selectedDate = $request->has('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        $data = $this->getMaturasiData($selectedDate);

        $pdf = Pdf::loadView('Cetak.cetak-maturasi', $data);
        $customPaper = array(0, 0, 609.448, 935.433);
        
        $pdf->setPaper($customPaper, 'portrait'); // Set Portrait

        return $pdf->stream('Laporan-Maturasi-' . $selectedDate->format('d-m-Y') . '.pdf');
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

        // 1. Hitung Snapshot Stok pada Tanggal Filter
        $snap = $this->hitungSnapshot($maturasi, $tanggalFilter);

        // 2. Ambil Nomor Bak (Parsing dari string "Di Bak Maturasi-X")
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $maturasi->uraian, $matches)) {
            $nomorBak = "Bak Maturasi " . $matches[1]; 
        }

        // 3. Ambil K3 Olah (Dari Uji Maturasi / Laboratorium)
        $ujiMaturasi = HasilUjiMaturasi::where('no_kamar', $uraian)
                        ->whereDate('tanggal', '<=', $tanggalFilter)
                        ->orderBy('tanggal', 'desc')
                        ->first();

        // 4. [FIXED] Ambil K3 Masuk (Dari Uji Bokar Diolah)
        $k3Masuk = 0;
        if (!empty($snap['tgl_masuk']) && $nomorBak) {
            $dataBokar = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                            ->whereDate('tanggal', $snap['tgl_masuk'])
                            ->first();
            if ($dataBokar) {
                $k3Masuk = $dataBokar->k3;
            }
        }

        // 5. Trace Back Asal Bokar
        $asal_bokar_output = $maturasi->asal_bokar; 

        if ($snap['stok_akhir'] > 0 && $nomorBak) {
            $realBatchStartDate = $tanggalFilter->copy();
            $logs = PengolahanMaturasi::where('maturasi_id', $maturasi->id)
                ->whereDate('tgl_laporan', '<=', $tanggalFilter)
                ->orderBy('tgl_laporan', 'desc') 
                ->get();

            $currentTracingStock = $snap['stok_akhir'];

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                $masuk = $log->masuk_hi;
                $keluar = $log->diolah + $log->mutasi;
                $prevStock = $currentTracingStock - $masuk + $keluar;
                if ($prevStock <= 0.01) { break; }
                $currentTracingStock = $prevStock;
            }

            // Ambil jenis dari HasilUjiBokarDiolah juga
            $jenisList = HasilUjiBokarDiolah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $tanggalFilter)
                ->pluck('jenis')
                ->map(function ($item) { return strtoupper(trim($item)); })
                ->unique()->filter()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                if (count($jenisList) > 1) {
                    $asal_bokar_output = 'CMP (' . implode(', ', $jenisList) . ')';
                } else {
                    $asal_bokar_output = $jenisList[0];
                }
            }
        } elseif ($snap['stok_akhir'] <= 0) {
            $asal_bokar_output = '-';
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
            
            // --- OUTPUT K3 ---
            'k3_masuk' => $k3Masuk,             // Data dari Uji Bokar Diolah
            'k3_olah'  => $ujiMaturasi->k3 ?? 0, // Data dari Uji Maturasi
            'po'       => $ujiMaturasi->po ?? 0,
            'pri'      => $ujiMaturasi->pri ?? 0,
            'tgl_uji'  => $ujiMaturasi->tanggal ?? null,
            // -----------------
            
            'asal_bokar' => $asal_bokar_output,
        ]);
    }

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

        $tgl_masuk_stok = $maturasi->tgl_masuk;
        if (($data['stok_awal'] ?? 0) <= 0 && ($data['masuk_hi'] ?? 0) > 0) {
            $tgl_masuk_stok = $maturasi->tgl_masuk;
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
}