<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Maturasi;
use Illuminate\Support\Facades\DB;

class MaturasiSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Nonaktifkan foreign key sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Kosongkan tabel dengan aman
        DB::table('maturasi')->truncate();

        // 🔓 Aktifkan lagi foreign key setelah truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Siapkan data dummy
        $baks = [];
        for ($i = 1; $i <= 49; $i++) {
            $baks[] = [
                'uraian' => 'Di Bak Maturasi ' . $i,
                'stok_akhir' => 0,
                'tgl_masuk' => null,
                'umur' => 0,
                'keterangan' => 'Data Awal',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Masukkan semua data ke tabel
        Maturasi::insert($baks);
    }
}
