<?php

namespace App\Http\Controllers; // Pastikan namespace ini sesuai struktur folder Anda

use Illuminate\Http\Request;
use App\Models\HasilUjiLabBokar; // Pastikan model ini ada dan benar path-nya
use Illuminate\Validation\Rule;

// PERBAIKAN: Ubah nama class di sini agar cocok dengan nama file
class HasilUjiBokarController extends Controller
{
    /**
     * Menampilkan daftar data hasil uji lab bokar.
     * Mengirim semua data ke view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil semua data dari model, urutkan berdasarkan tanggal terbaru
        $data_lab = HasilUjiLabBokar::orderBy('tanggal', 'desc')->get();

        // Kembalikan view beserta data
        // Pastikan path 'Pengolahan.hasil_uji_lab_bokar' benar
        return view('Pengolahan.hasil_uji_bokar', compact('data_lab'));
    }

    /**
     * Menyimpan data baru hasil uji lab bokar ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validasi input dari form modal tambah
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            // Pastikan 'no_sampel' unik di tabel 'hasil_uji_lab_bokar'
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0', // 'nullable' berarti boleh kosong
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0', // Pastikan nama kolom 'ask' atau 'ash' di DB?
            'po'        => 'nullable|numeric|min:0',
            'pa'        => 'nullable|numeric|min:0',
            'pri'       => 'nullable|numeric|min:0',
        ]);

        // Buat record baru di database menggunakan data yang sudah divalidasi
        HasilUjiLabBokar::create($validated);

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('hasil_uji_lab_bokar.index')
                         ->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    /**
     * Mengambil satu data spesifik untuk ditampilkan di modal Detail (via AJAX).
     * Menggunakan Route Model Binding ($hasilUjiLabBokar otomatis di-inject).
     *
     * @param  \App\Models\HasilUjiLabBokar  $hasilUjiLabBokar
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(HasilUjiLabBokar $hasilUjiLabBokar) // Nama parameter $hasilUjiLabBokar HARUS cocok dengan {parameter} di route resource
    {
        // Langsung return data sebagai JSON
        return response()->json($hasilUjiLabBokar);
    }

    /**
     * Mengambil satu data spesifik untuk ditampilkan di form modal Edit (via AJAX).
     * Menggunakan Route Model Binding.
     *
     * @param  \App\Models\HasilUjiLabBokar  $hasilUjiLabBokar
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(HasilUjiLabBokar $hasilUjiLabBokar) // Nama parameter $hasilUjiLabBokar HARUS cocok
    {
        // Langsung return data sebagai JSON
        return response()->json($hasilUjiLabBokar);
    }


    /**
     * Memperbarui data yang sudah ada di database.
     * Menggunakan Route Model Binding.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HasilUjiLabBokar  $hasilUjiLabBokar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, HasilUjiLabBokar $hasilUjiLabBokar)
    {
        // Validasi input dari form modal edit
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => [
                'required',
                'string',
                'max:100',
                // Pastikan 'no_sampel' unik, tapi abaikan ID data yang sedang diedit
                Rule::unique('hasil_uji_lab_bokar')->ignore($hasilUjiLabBokar->id),
            ],
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0', // Pastikan nama kolom 'ask' atau 'ash' di DB?
            'po'        => 'nullable|numeric|min:0',
            'pa'        => 'nullable|numeric|min:0',
            'pri'       => 'nullable|numeric|min:0',
        ]);

        // Update record yang ada menggunakan data yang sudah divalidasi
        $hasilUjiLabBokar->update($validated);

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('hasil_uji_lab_bokar.index')
                         ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    /**
     * Menghapus data dari database.
     * Menggunakan Route Model Binding.
     *
     * @param  \App\Models\HasilUjiLabBokar  $hasilUjiLabBokar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(HasilUjiLabBokar $hasilUjiLabBokar)
    {
        // Hapus record dari database
        $hasilUjiLabBokar->delete();

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('hasil_uji_lab_bokar.index')
                         ->with('success', 'Data hasil uji lab berhasil dihapus.');
    }
}