<?php

namespace App\Http\Controllers;

use App\Models\Maturasi;
use Illuminate\Http\Request;

class MaturasiController extends Controller
{
    /**
     * Menampilkan halaman daftar data maturasi.
     */
    public function index()
    {
        // Ambil semua data maturasi dari database
        $semuaMaturasi = Maturasi::all();

        // Ubah koleksi data menjadi array asosiatif dengan 'uraian_proses' sebagai kunci
        // Ini akan membuat pencarian data di view menjadi sangat cepat dan efisien
        $data_maturasi = $semuaMaturasi->keyBy('uraian_proses');

        // Kirim data yang sudah terstruktur ke view
        return view('pengolahan.data_maturasi', compact('data_maturasi'));
    }

    // Metode lain seperti store, update, destroy bisa ditambahkan di sini untuk API...
}
