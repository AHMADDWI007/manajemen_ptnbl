<?php

namespace App\Http\Controllers;

use App\Models\Produksi;
use Illuminate\Http\Request;

class ProduksiController extends Controller
{
    public function index()
    {
        $data_produksi = Produksi::orderBy('tanggal', 'desc')->get();
        return view('Pengolahan.data_produksi', compact('data_produksi'));
    }

    public function create()
    {
        return view('tambah_produksi');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam' => 'required',
            'no_kamar' => 'required|string',
            'no_palet' => 'required|string',
            'status' => 'required|string',
        ]);

        Produksi::create($request->all());

        return redirect()->route('produksi.index')->with('success', 'Data produksi berhasil disimpan!');
    }

    public function edit($id)
    {
        $produksi = Produksi::findOrFail($id);
        return view('edit_produksi', compact('produksi'));
    }

    public function update(Request $request, $id)
    {
        $produksi = Produksi::findOrFail($id);
        $produksi->update($request->all());

        return redirect()->route('produksi.index')->with('success', 'Data produksi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $produksi = Produksi::findOrFail($id);
        $produksi->delete();

        return redirect()->route('produksi.index')->with('success', 'Data berhasil dihapus.');
    }
}
