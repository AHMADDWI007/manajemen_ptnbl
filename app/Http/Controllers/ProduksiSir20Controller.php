<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProduksiSir20;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProduksiSir20Controller extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data history
        $history = ProduksiSir20::orderBy('tanggal', 'desc')->get();
        
        // 2. Ambil tanggal untuk form (default hari ini)
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // 3. Ambil data hari ini (jika ada, untuk mengisi form edit)
        $data = ProduksiSir20::whereDate('tanggal', $selectedDate)->first();

        // ✅ PERBAIKAN: Kirim variabel $history, $data, dan $selectedDate ke view
        return view('Pengolahan.produksi_sir20', compact('history', 'data', 'selectedDate'));
    }

    // ✅ PERBAIKAN: Tambahkan Method STORE (Wajib untuk Resource Controller)
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
        ]);

        $input = $request->except('_token');

        // Bersihkan data null menjadi 0 untuk kolom angka
        $excludeFields = [
            'tanggal', 'remah_ruang_maturasi', 'remah_tgl_masuk', 
            'dryer_aktual_temp', 'dryer_waktu_cycle', 'dryer_jam_start', 
            'dryer_jam_stop', 'kontaminasi_logam', 'pack_nomor', 'keterangan'
        ];

        foreach ($input as $key => $value) {
            if (is_null($value) && !in_array($key, $excludeFields)) {
                $input[$key] = 0;
            }
        }

        // Hitung Kg Press otomatis (Validasi Server)
        $jml_bales = (float) ($input['jml_bales_press'] ?? 0);
        $input['kg_press'] = $jml_bales * 35;

        ProduksiSir20::updateOrCreate(
            ['tanggal' => $request->tanggal],
            $input
        );

        return redirect()->back()->with('success', 'Data Produksi SIR 20 berhasil disimpan!');
    }
}