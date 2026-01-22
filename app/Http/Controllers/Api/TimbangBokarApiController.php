<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengolahanBasah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimbangBokarApiController extends Controller
{
    public function index()
    {
        try {
            // Ambil data beserta relasi maturasi agar nama bak bisa tampil
            $data = PengolahanBasah::with('maturasi')->orderBy('tanggal', 'desc')->get();
            
            // Transformasi data agar field 'bak_maturasi' tersedia flat untuk Android
            $data = $data->map(function($item) {
                $item->bak_maturasi = $item->maturasi ? $item->maturasi->uraian : '-';
                return $item;
            });

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi Input
            $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d',
                'jenis'         => 'required|string|in:PT,DS,INHUT',
                // Android kirim nama string "Bak Maturasi 1", kita butuh ID.
                // OPSI TERBAIK: Android kirim ID_MATURASI, bukan string.
                // TAPI jika Android kirim string, kita harus cari ID-nya dulu.
                // Asumsi: Android kirim STRING nama bak di field 'bak_maturasi'
                'bak_maturasi'  => 'required|string', 
                'berat_truck'   => 'required|numeric|min:0',
                'berat_timbang' => 'required|numeric|min:0',
            ]);

            // Cari ID Maturasi berdasarkan nama (uraian)
            $maturasi = \App\Models\Maturasi::where('uraian', $request->bak_maturasi)->first();
            if (!$maturasi) {
                return response()->json(['success' => false, 'message' => 'Bak Maturasi tidak ditemukan'], 404);
            }

            // Hitung Netto Basah
            $netto_basah = $request->berat_timbang - $request->berat_truck;

            $basah = PengolahanBasah::create([
                'tanggal'       => $request->tanggal,
                'jenis'         => $request->jenis,
                'id_maturasi'   => $maturasi->id_maturasi, // Masukkan ID hasil pencarian
                'berat_truck'   => $request->berat_truck,
                'berat_timbang' => $request->berat_timbang,
                'netto_basah'   => $netto_basah,
                'k3'            => null,
                'netto_kering'  => null,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Data Timbang Bokar berhasil disimpan.', 
                'data'    => $basah
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        $data = PengolahanBasah::with('maturasi')->where('id_pengolahan_basah', $id)->firstOrFail();
        // Flatten bak_maturasi untuk edit form di Android
        $data->bak_maturasi = $data->maturasi ? $data->maturasi->uraian : '';
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id) {
        $data = PengolahanBasah::where('id_pengolahan_basah', $id)->firstOrFail();

        $request->validate([
            'tanggal'       => 'required|date_format:Y-m-d',
            'jenis'         => 'required|string|in:PT,DS,INHUT',
            'bak_maturasi'  => 'required|string',
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:0',
        ]);

        $maturasi = \App\Models\Maturasi::where('uraian', $request->bak_maturasi)->first();
        if (!$maturasi) {
            return response()->json(['success' => false, 'message' => 'Bak Maturasi tidak ditemukan'], 404);
        }

        $netto_basah = $request->berat_timbang - $request->berat_truck;

        $data->update([
            'tanggal'       => $request->tanggal,
            'jenis'         => $request->jenis,
            'id_maturasi'   => $maturasi->id_maturasi,
            'berat_truck'   => $request->berat_truck,
            'berat_timbang' => $request->berat_timbang,
            'netto_basah'   => $netto_basah,
        ]);

        return response()->json(['success' => true, 'message' => 'Data diperbarui']);
    }

    public function destroy($id) {
        $data = PengolahanBasah::where('id_pengolahan_basah', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}