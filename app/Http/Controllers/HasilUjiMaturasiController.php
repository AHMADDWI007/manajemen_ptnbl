<?php

namespace App\Http\Controllers;

use App\Models\HasilUjiMaturasi;
use App\Models\Maturasi; // 🔹 Pastikan model ini di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // 🔹 Untuk catatan error
use Illuminate\Routing\Controller; // 🔹 Pastikan ini di-import
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HasilUjiMaturasiController extends Controller
{
    public function index(): View
    {
        $data_maturasi = HasilUjiMaturasi::orderBy('tanggal', 'desc')->get();

        // Ambil hanya bak yang ada stok
        $bak_maturasi = Maturasi::where('stok_akhir', '>', 0)
            ->orderBy('uraian')
            ->select('id', 'uraian') 
            ->get();

        return view('Pengolahan.Hasil_Uji_Maturasi', compact('data_maturasi', 'bak_maturasi'));
    }


    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255|exists:maturasis,uraian', // Validasi ke tabel maturasi
            'k3' => 'nullable|numeric',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
        ],[
            'no_kamar.exists' => 'Uraian Bak Maturasi tidak ditemukan di tabel status.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 1. Simpan data uji maturasi DAN DAPATKAN ID-NYA
        $hasilUji = HasilUjiMaturasi::create($validator->validated());

        // 2. Update tabel maturasi
        try {
            $maturasi = Maturasi::where('uraian', $request->no_kamar)->firstOrFail();

            // ==========================================================
            // PERBAIKAN: HANYA SIMPAN ID-NYA
            // (Kita gunakan nama kolom 'id_hasil_uji_maturasi' dari migrasi Anda)
            // ==========================================================
            $maturasi->id_hasil_uji_maturasi = $hasilUji->id; // <-- INI PERBAIKANNYA
            $maturasi->save();
            // ==========================================================

        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi hasil uji maturasi ke tabel maturasi: " . $e->getMessage());
        }

        return redirect()->route('hasil_uji_maturasi.index')->with('success', 'Data hasil uji maturasi berhasil ditambahkan!');
    }

    public function show($id): JsonResponse
    {
        $data = HasilUjiMaturasi::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = HasilUjiMaturasi::findOrFail($id);
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'no_kamar' => 'required|string|max:255|exists:maturasis,uraian', // Validasi
            'k3' => 'nullable|numeric',
            'po' => 'nullable|numeric',
            'pa' => 'nullable|numeric',
            'pri' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUji = HasilUjiMaturasi::findOrFail($id);
        $hasilUji->update($validator->validated());

        // 🔹 Setelah diupdate, sinkronkan lagi ke tabel maturasi
        try {
            $maturasi = Maturasi::where('uraian', $request->no_kamar)->firstOrFail();

            // ==========================================================
            // PERBAIKAN: HANYA SIMPAN ID-NYA
            // ==========================================================
            $maturasi->id_hasil_uji_maturasi = $hasilUji->id; // <-- INI PERBAIKANNYA
            $maturasi->save();
            // ==========================================================
            
        } catch (\Exception $e) {
            Log::error("Gagal update hasil uji maturasi ke tabel maturasi: " . $e->getMessage());
        }

        return redirect()->route('hasil_uji_maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $hasilUji = HasilUjiMaturasi::findOrFail($id);
            
            // Cari maturasi yang terhubung
            $maturasi = Maturasi::where('id_hasil_uji_maturasi', $hasilUji->id)->first();
            
            if ($maturasi) {
                // Reset ID-nya di 'maturasis'
                $maturasi->id_hasil_uji_maturasi = null;
                $maturasi->save();
            }
            
            $hasilUji->delete(); // Hapus log uji
            
        } catch (\Exception $e) {
             Log::error("Gagal menghapus hasil uji: " . $e->getMessage());
             return redirect()->route('hasil_uji_maturasi.index')->with('error', 'Gagal menghapus data.');
        }

        return redirect()->route('hasil_uji_maturasi.index')->with('success', 'Data berhasil dihapus!');
    }
}