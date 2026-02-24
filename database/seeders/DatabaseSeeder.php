<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. DATA USER (Tetap Paling Awal)
            UserSeeder::class,

            // 2. DUMMY LHP (Jalankan ini duluan untuk RESET & ISI MATURASI)
            // Ini akan menghapus data lama dan mengisi stok Maturasi per 1 Jan 2026
            DummyLHPSeeder::class, 

            // --- SEEDER DI BAWAH INI AKAN MENIMPA/MENAMBAH SETELAH RESET ---

            // 3. MaturasiSeeder TIDAK PERLU DIJALANKAN LAGI 
            // Karena datanya sudah di-handle oleh DummyLHPSeeder sesuai Excel.
            // MaturasiSeeder::class, 

            // 4. Koreksi Data WIP (Bahan Proses)
            // Jalankan SETELAH DummyLHP, supaya datanya masuk dan TIDAK TERHAPUS.
            PerbaikanDataSaldoSeeder::class,

            // 5. Saldo Awal Gudang & Penjualan (Opsional)
            // SaldoAwalGudangSeeder::class,
            // PenjualanAwalSeeder::class,

            // 6. Data Awal Sistem SIR (Lokasi, Mutu, Saldo Awal)
            DataAwalSirSeeder::class,

            // 7. Pengolahan Basah (Saldo Awal Bokar)
            PengolahanBasahSeeder::class,
        ]);
    }
}