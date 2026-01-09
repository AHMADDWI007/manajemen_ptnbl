<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiMaturasi;
use App\Models\Maturasi;

class HasilUjiMaturasiApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiMaturasi::latest('tanggal')->get()]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'  => 'required|date',
            'no_kamar' => 'required|string|exists:maturasis,uraian',
            'k3'       => 'nullable|numeric',
            'po'       => 'nullable|numeric',
            'pa'       => 'nullable|numeric',
            'pri'      => 'nullable|numeric',
        ]);

        try {
            $hasil = HasilUjiMaturasi::create($validated);

            // Update relasi ID di tabel maturasi
            $maturasi = Maturasi::where('uraian', $request->no_kamar)->first();
            if ($maturasi) {
                // Di sini kita asumsikan kolomnya id_hasil_uji_maturasi (sesuai diskusi terakhir)
                // Jika error kolom, pastikan migrasi sudah jalan
                // $maturasi->id_hasil_uji_maturasi = $hasil->id; 
                // $maturasi->save();
            }

            return response()->json(['success' => true, 'message' => 'Data Lab Maturasi tersimpan'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}