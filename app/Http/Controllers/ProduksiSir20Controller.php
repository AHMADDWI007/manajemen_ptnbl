<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProduksiSir20;
use Illuminate\Http\Request;

class ProduksiSir20Controller extends Controller
{
    public function index()
    {
        // Ambil semua data dan jadikan 'uraian' sebagai kunci untuk pencarian mudah
        $data_produksi = ProduksiSir20::all()->keyBy('uraian');

        // Hitung total untuk baris jumlah
        $totals = [
            'masuk' => $data_produksi->sum('masuk'),
            'total' => $data_produksi->sum('total'),
            'produksi_sd_hi' => $data_produksi->sum('produksi_sd_hi'),
            'saldo_akhir' => $data_produksi->sum('saldo_akhir'),
            'pt_nb' => $data_produksi->sum('pt_nb'),
            'total_100_persen' => $data_produksi->sum('total_100_persen'),
        ];

        // Kirim data yang sudah dikelompokkan dan totalnya ke view
        return view('Pengolahan.produksi_sir20', compact('data_produksi', 'totals'));
    }
}
