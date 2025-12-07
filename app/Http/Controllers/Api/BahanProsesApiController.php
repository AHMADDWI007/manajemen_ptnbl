<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// ✅ Pastikan Model di-import dengan benar
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection; // Gunakan Illuminate\Support\Collection

class BahanProsesApiController extends Controller
{
    private $masterUraian = [
        'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
        'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
        'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
    ];

    public function index(Request $request)
    {
        try {
            $selectedDate = $request->input('date') 
                ? Carbon::parse($request->input('date')) 
                : Carbon::today();
            
            $yesterday = $selectedDate->copy()->subDay();

            // Ambil Data
            $dataToday = BahanProses::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');
            $dataYesterday = BahanProses::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');

            // Ambil dari Maturasi
            $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $selectedDate)
                ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
            $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;

            $finalData = new Collection();
            $prevWipKeluar = 0;

            foreach ($this->masterUraian as $uraian) {
                $row = $dataToday->get($uraian);

                if ($row) {
                    $prevWipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $row->wip_keluar;
                    $finalData->push($row);
                } else {
                    // Virtual Row
                    $virtualRow = new BahanProses();
                    $virtualRow->id = null;
                    $virtualRow->tanggal = $selectedDate->format('Y-m-d');
                    $virtualRow->uraian = $uraian;
                    
                    $saldoAwal = isset($dataYesterday[$uraian]) ? $dataYesterday[$uraian]->saldo_akhir : 0;
                    $virtualRow->saldo_awal = $saldoAwal;

                    if ($uraian == 'Lantai Umpan Kering') {
                        $wipMasuk = $inputDariMaturasi;
                    } else {
                        $wipMasuk = $prevWipKeluar;
                    }
                    $virtualRow->wip_masuk = $wipMasuk;

                    if ($uraian == 'Di Dalam Dryer/Press Bale') {
                        $wipKeluar = 0;
                        $produksiSIR20 = $wipMasuk;
                        $prevWipKeluar = 0;
                    } else {
                        $wipKeluar = $wipMasuk;
                        $produksiSIR20 = 0;
                        $prevWipKeluar = $wipMasuk;
                    }
                    
                    $virtualRow->wip_keluar = $wipKeluar;
                    $virtualRow->produksi_sir20 = $produksiSIR20;
                    $virtualRow->rekfif = 0;
                    $virtualRow->saldo_akhir = $saldoAwal + $wipMasuk - $wipKeluar - $produksiSIR20;
                    
                    $finalData->push($virtualRow);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $finalData,
                'selected_date' => $selectedDate->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            Log::error("Error BahanProses: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        // ... (Kode store Anda yang sudah benar sebelumnya) ...
        // Pastikan ada di file ini juga
         try {
            $inputDate = Carbon::parse($request->tanggal);
            $uraian = $request->uraian;
            
            $saldoAwal = $request->saldo_awal;
            $wipMasuk = $request->wip_masuk;
            $wipKeluar = $request->wip_keluar;
            $produksi = $request->produksi_sir20 ?? 0;
            $rektif = $request->rekfif ?? 0;
            $keterangan = $request->keterangan;

            $saldoAkhir = $saldoAwal + $wipMasuk - $wipKeluar - $produksi + $rektif;

            BahanProses::updateOrCreate(
                ['tanggal' => $inputDate->format('Y-m-d'), 'uraian' => $uraian],
                [
                    'saldo_awal' => $saldoAwal,
                    'wip_masuk' => $wipMasuk,
                    'wip_keluar' => $wipKeluar,
                    'produksi_sir20' => $produksi,
                    'rekfif' => $rektif,
                    'saldo_akhir' => $saldoAkhir,
                    'keterangan' => $keterangan
                ]
            );
            
            // Trigger update baris bawahnya (opsional, tapi bagus)
             $urutanSaatIni = array_search($uraian, $this->masterUraian);
            if ($urutanSaatIni !== false && $urutanSaatIni < count($this->masterUraian) - 1) {
                $nextUraian = $this->masterUraian[$urutanSaatIni + 1];
                $nextData = BahanProses::whereDate('tanggal', $inputDate)->where('uraian', $nextUraian)->first();
                if ($nextData) {
                    $nextData->wip_masuk = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $wipKeluar;
                    $nextData->saldo_akhir = $nextData->saldo_awal + $nextData->wip_masuk - $nextData->wip_keluar - $nextData->produksi_sir20 + $nextData->rekfif;
                    $nextData->save();
                }
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}