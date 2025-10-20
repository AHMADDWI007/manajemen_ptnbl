<?php

namespace App\Http\Controllers;

use App\Models\HasilUjiMaturasi; // Pastikan Model diimpor
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HasilUjiMaturasiController extends Controller
{
    public function index()
    {
        $data_maturasi = HasilUjiMaturasi::orderBy('tanggal', 'desc')->get();
        // Pastikan nama view ini cocok dengan nama file Anda
        return view('Pengolahan.Hasil_Uji_Maturasi', compact('data_maturasi'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255',
            'k3' => 'nullable|numeric',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiMaturasi::create($validator->validated());

        return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data hasil uji maturasi berhasil ditambahkan!');
    }

    public function show($id)
    {
        $data = HasilUjiMaturasi::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiMaturasi::findOrFail($id);
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255',
            'k3' => 'nullable|numeric',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUji = HasilUjiMaturasi::findOrFail($id);
        $hasilUji->update($validator->validated());

        return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $hasilUji = HasilUjiMaturasi::findOrFail($id);
        $hasilUji->delete();
        return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data berhasil dihapus!');
    }
}

