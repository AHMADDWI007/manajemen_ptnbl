<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    public function index()
    {
        // 🔥 TAMBAH 'info_penting_dashboard' di sini
        $pengaturan = Pengaturan::whereIn('kunci', [
            'url_api_bokar', 
            'ttd_kiri_nama', 
            'ttd_kiri_jabatan', 
            'ttd_kanan_nama', 
            'ttd_kanan_jabatan',
            // 'info_penting_dashboard' 
        ])->get()->keyBy('kunci');

        $apiBokar = $pengaturan->get('url_api_bokar');
        $ttd_kiri_nama = $pengaturan->get('ttd_kiri_nama');
        $ttd_kiri_jabatan = $pengaturan->get('ttd_kiri_jabatan');
        $ttd_kanan_nama = $pengaturan->get('ttd_kanan_nama');
        $ttd_kanan_jabatan = $pengaturan->get('ttd_kanan_jabatan');
        // $info_penting = $pengaturan->get('info_penting_dashboard'); // 🔥 Tangkap nilainya

        return view('Pengaturan.pengaturan', compact(
            'apiBokar', 
            'ttd_kiri_nama', 
            'ttd_kiri_jabatan', 
            'ttd_kanan_nama', 
            'ttd_kanan_jabatan',
            // 'info_penting' // 🔥 Kirim ke blade
        ));
    }

    public function update(Request $request)
    {
        // 🔥 Tambahkan validasi untuk info penting
        $request->validate([
            'url_api_bokar'     => 'required|url',
            'ttd_kiri_nama'     => 'required|string|max:255',
            'ttd_kiri_jabatan'  => 'required|string|max:255',
            'ttd_kanan_nama'    => 'required|string|max:255',
            'ttd_kanan_jabatan' => 'required|string|max:255',
            // 'info_penting_dashboard' => 'nullable|string|max:500', // Boleh kosong
        ]);

        DB::beginTransaction();
        try {
            // 🔥 Tambahkan key nya di pengumpulan data
            $data = $request->only([
                'url_api_bokar', 
                'ttd_kiri_nama', 
                'ttd_kiri_jabatan', 
                'ttd_kanan_nama', 
                'ttd_kanan_jabatan',
                // 'info_penting_dashboard'
            ]);

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