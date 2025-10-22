<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class HasilUjiLabBokarController extends Controller
{
    /**
     * Menampilkan SEMUA daftar data hasil uji lab bokar.
     * Filter akan dilakukan oleh DataTables di sisi klien.
     */
    public function index()
    {
        // Ambil SEMUA data, diurutkan berdasarkan tanggal terbaru
        $data_lab = HasilUjiLabBokar::orderBy('tanggal', 'desc')->get();

        // Kirim semua data ke view
        return view('Pengolahan.hasil_uji_lab_bokar', compact('data_lab'));
    }

    /**
     * Menyimpan data baru ke dalam database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
        ]);

        HasilUjiLabBokar::create($validated);

        return redirect()->back()->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    /**
     * Memperbarui data yang ada di database.
     */
    public function update(Request $request, HasilUjiLabBokar $hasilUjiLabBokar)
    {
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => [
                'required',
                'string',
                'max:100',
                Rule::unique('hasil_uji_lab_bokar')->ignore($hasilUjiLabBokar->id),
            ],
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
        ]);

        $hasilUjiLabBokar->update($validated);

        return redirect()
            ->route('hasil_uji_lab_bokar.index') // Pastikan 'hasil_uji_lab_bokar.index' adalah nama route Anda
            ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    public function show($id)
    {
        $data = HasilUjiLabBokar::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiLabBokar::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Menghapus data dari database.
     */
    public function destroy(HasilUjiLabBokar $hasilUjiLabBokar)
    {
        $hasilUjiLabBokar->delete();

        return redirect()
            ->back()
            ->with('success', 'Data hasil uji lab berhasil dihapus.');
    }
}