<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BahanProses;
use Carbon\Carbon;

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
        // Ganti tanggal di bawah ini sesuai kebutuhan (misal: '2025-11-28')
        // Atau gunakan Carbon::yesterday()->format('Y-m-d') untuk dinamis
        $targetDate = '2025-11-30'; 
        
        $this->command->info("Memulai perbaikan data saldo untuk tanggal: $targetDate");

        // Data Benar sesuai Excel Hijau
        $dataBenar = [
            'Lantai Umpan Kering'                    => 193,
            'Di Blending Tank 4'                     => 4000,
            'Di Lump Breaker-2 (Di Blending Tank-4)' => 300,
            'Di Pre Breaker-2 (Di Blending Tank-5)'  => 200,
            'Di Hammer Mill-2 (Di Blending Tank-6)'  => 100,
            'Di Blending Tank-7'                     => 100,
            'Di Trolley'                             => 0,
            'Di Dalam Dryer/Press Bale'              => 11760,
            'Di Reproses Ex WS.'                     => 0,
        ];

        foreach ($dataBenar as $uraian => $saldoBenar) {
            // Cek apakah data sudah ada
            $record = BahanProses::whereDate('tanggal', $targetDate)
                ->where('uraian', $uraian)
                ->first();

            if ($record) {
                // Update data jika ditemukan
                $record->update(['saldo_akhir' => $saldoBenar]);
                $this->command->info("✅ UPDATE: $uraian -> Menjadi $saldoBenar");
            } else {
                // Buat data baru jika tidak ditemukan
                BahanProses::create([
                    'tanggal'        => $targetDate,
                    'uraian'         => $uraian,
                    'saldo_awal'     => 0,
                    'wip_masuk'      => 0, 
                    'wip_keluar'     => 0, 
                    'produksi_sir20' => 0, 
                    'rekfif'         => 0,
                    'saldo_akhir'    => $saldoBenar,
                    'keterangan'     => 'Perbaikan Data Seeder'
                ]);
                $this->command->warn("⚠️ CREATE: $uraian -> Dibuat baru dengan saldo $saldoBenar");
            }
        }

        $this->command->info("------------------------------------------------");
        $this->command->info("SUKSES! Data saldo tanggal $targetDate telah diperbaiki.");
    }
}