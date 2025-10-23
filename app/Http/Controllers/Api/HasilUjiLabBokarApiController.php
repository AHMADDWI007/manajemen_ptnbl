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
            // ✅ PERBAIKAN: Tambahkan validasi untuk PO, PA, PRI
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            // ✅ AKHIR PERBAIKAN
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

    public function index()
    {
        try {
            // Ambil semua data dari model, urutkan dari yang terbaru
            $data = HasilUjiLabBokar::latest()->get();

            // Jika data ditemukan, kirim sebagai respons JSON
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil.',
                'data'    => $data
            ], 200); // Kode 200 berarti "OK"

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
            $data = HasilUjiLabBokar::findOrFail($id);
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

    public function update(Request $request, $id)
    {
        // Validasi, mirip seperti store tapi ada pengecualian untuk 'unique'
        $validatedData = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            // Aturan 'unique' diubah agar mengabaikan data dengan ID saat ini
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel,' . $id,
            'k3'        => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ask'       => 'required|numeric',
            // ✅ PERBAIKAN: Tambahkan validasi untuk PO, PA, PRI
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            // ✅ AKHIR PERBAIKAN
        ]);

        try {
            $data = HasilUjiLabBokar::findOrFail($id);
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

    public function destroy($id)
    {
        try {
            $data = HasilUjiLabBokar::findOrFail($id);
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