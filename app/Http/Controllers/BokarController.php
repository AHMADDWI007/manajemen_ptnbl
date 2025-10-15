<?php

namespace App\Http\Controllers;

use App\Models\Bokar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BokarController extends Controller
{
    /**
     * Menampilkan daftar data pengolahan basah (bokar).
     */
    public function index()
    {
        // Variabel diubah menjadi $data_basah agar sesuai dengan view yang telah kita buat
        $data_basah = Bokar::latest()->get();

        // Mengarahkan ke view yang benar dengan data yang sesuai
        return view('pengolahan.data_bokar', compact('data_basah'));
    }

    /**
     * Menyimpan data baru (biasanya untuk API).
     */
    public function store(Request $request)
    {
        // Validasi disesuaikan dengan kolom baru di model
        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'supplier'     => 'required|string|max:255',
            'berat_basah'  => 'required|numeric|min:0',
            'k3'           => 'required|numeric|min:0',
            'berat_kering' => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
        ]);

        Bokar::create($validated);

        // Untuk web, kembali ke halaman index. Untuk API, biasanya mengembalikan JSON.
        return redirect()->route('bokar.index')->with('success', 'Data pengolahan basah berhasil ditambahkan.');
    }

    /**
     * Menampilkan satu data spesifik (opsional, untuk detail view atau API).
     */
    public function show(Bokar $bokar)
    {
        // Untuk API, Anda bisa mengembalikan data sebagai JSON
        return response()->json($bokar);
    }

    /**
     * Memperbarui data yang ada (biasanya untuk API).
     */
    public function update(Request $request, Bokar $bokar)
    {
        $validated = $request->validate([
            'tanggal'      => 'required|date',
            'supplier'     => 'required|string|max:255',
            'berat_basah'  => 'required|numeric|min:0',
            'k3'           => 'required|numeric|min:0',
            'berat_kering' => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
        ]);

        $bokar->update($validated);

        return redirect()->route('bokar.index')->with('success', 'Data pengolahan basah berhasil diperbarui.');
    }

    /**
     * Menghapus data (biasanya untuk API atau admin).
     */
    public function destroy(Bokar $bokar)
    {
        $bokar->delete();

        return redirect()->route('bokar.index')->with('success', 'Data pengolahan basah berhasil dihapus.');
    }
}
