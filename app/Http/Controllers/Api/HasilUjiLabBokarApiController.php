<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar; // 1. Import Model yang sudah Anda buat

class HasilUjiLabBokarApiController extends Controller
{
    /**
     * Method ini akan dijalankan ketika aplikasi mobile mengirim data.
     */
    public function store(Request $request)
    {
        // 2. Validasi data yang masuk dari aplikasi mobile
        $validatedData = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ask'       => 'required|numeric',
        ]);

        // 3. Simpan data yang sudah divalidasi ke database
        try {
            HasilUjiLabBokar::create($validatedData);

            // 4. Kirim respons "sukses" kembali ke aplikasi mobile dalam format JSON
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar berhasil disimpan.'
            ], 201); // Kode 201 berarti "Created"

        } catch (\Exception $e) {
            // Jika terjadi error saat menyimpan, kirim respons "gagal"
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500); // Kode 500 berarti "Internal Server Error"
        }
    }
}