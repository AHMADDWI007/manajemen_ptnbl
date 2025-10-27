<?php

// ✅ PERBAIKAN: Sesuaikan namespace jika berbeda
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// ✅ PERBAIKAN: Import Model yang benar
use App\Models\HasilUjiBokarOlah;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Untuk error handling 404
use Illuminate\Validation\ValidationException; // Untuk error handling validasi

// ✅ PERBAIKAN: Ganti nama class controller
class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * Method ini akan dijalankan ketika aplikasi mobile mengirim data baru.
     */
    public function store(Request $request)
    {
        try {
             // ✅ PERBAIKAN: Validasi data Uji Bokar Olah
            $validatedData = $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d', // Format YYYY-MM-DD
                'bak_maturasi'  => 'required|string|max:255',
                'jenis'         => 'required|string|max:255',
                'netto_basah'   => 'required|numeric|min:0', // Angka desimal positif
                'k3'            => 'required|numeric|min:0|max:100', // Angka desimal 0-100
                'netto_kering'  => 'required|numeric|min:0', // Angka desimal positif
            ]);
            // ✅ AKHIR PERBAIKAN VALIDASI

            // Simpan data ke database menggunakan Model yang benar
            $hasilOlah = HasilUjiBokarOlah::create($validatedData);

            // Kirim respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil disimpan.',
                'data'    => $hasilOlah // Mengembalikan data yang baru dibuat (opsional)
            ], 201); // Kode 201 berarti "Created"

        } catch (ValidationException $e) {
             // Tangani error validasi
            return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid.',
                'errors'  => $e->errors(), // Kirim detail errornya
            ], 422); // Kode 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Tangani error server lainnya
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: Terjadi kesalahan pada server.'
                // 'message' => 'Gagal menyimpan data: ' . $e->getMessage() // Hindari menampilkan detail error ke user
            ], 500); // Kode 500 Internal Server Error
        }
    }

    /**
     * Method untuk mengambil semua data Uji Bokar Olah.
     */
    public function index()
    {
        try {
            // Ambil data, urutkan dari yang terbaru
            // ✅ PERBAIKAN: Gunakan Model HasilUjiBokarOlah
            $data = HasilUjiBokarOlah::latest('tanggal')->latest('created_at')->get();

            // Kirim respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil diambil.',
                'data'    => $data
            ], 200); // Kode 200 OK

        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * Method untuk mengambil satu data Uji Bokar Olah berdasarkan ID.
     */
    public function show($id)
    {
        try {
            // Cari data berdasarkan ID
            // ✅ PERBAIKAN: Gunakan Model HasilUjiBokarOlah
            $data = HasilUjiBokarOlah::findOrFail($id);

            // Kirim respons sukses
            return response()->json([
                'success' => true,
                'data'    => $data
            ], 200);

        } catch (ModelNotFoundException $e) {
            // Tangani jika ID tidak ditemukan
            return response()->json([
                'success' => false,
                'message' => 'Data Uji Bokar Olah tidak ditemukan.'
            ], 404); // Kode 404 Not Found
        } catch (\Exception $e) {
            // Tangani error lainnya
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * Method untuk memperbarui data Uji Bokar Olah.
     */
    public function update(Request $request, $id)
    {
        try {
            // Cari data yang akan diupdate
            // ✅ PERBAIKAN: Gunakan Model HasilUjiBokarOlah
            $data = HasilUjiBokarOlah::findOrFail($id);

             // ✅ PERBAIKAN: Validasi data Uji Bokar Olah (mirip store, tanpa unique)
            $validatedData = $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d',
                'bak_maturasi'  => 'required|string|max:255',
                'jenis'         => 'required|string|max:255',
                'netto_basah'   => 'required|numeric|min:0',
                'k3'            => 'required|numeric|min:0|max:100',
                'netto_kering'  => 'required|numeric|min:0',
                // Anda bisa menambahkan validasi unique jika ada field unik di Uji Bokar Olah
                // 'field_unik' => 'required|string|unique:hasil_uji_bokar_olah,field_unik,' . $id,
            ]);
            // ✅ AKHIR PERBAIKAN VALIDASI

            // Lakukan update
            $data->update($validatedData);

            // Kirim respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil diperbarui.',
                'data'    => $data // Kembalikan data yang sudah diupdate
            ], 200); // Kode 200 OK

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data Uji Bokar Olah tidak ditemukan.'
            ], 404);
        } catch (ValidationException $e) {
             return response()->json([
                'success' => false,
                'message' => 'Data yang dikirim tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * Method untuk menghapus data Uji Bokar Olah.
     */
    public function destroy($id)
    {
        try {
            // Cari data
            // ✅ PERBAIKAN: Gunakan Model HasilUjiBokarOlah
            $data = HasilUjiBokarOlah::findOrFail($id);
            // Hapus data
            $data->delete();

            // Kirim respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil dihapus.'
            ], 200); // 200 OK (atau 204 No Content)

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data Uji Bokar Olah tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: Terjadi kesalahan pada server.'
            ], 500);
        }
    }
}