<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BahanProsesApiController extends Controller
{
    private $masterUraian = [
        'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
        'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
        'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
    ];

    public function index(Request $request)
    {
        try {
            $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
            
            // Logic Recalculate Flow (Simplified for API read)
            // Idealnya logic ini di-service agar tidak duplikat dengan Web Controller
            // Disini kita ambil data yang sudah ada saja
            $data = BahanProses::whereDate('tanggal', $date)->get();
            
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Simpan Rektif user dari Android
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'uraian'  => 'required|string',
            'rekfif'  => 'nullable|numeric'
        ]);

        try {
            BahanProses::updateOrCreate(
                ['tanggal' => $validated['tanggal'], 'uraian' => $validated['uraian']],
                ['rekfif' => $validated['rekfif'] ?? 0]
            );
            // Note: Recalculate Flow harusnya dipanggil disini jika logicnya di-share
            return response()->json(['success' => true, 'message' => 'Rektif Disimpan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}