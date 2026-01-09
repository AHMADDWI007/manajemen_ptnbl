<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProduksiSir;
use Carbon\Carbon;

class GudangSirApiController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $data = ProduksiSir::whereDate('created_at', $date)->get(); // Sesuaikan kolom tanggal di DB
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'masuk' => 'nullable|numeric',
            'pengiriman' => 'nullable|numeric'
        ]);

        try {
            // Logic update saldo sederhana
            // Disarankan logic saldo disamakan dengan Web Controller
            ProduksiSir::updateOrCreate(
                ['tanggal' => $validated['tanggal'], 'uraian' => $validated['uraian']], // Cek kolom tanggal/created_at
                $validated
            );
            return response()->json(['success' => true, 'message' => 'Data Gudang Tersimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}