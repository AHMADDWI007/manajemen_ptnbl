<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiTroli;
use Illuminate\Http\Request;

class HasilUjiTroliApiController extends Controller
{
    /**
     * Simpan data hasil uji troli dari aplikasi mobile.
     */
    public function store(Request $request)
    {
        // 🔹 Validasi input dari Android
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'no_trolly' => 'required|string|max:50',
            'k3' => 'required|numeric',
            'po' => 'required|numeric',
            'pa' => 'required|numeric',
            'pri' => 'required|numeric',
            'jam_sample' => 'required|string|max:20',
            //'lama_pengeringan' => 'required|string|max:50',
        ]);

        try {
            // 🔹 Simpan ke database
            $hasil = HasilUjiTroli::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data hasil uji troli berhasil disimpan.',
                'data' => $hasil,
            ], 201);

        } catch (\Exception $e) {
            // 🔹 Tangani error jika gagal
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $data = HasilUjiTroli::latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Data Uji Troli berhasil diambil.',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = HasilUjiTroli::findOrFail($id);
            return response()->json([
                'success' => true,
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404); // Kode 404 berarti "Not Found"
        }
    }

    /**
     * (UPDATE) Menyimpan perubahan data.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal'          => 'required|date',
            'no_trolly'        => 'required|string|max:50|unique:hasil_uji_troli,no_trolly,' . $id,
            'k3'               => 'required|numeric',
            'po'               => 'required|numeric',
            'pa'               => 'required|numeric',
            'pri'              => 'required|numeric',
            'jam_sample'       => 'required|string|max:20',
            //'lama_pengeringan' => 'required|string|max:50',
        ]);

        try {
            $data = HasilUjiTroli::findOrFail($id);
            $data->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * (DELETE) Menghapus data.
     */
    public function destroy($id)
    {
        try {
            $data = HasilUjiTroli::findOrFail($id);
            $data->delete();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.'
            ], 500);
        }
    }
}
