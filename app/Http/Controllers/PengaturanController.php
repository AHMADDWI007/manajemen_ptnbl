<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    public function index()
    {
        // Mengambil semua kunci yang dibutuhkan di Blade
        $pengaturan = Pengaturan::whereIn('kunci', [
            'url_api_bokar', 
            'ttd_kiri_nama', 
            'ttd_kiri_jabatan', 
            'ttd_kanan_nama', 
            'ttd_kanan_jabatan'
        ])->get()->keyBy('kunci');

        // Memecah ke variabel masing-masing agar sesuai dengan pemanggilan di Blade kamu
        $apiBokar = $pengaturan->get('url_api_bokar');
        $ttd_kiri_nama = $pengaturan->get('ttd_kiri_nama');
        $ttd_kiri_jabatan = $pengaturan->get('ttd_kiri_jabatan');
        $ttd_kanan_nama = $pengaturan->get('ttd_kanan_nama');
        $ttd_kanan_jabatan = $pengaturan->get('ttd_kanan_jabatan');

        return view('Pengaturan.pengaturan', compact(
            'apiBokar', 
            'ttd_kiri_nama', 
            'ttd_kiri_jabatan', 
            'ttd_kanan_nama', 
            'ttd_kanan_jabatan'
        ));
    }

    public function update(Request $request)
    {
        // Validasi semua input dari form
        $request->validate([
            'url_api_bokar'     => 'required|url',
            'ttd_kiri_nama'     => 'required|string|max:255',
            'ttd_kiri_jabatan'  => 'required|string|max:255',
            'ttd_kanan_nama'    => 'required|string|max:255',
            'ttd_kanan_jabatan' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Kita kumpulkan semua data input
            $data = $request->only([
                'url_api_bokar', 
                'ttd_kiri_nama', 
                'ttd_kiri_jabatan', 
                'ttd_kanan_nama', 
                'ttd_kanan_jabatan'
            ]);

            // Looping untuk update atau insert jika data belum ada (Sistem Sapu Jagat)
            foreach ($data as $kunci => $nilai) {
                Pengaturan::updateOrCreate(
                    ['kunci' => $kunci],
                    ['nilai' => $nilai]
                );
            }

            DB::commit();
            return redirect()->back()->with('success', 'Seluruh pengaturan sistem berhasil diperbarui!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}