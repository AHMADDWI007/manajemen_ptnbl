<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabSIR20;
use App\Models\Pallet; 
use Carbon\Carbon; // 🔥 PENTING: Tanpa ini, Carbon::parse akan menyebabkan error 500
use Illuminate\Http\Request;

class HasilUjiSir20ApiController extends Controller
{
    public function index(Request $request) {
        $query = HasilUjiLabSIR20::orderBy('tanggal', 'desc');
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('tanggal', $request->date);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function getAvailablePallets()
    {
        try {
            // 1. Ambil nomor palet yang sudah pernah diuji agar tidak muncul lagi
            $palletSudahDiuji = HasilUjiLabSIR20::pluck('no_palet')->toArray();

            // 2. Ambil data Pallet dari tabel fisik yang belum terjual
            $palletTersedia = Pallet::whereNull('tanggal_penjualan')
                                ->orderBy('id_pallet', 'asc')
                                ->get(['no_pallet']); // Ambil no_pallet saja

            $daftarPalet = [];
            
            foreach ($palletTersedia as $p) {
                if (!in_array($p->no_pallet, $palletSudahDiuji)) {
                    $daftarPalet[] = [
                        // Ini yang dikirim ke database
                        'nomor' => (string)$p->no_pallet, 
                        // Ini yang akan tampil di Spinner Android Maswi
                        'label' => 'Palet No. ' . $p->no_pallet 
                    ];
                }
            }

            return response()->json($daftarPalet);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet',
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'ash'           => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'vm'            => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'money'         => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'nitrogen'      => 'nullable|numeric', // 🔥 Ubah jadi nullable
        ]);

        try {
            $data = HasilUjiLabSIR20::create($validated);
            return response()->json(['success' => true, 'message' => 'Data Berhasil Tersimpan'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        $data = HasilUjiLabSIR20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id) {
        $data = HasilUjiLabSIR20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'jenis_kemasan' => 'required|string',
            'no_palet'      => 'required|string|unique:hasil_uji_lab_sir_20,no_palet,'.$id.',id_hasil_uji_lab_sir_20',
            'po'            => 'required|numeric',
            'pa'            => 'required|numeric',
            'pri'           => 'required|numeric',
            'dirt'          => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'ash'           => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'vm'            => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'money'         => 'nullable|numeric', // 🔥 Ubah jadi nullable
            'nitrogen'      => 'nullable|numeric', // 🔥 Ubah jadi nullable
        ]);
        $data->update($validated);
        return response()->json(['success' => true, 'message' => 'Data Berhasil Diperbarui']);
    }

    public function destroy($id) {
        $data = HasilUjiLabSIR20::where('id_hasil_uji_lab_sir_20', $id)->firstOrFail();
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data Berhasil Dihapus']);
    }
}