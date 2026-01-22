<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenjualanSir20;
use App\Models\ProduksiSir; // Untuk cek pengiriman Gudang
use Carbon\Carbon;
use App\Http\Controllers\DataProduksi\PenjualanSir20Controller; // Import Controller Web

class PenjualanSirApiController extends Controller
{
    // [INDEX] Ambil Data List untuk Tabel Android
    public function index(Request $request)
    {
        $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($dateStr);

        // Ambil data hari ini
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');
        
        // Ambil data referensi (Bulan lalu & Kemarin) untuk menghitung saldo jika data hari ini kosong
        // (Logika ini meniru method index() di Web Controller agar angka akumulasinya muncul di JSON)
        
        $yesterday = $selectedDate->copy()->subDay();
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');
        
        $lastMonthDate = $selectedDate->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonthDate)->get()->keyBy('uraian');

        $masterUraian = [
            '5.1' => 'SIR20 PTNBL',
            '5.2' => 'SIR20 PTPN4',
        ];

        $list = [];
        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB[$uraian] ?? null;
            $itemKemarin = $dataKemarin[$uraian] ?? null;
            $itemBulanLalu = $dataBulanLalu[$uraian] ?? null;

            // Logika Saldo Awal
            if ($itemToday) {
                $sd_bulan_lalu = $itemToday->sd_bulan_lalu;
            } else {
                $sd_bulan_lalu = $itemBulanLalu ? $itemBulanLalu->total_sd_hari_ini : 0;
            }

            // Logika Akumulasi Bulan Ini
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0;
            } else {
                if ($itemToday) {
                    $bln_ini_lalu = $itemToday->bln_ini_lalu;
                } else {
                    $bln_ini_lalu = $itemKemarin ? ($itemKemarin->bln_ini_lalu + $itemKemarin->hari_ini) : 0;
                }
            }

            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;
            $total_sd_hari_ini = $sd_bulan_lalu + $bln_ini_lalu + $hari_ini;

            $list[] = [
                'id_penjualan_sir20' => $itemToday->id_penjualan_sir20 ?? null,
                'no' => $no,
                'uraian' => $uraian,
                'sd_bulan_lalu' => (float)$sd_bulan_lalu,
                'bln_ini_lalu' => (float)$bln_ini_lalu,
                'hari_ini' => (float)$hari_ini,
                'total_sd_hari_ini' => (float)$total_sd_hari_ini,
                'keterangan' => $itemToday->keterangan ?? ''
            ];
        }

        return response()->json(['success' => true, 'data' => $list]);
    }

    // [STORE] Simpan Data dengan Perhitungan Ulang
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'hari_ini' => 'required|numeric'
        ]);

        try {
            // Panggil Logic Web Controller agar perhitungan Saldo Konsisten
            $webController = new PenjualanSir20Controller();
            $webController->recalculateAndSave(
                $request->tanggal,
                $request->uraian,
                $request->hari_ini,
                $request->keterangan
            );

            return response()->json(['success' => true, 'message' => 'Penjualan Tersimpan & Saldo Diupdate']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // [GET PENGIRIMAN] Untuk Saran Input Otomatis
    public function getPengirimanGudang(Request $request)
    {
        $date = $request->query('date');
        $gudangSir = ProduksiSir::where('uraian', 'Di Gudang SIR')
            ->whereDate('created_at', $date)
            ->first();
        
        $pengiriman = $gudangSir ? $gudangSir->pengiriman : 0;
        return response()->json(['pengiriman' => (float)$pengiriman]);
    }
}