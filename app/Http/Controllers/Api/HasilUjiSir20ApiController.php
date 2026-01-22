<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabSir20;

class HasilUjiSir20ApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiLabSir20::latest('tanggal')->get()]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet', // Cek unique create
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'required|numeric',
            'ash'           => 'required|numeric',
            'vm'            => 'required|numeric',
            'money'         => 'required|numeric',
            'nitrogen'      => 'required|numeric',
        ]);

        try {
            $data = HasilUjiLabSir20::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔥 SHOW
    public function show($id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $data]);
    }

    // 🔥 UPDATE
    public function update(Request $request, $id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();

        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            // 🔥 PERHATIKAN LOGIKA UNIQUE UPDATE: except id, idColumn
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet,'.$id.',id_hasil_uji_lab_sir_20',
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'required|numeric',
            'ash'           => 'required|numeric',
            'vm'            => 'required|numeric',
            'money'         => 'required|numeric',
            'nitrogen'      => 'required|numeric',
        ]);

        $data->update($validated);
        return response()->json(['success' => true, 'message' => 'Diperbarui']);
    }

    // 🔥 DESTROY
    public function destroy($id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Dihapus']);
    }
}