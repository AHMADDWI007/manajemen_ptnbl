<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengolahanBasah; // Tetap pakai model utama
// Hapus 'use App\Http\Resources\HasilUjiBokarOlahResource;'
use Illuminate\Http\Request;

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * [TABEL 2] Mengambil data yang K3-nya SUDAH diisi.
     * Endpoint: GET /hasil-uji-bokar-olah
     */
    public function index()
    {
        try {
            // Ambil data yang K3-nya TIDAK NULL
            $data = PengolahanBasah::whereNotNull('k3')
                        ->orderBy('tanggal', 'desc')
                        ->get();
            
            // ✅ PERUBAHAN: Langsung kirim data mentah
            // Resource tidak dipakai lagi
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data Uji Bokar Olah dimuat']);
        
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [TABEL 2] "Hapus" data (Reset K3 dan Netto Kering menjadi NULL).
     * Endpoint: DELETE /uji-bokar-olah/{id}
     */
    public function destroy($id)
    {
        // Fungsi ini tidak berubah
        $data = PengolahanBasah::find($id);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        try {
            // Reset K3-nya agar kembali ke daftar pending di Form 2
            $data->k3 = null;
            $data->netto_kering = null;
            $data->save();

            return response()->json(['success' => true, 'message' => 'Data K3 berhasil direset']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}