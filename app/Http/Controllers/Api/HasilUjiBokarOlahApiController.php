<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah; // ✅ Import Model tabel PERTAMA (pengolahan_basah)
use App\Models\HasilUjiBokarDiolah; // ✅ Import Model tabel KEDUA (hasil_uji_bokar_diolah)
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * ✅ [TABEL 2] Mengambil data yang K3-nya SUDAH diisi.
     * Endpoint: GET /hasil-uji-bokar-olah
     * (Dipanggil oleh DataUjiBokarOlahActivity)
     */
    public function index()
    {
        try {
            // Ambil data HANYA dari tabel hasil_uji_bokar_diolah
            $data = HasilUjiBokarDiolah::latest('tanggal')->latest('created_at')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil diambil.',
                'data' => $data
            ], 200);
        
        } catch (\Exception $e) {
            Log::error('Error index HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data.'], 500);
        }
    }

    /**
     * ✅ [FORM 2 - Simpan] Menerima data BARU untuk tabel hasil_uji_bokar_diolah.
     * Endpoint: POST /hasil-uji-bokar-olah
     * (Dipanggil oleh UjiBokarOlahActivity sebagai Panggilan API ke-2)
     */
   public function store(Request $request)
    {
        try {
            // ✅ PERBAIKAN: Hapus 'id_timbang' dari validasi
            $validatedData = $request->validate([
                'tanggal' => 'required|date_format:Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d',
                'bak_maturasi' => 'required|string|unique:hasil_uji_bokar_diolah,bak_maturasi,NULL,id,tanggal,' . $request->tanggal,
                'jenis' => 'required|string',
                'netto_basah' => 'required|numeric',
                'k3' => 'required|numeric',
                'netto_kering' => 'required|numeric',
            ],[
                'bak_maturasi.unique' => 'Data K3 untuk Bak Maturasi & Tanggal ini sudah diinput.'
            ]);
            // ✅ AKHIR PERBAIKAN

            // Buat baris baru di tabel 'hasil_uji_bokar_diolah'
            // (id_timbang tidak dimasukkan karena tidak ada di tabel)
            $dataBaru = HasilUjiBokarDiolah::create($validatedData);

            return response()->json([
                'success' => true, 
                'data' => $dataBaru, 
                'message' => 'Data berhasil disimpan ke tabel hasil uji'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error store HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // ✅ AKHIR PERBAIKAN

    /**
     * ✅ [TABEL 2] "Hapus" data.
     * Ini akan menghapus data dari 'hasil_uji_bokar_diolah'
     * DAN me-reset K3 di 'pengolahan_basah' menjadi NULL.
     * Endpoint: DELETE /uji-bokar-olah/{id}
     * (Dipanggil oleh DataUjiBokarOlahActivity)
     */
    public function destroy($id)
    {
        try {
            // 1. Cari data di tabel KEDUA (hasil_uji_bokar_diolah) berdasarkan ID-nya
            $dataOlah = HasilUjiBokarDiolah::findOrFail($id);

            // 2. Ambil kunci unik (bak_maturasi dan tanggal) dari data olah
            $bakMaturasi = $dataOlah->bak_maturasi;
            $tanggal = $dataOlah->tanggal;

            // 3. Cari data di tabel PERTAMA (pengolahan_basah) menggunakan kunci tersebut
            $dataTimbang = PengolahanBasah::where('bak_maturasi', $bakMaturasi)
                                          ->where('tanggal', $tanggal)
                                          ->first();

            if ($dataTimbang) {
                // 4. Reset K3 dan Netto Kering di tabel PERTAMA
                $dataTimbang->k3 = null;
                $dataTimbang->netto_kering = null;
                $dataTimbang->save();
            }

            // 5. Hapus data dari tabel KEDUA
            $dataOlah->delete();

            return response()->json([
                'success' => true, 
                'message' => 'Data K3 berhasil direset (dihapus dari tabel olah).'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data olah tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Error destroy HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // ✅ AKHIR PERBAIKAN
    
    // CATATAN: Fungsi show() dan update() tidak ada di controller ini
    // karena alur kerja Anda:
    // - EDIT data mentah dilakukan di TimbangBokarApiController
    // - "Edit" data K3 pada dasarnya adalah HAPUS (destroy) lalu INPUT ULANG (store)
}