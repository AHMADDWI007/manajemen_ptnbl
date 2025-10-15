<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar;

class HasilUjiLabBokarController extends Controller
{
    // Tampilkan data
    public function index()
    {
        $data_lab = HasilUjiLabBokar::latest()->get();
        return view('Pengolahan.hasil_uji_lab_bokar', compact('data_lab'));
    }

    // Simpan data baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:50',
            'hasil_uji' => 'nullable|string|max:255',
            'status' => 'required|string|max:50'
        ]);

        HasilUjiLabBokar::create($validated);

        return redirect()->back()->with('success', 'Data hasil uji lab berhasil disimpan');
    }
}
