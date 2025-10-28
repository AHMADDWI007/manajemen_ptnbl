<?php

namespace App\Http\Controllers;

use App\Models\PengolahanBasah; // <-- PENTING! Gunakan model PengolahanBasah
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HasilUjiBokarDiolahController extends Controller
{
    /**
     * Tampilkan data dari 'pengolahan_basah'
     */
    public function index()
    {
        // Ganti nama variabel agar sesuai dengan view
        $data_diolah = PengolahanBasah::orderBy('tanggal', 'desc')->get();
        
        // Ambil data 'pengolahan_basah' yang K3-nya masih KOSONG
        // untuk mengisi dropdown di modal tambah K3
        $daftar_bak_belum_uji = PengolahanBasah::whereNull('k3')
                                ->orderBy('tanggal', 'desc')
                                ->get();

        return view('Pengolahan.hasil_uji_bokar_diolah', compact('data_diolah', 'daftar_bak_belum_uji'));
    }

    /**
     * Ini BUKAN store (create), tapi UPDATE K3
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Validasi id dari 'pengolahan_basah'
            'pengolahan_basah_id' => 'required|exists:pengolahan_basah,id',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 1. Cari data pengolahan basah berdasarkan ID
        $data = PengolahanBasah::find($request->pengolahan_basah_id);

        if (!$data) {
             return redirect()->back()->withErrors(['error' => 'Data pengolahan basah tidak ditemukan.']);
        }

        // 2. Ambil Netto Basah-nya
        $netto_basah = $data->netto_basah;
        $k3_value = $request->k3;

        // 3. Hitung Netto Kering
        $netto_kering = $netto_basah * ($k3_value / 100);

        // 4. Update data tersebut
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering,
        ]);

        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil disimpan.');
    }

    // Fungsi show, edit, update, destroy sekarang mengarah ke PengolahanBasah
    
    public function show($id)
    {
        $data = PengolahanBasah::find($id); // Ganti ke PengolahanBasah
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = PengolahanBasah::find($id); // Ganti ke PengolahanBasah
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // Fungsi update di halaman ini sekarang MENGEDIT K3
        $validator = Validator::make($request->all(), [
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
             return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = PengolahanBasah::find($id);
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan.']);
        }
        
        $netto_kering = $data->netto_basah * ($request->k3 / 100);

        $data->update([
            'k3' => $request->k3,
            'netto_kering' => $netto_kering
        ]);

        return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id)
    {
        // Hati-hati! Ini akan menghapus data dari 'pengolahan_basah'
        $data = PengolahanBasah::find($id);
        if ($data) {
            $data->delete();
            return redirect()->route('hasil_uji_bokar_diolah.index')->with('success', 'Data berhasil dihapus.');
        }
        return redirect()->route('hasil_uji_bokar_diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }
}