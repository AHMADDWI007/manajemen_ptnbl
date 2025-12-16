<?php

namespace App\Http\Controllers\DataProduksi;

use App\Http\Controllers\Controller;
use App\Models\ProduksiSir20;
use App\Models\Maturasi; // ✅ 1. Import Model Maturasi
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProduksiSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $history = ProduksiSir20::orderBy('tanggal', 'desc')->get();
        
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        $data = ProduksiSir20::whereDate('tanggal', $selectedDate)->first();

        // ✅ 2. Ambil Daftar Maturasi yang Stok-nya > 0 (Hanya bak aktif)
        // KODE BARU (Tampilkan semua bak walau stok 0)
        $daftar_maturasi = Maturasi::orderBy('id')->get();

        // ✅ 3. Kirim variabel $daftar_maturasi ke view
        return view('DataProduksi.produksi-sir20', compact('history', 'data', 'selectedDate', 'daftar_maturasi'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
        ]);

        $input = $request->except('_token');

        // ✅ 4. KONVERSI ARRAY KE STRING (PENTING!)
        // Karena dropdown multiple mengirim data sebagai array ['Bak 1', 'Bak 2']
        // Kita ubah jadi string "Bak 1, Bak 2" untuk disimpan di database
        if (isset($input['remah_ruang_maturasi']) && is_array($input['remah_ruang_maturasi'])) {
            $input['remah_ruang_maturasi'] = implode(', ', $input['remah_ruang_maturasi']);
        }

        // Bersihkan data null
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

        // Hitung Kg Press
        $jml_bales = (float) ($input['jml_bales_press'] ?? 0);
        $input['kg_press'] = $jml_bales * 35;

        ProduksiSir20::updateOrCreate(
            ['tanggal' => $request->tanggal],
            $input
        );

        return redirect()->back()->with('success', 'Laporan Produksi Harian berhasil disimpan!');
    }
}