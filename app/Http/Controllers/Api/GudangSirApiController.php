<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProduksiSir;
use App\Models\BahanProses;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GudangSirApiController extends Controller
{
    public function index(Request $request)
    {
        $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($dateStr);

        // 1. Ambil data Gudang & Mutu HARI INI
        $dataDB = ProduksiSir::where('tanggal', $selectedDate->format('Y-m-d'))->get()->keyBy('uraian');

        // 2. Ambil WIP Hari Ini (Untuk referensi input Android)
        $produksiWip = BahanProses::whereDate('tanggal', $selectedDate)->sum('produksi_sir20');

        // --- TABEL IV (GUDANG) ---
        $masterGudang = [
            '4.1' => 'Di Gudang SIR',
            '4.2' => 'Di Areal Press Bale',
            '4.3' => 'Di Gudang TOH 1',
            '4.4' => 'Di Gudang TOH 2',
        ];

        $listGudang = new Collection();
        foreach ($masterGudang as $no => $namaGudang) {
            $row = $dataDB[$namaGudang] ?? null;

            if ($row) {
                // Data Sudah Ada
                $item = $row;
                $item->no = $no;
                $item->total = $item->saldo_awal + $item->masuk; // Hitung ulang untuk display
                // Pastikan saldo_akhir sesuai DB
            } else {
                // Data Belum Ada (Virtual Preview)
                $lastData = ProduksiSir::where('uraian', $namaGudang)
                                ->where('tanggal', '<', $selectedDate->format('Y-m-d'))
                                ->orderBy('tanggal', 'desc')
                                ->first();

                $saldo_awal = $lastData ? $lastData->saldo_akhir : 0;
                
                // Construct Object
                $item = [
                    'id' => null,
                    'no' => $no,
                    'uraian' => $namaGudang,
                    'saldo_awal' => (float)$saldo_awal,
                    'masuk' => 0,
                    'total' => (float)$saldo_awal, // Awal + 0
                    'pengiriman' => 0,
                    'saldo_akhir' => (float)$saldo_awal, // Total - 0
                    'keterangan' => '-'
                ];
            }
            $listGudang->push($item);
        }

        // Cari Saldo Akhir Gudang SIR untuk referensi Mutu
        $gudangSIR = $listGudang->firstWhere('uraian', 'Di Gudang SIR');
        // Handle jika object (dari DB) atau array (dari virtual)
        $saldoAkhirGudangSIR = is_object($gudangSIR) ? $gudangSIR->saldo_akhir : $gudangSIR['saldo_akhir'];


        // --- TABEL VI (MUTU) ---
        $masterMutu = [
            '6.1' => 'Mutu Prima (siap jual)',
            '6.2' => 'PO / PRI Low',
            '6.3' => 'WhiteSpot (WS)',
            '6.4' => 'Kontaminasi',
            '6.5' => 'Repacking On Hold',
        ];

        $listMutu = new Collection();
        foreach ($masterMutu as $no => $uraian) {
            $row = $dataDB[$uraian] ?? null;
            if ($row) {
                $item = $row;
                $item->no = $no;
            } else {
                $item = [
                    'id' => null,
                    'no' => $no,
                    'uraian' => $uraian,
                    'kg' => 0,
                    'pallet' => 0,
                    'keterangan' => '-'
                ];
            }
            $listMutu->push($item);
        }

        return response()->json([
            'success' => true,
            'data_gudang' => $listGudang,
            'data_mutu' => $listMutu,
            'info' => [
                'wip_hari_ini' => (float)$produksiWip,
                'saldo_gudang_sir' => (float)$saldoAkhirGudangSIR
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Validasi basic
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string'
        ]);
        
        $tgl = $request->tanggal;
        $uraian = $request->uraian;

        // Cek Tipe Input: MUTU atau GUDANG
        $isMutu = in_array($uraian, ['Mutu Prima (siap jual)', 'PO / PRI Low', 'WhiteSpot (WS)', 'Kontaminasi', 'Repacking On Hold']);

        if ($isMutu) {
            // --- LOGIC SIMPAN MUTU ---
            $dataUpdate = [
                'kg' => $request->kg ?? 0,
                'pallet' => $request->pallet ?? 0,
                'keterangan' => $request->keterangan
            ];
        } else {
            // --- LOGIC SIMPAN GUDANG ---
            
            // Cari Saldo Awal (Mundur ke belakang)
            $prevData = ProduksiSir::where('uraian', $uraian)
                        ->where('tanggal', '<', $tgl)
                        ->orderBy('tanggal', 'desc')
                        ->first();
                        
            $saldo_awal = $prevData ? $prevData->saldo_akhir : 0;
            $prod_bln_lalu = $prevData ? $prevData->prod_sd_hi : 0;

            $masuk = $request->masuk ?? 0;
            $pengiriman = $request->pengiriman ?? 0;

            $total = $saldo_awal + $masuk;
            $prod_sd_hi = $prod_bln_lalu + $masuk;
            $saldo_akhir = $total - $pengiriman;

            $dataUpdate = [
                'saldo_awal' => $saldo_awal,
                'masuk' => $masuk,
                'total' => $total,
                'prod_bln_lalu' => $prod_bln_lalu,
                'prod_sd_hi' => $prod_sd_hi,
                'pengiriman' => $pengiriman,
                'saldo_akhir' => $saldo_akhir,
                'total_i_sd_iv' => $saldo_akhir,
                'keterangan' => $request->keterangan
            ];
        }

        ProduksiSir::updateOrCreate(
            ['uraian' => $uraian, 'tanggal' => $tgl],
            $dataUpdate
        );

        return response()->json([
            'success' => true,
            'message' => 'Data Gudang/Mutu berhasil disimpan!'
        ]);
    }
}