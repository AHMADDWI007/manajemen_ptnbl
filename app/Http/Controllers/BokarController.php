<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bokar;

class BokarController extends Controller
{
    // Tampilkan semua data
    public function index()
    {
        $data_bokar = Bokar::latest()->get();
        return view('pengolahan.data_bokar', compact('data_bokar'));
    }

    // Simpan data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'berat_penuh' => 'required|numeric|min:0',
            'berat_truk' => 'required|numeric|min:0',
        ]);

        $berat_muatan = $request->berat_penuh - $request->berat_truk;

        Bokar::create([
            'tanggal' => $request->tanggal,
            'berat_penuh' => $request->berat_penuh,
            'berat_truk' => $request->berat_truk,
            'berat_muatan' => $berat_muatan,
        ]);

        return redirect()->route('bokar.index')->with('success', 'Data bokar berhasil ditambahkan.');
    }
}
