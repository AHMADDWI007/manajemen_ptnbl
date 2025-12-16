<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar; 
use Illuminate\Validation\Rule;

class HasilUjiBokarController extends Controller
{
    public function index()
    {
        $data_lab = HasilUjiLabBokar::orderBy('tanggal', 'desc')->get();
        return view('DataLaboratorium.hasil-uji-bokar', compact('data_lab'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        HasilUjiLabBokar::create($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    /**
     * [PERBAIKAN] Gunakan $id biasa, lalu cari manual
     */
    public function show($id)
    {
        $data = HasilUjiLabBokar::find($id);
        return response()->json($data);
    }

    /**
     * [PERBAIKAN] Gunakan $id biasa, lalu cari manual
     */
    public function edit($id)
    {
        $data = HasilUjiLabBokar::find($id);
        
        // Cek jika data tidak ditemukan (opsional, biar aman)
        if(!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    /**
     * [PERBAIKAN] Gunakan $id biasa
     */
    public function update(Request $request, $id)
    {
        // 1. Cari dulu datanya
        $hasilUjiLabBokar = HasilUjiLabBokar::find($id);

        if(!$hasilUjiLabBokar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => [
                'required',
                'string',
                'max:100',
                // Perhatikan pemanggilan ID di sini
                Rule::unique('hasil_uji_lab_bokar')->ignore($hasilUjiLabBokar->id),
            ],
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        $hasilUjiLabBokar->update($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    /**
     * [PERBAIKAN] Gunakan $id biasa
     */
    public function destroy($id)
    {
        $hasilUjiLabBokar = HasilUjiLabBokar::find($id);
        
        if ($hasilUjiLabBokar) {
            $hasilUjiLabBokar->delete();
            return redirect()->route('hasil-uji-bokar.index') 
                             ->with('success', 'Data hasil uji lab berhasil dihapus.');
        }

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('error', 'Data gagal dihapus / tidak ditemukan.');
    }
}