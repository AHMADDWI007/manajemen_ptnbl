<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BahanDalamProses;
use Illuminate\Http\Request;

class BahanDalamProsesController extends Controller
{
    public function index()
    {
        // PERUBAHAN DI SINI:
        // Menghapus ->latest('tanggal') dan langsung menggunakan ->all()
        // karena view ini mengandalkan 'uraian' sebagai kunci, bukan urutan tanggal.
        $data_produksi = BahanDalamProses::all()->keyBy('uraian');

        // Hitung total untuk baris jumlah
        $totals = [
            'wip_masuk'      => $data_produksi->sum('wip_masuk'),
            'produksi_sir20' => $data_produksi->sum('produksi_sir20'),
            'saldo_akhir'    => $data_produksi->sum('saldo_akhir'),
        ];

        // Kirim data yang sudah dikelompokkan dan totalnya ke view
        return view('pengolahan.data_produksi', compact('data_produksi', 'totals'));
    }
}

