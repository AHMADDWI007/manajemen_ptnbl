<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\HasilUjiMaturasi;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Maturasi;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HasilUjiMaturasiApiController extends Controller
{
    /**
     * Menyimpan data Uji Maturasi baru
     * Endpoint: POST /uji-maturasi
     */
    public function store(Request $request)
    {
        try {
            // ✅ PERBAIKAN: Validasi disamakan dengan controller Web
            $validatedData = $request->validate([
                'tanggal'  => 'required|date',
                // Hapus 'unique' agar sama dengan Web (boleh uji ulang)
                'no_kamar' => 'required|string|max:255|exists:maturasis,uraian',
                // Ubah 'required' -> 'nullable' agar sama dengan Web
                'k3'       => 'nullable|numeric',
                'po'       => 'nullable|numeric',
                'pa'       => 'nullable|numeric',
                'pri'      => 'nullable|numeric',
            ], [
                'no_kamar.exists' => 'No Kamar/Uraian Bak tidak ditemukan di tabel maturasi.'
            ]);
            // ✅ AKHIR PERBAIKAN VALIDASI

            $hasilUji = HasilUjiMaturasi::create($validatedData);

            // Sinkronisasi ke tabel maturasis (Logika ini sudah benar)
            try {
                $maturasi = Maturasi::where('uraian', $request->no_kamar)->firstOrFail();
                $maturasi->id_hasil_uji_maturasi = $hasilUji->id;
                $maturasi->save();
            } catch (\Exception $e) {
                Log::error("API Gagal sinkronisasi 'store' Uji Maturasi: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Uji Maturasi berhasil disimpan.'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error store Uji Maturasi API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil semua data Uji Maturasi
     * Endpoint: GET /hasil-uji-lab-maturasi
     */
    public function index()
    {
        try {
            $data = HasilUjiMaturasi::latest('tanggal')->get();
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Maturasi berhasil diambil.',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error index Uji Maturasi API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data.'
            ], 500);
        }
    }

    /**
     * Mengambil detail 1 data Uji Maturasi
     * Endpoint: GET /uji-maturasi/{id}
     */
    public function show($id)
    {
        try {
            $data = HasilUjiMaturasi::findOrFail($id);
            return response()->json([
                'success' => true,
                'data'    => $data
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error show Uji Maturasi API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan.'
            ], 500);
        }
    }

    /**
     * Menyimpan perubahan data Uji Maturasi
     * Endpoint: PUT /uji-maturasi/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            // ✅ PERBAIKAN: Validasi disamakan dengan controller Web
            $validatedData = $request->validate([
                'tanggal'  => 'required|date',
                'no_kamar' => 'required|string|max:255|exists:maturasis,uraian',
                'k3'       => 'nullable|numeric',
                'po'       => 'nullable|numeric',
                'pa'       => 'nullable|numeric',
                'pri'      => 'nullable|numeric',
            ], [
                'no_kamar.exists' => 'No Kamar/Uraian Bak tidak ditemukan di tabel maturasi.'
            ]);
            // ✅ AKHIR PERBAIKAN VALIDASI

            $hasilUji = HasilUjiMaturasi::findOrFail($id);
            $hasilUji->update($validatedData);

            // Sinkronisasi ke tabel maturasis (Logika ini sudah benar)
            try {
                $maturasi = Maturasi::where('uraian', $request->no_kamar)->firstOrFail();
                $maturasi->id_hasil_uji_maturasi = $hasilUji->id;
                $maturasi->save();
            } catch (\Exception $e) {
                Log::error("API Gagal sinkronisasi 'update' Uji Maturasi: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui.'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error update Uji Maturasi API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus data Uji Maturasi
     * Endpoint: DELETE /uji-maturasi/{id}
     */
    public function destroy($id)
    {
        try {
            $hasilUji = HasilUjiMaturasi::findOrFail($id);

            // Sinkronisasi ke tabel maturasis (Logika ini sudah benar)
            try {
                $maturasi = Maturasi::where('id_hasil_uji_maturasi', $hasilUji->id)->first();
                if ($maturasi) {
                    $maturasi->id_hasil_uji_maturasi = null;
                    $maturasi->save();
                }
            } catch (\Exception $e) {
                Log::error("API Gagal sinkronisasi 'delete' Uji Maturasi: " . $e->getMessage());
            }

            $hasilUji->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error destroy Uji Maturasi API: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.'
            ], 500);
        }
    }
}
