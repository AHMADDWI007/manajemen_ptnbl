<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiTroli;

class HasilUjiTroliApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiTroli::latest('tanggal')->get()]);
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
            $data = HasilUjiTroli::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}