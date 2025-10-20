<?php

namespace App\Http\Controllers;

use App\Models\HasilUjiSir20;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HasilUjiSir20Controller extends Controller
{
    public function index()
    {
        $data_sir_20 = HasilUjiSir20::orderBy('id', 'desc')->get();
        // Pastikan nama view ini cocok dengan nama file Anda
        return view('Pengolahan.Hasil_Uji_SIR_20', compact('data_sir_20'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        HasilUjiSir20::create($validator->validated());

        return redirect()->route('hasil-uji-sir20.index')->with('success', 'Data SIR 20 berhasil ditambahkan!');
    }

    public function show($id)
    {
        $data = HasilUjiSir20::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiSir20::findOrFail($id);
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
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

        $hasilUji = HasilUjiSir20::findOrFail($id);
        $hasilUji->update($validator->validated());

        return redirect()->route('hasil-uji-sir20.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $hasilUji = HasilUjiSir20::findOrFail($id);
        $hasilUji->delete();
        return redirect()->route('hasil-uji-sir20.index')->with('success', 'Data berhasil dihapus!');
    }
}
