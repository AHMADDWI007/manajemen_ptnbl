<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar; // Pastikan model di-import
use Illuminate\Validation\Rule;
use Carbon\Carbon; // Carbon tidak digunakan di sini, bisa dihapus jika tidak perlu

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
        // TAMBAHKAN VALIDASI UNTUK PO, PA, PRI
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
            'po'        => 'nullable|numeric|min:0', // Tambahkan validasi po
            'pa'        => 'nullable|numeric|min:0', // Tambahkan validasi pa
            'pri'       => 'nullable|numeric|min:0', // Tambahkan validasi pri
        ]);

        HasilUjiLabBokar::create($validated);

        // Redirect ke index agar notifikasi terlihat
        return redirect()->route('hasil_uji_lab_bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    /**
     * Mengambil data untuk modal Detail (Gunakan Route Model Binding).
     */
    public function show(HasilUjiLabBokar $hasilUjiLabBokar) // Ganti $id
    {
        // Tidak perlu findOrFail, Laravel sudah melakukannya
        return response()->json($hasilUjiLabBokar);
    }

    /**
     * Mengambil data untuk modal Edit (Gunakan Route Model Binding).
     */
    public function edit(HasilUjiLabBokar $hasilUjiLabBokar) // Ganti $id
    {
        // Tidak perlu findOrFail
        return response()->json($hasilUjiLabBokar);
    }


    /**
     * Memperbarui data yang ada di database.
     */
    public function update(Request $request, HasilUjiLabBokar $hasilUjiLabBokar)
    {
         // TAMBAHKAN VALIDASI UNTUK PO, PA, PRI
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
            'po'        => 'nullable|numeric|min:0', // Tambahkan validasi po
            'pa'        => 'nullable|numeric|min:0', // Tambahkan validasi pa
            'pri'       => 'nullable|numeric|min:0', // Tambahkan validasi pri
        ]);

        $hasilUjiLabBokar->update($validated);

        return redirect()->route('hasil_uji_lab_bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    /**
     * Menghapus data dari database.
     */
    public function destroy(HasilUjiLabBokar $hasilUjiLabBokar)
    {
        $hasilUjiLabBokar->delete();

        // Redirect ke index agar notifikasi terlihat
        return redirect()->route('hasil_uji_lab_bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil dihapus.');
    }
}