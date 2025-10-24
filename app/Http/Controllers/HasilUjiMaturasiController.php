<?php

namespace App\Http\Controllers;

use App\Models\HasilUjiMaturasi; // Pastikan Model diimpor
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// Gunakan Route Model Binding untuk konsistensi (opsional tapi disarankan)
// use Illuminate\Validation\Rule;

class HasilUjiMaturasiController extends Controller
{
    public function index()
    {
        $data_maturasi = HasilUjiMaturasi::orderBy('tanggal', 'desc')->get();
        // Pastikan nama view ini cocok: Pengolahan/hasil_uji_maturasi.blade.php
        return view('Pengolahan.hasil_uji_maturasi', compact('data_maturasi'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255', // Mungkin perlu unique?
            'k3' => 'nullable|numeric|min:0',
            'po' => 'nullable|numeric|min:0',
            'pa' => 'nullable|numeric|min:0',
            'pri' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiMaturasi::create($validator->validated());

        // PERBAIKAN: Gunakan underscore '_' sesuai nama route dari resource
        return redirect()->route('hasil_uji_maturasi.index')
                         ->with('success', 'Data hasil uji maturasi berhasil ditambahkan!');
    }

    // Gunakan Route Model Binding agar lebih ringkas & aman
    public function show(HasilUjiMaturasi $hasilUjiMaturasi) // Nama variabel $hasilUjiMaturasi
    {
        // findOrFail tidak perlu lagi
        return response()->json($hasilUjiMaturasi);
    }

    // Gunakan Route Model Binding
    public function edit(HasilUjiMaturasi $hasilUjiMaturasi) // Nama variabel $hasilUjiMaturasi
    {
        // findOrFail tidak perlu lagi
        return response()->json($hasilUjiMaturasi);
    }

    // Gunakan Route Model Binding
    public function update(Request $request, HasilUjiMaturasi $hasilUjiMaturasi) // Nama variabel $hasilUjiMaturasi
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255', // Jika perlu unique on update, gunakan Rule::unique
            'k3' => 'nullable|numeric|min:0',
            'po' => 'nullable|numeric|min:0',
            'pa' => 'nullable|numeric|min:0',
            'pri' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // findOrFail tidak perlu lagi
        $hasilUjiMaturasi->update($validator->validated());

        // PERBAIKAN: Gunakan underscore '_' sesuai nama route dari resource
        return redirect()->route('hasil_uji_maturasi.index')
                         ->with('success', 'Data berhasil diperbarui!');
    }

    // Gunakan Route Model Binding
    public function destroy(HasilUjiMaturasi $hasilUjiMaturasi) // Nama variabel $hasilUjiMaturasi
    {
        // findOrFail tidak perlu lagi
        $hasilUjiMaturasi->delete();

        // PERBAIKAN: Gunakan underscore '_' sesuai nama route dari resource
        return redirect()->route('hasil_uji_maturasi.index')
                         ->with('success', 'Data berhasil dihapus!');
    }
}