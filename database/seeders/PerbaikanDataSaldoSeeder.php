<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BahanProses;

class PerbaikanDataSaldoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // --- KONFIGURASI TANGGAL ---
        // PENTING: Kita set ke 31 Des 2025 agar menjadi Saldo Awal di 01 Jan 2026
        $targetDate = '2025-12-31'; 
        
        $this->command->info("Memulai seeding data saldo akhir untuk tanggal: $targetDate");

        // Data Benar sesuai Gambar Excel yang Abang kirim (16.094 Total)
        $dataBenar = [
            'Lantai Umpan Kering'                    => 0,
            'Di Blending Tank 4'                     => 2975,
            'Di Lump Breaker-2 (Di Blending Tank-4)' => 100,
            'Di Pre Breaker-2 (Di Blending Tank-5)'  => 50,
            'Di Hammer Mill-2 (Di Blending Tank-6)'  => 50,
            'Di Blending Tank-7'                     => 39,
            'Di Trolley'                             => 735,
            'Di Dalam Dryer/Press Bale'              => 12145,
            'Di Reproses Ex WS.'                     => 0,
        ];

        foreach ($dataBenar as $uraian => $saldoBenar) {
            // Gunakan updateOrCreate agar aman dijalankan berulang kali
            BahanProses::updateOrCreate(
                [
                    'tanggal' => $targetDate,
                    'uraian'  => $uraian
                ],
                [
                    'saldo_awal'     => 0,
                    'wip_masuk'      => 0,  // Sesuai nama kolom di DB Abang
                    'wip_keluar'     => 0,
                    'produksi_sir20' => 0,
                    'rekfif'         => 0,  // Sesuai typo kolom di DB Abang ('rekfif')
                    'saldo_akhir'    => $saldoBenar,
                    'keterangan'     => 'Saldo Awal 2026 (Seeder)',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );
            
            $this->command->info("✅ DATA: $uraian -> Saldo Akhir $saldoBenar");
        }

        $this->command->info("------------------------------------------------");
        $this->command->info("SUKSES! Silakan cek Laporan Tanggal 01/01/2026.");
    }
}