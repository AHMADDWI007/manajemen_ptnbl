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
            $data = PengolahanBasah::orderBy('tanggal', 'desc')->get();
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d',
                'jenis'         => 'required|string|in:PT,DS,INHUT',
                'bak_maturasi'  => 'required|string|max:255',
                'berat_truck'   => 'required|numeric|min:0',
                'berat_timbang' => 'required|numeric|min:0',
            ]);

            // Hitung Netto Basah otomatis
            $netto_basah = $request->berat_timbang - $request->berat_truck;

            $basah = PengolahanBasah::create(array_merge($validatedData, [
                'netto_basah'  => $netto_basah,
                'k3'           => null,
                'netto_kering' => null,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Data Timbang Bokar berhasil disimpan.',
                'data'    => $basah
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store Timbang Bokar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        return response()->json(['success' => true, 'data' => PengolahanBasah::findOrFail($id)]);
    }

    public function destroy($id) {
        PengolahanBasah::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}