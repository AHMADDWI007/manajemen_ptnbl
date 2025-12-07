<?php

namespace App\Http\Controllers;

use App\Models\PenjualanSir20;
use App\Models\ProduksiSir; // PENTING: Import Model Data SIR
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PenjualanSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        $headerBulanLalu = $selectedDate->copy()->subMonth()->translatedFormat('F Y');

        // =========================================================================
        // 1. AMBIL & PILAH DATA PENGIRIMAN DARI DATA SIR (GUDANG) HARI INI
        // =========================================================================
        $dataSirHariIni = ProduksiSir::whereDate('created_at', $selectedDate)->get();

        $autoPengirimanPTNBL = 0;
        $autoPengirimanPTPN4 = 0;

        foreach ($dataSirHariIni as $itemSir) {
            $ket = strtoupper($itemSir->keterangan ?? ''); // Ambil keterangan, uppercase biar aman
            $qty = $itemSir->pengiriman ?? 0;

            // Logika Pemilahan:
            // Jika keterangan mengandung kata "PTPN", masukkan ke PTPN4
            if (Str::contains($ket, 'PTPN')) {
                $autoPengirimanPTPN4 += $qty;
            } else {
                // Selain itu (misal PTNB, atau kosong), masukkan ke PTNBL
                $autoPengirimanPTNBL += $qty;
            }
        }

        // =========================================================================
        // 2. SIAPKAN DATA PENJUALAN
        // =========================================================================
        
        // Ambil Data Penjualan yang sudah tersimpan (Hari Ini)
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');

        // Data history (Kemarin)
        $yesterday = $selectedDate->copy()->subDay();
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');

        // Data history (Akhir Bulan Lalu)
        $lastMonthDate = $selectedDate->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonthDate)->get()->keyBy('uraian');

        $masterUraian = [
            '5.1' => 'SIR20 PTNBL',
            '5.2' => 'SIR20 PTPN4',
        ];

        $tabelData = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB[$uraian] ?? null;
            $itemKemarin = $dataKemarin[$uraian] ?? null;
            $itemBulanLalu = $dataBulanLalu[$uraian] ?? null;

            // --- A. Hitung s/d Bulan Lalu ---
            if ($itemToday) {
                $sd_bulan_lalu = $itemToday->sd_bulan_lalu;
            } else {
                $sd_bulan_lalu = $itemBulanLalu ? $itemBulanLalu->total_sd_hari_ini : 0;
            }

            // --- B. Hitung Yg Lalu (Bulan Ini) ---
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0;
            } else {
                if ($itemToday) {
                    $bln_ini_lalu = $itemToday->bln_ini_lalu;
                } else {
                    $bln_ini_lalu = $itemKemarin ? ($itemKemarin->bln_ini_lalu + $itemKemarin->hari_ini) : 0;
                }
            }

            // --- C. LOGIKA OTOMATIS HARI INI (UPDATE) ---
            if ($itemToday) {
                // KASUS 1: Data SUDAH disimpan/diedit user -> Gunakan data database
                $hari_ini = $itemToday->hari_ini;
            } else {
                // KASUS 2: Data BELUM ada -> Gunakan nilai otomatis dari Data SIR
                if ($uraian == 'SIR20 PTNBL') {
                    $hari_ini = $autoPengirimanPTNBL; 
                } elseif ($uraian == 'SIR20 PTPN4') {
                    $hari_ini = $autoPengirimanPTPN4;
                } else {
                    $hari_ini = 0;
                }
            }

            // --- D. Hitung Total ---
            $total_bln_ini = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

            $tabelData->push((object)[
                'id' => $itemToday->id ?? null,
                'no' => $no,
                'uraian' => $uraian,
                'sd_bulan_lalu' => $sd_bulan_lalu,
                'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini,
                'total_bln_ini' => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $itemToday->keterangan ?? '-',
            ]);
        }

        return view('Pengolahan.penjualan_sir20', [
            'tabelData' => $tabelData,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'headerBulanLalu' => $headerBulanLalu
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'hari_ini' => 'required|numeric|min:0',
        ]);

        $tgl = Carbon::parse($request->tanggal);
        $uraian = $request->uraian;
        $hari_ini = $request->hari_ini;

        // 1. Cari Data Akhir Bulan Lalu
        $lastMonthDate = $tgl->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)
                            ->whereDate('tanggal', $lastMonthDate)
                            ->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;

        // 2. Cari Data Kemarin
        $yesterday = $tgl->copy()->subDay();
        $dataKemarin = PenjualanSir20::where('uraian', $uraian)
                        ->whereDate('tanggal', $yesterday)
                        ->first();
        
        if ($tgl->day == 1) {
            $bln_ini_lalu = 0; 
        } else {
            $bln_ini_lalu = $dataKemarin ? ($dataKemarin->bln_ini_lalu + $dataKemarin->hari_ini) : 0;
        }

        // 3. Kalkulasi Total
        $total_bln_ini = $bln_ini_lalu + $hari_ini;
        $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

        // 4. Simpan
        PenjualanSir20::updateOrCreate(
            [
                'tanggal' => $tgl->format('Y-m-d'),
                'uraian' => $uraian
            ],
            [
                'sd_bulan_lalu' => $sd_bulan_lalu,
                'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini,
                'total_bln_ini' => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $request->keterangan
            ]
        );

        return redirect()->route('penjualan_sir20.index', ['filter_tanggal' => $tgl->format('Y-m-d')])
                         ->with('success', 'Data Penjualan berhasil disimpan.');
    }

    public function destroy($id)
    {
        $data = PenjualanSir20::find($id);
        if ($data) {
            $data->delete();
            return back()->with('success', 'Data berhasil di-reset.');
        }
        return back();
    }
}