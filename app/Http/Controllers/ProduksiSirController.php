<?php

namespace App\Http\Controllers;

use App\Models\ProduksiSir;
use App\Models\BahanProses; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;

class ProduksiSirController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // 1. Ambil data HARI INI
        $dataDB = ProduksiSir::where('tanggal', $selectedDate->format('Y-m-d'))->get()->keyBy('uraian');

        // 2. Ambil Data Produksi WIP (Untuk referensi JS Modal Gudang)
        $produksiHariIni = BahanProses::whereDate('tanggal', $selectedDate)
            ->sum('produksi_sir20');

        // --- TABEL IV (GUDANG) ---
        $masterGudang = [
            '4.1' => 'Di Gudang SIR',
            '4.2' => 'Di Areal Press Bale',
            '4.3' => 'Di Gudang TOH 1',
            '4.4' => 'Di Gudang TOH 2',
        ];

        $tabelIV = new Collection();
        foreach ($masterGudang as $no => $namaGudang) {
            $row = $dataDB[$namaGudang] ?? null;

            if ($row) {
                // KASUS A: Data SUDAH ADA (Disimpan)
                $item = $row;
                $item->no = $no;
                $item->total = $item->saldo_awal + $item->masuk;
                $item->saldo_akhir = $item->total - $item->pengiriman;
            } else {
                // KASUS B: Data BELUM ADA (Virtual/Preview)
                
                // Cari Saldo Awal dari Data Terakhir (Mundur ke belakang)
                $lastData = ProduksiSir::where('uraian', $namaGudang)
                                ->where('tanggal', '<', $selectedDate->format('Y-m-d'))
                                ->orderBy('tanggal', 'desc')
                                ->first();

                $saldo_awal = $lastData ? $lastData->saldo_akhir : 0;
                $prod_bln_lalu = $lastData ? $lastData->prod_sd_hi : 0;

                // PREVIEW 0
                $masuk = 0;
                $pengiriman = 0;
                
                $total = $saldo_awal + $masuk;
                $prod_sd_hi = $prod_bln_lalu + $masuk;
                $saldo_akhir = $total - $pengiriman;

                $item = (object)[
                    'id' => null, // ID Null = Belum Disimpan
                    'no' => $no,
                    'uraian' => $namaGudang,
                    'saldo_awal' => $saldo_awal,
                    'masuk' => $masuk,
                    'total' => $total,
                    'prod_bln_lalu' => $prod_bln_lalu,
                    'prod_sd_hi' => $prod_sd_hi,
                    'pengiriman' => $pengiriman,
                    'saldo_akhir' => $saldo_akhir,
                    'total_i_sd_iv' => $saldo_akhir,
                    'keterangan' => '-'
                ];
            }
            $tabelIV->push($item);
        }

        // ✅ HITUNG SALDO AKHIR GUDANG SIR (UNTUK JS MUTU)
        // Kita cari data 'Di Gudang SIR' di $tabelIV yang baru saja kita loop
        $gudangSIR = $tabelIV->firstWhere('uraian', 'Di Gudang SIR');
        $saldoAkhirGudangSIR = $gudangSIR ? $gudangSIR->saldo_akhir : 0;

        // --- TABEL VI (MUTU) ---
        $masterMutu = [
            '6.1' => 'Mutu Prima (siap jual)',
            '6.2' => 'PO / PRI Low',
            '6.3' => 'WhiteSpot (WS)',
            '6.4' => 'Kontaminasi',
            '6.5' => 'Repacking On Hold',
        ];
        
        $tabelVI = new Collection();
        foreach ($masterMutu as $no => $uraian) {
            $row = $dataDB[$uraian] ?? null;
            if ($row) {
                $row->no = $no;
                $item = $row;
            } else {
                $item = (object)[
                    'id' => null, 'no' => $no, 'uraian' => $uraian,
                    'kg' => 0, 'pallet' => 0, 'keterangan' => '-'
                ];
            }
            $tabelVI->push($item);
        }

        return view('Pengolahan.data_produksi_sir20', [
            'tabelIV' => $tabelIV,
            'tabelVI' => $tabelVI,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'produksiHariIni' => $produksiHariIni, // Dikirim ke View untuk JS Gudang
            'saldoAkhirGudangSIR' => $saldoAkhirGudangSIR // ✅ Dikirim ke View untuk JS Mutu
        ]);
    }

    // ... (Store, Destroy, dll TETAP SAMA seperti sebelumnya) ...
    public function store(Request $request)
    {
        $request->validate(['tanggal' => 'required|date', 'uraian' => 'required|string']);
        $tgl = $request->tanggal;

        // Saldo Awal
        $prevData = ProduksiSir::where('uraian', $request->uraian)
                    ->where('tanggal', '<', $tgl)
                    ->orderBy('tanggal', 'desc')
                    ->first();
        $saldo_awal = $prevData ? $prevData->saldo_akhir : 0;
        $prod_bln_lalu = $prevData ? $prevData->prod_sd_hi : 0;

        $isMutu = in_array($request->uraian, ['Mutu Prima (siap jual)', 'PO / PRI Low', 'WhiteSpot (WS)', 'Kontaminasi', 'Repacking On Hold']);

        if ($isMutu) {
            $dataUpdate = [
                'kg' => $request->kg ?? 0,
                'pallet' => $request->pallet ?? 0,
                'keterangan' => $request->keterangan
            ];
        } else {
            // Logic Gudang
            $masuk = $request->masuk;
            // Auto-fill di Backend (Safety Net): Jika kosong, ambil dari WIP
            if ($request->uraian == 'Di Gudang SIR' && ($masuk === null)) {
                 $masuk = BahanProses::whereDate('tanggal', $tgl)->sum('produksi_sir20');
            }
            $masuk = $masuk ?? 0;
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
            ['uraian' => $request->uraian, 'tanggal' => $tgl],
            $dataUpdate
        );

        return redirect()->route('produksi-sir.index', ['filter_tanggal' => $tgl])->with('success', 'Data berhasil disimpan!');
    }

    public function destroy($id) {
        $data = ProduksiSir::find($id);
        if($data) $data->delete();
        return back()->with('success', 'Data di-reset.');
    }
}