<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabTroli;
use Carbon\Carbon;

class HasilUjiTroliApiController extends Controller
{
    // 🔥 PERBAIKAN: Menangkap Request dan mem-filter berdasarkan tanggal
    public function index(Request $request) {
        $query = HasilUjiLabTroli::query();

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
            'no_trolly' => 'required|string',
            'k3'        => 'required|numeric',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            'jam_sample'=> 'nullable|string'
        ]);

        try {
            $data = HasilUjiLabTroli::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔥 SHOW (Detail)
    public function show($id) {
        $data = HasilUjiLabTroli::where('id_hasil_uji_lab_troli', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $data]);
    }

    // 🔥 UPDATE
    public function update(Request $request, $id) {
        $data = HasilUjiLabTroli::where('id_hasil_uji_lab_troli', $id)->firstOrFail();

        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'no_trolly' => 'required|string',
            'k3'        => 'required|numeric',
            'po'        => 'required|numeric',
            'pa'        => 'required|numeric',
            'pri'       => 'required|numeric',
            'jam_sample'=> 'nullable|string'
        ]);

        $data->update($validated);
        return response()->json(['success' => true, 'message' => 'Diperbarui']);
    }

    // 🔥 DESTROY (Hapus)
    public function destroy($id) {
        $data = HasilUjiLabTroli::where('id_hasil_uji_lab_troli', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Dihapus']);
    }
}