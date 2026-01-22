<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProduksiSir;
use App\Models\BahanProses;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Http\Controllers\DataProduksi\PenjualanSir20Controller; 

class GudangSirApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($dateStr);

            // 1. Ambil Data Hari Ini & Kemarin (Logic sama dengan Web Controller)
            $dataDB = ProduksiSir::whereDate('created_at', $selectedDate)->get()->keyBy('uraian');
            
            $prevDataDB = ProduksiSir::whereDate('created_at', '<', $selectedDate)
                ->orderBy('created_at', 'desc')->get()->unique('uraian')->keyBy('uraian');

            // 2. Susun Data TABEL IV (GUDANG)
            $masterGudang = [
                '4.1' => 'Di Gudang SIR',
                '4.2' => 'Di Areal Press Bale',
                '4.3' => 'Di Gudang TOH 1',
                '4.4' => 'Di Gudang TOH 2',
            ];

            $listGudang = [];
            $totalSaldoAkhir = 0;

            foreach ($masterGudang as $no => $nama) {
                $item = $dataDB[$nama] ?? null;
                $prevItem = $prevDataDB[$nama] ?? null;

                // Logic Estafet Saldo
                $saldo_awal = $item ? $item->saldo_awal : ($prevItem ? $prevItem->saldo_akhir : 0);
                $prod_bln_lalu = $item ? $item->prod_bln_lalu : ($prevItem ? $prevItem->prod_sd_hi : 0);
                
                $masuk = $item->masuk ?? 0;
                $pengiriman = $item->pengiriman ?? 0;
                $total = $saldo_awal + $masuk;
                $prod_sd_hi = $prod_bln_lalu + $masuk;
                $saldo_akhir = $total - $pengiriman;

                // Jika nama "Di Gudang SIR", simpan saldo akhirnya untuk referensi info
                if ($nama == 'Di Gudang SIR') {
                    $saldoGudangUtama = $saldo_akhir;
                }

                $listGudang[] = [
                    'id_produksi_sir' => $item->id_produksi_sir ?? null,
                    'no' => $no,
                    'uraian' => $nama,
                    'saldo_awal' => (float)$saldo_awal,
                    'masuk' => (float)$masuk,
                    'total' => (float)$total,
                    'prod_bln_lalu' => (float)$prod_bln_lalu,
                    'prod_sd_hi' => (float)$prod_sd_hi,
                    'pengiriman' => (float)$pengiriman,
                    'saldo_akhir' => (float)$saldo_akhir,
                    'keterangan' => $item->keterangan ?? ''
                ];
            }

            // 3. Susun Data TABEL VI (MUTU)
            $masterMutu = [
                '6.1' => 'Mutu Prima (siap jual)',
                '6.2' => 'PO / PRI Low',
                '6.3' => 'WhiteSpot (WS)',
                '6.4' => 'Kontaminasi',
                '6.5' => 'Repacking On Hold',
            ];

            $listMutu = [];
            foreach ($masterMutu as $no => $nama) {
                $item = $dataDB[$nama] ?? null;
                $listMutu[] = [
                    'id_produksi_sir' => $item->id_produksi_sir ?? null,
                    'no' => $no,
                    'uraian' => $nama,
                    'kg' => (float)($item->kg ?? 0),
                    'pallet' => (int)($item->pallet ?? 0),
                    'keterangan' => $item->keterangan ?? ''
                ];
            }

            // 4. Info Tambahan (WIP Hari Ini untuk saran input)
            $wip = BahanProses::whereDate('tanggal', $selectedDate)
                ->where('uraian', 'Di Dalam Dryer/Press Bale')->first();
            $wipKg = $wip ? $wip->produksi_sir20 : 0;

            return response()->json([
                'success' => true,
                'dataGudang' => $listGudang,
                'dataMutu' => $listMutu,
                'info' => [
                    'wipHariIni' => (float)$wipKg,
                    'saldoGudangSir' => (float)($saldoGudangUtama ?? 0)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required',
        ]);

        $tgl = Carbon::parse($request->tanggal);
        DB::beginTransaction();

        try {
            // Logic ini SAMA PERSIS dengan Web Controller
            // Cari data sebelumnya untuk Saldo Awal
            $prevData = ProduksiSir::where('uraian', $request->uraian)
                ->whereDate('created_at', '<', $tgl)
                ->orderBy('created_at', 'desc')->first();

            $saldo_awal = $prevData ? $prevData->saldo_akhir : 0;
            $prod_bln_lalu = $prevData ? $prevData->prod_sd_hi : 0;

            // Parameter dari Android
            $masuk = $request->input('masuk', 0);
            $pengiriman = $request->input('pengiriman', 0);
            $kg = $request->input('kg', 0);
            $pallet = $request->input('pallet', 0);

            // Hitung
            $total = $saldo_awal + $masuk;
            $prod_sd_hi = $prod_bln_lalu + $masuk;
            $saldo_akhir = $total - $pengiriman;

            // Simpan
            ProduksiSir::updateOrCreate(
                [
                    'uraian' => $request->uraian,
                    'created_at' => $tgl->format('Y-m-d H:i:s') // Kunci Harian
                ],
                [
                    'saldo_awal' => $saldo_awal,
                    'masuk' => $masuk,
                    'total' => $total,
                    'prod_bln_lalu' => $prod_bln_lalu,
                    'prod_sd_hi' => $prod_sd_hi,
                    'pengiriman' => $pengiriman,
                    'saldo_akhir' => $saldo_akhir,
                    
                    // Field Mutu
                    'kg' => $kg,
                    'pallet' => $pallet,
                    
                    'keterangan' => $request->keterangan,
                    'updated_at' => Carbon::now()
                ]
            );

            // Trigger Sinkronisasi Penjualan (Jika Gudang SIR)
            if ($request->uraian == 'Di Gudang SIR') {
                $penjualan = new PenjualanSir20Controller();
                $penjualan->recalculateAndSave($tgl->format('Y-m-d'), 'SIR20 PTNBL', $pengiriman, 'Auto Sync Android');
                $penjualan->recalculateAndSave($tgl->format('Y-m-d'), 'SIR20 PTPN4', 0, 'Auto Sync Android');
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data tersimpan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}