<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Maturasi;
class MaturasiSeeder extends Seeder {
    public function run(): void {
        for ($i = 1; $i <= 49; $i++) {
            Maturasi::firstOrCreate(
                // PASTIKAN INI PAKAI TANDA HUBUNG
                ['uraian' => "Di Bak Maturasi-" . $i], 
                ['keterangan' => 'KOSONG']
            );
        }
    }
}