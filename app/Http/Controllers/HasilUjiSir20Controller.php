<?php

namespace App\Http\Controllers;

use App\Models\HasilUjiSIR20; // Model baru untuk SIR 20
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HasilUjiSIR20Controller extends Controller
{
    public function index()
    {
        $data_sir_20 = HasilUjiSIR20::orderBy('tanggal', 'desc')->get();
        return view('Pengolahan.hasil_uji_sir_20', compact('data_sir_20'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_palet' => 'required|string|max:255',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
            'dirt' => 'nullable|numeric',
            'ash' => 'nullable|numeric',
            'vm' => 'nullable|numeric',
            'money' => 'nullable|numeric',
            'nitrogen' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiSIR20::create($validator->validated());

        return redirect()->route('hasil_uji_sir_20.index')->with('success', 'Data hasil uji SIR 20 berhasil ditambahkan!');
    }

    public function show($id)
    {
        $data = HasilUjiSIR20::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiSIR20::findOrFail($id);
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_palet' => 'required|string|max:255',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
            'dirt' => 'nullable|numeric',
            'ash' => 'nullable|numeric',
            'vm' => 'nullable|numeric',
            'money' => 'nullable|numeric',
            'nitrogen' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUji = HasilUjiSIR20::findOrFail($id);
        $hasilUji->update($validator->validated());

        return redirect()->route('hasil_uji_sir_20.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $hasilUji = HasilUjiSIR20::findOrFail($id);
        $hasilUji->delete();
        return redirect()->route('hasil_uji_sir_20.index')->with('success', 'Data berhasil dihapus!');
    }
}
