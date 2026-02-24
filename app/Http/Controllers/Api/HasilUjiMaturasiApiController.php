<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabMaturasi;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class HasilUjiMaturasiApiController extends Controller
{
    /**
     * [LIST TABLE] Mengambil data hasil uji maturasi
     */
    public function index(Request $request) 
    {
        try {
            $query = HasilUjiLabMaturasi::with('maturasi')->latest('tanggal');

            // Filter Tanggal
            if ($request->has('date') && !empty($request->date)) {
                $query->whereDate('tanggal', $request->date);
            }

            $data = $query->get()->map(function($item) {
                return [
                    'id'          => $item->id_hasil_uji_lab_maturasi,
                    'tanggal'     => $item->tanggal,
                    'id_maturasi' => $item->id_maturasi, // 🔥 Kirim ID ke Android
                    'no_kamar'    => $item->maturasi ? $item->maturasi->uraian : 'Bak Terhapus', // Label tampilan
                    'k3'          => $item->k3 !== null ? (string)$item->k3 : null,
                    'po'          => $item->po !== null ? (string)$item->po : null,
                    'pa'          => $item->pa !== null ? (string)$item->pa : null,
                    'pri'         => $item->pri !== null ? (string)$item->pri : null,
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [STORE] Simpan data baru (SINKRON WEB ADMIN)
     */
    public function store(Request $request) 
    {
        // 1. Validasi Input (Gunakan id_maturasi persis seperti di Web)
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'id_maturasi' => 'required|exists:maturasi,id_maturasi', // 🔥 Wajib pakai ID
            'k3'          => 'nullable|numeric',
            'po'          => 'nullable|numeric',
            'pa'          => 'nullable|numeric',
            'pri'         => 'nullable|numeric',
        ], [
            'id_maturasi.exists' => 'Bak Maturasi tidak ditemukan di sistem.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 2. Simpan Langsung ke Database (Sama persis seperti Web)
            HasilUjiLabMaturasi::create($validator->validated());

            return response()->json(['success' => true, 'message' => 'Data Lab Maturasi berhasil disimpan'], 201);

        } catch (\Exception $e) {
            Log::error("API Store Uji Maturasi Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [SHOW/EDIT] Mengambil detail 1 baris (Untuk Form Edit di Android)
     */
    public function show($id)
    {
        $data = HasilUjiLabMaturasi::with('maturasi')->find($id);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // 🔥 Format Sesuai Model Android (TIDAK PERLU KONVERSI STRING NAMA)
        $formatted = [
            'id'          => $data->id_hasil_uji_lab_maturasi,
            'tanggal'     => $data->tanggal,
            'id_maturasi' => $data->id_maturasi, // 🔥 Kirim ID nya untuk set Spinner
            'no_kamar'    => $data->maturasi ? $data->maturasi->uraian : '-', 
            'k3'          => $data->k3 !== null ? (string)$data->k3 : null,
            'po'          => $data->po !== null ? (string)$data->po : null,
            'pa'          => $data->pa !== null ? (string)$data->pa : null,
            'pri'         => $data->pri !== null ? (string)$data->pri : null,
        ];

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    /**
     * [UPDATE] Simpan Perubahan (SINKRON WEB ADMIN)
     */
    public function update(Request $request, $id)
    {
        $hasilUji = HasilUjiLabMaturasi::find($id);
        
        if (!$hasilUji) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi identik dengan fungsi Store
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'id_maturasi' => 'required|exists:maturasi,id_maturasi', // 🔥 Wajib pakai ID
            'k3'          => 'nullable|numeric',
            'po'          => 'nullable|numeric',
            'pa'          => 'nullable|numeric',
            'pri'         => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // Update Database langsung dengan data tervalidasi
            $hasilUji->update($validator->validated());

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui'], 200);

        } catch (\Exception $e) {
            Log::error("API Update Uji Maturasi Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [DESTROY] Menghapus Data
     */
    public function destroy($id)
    {
        try {
            $hasilUji = HasilUjiLabMaturasi::find($id);
            
            if ($hasilUji) {
                $hasilUji->delete();
                return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
            }
            
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            
        } catch (\Exception $e) {
            Log::error("API Hapus Uji Maturasi Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data'], 500);
        }
    }
}