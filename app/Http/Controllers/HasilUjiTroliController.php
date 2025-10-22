<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HasilUjiTroli;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // <-- DITAMBAHKAN untuk validasi unik

class HasilUjiTroliController extends Controller
{
    /**
     * Menampilkan semua data.
     */
    public function index()
    {
        $data_troli = HasilUjiTroli::orderBy('tanggal', 'desc')->get();
        return view('Pengolahan.hasil_uji_troli', compact('data_troli'));
    }

    /**
     * Menyimpan data baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'         => 'required|date',
            // Tambahkan validasi unik untuk no_trolly
            'no_trolly'       => 'required|string|max:255|unique:hasil_uji_troli,no_trolly',
            'k3'              => 'nullable|numeric',
            'po'              => 'nullable|numeric',
            'pa'              => 'nullable|numeric',
            'pri'             => 'nullable|numeric',
            'jam_sample'      => 'nullable|date_format:H:i',
            'lama_pengeringan' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiTroli::create($validator->validated());

        // PERBAIKAN: Redirect ke nama route yang benar
        return redirect()->route('hasil-uji-troli.index')
                         ->with('success', 'Data hasil uji troli berhasil ditambahkan!');
    }

    // ==========================================================
    // FUNGSI AKSI BARU DITAMBAHKAN
    // ==========================================================
    
    /**
     * Mengambil data untuk modal Detail.
     */
    public function show($id)
    {
        $data = HasilUjiTroli::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Mengambil data untuk modal Edit.
     */
    public function edit($id)
    {
        $data = HasilUjiTroli::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Memperbarui data di database.
     */
    public function update(Request $request, $id)
    {
        $data = HasilUjiTroli::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tanggal'         => 'required|date',
            'no_trolly'       => [
                'required',
                'string',
                'max:255',
                // Pastikan no_trolly unik, kecuali untuk data ini sendiri
                Rule::unique('hasil_uji_troli')->ignore($data->id),
            ],
            'k3'              => 'nullable|numeric',
            'po'              => 'nullable|numeric',
            'pa'              => 'nullable|numeric',
            'pri'             => 'nullable|numeric',
            'jam_sample'      => 'nullable|date_format:H:i',
            'lama_pengeringan' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data->update($validator->validated());

        return redirect()->route('hasil_uji_troli.index')
                         ->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Menghapus data dari database.
     */
    public function destroy($id)
    {
        $data = HasilUjiTroli::findOrFail($id);
        $data->delete();
        
        return redirect()->route('hasil_uji_troli.index')
                         ->with('success', 'Data berhasil dihapus!');
    }
}