<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\HasilUjiLabSir20;
use App\Http\Controllers\Controller;

class HasilUjiSir20ApiController extends Controller
{
    public function index() {
        return response()->json(['success' => true, 'data' => HasilUjiLabSir20::latest('tanggal')->get()]);
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

    // 🔥 API BARU: Mendapatkan Daftar Pallet yang Tersedia (Belum Diuji)
    public function getAvailablePallets()
    {
        // 1. Ambil semua nomor pallet yang SUDAH diuji (pluck biar ringan)
        $sudahDiuji = HasilUjiLabSIR20::pluck('no_palet')->toArray();

        // 2. Ambil data produksi (Misal: 2 bulan terakhir, urut dari yang terbaru)
        // Kita tidak filter by date request lagi.
        $produksi = ProduksiSir20::orderBy('tanggal_produksi', 'desc')
            ->take(60) // Limit biar server gak meledak kalau data ribuan
            ->get();

        $daftarPalet = [];

        foreach ($produksi as $prod) {
            $start = (int) $prod->nomor_start;
            $end = (int) $prod->nomor_end;
            $tglProduksi = $prod->tanggal_produksi; // Info tambahan buat label

            // Loop nomor dalam batch produksi ini
            for ($i = $start; $i <= $end; $i++) {
                $nomorStr = (string) $i;

                // 3. Cek apakah nomor ini sudah ada di array $sudahDiuji?
                if (!in_array($nomorStr, $sudahDiuji)) {
                    $daftarPalet[] = [
                        'nomor' => $nomorStr,
                        // Tampilkan info tanggal biar user tau ini stok kapan
                        'label' => "Palet No. $nomorStr ($tglProduksi)" 
                    ];
                }
            }
        }

        // Sortir biar nomor urut (opsional, bisa sort by nomor asc)
        // usort($daftarPalet, function($a, $b) { return $a['nomor'] - $b['nomor']; });

        return response()->json($daftarPalet);
    }
}