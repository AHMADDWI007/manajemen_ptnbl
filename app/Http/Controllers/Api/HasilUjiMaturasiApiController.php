<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabMaturasi;
use App\Models\Maturasi;
use Illuminate\Support\Facades\Validator;

class HasilUjiMaturasiApiController extends Controller
{
    public function index() {
        // 🔥 PERBAIKAN: Eager Load 'maturasi' dan Mapping Data
        $data = HasilUjiLabMaturasi::with('maturasi') // Pastikan relasi 'maturasi' ada di Model HasilUjiLabMaturasi
            ->latest('tanggal')
            ->get()
            ->map(function($item) {
                return [
                    'id'       => $item->getKey(),
                    'tanggal'  => $item->tanggal,
                    // 🔥 Ubah 'id_maturasi' menjadi 'no_kamar' (String Uraian)
                    'no_kamar' => $item->maturasi ? $item->maturasi->uraian : '-', 
                    'k3'       => (string)$item->k3, // Cast ke string agar aman di Android
                    'po'       => (string)$item->po,
                    'pa'       => (string)$item->pa,
                    'pri'      => (string)$item->pri,
                ];
            });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request) {
        // 1. Validasi Input
        // Kita cek 'exists:maturasi,uraian' untuk memastikan string dari Android valid
        $validator = Validator::make($request->all(), [
            'tanggal'  => 'required|date',
            'no_kamar' => 'required|string|exists:maturasi,uraian', 
            'k3'       => 'nullable|numeric',
            'po'       => 'nullable|numeric',
            'pa'       => 'nullable|numeric',
            'pri'      => 'nullable|numeric',
        ]);

        // Return error JSON jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // 2. Cari ID Maturasi berdasarkan String 'no_kamar'
            // Android mengirim: "Di Bak Maturasi-1"
            // Kita ambil ID-nya: 1
            $maturasi = Maturasi::where('uraian', $request->no_kamar)->first();

            if (!$maturasi) {
                return response()->json(['success' => false, 'message' => 'Data Bak Maturasi tidak ditemukan di database.'], 404);
            }

            // 3. Simpan ke Database
            // Kita menyusun array manual agar key-nya sesuai kolom database ('id_maturasi')
            HasilUjiLabMaturasi::create([
                'tanggal'     => $request->tanggal,
                'id_maturasi' => $maturasi->id_maturasi, // 🔥 PENTING: Masukkan ID, bukan String
                'k3'          => $request->k3,
                'po'          => $request->po,
                'pa'          => $request->pa,
                'pri'         => $request->pri,
            ]);

            return response()->json(['success' => true, 'message' => 'Data Lab Maturasi berhasil disimpan'], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $data = HasilUjiLabMaturasi::with('maturasi')->find($id);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // 🔥 MAPPING DATA AGAR SESUAI MODEL ANDROID
        $formatted = [
            'id'       => $data->getKey(), // 🔥 Gunakan getKey()
            'tanggal'  => $data->tanggal,
            // Android butuh "no_kamar" berisi String Uraian, bukan ID angka
            'no_kamar' => $data->maturasi ? $data->maturasi->uraian : null, 
            'k3'       => (string)$data->k3,
            'po'       => (string)$data->po,
            'pa'       => (string)$data->pa,
            'pri'      => (string)$data->pri,
        ];

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    /**
     * [UPDATE] Simpan Perubahan
     * Masalah sebelumnya: Langsung update($request->all()) akan error karena 'no_kamar' tidak ada di tabel
     */
    public function update(Request $request, $id)
    {
        // 1. Cari Data Lama
        $hasilUji = HasilUjiLabMaturasi::find($id);
        if (!$hasilUji) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // 2. Validasi
        $validator = Validator::make($request->all(), [
            'tanggal'  => 'required|date',
            'no_kamar' => 'required|string|exists:maturasi,uraian', // Validasi string uraian
            'k3'       => 'nullable|numeric',
            'po'       => 'nullable|numeric',
            'pa'       => 'nullable|numeric',
            'pri'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // 3. Cari ID Maturasi berdasarkan String 'no_kamar'
            // Android kirim: "Di Bak Maturasi-1" -> Kita cari ID-nya
            $maturasi = Maturasi::where('uraian', $request->no_kamar)->first();

            // 4. Update Database
            $hasilUji->update([
                'tanggal'     => $request->tanggal,
                'id_maturasi' => $maturasi->id_maturasi, // 🔥 Masukkan ID, bukan String
                'k3'          => $request->k3,
                'po'          => $request->po,
                'pa'          => $request->pa,
                'pri'         => $request->pri,
            ]);

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui'], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }
}