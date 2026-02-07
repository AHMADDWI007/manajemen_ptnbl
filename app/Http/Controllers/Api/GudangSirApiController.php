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
            'uraian'  => 'required',
        ]);

        $tgl = Carbon::parse($request->tanggal)->startOfDay();
        DB::beginTransaction();

        try {
            // ==========================================================
            // 1. UPDATE TABEL IV (GUDANG / LOKASI)
            // ==========================================================
            // Logic Saldo Awal (Estafet dari hari sebelumnya)
            $prevGudang = ProduksiSir::where('uraian', $request->uraian)
                ->where('created_at', '<', $tgl)
                ->orderBy('created_at', 'desc')->first();

            $saldoAwal = $prevGudang ? $prevGudang->saldo_akhir : 0;
            $prodLalu  = $prevGudang ? $prevGudang->prod_sd_hi : 0;

            $masuk = $request->input('masuk', 0);
            $pengiriman = $request->input('pengiriman', 0);
            
            // Hitung
            $total = $saldoAwal + $masuk;
            $prodSdHi = $prodLalu + $masuk;
            $saldoAkhir = $total - $pengiriman;

            ProduksiSir::updateOrCreate(
                ['uraian' => $request->uraian, 'created_at' => $tgl],
                [
                    'saldo_awal'    => $saldoAwal,
                    'masuk'         => $masuk,
                    'total'         => $total,
                    'prod_bln_lalu' => $prodLalu,
                    'prod_sd_hi'    => $prodSdHi,
                    'pengiriman'    => $pengiriman,
                    'saldo_akhir'   => $saldoAkhir,
                    'keterangan'    => $request->keterangan,
                    // Simpan pallet total gudang jika ada
                    'pallet'        => $request->input('pallet_gudang', 0) 
                ]
            );

            // ==========================================================
            // 2. UPDATE TABEL VI (RINCIAN MUTU - 5 ITEM)
            // ==========================================================
            // Hanya update mutu jika inputnya dari "Di Gudang SIR" (Pusat Produksi)
            // Atau jika Anda ingin update mutu dari gudang manapun, hapus if ini.
            if ($request->uraian == 'Di Gudang SIR') {
                
                $mutuItems = [
                    'Mutu Prima (siap jual)' => ['kg' => 'mutu_prima', 'pal' => 'pal_prima'],
                    'PO / PRI Low'           => ['kg' => 'po_pri',     'pal' => 'pal_po'],
                    'WhiteSpot (WS)'         => ['kg' => 'ws',         'pal' => 'pal_ws'],
                    'Kontaminasi'            => ['kg' => 'kontaminasi','pal' => 'pal_kontam'],
                    'Repacking On Hold'      => ['kg' => 'repacking',  'pal' => 'pal_repack'],
                ];

                foreach ($mutuItems as $namaMutu => $field) {
                    $kgVal = $request->input($field['kg'], 0);
                    $palVal = $request->input($field['pal'], 0);

                    // Update row mutu
                    ProduksiSir::updateOrCreate(
                        ['uraian' => $namaMutu, 'created_at' => $tgl],
                        [
                            'kg'         => $kgVal,
                            'pallet'     => $palVal,
                            'keterangan' => '-'
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data Gudang & Mutu Tersimpan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}