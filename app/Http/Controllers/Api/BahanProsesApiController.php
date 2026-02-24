<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use App\Models\ProduksiSir20;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class BahanProsesApiController extends Controller
{
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
            
            // 🔥 WAJIB: Jalankan Recalculate agar data HP selalu fresh
            $this->recalculateAndSaveFlow($date); 
            
            // Ambil data
            $rawData = BahanProses::whereDate('tanggal', $date)->get();
            
            // 🔥 URUTKAN SESUAI MASTER URAIAN AGAR RAPI DI HP
            $sortedData = $rawData->sortBy(function($item) {
                return $this->masterUraian[$item->uraian] ?? 99;
            })->values()->all();
            
            return response()->json(['success' => true, 'data' => $sortedData]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) 
    { return $this->processData($request); }
    public function update(Request $request, $id) { return $this->processData($request); }

    private function processData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'uraian'      => 'required|string',
            'wip_keluar'  => 'required|numeric|min:0',
            'rekfif'      => 'nullable|numeric',
            'keterangan'  => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);

        try {
            BahanProses::updateOrCreate(
                ['tanggal' => $request->tanggal, 'uraian' => $request->uraian],
                [
                    'wip_keluar' => $request->wip_keluar,
                    'rekfif'     => $request->rekfif ?? 0,
                    'keterangan' => $request->keterangan
                ]
            );

            $this->recalculateAndSaveFlow(Carbon::parse($request->tanggal));
            return response()->json(['success' => true, 'message' => 'Data berhasil disimpan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔥 UBAH JADI PUBLIC
    public function recalculateAndSaveFlow($date)
    {
        $date = ($date instanceof Carbon) ? $date : Carbon::parse($date);
        
        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $date)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
        
        $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $date)->sum('kg_yang_dipress');
        $prevWipKeluar = 0;

        foreach (array_keys($this->masterUraian) as $index => $uraian) {
            $existingRow = BahanProses::whereDate('tanggal', $date)->where('uraian', $uraian)->first();
            $userWipKeluar = $existingRow ? $existingRow->wip_keluar : 0;
            $rektifUser    = $existingRow ? $existingRow->rekfif : 0;
            $ketUser       = $existingRow ? $existingRow->keterangan : null;

            $lastData = BahanProses::where('uraian', $uraian)
                ->whereDate('tanggal', '<', $date)
                ->orderBy('tanggal', 'desc')->first();
            
            $saldoAwal = $lastData ? $lastData->saldo_akhir : ($existingRow ? $existingRow->saldo_awal : 0);

            $wipMasuk = ($index === 0) ? $inputDariMaturasi : $prevWipKeluar;
            
            $produksi = 0;
            $finalWipKeluar = $userWipKeluar;

            // 🔥 SINKRON WEB: Logika Mutlak Dryer
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $produksi = $realProduction;
                $finalWipKeluar = $produksi; 
            }

            $saldoAkhir = ($saldoAwal + $wipMasuk + $rektifUser) - $finalWipKeluar;

            BahanProses::updateOrCreate(
                ['tanggal' => $date->format('Y-m-d'), 'uraian' => $uraian],
                [
                    'saldo_awal'     => $saldoAwal,
                    'wip_masuk'      => $wipMasuk,
                    'wip_keluar'     => $finalWipKeluar,
                    'produksi_sir20' => $produksi,
                    'rekfif'         => $rektifUser,
                    'saldo_akhir'    => $saldoAkhir,
                    'keterangan'     => $ketUser
                ]
            );

            $prevWipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $finalWipKeluar;
        }
    }
}