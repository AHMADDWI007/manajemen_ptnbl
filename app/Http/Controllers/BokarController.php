<?php

namespace App\Http\Controllers;

use App\Models\Bokar;
use Illuminate\Http\Request;

class BokarController extends Controller
{
    /**
     * Menampilkan semua data pengolahan bokar.
     */
    public function index()
    {
        $data_basah = Bokar::all();

        $total_ds = Bokar::where('jenis', 'DS')->sum('netto_kering');
    $total_pt = Bokar::where('jenis', 'PT')->sum('netto_kering');
    $jumlah_total = $total_ds + $total_pt;
        return view('pengolahan.data_bokar', compact(
            'data_basah',
            'total_ds',
            'total_pt',
            'jumlah_total'
        ));
    }

    /**
     * Menyimpan data baru (dengan logika otomatis hitung netto).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal'        => 'required|date',
            'bak_maturasi'   => 'required|string|max:255',
            'jenis'          => 'required|string|max:255',
            'berat_truck'    => 'required|numeric|min:0',
            'berat_timbang'  => 'required|numeric|min:0',
            'k3'             => 'required|numeric|min:0',
        ]);

        // 💡 Hitung otomatis:
        $validated['netto_basah']  = $validated['berat_timbang'] - $validated['berat_truck'];
        $validated['netto_kering'] = $validated['netto_basah'] * ($validated['k3'] / 100);

        Bokar::create($validated);

        return redirect()->route('bokar.index')->with('success', '✅ Data berhasil ditambahkan!');
    }

    /**
     * Mengambil data untuk modal edit (AJAX).
     */
    public function edit($id)
    {
        $bokar = Bokar::findOrFail($id);
        return response()->json($bokar);
    }

    /**
     * Mengupdate data (dengan perhitungan otomatis juga).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal'        => 'required|date',
            'bak_maturasi'   => 'required|string|max:255',
            'jenis'          => 'required|string|max:255',
            'berat_truck'    => 'required|numeric|min:0',
            'berat_timbang'  => 'required|numeric|min:0',
            'k3'             => 'required|numeric|min:0',
        ]);

        // 💡 Hitung ulang otomatis
        $validated['netto_basah']  = $validated['berat_timbang'] - $validated['berat_truck'];
        $validated['netto_kering'] = $validated['netto_basah'] * ($validated['k3'] / 100);

        Bokar::findOrFail($id)->update($validated);

        return redirect()->route('bokar.index')->with('success', '✅ Data berhasil diperbarui!');
    }

    /**
     * Menghapus data.
     */
    public function destroy($id)
    {
        Bokar::findOrFail($id)->delete();
        return redirect()->route('bokar.index')->with('success', '🗑️ Data berhasil dihapus!');
    }

    /**
     * Menampilkan detail data (AJAX).
     */
    public function show($id)
    {
        $bokar = Bokar::findOrFail($id);
        return response()->json($bokar);
    }
}
