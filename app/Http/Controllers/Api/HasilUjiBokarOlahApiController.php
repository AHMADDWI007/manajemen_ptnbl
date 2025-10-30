<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PengolahanBasah; // Tetap pakai model utama
use Illuminate\Support\Facades\Validator; // Untuk validasi
use App\Models\HasilUjiBokarDiolah; // Model untuk tabel kedua

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
           $data = HasilUjiBokarDiolah::orderBy('tanggal', 'desc')->get();
            
            // ✅ PERUBAHAN: Langsung kirim data mentah
            // Resource tidak dipakai lagi
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data Uji Bokar Olah dimuat']);
        
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------
    // ✅ PERBAIKAN: TAMBAHKAN FUNGSI 'store' BARU INI
    // -------------------------------------------------------------------
    /**
     * [FORM 2 SIMPAN] Menerima data BARU untuk tabel hasil_uji_bokar_diolah.
     * Endpoint: POST /hasil-uji-bokar-olah
     */
    public function store(Request $request)
    {
        // Validasi data yang dikirim dari Android
        $validator = Validator::make($request->all(), [
            'id_timbang' => 'required|integer|exists:pengolahan_basah,id',
            'tanggal' => 'required|date_format:Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d', // Sesuaikan format tanggal
            'bak_maturasi' => 'required|string',
            'jenis' => 'required|string',
            'netto_basah' => 'required|numeric',
            'k3' => 'required|numeric',
            'netto_kering' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            // Buat baris baru di tabel 'hasil_uji_bokar_diolah'
            $dataBaru = HasilUjiBokarDiolah::create([
                'tanggal' => $request->tanggal,
                'bak_maturasi' => $request->bak_maturasi,
                'jenis' => $request->jenis,
                'netto_basah' => $request->netto_basah,
                'k3' => $request->k3,
                'netto_kering' => $request->netto_kering,
                // Anda bisa tambahkan 'id_pengolahan_basah' => $request->id_timbang jika ada kolomnya
            ]);

            return response()->json(['success' => true, 'data' => $dataBaru, 'message' => 'Data berhasil disimpan ke tabel hasil uji'], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // -------------------------------------------------------------------
    // ✅ AKHIR FUNGSI BARU
    // -------------------------------------------------------------------


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