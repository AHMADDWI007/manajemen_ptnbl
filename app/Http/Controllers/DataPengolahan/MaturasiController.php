<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah; 
use App\Models\HasilUjiLabMaturasi;    
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
    // HELPER 1: HITUNGAN STOK & SNAPSHOT
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
        return (float) ($sums->sum_masuk - $sums->sum_diolah - $sums->sum_mutasi);
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

    protected function hitungSnapshot(Maturasi $maturasi, Carbon $selectedDate): array
    {
        // PK: id_maturasi
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
            'stok_awal'  => $stok_awal,
            'diolah'     => $transaksi['diolah'],
            'mutasi'     => $transaksi['mutasi'],
            'masuk_hi'   => $masuk_hi_today,
            'stok_akhir' => $stok_akhir,
            'tgl_masuk'  => $tgl_masuk,
            'umur'       => $umur,
            'keterangan' => $keterangan,
        ];
    }

    // =========================================================================
    // HELPER 2: LOGIKA "ASAL BOKAR" (CMP DETAILED)
    // =========================================================================
    
    private function getDetailedAsalBokarString(Maturasi $maturasi, float $stokAkhir, Carbon $filterDate)
    {
        if ($stokAkhir <= 0) return '-';

        $defaultAsal = $maturasi->asal_bokar ?? '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = $stokAkhir;

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk = $log->masuk_hi;
                $keluar = $log->diolah + $log->mutasi;
                
                $prevStock = $currentTracingStock - $masuk + $keluar;
                
                if ($prevStock <= 0.01) { 
                    break; 
                }
                $currentTracingStock = $prevStock;
            }

            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function ($item) { return strtoupper(trim($item)); })
                ->unique()
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            if (!empty($jenisList)) {
                if (count($jenisList) > 1) {
                    return 'CMP (' . implode(', ', $jenisList) . ')';
                } else {
                    return $jenisList[0];
                }
            }

            return $defaultAsal;

        } catch (\Exception $e) {
            return $defaultAsal;
        }
    }

    // =========================================================================
    // FUNGSI UTAMA (GET DATA UNTUK VIEW)
    // =========================================================================
    
    private function getMaturasiData($selectedDate)
    {
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        
        $dataTampilan = new Collection();
        $bak_aktif_list = [];

        foreach ($data_maturasi_db as $bak) {
            // 1. Hitung Snapshot
            $snap = $this->hitungSnapshot($bak, $selectedDate);
            $row = clone $bak;
            foreach ($snap as $k => $v) $row->$k = $v;
            $row->updated_at = $selectedDate;

            // 2. Ambil K3 Masuk & Olah
            $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tanggal', '<=', $selectedDate)
                ->orderBy('tanggal', 'desc')->first();
            $row->k3_masuk = $ujiMasuk ? $ujiMasuk->k3 : 0;

            $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tanggal', '<=', $selectedDate)
                ->orderBy('tanggal', 'desc')->first();
            $row->k3_olah = $ujiLab->k3 ?? 0;
            $row->po      = $ujiLab->po ?? 0;
            $row->pri     = $ujiLab->pri ?? 0;
            $row->tgl_uji = $ujiLab->tanggal ?? null;

            // 3. Logika Asal Bokar
            $row->asal_bokar = $this->getDetailedAsalBokarString($bak, $row->stok_akhir, $selectedDate);

            if ($row->stok_akhir <= 0) {
                $row->asal_bokar = '-';
            }

            $dataTampilan->push($row);

            if ($row->stok_akhir > 0) {
                $bak_aktif_list[] = $row->uraian; 
            }
        }

        // Footer Summary
        $footer_data = [
            'total_stok_awal'  => $dataTampilan->sum('stok_awal'),
            'total_diolah'     => $dataTampilan->sum('diolah'),
            'total_mutasi'     => $dataTampilan->sum('mutasi'),
            'total_masuk_hi'   => $dataTampilan->sum('masuk_hi'),
            'total_stok_akhir' => $dataTampilan->sum('stok_akhir'),
            'maturasi_diolah_sd_kemarin'  => PengolahanMaturasi::whereDate('tgl_laporan', '<', $selectedDate)->sum('diolah'),
            'maturasi_diolah_hari_ini'    => $dataTampilan->sum('diolah'),
            'maturasi_diolah_sd_hari_ini' => PengolahanMaturasi::whereDate('tgl_laporan', '<=', $selectedDate)->sum('diolah'),
        ];

        return [
            'data_maturasi'  => $dataTampilan,
            'bak_aktif_list' => $bak_aktif_list,
            'selected_date'  => $selectedDate->format('Y-m-d'),
            'footer_data'    => $footer_data
        ];
    }

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
        $pdf->setPaper('a4', 'landscape'); 

        return $pdf->stream('Laporan-Maturasi-' . $selectedDate->format('d-m-Y') . '.pdf');
    }

    // --- FUNGSI STORE ---
    public function store(Request $request): RedirectResponse
    {
        $clean = fn($v) => $v ? str_replace(',', '.', str_replace('.', '', $v)) : 0;
        $request->merge([
            'diolah' => $clean($request->input('diolah')),
            'mutasi' => $clean($request->input('mutasi')),
        ]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian', // Tabel maturasi
            'tanggal_input_harian' => 'required|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $tglInput = Carbon::parse($data['tanggal_input_harian']);
        $maturasi = Maturasi::where('uraian', $data['uraian'])->firstOrFail();

        PengolahanMaturasi::create([
            'id_maturasi' => $maturasi->id_maturasi,
            'tgl_laporan' => $tglInput,
            'diolah' => $data['diolah'],
            'mutasi' => $data['mutasi'],
            'masuk_hi' => 0,
            'keterangan' => $data['keterangan'] ?? 'Input Manual'
        ]);

        $snap = $this->hitungSnapshot($maturasi, $tglInput);
        
        $maturasi->update([
            'stok_awal' => $snap['stok_awal'],
            'diolah' => $snap['diolah'],
            'mutasi' => $snap['mutasi'],
            'masuk_hi' => $snap['masuk_hi'],
            'stok_akhir' => $snap['stok_akhir'],
            'tgl_masuk' => $snap['tgl_masuk'],
            'umur' => $snap['umur'],
            'updated_at' => $tglInput 
        ]);

        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tglInput->format('Y-m-d')
        ])->with('success', 'Data berhasil disimpan.');
    }
    
    // --- FUNGSI RESET ---
    public function reset($id_maturasi): RedirectResponse
    {
        $maturasi = Maturasi::findOrFail($id_maturasi);
        
        $maturasi->riwayatPengolahan()->delete();
        $maturasi->update([
            'stok_awal' => 0, 'tgl_masuk' => null, 'umur' => 0,
            'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
            'stok_akhir' => 0, 'asal_bokar' => null, 'keterangan' => 'KOSONG',
        ]);
        return redirect()->back()->with('success', 'Data Bak telah di-reset total.');
    }
    
    // --- FUNGSI AJAX EDIT / DETAIL ---
    public function getPreviousData(Request $request): JsonResponse
    {
        $uraian = $request->input('uraian');
        $tgl = $request->input('tanggal_filter') ? Carbon::parse($request->input('tanggal_filter')) : Carbon::today();

        $maturasi = Maturasi::where('uraian', $uraian)->first();
        if (!$maturasi) return response()->json(['error' => 'Bak tidak ditemukan'], 404);

        $snap = $this->hitungSnapshot($maturasi, $tgl);
        
        // Data K3
        $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)
            ->latest('tanggal')->first();

        $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)
            ->latest('tanggal')->first();

        $asalBokarDetailed = $this->getDetailedAsalBokarString($maturasi, $snap['stok_akhir'], $tgl);

        return response()->json([
            'stok_awal' => $snap['stok_awal'],
            'umur'      => $snap['umur'],
            'diolah'    => $snap['diolah'],
            'mutasi'    => $snap['mutasi'],
            'masuk_hi'  => $snap['masuk_hi'],
            'stok_akhir'=> $snap['stok_akhir'],
            'tgl_masuk' => $snap['tgl_masuk'],
            
            'k3_masuk'  => $ujiMasuk->k3 ?? 0,
            'k3_olah'   => $ujiLab->k3 ?? 0,
            'po'        => $ujiLab->po ?? 0,
            'pri'       => $ujiLab->pri ?? 0,
            'tgl_uji'   => $ujiLab->tanggal ?? null,
            
            'asal_bokar'=> $asalBokarDetailed,
            'keterangan'=> $snap['keterangan'],
            'updated_at'=> $maturasi->updated_at,
        ]);
    }

    public function edit(Request $request, $id_maturasi): JsonResponse
    {
        $maturasi = Maturasi::find($id_maturasi);
        if (!$maturasi) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        $selectedDate = $request->has('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        $snap = $this->hitungSnapshot($maturasi, $selectedDate);
        $asalBokarDetailed = $this->getDetailedAsalBokarString($maturasi, $snap['stok_akhir'], $selectedDate);

        return response()->json([
            'id_maturasi'=> $maturasi->id_maturasi,
            'uraian'     => $maturasi->uraian,
            'stok_awal'  => $snap['stok_awal'],
            'umur'       => $snap['umur'],
            'tgl_masuk'  => $snap['tgl_masuk'] ? Carbon::parse($snap['tgl_masuk'])->format('Y-m-d') : null,
            'diolah'     => $snap['diolah'],
            'mutasi'     => $snap['mutasi'],
            'masuk_hi'   => $snap['masuk_hi'],
            'stok_akhir' => $snap['stok_akhir'],
            'asal_bokar' => $asalBokarDetailed,
            'keterangan' => $snap['keterangan'],
            'updated_at' => $selectedDate->format('Y-m-d'), 
        ]);
    }
}