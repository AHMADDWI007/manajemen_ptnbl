<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Pallet;
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\HasilUjiLabSir20;
use App\Http\Controllers\Controller;

class HasilUjiSir20ApiController extends Controller
{
    public function index(Request $request) {
        $query = HasilUjiLabSir20::orderBy('tanggal', 'desc');

        // 🔥 TAMBAHAN FILTER TANGGAL
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('tanggal', $request->date);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet', // Cek unique create
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'required|numeric',
            'ash'           => 'required|numeric',
            'vm'            => 'required|numeric',
            'money'         => 'required|numeric',
            'nitrogen'      => 'required|numeric',
        ]);

        try {
            $data = HasilUjiLabSir20::create($validated);
            return response()->json(['success' => true, 'message' => 'Tersimpan', 'data' => $data], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔥 SHOW
    public function show($id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $data]);
    }

    // 🔥 UPDATE
    public function update(Request $request, $id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();

        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            // 🔥 PERHATIKAN LOGIKA UNIQUE UPDATE: except id, idColumn
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet,'.$id.',id_hasil_uji_lab_sir_20',
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'required|numeric',
            'ash'           => 'required|numeric',
            'vm'            => 'required|numeric',
            'money'         => 'required|numeric',
            'nitrogen'      => 'required|numeric',
        ]);

        $data->update($validated);
        return response()->json(['success' => true, 'message' => 'Diperbarui']);
    }

    // 🔥 DESTROY
    public function destroy($id) {
        $data = HasilUjiLabSir20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Dihapus']);
    }

    // 🔥 API BARU: Mendapatkan Daftar Pallet yang Tersedia (Belum Diuji) SINKRON WEB
    public function getAvailablePallets()
    {
        // 1. Ambil SEMUA nomor palet yang sudah pernah diuji
        $palletSudahDiuji = HasilUjiLabSIR20::pluck('no_palet')->toArray();

        // 2. Ambil data Pallet Fisik dari Tabel Pallet
        $palletTersedia = Pallet::whereNull('tanggal_penjualan')
                            ->orderBy('id_pallet', 'asc') // Urutkan berdasarkan ID
                            ->get(['id_pallet', 'no_pallet', 'tanggal_produksi']);

        $daftarPalet = [];
        
        foreach ($palletTersedia as $p) {
            // 3. Hanya masukkan palet yang BELUM ada di tabel hasil uji
            if (!in_array($p->no_pallet, $palletSudahDiuji)) {
                
                $daftarPalet[] = [
                    // VALUE: Tetap 'no_pallet' string agar Controller Store tidak Error Validasi
                    'nomor' => $p->no_pallet, 
                    
                    // LABEL: Tampilkan ID Pallet sesuai format Web Admin
                    'label' => $p->no_pallet,
                    
                    'tanggal_prod' => $p->tanggal_produksi
                ];
            }
        }

        return response()->json($daftarPalet);
    }
}