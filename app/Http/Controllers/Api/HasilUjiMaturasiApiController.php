<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiMaturasi;

class HasilUjiMaturasiApiController extends Controller
{
    public function store(Request $request)
    {
        // ✅ Validasi data dari aplikasi mobile
        $validatedData = $request->validate([
            'tanggal'   => 'required|date',
            'no_kamar'  => 'required|string|max:50',
            'k3'        => 'required|numeric',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
        ]);

        try {
            // ✅ Simpan ke database
            HasilUjiMaturasi::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Data Uji Maturasi berhasil disimpan.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            // Ambil semua data dari model, urutkan dari yang terbaru
            $data = HasilUjiMaturasi::latest()->get();

            // Jika data ditemukan, kirim sebagai respons JSON
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Maturasi berhasil diambil.',
                'data'    => $data
            ], 200); // 200 = OK

        } catch (\Exception $e) {
            // Jika terjadi error, kirim respons gagal
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = HasilUjiMaturasi::findOrFail($id);
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
     * Method untuk menyimpan perubahan data. (UPDATE)
     */
    public function update(Request $request, $id)
    {
        // Validasi, mirip seperti store. Jika no_kamar harus unik, tambahkan rule 'unique'
        $validatedData = $request->validate([
            'tanggal'  => 'required|date',
            'no_kamar' => 'required|string|max:50', // Contoh: 'unique:hasil_uji_maturasi,no_kamar,' . $id
            'k3'       => 'required|numeric',
            'po'       => 'required|numeric',
            'pa'       => 'required|numeric',
            'pri'      => 'required|numeric',
        ]);

        try {
            $data = HasilUjiMaturasi::findOrFail($id);
            $data->update($validatedData);

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
     * Method untuk menghapus data. (DELETE)
     */
    public function destroy($id)
    {
        try {
            $data = HasilUjiMaturasi::findOrFail($id);
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
