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
        // Validasi input
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            // Cek unique pada kolom no_sampel di tabel hasil_uji_lab_bokar
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0', // Sesuai migrasi: 'ask' (Ash Content)
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        HasilUjiLabBokar::create($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    public function show($id)
    {
        // 🔥 PERBAIKAN: Karena PK custom, find() mungkin tetap bekerja jika model dikonfigurasi benar.
        // Tapi untuk amannya, kita bisa pakai findOrFail atau where().
        $data = HasilUjiLabBokar::find($id);
        
        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiLabBokar::find($id);
        
        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // 1. Cari Data
        $hasilUjiLabBokar = HasilUjiLabBokar::find($id);

        if (!$hasilUjiLabBokar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // 2. Validasi
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => [
                'required',
                'string',
                'max:100',
                // 🔥 PERBAIKAN PENTING: Unique ignore harus mengacu pada KOLOM PRIMARY KEY yang baru
                // Format: Rule::unique('nama_tabel')->ignore($id_value, 'nama_kolom_pk')
                Rule::unique('hasil_uji_lab_bokar')->ignore($hasilUjiLabBokar->id_hasil_uji_lab_bokar, 'id_hasil_uji_lab_bokar'),
            ],
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        // 3. Update
        $hasilUjiLabBokar->update($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

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