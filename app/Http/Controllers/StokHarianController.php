<?php

namespace App\Http\Controllers;

use App\Models\StokHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class StokHarianController extends Controller
{
    public function index()
    {
        $data = StokHarian::orderBy('tanggal', 'desc')->get();
        return view('stok_harian.index', compact('data'));
    }

    // Ambil data dari API utama
    public function ambilData()
    {
        $today = Carbon::today()->toDateString();
        $apiUrl = "https://bokar.ptnb.co.id/get_bokar.php?tgl={$today}&kode=total";

        try {
            $response = Http::get($apiUrl);
            if ($response->successful()) {
                $data = $response->json();
                $total = $data['total'] ?? 0;

                // Simpan ke database
                $stok = StokHarian::updateOrCreate(
                    ['tanggal' => $today],
                    [
                        'bokar_masuk_hi' => $total,
                        'stok_akhir' => $total, // nanti bisa disesuaikan rumus stok akhir
                    ]
                );

                return redirect()->back()->with('success', "Data bokar tanggal {$today} berhasil diambil: {$total}");
            } else {
                return redirect()->back()->with('error', 'Gagal mengambil data dari API eksternal.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Detail data (petani, PT, Inhut)
    public function show($tanggal)
    {
        $apiUrl = "https://bokar.ptnb.co.id/get_bokar.php?tgl={$tanggal}&kode=detail";

        try {
            $response = Http::get($apiUrl);
            if ($response->successful()) {
                $data = $response->json();

                // Contoh response detail:
                // { "petani": 1234, "pt": 2456, "inhut": 789 }

                // Update data detail ke DB
                StokHarian::where('tanggal', $tanggal)->update([
                    'detail_masuk_hi' => $data
                ]);

                return view('stok_harian.detail', compact('data', 'tanggal'));
            } else {
                return redirect()->back()->with('error', 'Gagal mengambil data detail.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
