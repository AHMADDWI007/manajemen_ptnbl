<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar;

class HasilUjiLabBokarApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiLabBokar::latest()->get()]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string',
            'no_sampel' => 'required|string|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ask'       => 'required|numeric',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
        ]);

        try {
            $data = HasilUjiLabBokar::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function show($id) {
        $data = HasilUjiLabBokar::where('id_hasil_uji_lab_bokar', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id) {
        $data = HasilUjiLabBokar::findOrFail($id);
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string',
            // 🔥 PERBAIKAN: Sebutkan nama kolom PK di parameter ke-3 unique
            'no_sampel' => 'required|string|unique:hasil_uji_lab_bokar,no_sampel,' . $id . ',id_hasil_uji_lab_bokar',
            'k3'        => 'required|numeric',
            'dirt'      => 'required|numeric',
            'ask'       => 'required|numeric',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
        ]);
        $data->update($validated);
        return response()->json(['success' => true, 'message' => 'Diperbarui']);
    }

    public function destroy($id) {
        $data = HasilUjiLabBokar::where('id_hasil_uji_lab_bokar', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Dihapus']);
    }
}