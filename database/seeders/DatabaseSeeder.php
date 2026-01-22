<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder secara berurutan
        // Urutan ini PENTING karena ada relasi antar tabel
        
        $this->call([
            // 1. Master Data User & Maturasi (Wajib duluan)
            UserSeeder::class,
            MaturasiSeeder::class,

            // 2. Data Transaksi Harian (Bokar -> Maturasi)
            DummyPengolahanBasahSeeder::class,

            // 3. Koreksi Data WIP (Terakhir, karena butuh data referensi)
            // PerbaikanDataSaldoSeeder::class,

            // 4. Saldo Awal Gudang Produksi SIR
            // SaldoAwalGudangSeeder::class,

            // 5. Saldo Awal Penjualan SIR20
            // PenjualanAwalSeeder::class,
        ]);
    }
}