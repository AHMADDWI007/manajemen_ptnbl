<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PenjualanSir20;
use App\Models\ProduksiSir;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PenjualanSirApiController extends Controller
{
    public function index(Request $request)
    {
        $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($dateStr);

        // 1. HITUNG OTOMATIS DARI GUDANG SIR (LOGIKA SAMA DENGAN WEB)
        $dataSirHariIni = ProduksiSir::whereDate('created_at', $selectedDate)->get(); // Atau created_at/tanggal sesuai DB
        // Note: Pastikan field tanggal di ProduksiSir konsisten, di web pakai created_at, 
        // tapi sebaiknya pakai 'tanggal' jika ada. Saya pakai logika web Anda:
        
        $autoPTNBL = 0;
        $autoPTPN4 = 0;

        foreach ($dataSirHariIni as $item) {
            $ket = strtoupper($item->keterangan ?? '');
            $qty = $item->pengiriman ?? 0;
            if (Str::contains($ket, 'PTPN')) {
                $autoPTPN4 += $qty;
            } else {
                $autoPTNBL += $qty;
            }
        }

        // 2. AMBIL DATA PENJUALAN
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');
        $yesterday = $selectedDate->copy()->subDay();
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');
        $lastMonth = $selectedDate->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonth)->get()->keyBy('uraian');

        $master = ['5.1' => 'SIR20 PTNBL', '5.2' => 'SIR20 PTPN4'];
        $listData = new Collection();

        foreach ($master as $no => $uraian) {
            $today = $dataDB[$uraian] ?? null;
            $kemarin = $dataKemarin[$uraian] ?? null;
            $blnLalu = $dataBulanLalu[$uraian] ?? null;

            // Hitung s/d Bulan Lalu
            $sd_bulan_lalu = $today ? $today->sd_bulan_lalu : ($blnLalu ? $blnLalu->total_sd_hari_ini : 0);

            // Hitung Yg Lalu (Bulan Ini)
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0;
            } else {
                $bln_ini_lalu = $today ? $today->bln_ini_lalu : ($kemarin ? ($kemarin->bln_ini_lalu + $kemarin->hari_ini) : 0);
            }

            // Hitung Hari Ini (Otomatis vs Database)
            if ($today) {
                $hari_ini = $today->hari_ini; // Data tersimpan
            } else {
                // Belum disimpan, pakai auto hitung
                $hari_ini = ($uraian == 'SIR20 PTNBL') ? $autoPTNBL : $autoPTPN4;
            }

            $total_bln_ini = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

            $listData->push([
                'id' => $today->id ?? null,
                'no' => $no,
                'uraian' => $uraian,
                'sd_bulan_lalu' => (double)$sd_bulan_lalu,
                'bln_ini_lalu' => (double)$bln_ini_lalu,
                'hari_ini' => (double)$hari_ini,
                'total_bln_ini' => (double)$total_bln_ini,
                'total_sd_hari_ini' => (double)$total_sd_hari_ini,
                'keterangan' => $today->keterangan ?? '-'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $listData
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['tanggal' => 'required|date', 'uraian' => 'required']);
        $tgl = Carbon::parse($request->tanggal);
        
        // Hitung ulang saldo (Logic Backend agar aman)
        $lastMonth = $tgl->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $request->uraian)->whereDate('tanggal', $lastMonth)->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;

        $yesterday = $tgl->copy()->subDay();
        $dataKemarin = PenjualanSir20::where('uraian', $request->uraian)->whereDate('tanggal', $yesterday)->first();
        $bln_ini_lalu = ($tgl->day == 1) ? 0 : ($dataKemarin ? ($dataKemarin->bln_ini_lalu + $dataKemarin->hari_ini) : 0);

        $hari_ini = $request->hari_ini ?? 0;
        $total_bln_ini = $bln_ini_lalu + $hari_ini;
        $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

        PenjualanSir20::updateOrCreate(
            ['tanggal' => $tgl->format('Y-m-d'), 'uraian' => $request->uraian],
            [
                'sd_bulan_lalu' => $sd_bulan_lalu,
                'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini,
                'total_bln_ini' => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $request->keterangan
            ]
        );

        return response()->json(['success' => true, 'message' => 'Tersimpan']);
    }
}