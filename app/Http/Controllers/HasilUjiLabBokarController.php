<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar;
use Illuminate\Validation\Rule;

class HasilUjiLabBokarController extends Controller
{
    /**
     * Menampilkan daftar semua data hasil uji lab bokar.
     */
    public function index()
    {
        // PERUBAHAN DI SINI: Nama variabel diubah menjadi $data_lab
        $data_lab = HasilUjiLabBokar::latest()->get();
        
        // Sekarang compact('data_lab') akan berfungsi dengan benar karena variabelnya ada
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

        // PERUBAHAN DI SINI: Mengarahkan kembali ke halaman index setelah update
        return redirect()->route('hasil-uji-lab-bokar.index')->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    /**
     * Menghapus data dari database.
     */
    public function destroy(HasilUjiLabBokar $hasilUjiLabBokar)
    {
        $hasilUjiLabBokar->delete();

        return redirect()->back()->with('success', 'Data hasil uji lab berhasil dihapus.');
    }
}