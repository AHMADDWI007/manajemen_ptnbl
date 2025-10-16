<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PenjualanSir20;
use App\Models\MutuPrima;
use Illuminate\Http\Request;

class PenjualanSir20Controller extends Controller
{
    public function index()
    {
        // --- Data untuk Tabel V (Telah Dijual) ---
        $data_penjualan = PenjualanSir20::all()->keyBy('uraian');
        $totals_penjualan = [
            'penjualan_bulan_ini_yg_lalu'   => $data_penjualan->sum('penjualan_bulan_ini_yg_lalu'),
            'penjualan_bulan_ini_hari_ini'  => $data_penjualan->sum('penjualan_bulan_ini_hari_ini'),
            'total_bulan_ini'               => $data_penjualan->sum('total_bulan_ini'),
            'total_penjualan_sd_hari_ini'   => $data_penjualan->sum('total_penjualan_sd_hari_ini'),
        ];

        // --- Data untuk Tabel VI (Mutu Prima) ---
        $data_mutu = MutuPrima::all()->keyBy('uraian');
        $totals_mutu = [
            'kg'     => $data_mutu->sum('kg'),
            'pallet' => $data_mutu->sum('pallet'),
        ];
        
        // Kirim semua data ke satu view
        return view('Pengolahan.penjualan_sir20', compact(
            'data_penjualan', 
            'totals_penjualan',
            'data_mutu',
            'totals_mutu'
        ));
    }
}
