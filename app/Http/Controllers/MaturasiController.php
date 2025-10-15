<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maturasi;

class MaturasiController extends Controller
{
    /**
     * Tampilkan semua data maturasi.
     */
    public function index()
    {
        $data_maturasi = Maturasi::all();
        return view('Pengolahan.data_maturasi', compact('data_maturasi'));
    }

    /**
     * Simpan data baru (tanpa hasil lab).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'berat_penuh' => 'required|numeric',
            'berat_truk' => 'required|numeric',
        ]);

        $validated['berat_muatan'] = $validated['berat_penuh'] - $validated['berat_truk'];

        Maturasi::create($validated);

        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }

    /**
     * Form edit data untuk menambahkan hasil lab.
     */
    public function edit($id)
    {
        $maturasi = Maturasi::findOrFail($id);
        return view('edit_maturasi', compact('maturasi'));
    }

    /**
     * Update data maturasi (termasuk hasil lab).
     */
    public function update(Request $request, $id)
    {
        $maturasi = Maturasi::findOrFail($id);
        $maturasi->update($request->all());
        return redirect()->route('maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Hapus data maturasi (opsional).
     */
    public function destroy($id)
    {
        $maturasi = Maturasi::findOrFail($id);
        $maturasi->delete();
        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }
}
