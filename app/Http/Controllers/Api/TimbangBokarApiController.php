<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengolahanBasah; // ✅ DIGANTI: Menggunakan model PengolahanBasah
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimbangBokarApiController extends Controller
{
    /**
     * [TABEL 1] Mengambil SEMUA data timbang (K3 isi maupun NULL).
     * Endpoint: GET /hasil-timbang-bokar
     */
    public function index()
    {
        try {
            $data = PengolahanBasah::orderBy('tanggal', 'desc')->get(); // ✅ DIGANTI
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data dimuat']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [FORM 1] Menyimpan data timbang BARU.
     * Endpoint: POST /timbang-bokar
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'jenis' => 'required|string|max:255',
             // ✅ DIGANTI: Validasi unik ke tabel 'pengolahan_basah'
            'bak_maturasi' => 'required|string|max:255|unique:pengolahan_basah,bak_maturasi',
            'berat_truck' => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:0',
            'netto_basah' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            // k3 dan netto_kering otomatis NULL saat dibuat
            $data = PengolahanBasah::create($request->all()); // ✅ DIGANTI
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data timbang berhasil disimpan'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [FORM 1 Edit] Mengambil detail 1 data timbang.
     * Endpoint: GET /timbang-bokar/{id}
     */
    public function show($id)
    {
        $data = PengolahanBasah::find($id); // ✅ DIGANTI
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['success' => true, 'data' => $data, 'message' => 'Data dimuat']);
    }

    /**
     * [FORM 1 Edit] Update data timbang mentah.
     * Endpoint: PUT /timbang-bokar/{id}
     */
    public function update(Request $request, $id)
    {
        $data = PengolahanBasah::find($id); // ✅ DIGANTI
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'jenis' => 'required|string|max:255',
             // ✅ DIGANTI: Validasi unik ke tabel 'pengolahan_basah'
            'bak_maturasi' => 'required|string|max:255|unique:pengolahan_basah,bak_maturasi,' . $id,
            'berat_truck' => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:0',
            'netto_basah' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            // Update data mentah
            $data->update($request->only(['tanggal', 'jenis', 'bak_maturasi', 'berat_truck', 'berat_timbang', 'netto_basah']));

            // Jika K3 sudah ada, hitung ulang Netto Kering
            if ($data->k3) {
                $data->netto_kering = ($data->netto_basah * $data->k3) / 100.0;
                $data->save();
            }

            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data timbang berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [TABEL 1] Hapus permanen data timbang.
     * Endpoint: DELETE /timbang-bokar/{id}
     */
    public function destroy($id)
    {
        $data = PengolahanBasah::find($id); // ✅ DIGANTI
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data timbang berhasil dihapus']);
    }

    // --- METODE KHUSUS UNTUK FORM 2 (Input K3) ---

    /**
     * [FORM 2 SPINNER] Ambil data yang K3-nya masih NULL.
     * Endpoint: GET /timbang-bokar/pending-k3
     */
    public function getListPendingK3()
    {
        try {
            $data = PengolahanBasah::whereNull('k3')->orderBy('tanggal', 'asc')->get(); // ✅ DIGANTI
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data pending dimuat']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [FORM 2 SIMPAN] Update K3 dan hitung Netto Kering.
     * Endpoint: PUT /timbang-bokar/update-k3/{id}
     */
    public function updateK3(Request $request, $id)
    {
        $data = PengolahanBasah::find($id); // ✅ DIGANTI
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'k3' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi K3 gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $k3Value = (double)$request->k3;
            $nettoBasah = (double)$data->netto_basah;

            // Kalkulasi Netto Kering di backend
            $nettoKeringValue = ($nettoBasah * $k3Value) / 100.0;

            $data->k3 = $k3Value;
            $data->netto_kering = $nettoKeringValue;
            $data->save();

            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data K3 berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}