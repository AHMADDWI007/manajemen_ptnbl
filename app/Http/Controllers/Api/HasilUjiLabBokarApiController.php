<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar;
use Carbon\Carbon;

class HasilUjiLabBokarApiController extends Controller
{
    // 🔥 PERBAIKAN: Menangkap Request dan mem-filter berdasarkan tanggal
    public function index(Request $request) {
        $query = HasilUjiLabBokar::query();

        // Jika Android mengirimkan parameter 'date', filter datanya!
        if ($request->has('date')) {
            $date = Carbon::parse($request->query('date'))->format('Y-m-d');
            $query->whereDate('tanggal', $date);
        }

        $data = $query->orderBy('tanggal', 'desc')->get();

        return response()->json(['success' => true, 'data' => $data]);
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