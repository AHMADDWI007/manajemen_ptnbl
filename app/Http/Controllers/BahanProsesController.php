<?php

namespace App\Http\Controllers;

use App\Models\BahanProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BahanProsesController extends Controller
{
    /**
     * Menampilkan daftar data.
     */
    public function index()
    {
        // Ganti 'BahanProses' dengan nama model Anda jika berbeda
        $data_produksi = BahanProses::orderBy('id', 'desc')->get();
        // Pastikan path view sudah benar
        return view('Pengolahan.data_produksi', compact('data_produksi'));
    }

    /**
     * Menyimpan data baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|max:255',
            'wip_masuk' => 'nullable|numeric',
            'wip_keluar' => 'nullable|numeric',
            'produksi_sir20' => 'nullable|numeric',
            'rekfif' => 'nullable|string|max:255',
            'saldo_akhir' => 'nullable|numeric',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        BahanProses::create($validator->validated());

        return redirect()->route('bahan-proses.index')->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Mengambil data untuk modal detail (JSON).
     */
    public function show($id)
    {
        $data = BahanProses::findOrFail($id);
        return response()->json($data);
    }
    
    /**
     * Mengambil data untuk modal edit (JSON).
     */
    public function edit($id)
    {
        $data = BahanProses::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Memperbarui data.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|max:255',
            'wip_masuk' => 'nullable|numeric',
            'wip_keluar' => 'nullable|numeric',
            'produksi_sir20' => 'nullable|numeric',
            'rekfif' => 'nullable|string|max:255',
            'saldo_akhir' => 'nullable|numeric',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bahanProses = BahanProses::findOrFail($id);
        $bahanProses->update($validator->validated());

        return redirect()->route('bahan-proses.index')->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Menghapus data.
     */
    public function destroy($id)
    {
        $bahanProses = BahanProses::findOrFail($id);
        $bahanProses->delete();
        return redirect()->route('bahan-proses.index')->with('success', 'Data berhasil dihapus!');
    }
}

