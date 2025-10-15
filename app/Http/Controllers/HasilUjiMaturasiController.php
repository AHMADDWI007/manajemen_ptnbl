<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiMaturasi;

class HasilUjiMaturasiController extends Controller
{
    public function index()
    {
        // Ambil semua data hasil uji maturasi
        $data_maturasi = HasilUjiMaturasi::orderBy('tanggal', 'desc')->get();

        // Kirim ke view
        return view('Pengolahan.Hasil_Uji_Maturasi', compact('data_maturasi'));
    }
}
