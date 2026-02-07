<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pallet;
use App\Models\HasilUjiLabSIR20;

class UjiLabCepatSeeder extends Seeder
{
    public function run()
    {
        // 1. BERSIHKAN TABEL DULU (Opsional: Aktifkan jika ingin reset total)
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // HasilUjiLabSIR20::truncate(); // <--- INI AKAN MENGHAPUS SEMUA DATA LAMA
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Ambil semua Pallet
        $pallets = Pallet::all();
        $count = 0;

        foreach ($pallets as $p) {
            // Cek apakah sudah ada? (Agar tidak error duplicate entry jika truncate dimatikan)
            $exists = HasilUjiLabSIR20::where('no_palet', $p->no_pallet)->exists();

            if (!$exists) {
                HasilUjiLabSIR20::create([
                    'tanggal'       => $p->tanggal_produksi,
                    'jenis_kemasan' => 'SW',
                    'no_palet'      => $p->no_pallet,
                    
                    // Data Dummy Seragam
                    'po'            => 40.00,
                    'pa'            => 25.00,
                    'pri'           => 62.50,
                    'dirt'          => 0.02, 
                    'ash'           => 0.15,
                    'vm'            => 0.35,
                    'money'         => 50.00,
                    'nitrogen'      => 0.45,
                    
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $count++;
            }
        }

        $this->command->info("SELESAI: {$count} Data Uji Lab baru berhasil ditambahkan.");
    }
}