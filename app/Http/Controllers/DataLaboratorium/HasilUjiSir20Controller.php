<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabSIR20; // 🔥 [PERBAIKAN 1] Gunakan Model Baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HasilUjiSIR20Controller extends Controller
{
    public function index()
    {
        $data_sir_20 = HasilUjiLabSIR20::orderBy('tanggal', 'desc')->get();
        return view('DataLaboratorium.hasil-uji-sir20', compact('data_sir_20'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'nullable|string|in:MB5,SW',
            'no_palet'      => [
                'required', 
                'string', 
                'max:255',
                // 🔥 [PERBAIKAN 2] Validasi Unik di Tabel Baru
                'unique:hasil_uji_lab_sir_20,no_palet'
            ],
            'po'            => 'nullable|numeric',
            'pa'            => 'nullable|numeric',
            'pri'           => 'nullable|numeric',
            'dirt'          => 'nullable|numeric',
            'ash'           => 'nullable|numeric',
            'vm'            => 'nullable|numeric',
            'money'         => 'nullable|numeric',
            'nitrogen'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HasilUjiLabSIR20::create($validator->validated());

        return redirect()->route('hasil-uji-sir20.index')
                         ->with('success', 'Data hasil uji SIR 20 berhasil ditambahkan!');
    }

    public function show($id)
    {
        // 🔥 [PERBAIKAN 3] Gunakan PK custom
        $data = HasilUjiLabSIR20::find($id); // find($id) otomatis cari di PK model

        if(!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiLabSIR20::find($id);

        if(!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $hasilUji = HasilUjiLabSIR20::find($id);

        if (!$hasilUji) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $validator = Validator::make($request->all(), [
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'nullable|string|in:MB5,SW',
            'no_palet'      => [
                'required', 
                'string', 
                'max:255',
                // 🔥 [PERBAIKAN 4] Validasi Unique Ignore dengan PK yang Benar
                Rule::unique('hasil_uji_lab_sir_20')
                    ->ignore($hasilUji->id_hasil_uji_lab_sir_20, 'id_hasil_uji_lab_sir_20')
            ],
            'po'            => 'nullable|numeric',
            'pa'            => 'nullable|numeric',
            'pri'           => 'nullable|numeric',
            'dirt'          => 'nullable|numeric',
            'ash'           => 'nullable|numeric',
            'vm'            => 'nullable|numeric',
            'money'         => 'nullable|numeric',
            'nitrogen'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUji->update($validator->validated());

        return redirect()->route('hasil-uji-sir20.index')
                         ->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $hasilUji = HasilUjiLabSIR20::find($id);
        
        if ($hasilUji) {
            $hasilUji->delete();
            return redirect()->route('hasil-uji-sir20.index')
                             ->with('success', 'Data berhasil dihapus!');
        }

        return redirect()->route('hasil-uji-sir20.index')
                         ->with('error', 'Data gagal dihapus atau tidak ditemukan.');
    }
}