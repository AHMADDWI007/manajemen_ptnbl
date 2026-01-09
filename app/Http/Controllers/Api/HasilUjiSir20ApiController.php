<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiSir20;

class HasilUjiSir20ApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiSir20::latest('tanggal')->get()]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            'no_palet'      => 'required|string',
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
            $data = HasilUjiSir20::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}