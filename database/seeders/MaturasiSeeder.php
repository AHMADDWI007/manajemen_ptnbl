<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Maturasi;

class MaturasiSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 49; $i++) {
            Maturasi::updateOrCreate(
                ['uraian' => "Di Bak Maturasi-$i"], // Kunci Pencarian
                [
                    'stok_awal'  => 0,
                    'tgl_masuk'  => null,
                    'umur'       => 0,
                    'diolah'     => 0,
                    'mutasi'     => 0,
                    'masuk_hi'   => 0,
                    'stok_akhir' => 0,
                    'asal_bokar' => null,
                    'keterangan' => 'KOSONG'
                ]
            );
        }
    }
}