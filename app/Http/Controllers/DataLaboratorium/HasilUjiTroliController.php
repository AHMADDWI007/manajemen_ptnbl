<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HasilUjiLabTroli; // 🔥 [PERBAIKAN 1] Gunakan Model Baru
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HasilUjiTroliController extends Controller
{
    public function index()
    {
        $data_troli = HasilUjiLabTroli::orderBy('tanggal', 'desc')->get();
        return view('DataLaboratorium.hasil-uji-troli', compact('data_troli'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'          => 'required|date',
            'no_trolly'        => [
                'required',
                'string',
                'max:255',
                // 🔥 [PERBAIKAN 2] Tabel 'hasil_uji_lab_troli', Kolom 'no_trolly'
                'unique:hasil_uji_lab_troli,no_trolly'
            ],
            'k3'               => 'nullable|numeric|min:0',
            'po'               => 'nullable|numeric|min:0',
            'pa'               => 'nullable|numeric|min:0',
            'pri'              => 'nullable|numeric|min:0',
            'jam_sample'       => 'nullable|date_format:H:i',
            'lama_pengeringan' => 'nullable|string', // String karena bisa input "2 Jam" atau angka
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiLabTroli::create($validator->validated());

        return redirect()->route('hasil-uji-troli.index')
                         ->with('success', 'Data hasil uji troli berhasil ditambahkan!');
    }

    public function show($id)
    {
        // 🔥 [PERBAIKAN 3] Gunakan Model Baru
        $data = HasilUjiLabTroli::find($id);
        
        if(!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiLabTroli::find($id);
        
        if(!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // 1. Cari Data
        $hasilUjiTroli = HasilUjiLabTroli::find($id);

        if (!$hasilUjiTroli) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // 2. Validasi
        $validator = Validator::make($request->all(), [
            'tanggal'   => 'required|date',
            'no_trolly' => [
                'required',
                'string',
                'max:255',
                // 🔥 [PERBAIKAN 4] Unique ignore harus pakai PK yang benar
                // Format: ignore($nilai_id, $nama_kolom_pk)
                Rule::unique('hasil_uji_lab_troli')->ignore($hasilUjiTroli->id_hasil_uji_lab_troli, 'id_hasil_uji_lab_troli'),
            ],
            'k3'               => 'nullable|numeric|min:0',
            'po'               => 'nullable|numeric|min:0',
            'pa'               => 'nullable|numeric|min:0',
            'pri'              => 'nullable|numeric|min:0',
            'jam_sample'       => 'nullable|date_format:H:i',
            'lama_pengeringan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUjiTroli->update($validator->validated());

        return redirect()->route('hasil-uji-troli.index')
                         ->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $hasilUjiTroli = HasilUjiLabTroli::find($id);
        
        if ($hasilUjiTroli) {
            $hasilUjiTroli->delete();
            return redirect()->route('hasil-uji-troli.index')
                             ->with('success', 'Data berhasil dihapus!');
        }
        
        return redirect()->route('hasil-uji-troli.index')
                         ->with('error', 'Data gagal dihapus / tidak ditemukan.');
    }
}