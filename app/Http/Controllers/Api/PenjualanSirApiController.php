<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PenjualanSir20;
use Carbon\Carbon;

class PenjualanSirApiController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $data = PenjualanSir20::whereDate('tanggal', $date)->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'hari_ini' => 'required|numeric'
        ]);

        try {
            PenjualanSir20::updateOrCreate(
                ['tanggal' => $validated['tanggal'], 'uraian' => $validated['uraian']],
                ['hari_ini' => $validated['hari_ini']]
            );
            return response()->json(['success' => true, 'message' => 'Penjualan Tersimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}