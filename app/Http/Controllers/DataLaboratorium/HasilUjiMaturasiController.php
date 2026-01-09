<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabMaturasi; // 🔥 [PERBAIKAN] Nama Model Baru
use App\Models\Maturasi; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; 
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HasilUjiMaturasiController extends Controller
{
    public function index(): View
    {
        // 🔥 [PERBAIKAN] Gunakan Model Baru & Eager Loading 'maturasi'
        $data_maturasi = HasilUjiLabMaturasi::with('maturasi')
                                            ->orderBy('tanggal', 'desc')
                                            ->get();

        // Ambil hanya bak yang ada stok untuk dropdown
        // 🔥 [PERBAIKAN] Order by PK 'id_maturasi'
        $bak_maturasi = Maturasi::where('stok_akhir', '>', 0)
            ->orderBy('id_maturasi') 
            ->get();

        return view('DataLaboratorium.hasil-uji-maturasi', compact('data_maturasi', 'bak_maturasi'));
    }

    public function store(Request $request): RedirectResponse
    {
        // 🔥 PERBAIKAN: Validasi id_maturasi (bukan no_kamar/string)
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            // 🔥 [PERBAIKAN] Validasi FK 'id_maturasi'
            'id_maturasi' => 'required|exists:maturasi,id_maturasi', 
            'k3'          => 'nullable|numeric',
            'po'          => 'nullable|numeric',
            'pa'          => 'nullable|numeric',
            'pri'         => 'nullable|numeric',
        ],[
            'id_maturasi.exists' => 'Bak Maturasi tidak ditemukan.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Simpan data
        HasilUjiLabMaturasi::create($validator->validated());

        return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data hasil uji maturasi berhasil ditambahkan!');
    }

    public function show($id): JsonResponse
    {
        // 🔥 [PERBAIKAN] Gunakan Model Baru (find otomatis cari di PK custom)
        $data = HasilUjiLabMaturasi::with('maturasi')->find($id);
        
        if(!$data) return response()->json(['error' => 'Data tidak ditemukan'], 404);
        
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = HasilUjiLabMaturasi::with('maturasi')->find($id);
        
        if(!$data) return response()->json(['error' => 'Data tidak ditemukan'], 404);
        
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // 🔥 [PERBAIKAN] Cari data dulu
        $hasilUji = HasilUjiLabMaturasi::find($id);
        
        if(!$hasilUji) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'id_maturasi' => 'required|exists:maturasi,id_maturasi', // 🔥 Validasi FK
            'k3'          => 'nullable|numeric',
            'po'          => 'nullable|numeric',
            'pa'          => 'nullable|numeric',
            'pri'         => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $hasilUji->update($validator->validated());

        return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $hasilUji = HasilUjiLabMaturasi::find($id);
            
            if ($hasilUji) {
                $hasilUji->delete();
                return redirect()->route('hasil-uji-maturasi.index')->with('success', 'Data berhasil dihapus!');
            }
            
            return redirect()->route('hasil-uji-maturasi.index')->with('error', 'Data tidak ditemukan.');
            
        } catch (\Exception $e) {
             Log::error("Gagal menghapus hasil uji: " . $e->getMessage());
             return redirect()->route('hasil-uji-maturasi.index')->with('error', 'Gagal menghapus data.');
        }
    }
}