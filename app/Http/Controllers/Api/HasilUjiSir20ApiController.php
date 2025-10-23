<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiSir20;

class HasilUjiSir20ApiController extends Controller
{
    public function store(Request $request)
    {
        // ✅ Validasi data dari aplikasi mobile
        $validatedData = $request->validate([
            // ✅ PERBAIKAN: Tambahkan validasi untuk 'tanggal'
            'tanggal'   => 'required|date_format:Y-m-d',
            'jenis_kemasan' => 'required|string|max:255',
            'no_palet'  => 'required|string|max:100',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ash'       => 'required|numeric', // kadar abu
            'vm'        => 'required|numeric', // zat menguap
            'money'     => 'required|numeric', // viskositas mooney
            'nitrogen'  => 'required|numeric',
        ]);

        try {
            // ✅ Simpan ke database
            HasilUjiSir20::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Data Uji SIR 20 berhasil disimpan.'
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
            $data = HasilUjiSir20::all();

            return response()->json([
                'success' => true,
                'message' => 'Data hasil uji SIR 20 berhasil diambil.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = HasilUjiSir20::findOrFail($id);
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
     * (UPDATE) Menyimpan perubahan data.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            // ✅ PERBAIKAN: Tambahkan validasi untuk 'tanggal'
            'tanggal'   => 'required|date_format:Y-m-d',
             // ✅ PERBAIKAN: Tambahkan validasi untuk jenis_kemasan
            'jenis_kemasan' => 'required|string|max:255',
            'no_palet'  => 'required|string|max:100|unique:hasil_uji_sir_20,no_palet,' . $id,
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ash'       => 'required|numeric',
            'vm'        => 'required|numeric',
            'money'     => 'required|numeric',
            'nitrogen'  => 'required|numeric',
        ]);

        try {
            $data = HasilUjiSir20::findOrFail($id);
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
     * (DELETE) Menghapus data.
     */
    public function destroy($id)
    {
        try {
            $data = HasilUjiSir20::findOrFail($id);
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
