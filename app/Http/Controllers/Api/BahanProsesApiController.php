<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BahanProses;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class BahanProsesApiController extends Controller
{
    // Urutan Proses Pabrik yang Baku
    private $masterUraian = [
        'Lantai Umpan Kering' => 1, 
        'Di Blending Tank 4' => 2, 
        'Di Lump Breaker-2 (Di Blending Tank-4)' => 3,
        'Di Pre Breaker-2 (Di Blending Tank-5)' => 4, 
        'Di Hammer Mill-2 (Di Blending Tank-6)' => 5,
        'Di Blending Tank-7' => 6, 
        'Di Trolley' => 7, 
        'Di Dalam Dryer/Press Bale' => 8, 
        'Di Reproses Ex WS.' => 9
    ];

    public function index(Request $request)
    {
        try {
            $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
            
            // 🔥 SINKRON WEB: Hanya ambil data yang benar-benar ada di DB
            $rawData = BahanProses::whereDate('tanggal', $date)->get();
            
            // Jika data kosong (misal hari libur), buat data virtual agar HP tidak kosong
            if ($rawData->isEmpty()) {
                $sortedData = $this->generateVirtualData($date);
            } else {
                $sortedData = $rawData->sortBy(function($item) {
                    return $this->masterUraian[$item->uraian] ?? 99;
                })->values()->all();
            }
            
            return response()->json(['success' => true, 'data' => $sortedData]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) 
    {
        return $this->processData($request);
    }

    public function update(Request $request, $id) 
    {
        return $this->processData($request);
    }

    /**
     * FUNGSI PROSES DATA DARI MOBILE
     */
    private function processData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'uraian'      => 'required|string',
            'wip_keluar'  => 'required|numeric',
            'rekfif'      => 'nullable|numeric',
            'keterangan'  => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // 1. Simpan data manual dari HP
            BahanProses::updateOrCreate(
                ['tanggal' => $request->tanggal, 'uraian' => $request->uraian],
                [
                    'wip_keluar' => round($request->wip_keluar),
                    'rekfif'     => round($request->rekfif ?? 0),
                    'keterangan' => $request->keterangan
                ]
            );

            // 🔥 2. Pemicu Sinkronisasi Berantai (Sama seperti Web)
            $this->syncChainData($request->tanggal);

            return response()->json(['success' => true, 'message' => 'WIP berhasil diperbarui & saldo disinkronkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔥 LOGIKA SAKTI: SINKRONISASI BERANTAI & CLEANUP SAMPAH
     */
    public function syncChainData($startDate)
    {
        $tglAwalStr = Carbon::parse($startDate)->toDateString();

        // 1. Ambil daftar tanggal yang memang ada datanya di DB
        $existingDates = BahanProses::whereDate('tanggal', '>=', $tglAwalStr)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal')
            ->toArray();

        if (!in_array($tglAwalStr, $existingDates)) {
            array_unshift($existingDates, $tglAwalStr);
        }

        foreach ($existingDates as $tglStr) {
            // Ambil data pendukung aktivitas pabrik
            $maturasiToday = \App\Models\PengolahanMaturasi::whereDate('tgl_laporan', $tglStr)
                ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
            $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
            
            $realProduction = \App\Models\ProduksiSir20::whereDate('tanggal_produksi', $tglStr)->sum('kg_yang_dipress');
            
            // 🔥 PENENTU UTAMA: Jika Produksi ditiadakan, maka saklar aktivitas mati
            $adaAktivitasPabrik = ($inputDariMaturasi > 0 || $realProduction > 0);

            $prevWipKeluar = 0;
            $uraianList = [
                'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
                'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
                'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
            ];

            foreach ($uraianList as $index => $uraian) {
                $row = BahanProses::whereDate('tanggal', $tglStr)->where('uraian', $uraian)->first();
                
                $lastData = BahanProses::where('uraian', $uraian)
                    ->whereDate('tanggal', '<', $tglStr)
                    ->orderBy('tanggal', 'desc')->first();
                
                $saldoAwal = $lastData ? $lastData->saldo_akhir : ($row ? $row->saldo_awal : 0);
                
                // 🔥 LOGIKA PAKSA NOL:
                // Jika Produksi dihapus ($adaAktivitasPabrik = false), 
                // maka Masuk, Keluar, dan Rektif WAJIB Nol tanpa kecuali.
                $wipMasuk      = ($adaAktivitasPabrik) ? (($index === 0) ? $inputDariMaturasi : $prevWipKeluar) : 0;
                $userWipKeluar = ($adaAktivitasPabrik) ? ($row ? $row->wip_keluar : 0) : 0;
                $rektif        = ($adaAktivitasPabrik) ? ($row ? $row->rekfif : 0) : 0;

                if ($uraian == 'Di Dalam Dryer/Press Bale') {
                    $finalWipKeluar = ($realProduction > 0) ? $realProduction : $userWipKeluar;
                    $produksi = $realProduction;
                } else {
                    $finalWipKeluar = $userWipKeluar;
                    $produksi = 0;
                }

                $saldoAkhir = ($saldoAwal + $wipMasuk + $rektif) - $finalWipKeluar;

                // Update database: Angka jadi 0, tapi baris tidak dihapus agar siap di-update nanti
                BahanProses::updateOrCreate(
                    ['tanggal' => $tglStr, 'uraian' => $uraian],
                    [
                        'saldo_awal'     => round($saldoAwal),
                        'wip_masuk'      => round($wipMasuk),
                        'wip_keluar'     => round($finalWipKeluar),
                        'produksi_sir20' => round($produksi),
                        'rekfif'         => round($rektif),
                        'saldo_akhir'    => round($saldoAkhir),
                    ]
                );
                
                $prevWipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $finalWipKeluar;
            }
        }
    }

    /**
     * GENERATE DATA TAMPILAN JIKA DB KOSONG (HARI LIBUR)
     */
    private function generateVirtualData($date) {
        $list = [];
        // Gunakan urutan yang sudah didefinisikan
        $uraianNames = array_flip($this->masterUraian);
        ksort($uraianNames);

        foreach($uraianNames as $order => $u) {
            $last = BahanProses::where('uraian', $u)
                ->whereDate('tanggal', '<', $date)
                ->orderBy('tanggal', 'desc')->first();
                
            $list[] = [
                'uraian' => $u,
                'tanggal' => $date->format('Y-m-d'),
                'saldo_awal' => $last ? (float)$last->saldo_akhir : 0,
                'wip_masuk' => 0,
                'wip_keluar' => 0,
                'produksi_sir20' => 0,
                'rekfif' => 0,
                'saldo_akhir' => $last ? (float)$last->saldo_akhir : 0,
                'keterangan' => null
            ];
        }
        return $list;
    }
}