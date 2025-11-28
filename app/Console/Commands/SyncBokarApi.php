<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TransaksiApiBokar;
use Carbon\Carbon;

class SyncBokarApi extends Command
{
    // Nama perintah yang akan kita ketik di terminal
    // Contoh penggunaan: 
    // php artisan bokar:sync (untuk hari ini)
    // php artisan bokar:sync --start=2025-11-17 --end=2025-11-19 (untuk rentang tanggal)
    protected $signature = 'bokar:sync {--start= : Tanggal awal (Y-m-d)} {--end= : Tanggal akhir (Y-m-d)}';

    protected $description = 'Tarik data API Bokar (Petani, Inhut, PTPN, Total) dan simpan ke DB';

    public function handle()
    {
        // 1. Tentukan Rentang Tanggal
        $startDate = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::today();
        $endDate   = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::today();

        // Daftar kode sesuai URL Anda
        $kodeList = ['petani', 'inhut', 'ptpn', 'total'];

        $this->info("Memulai sinkronisasi dari " . $startDate->format('Y-m-d') . " sampai " . $endDate->format('Y-m-d'));

        // 2. Looping per hari
        while ($startDate->lte($endDate)) {
            $currentDateStr = $startDate->format('Y-m-d');
            $this->line("------------------------------------------------");
            $this->info("Proses Tanggal: $currentDateStr");

            // 3. Looping per kode (petani, inhut, dll)
            foreach ($kodeList as $kode) {
                $url = "https://bokar.ptnb.co.id/get_bokar.php?tgl={$currentDateStr}&kode={$kode}";
                
                $this->line("Requesting ($kode)...");

                try {
                    // Kirim request ke API
                    $response = Http::timeout(10)->get($url);

                    if ($response->successful()) {
                        $data = $response->json();

                        // Pastikan respon memiliki data yang diharapkan
                        // JSON Output Anda: { "tanggal": "...", "masuk_sd_kemarin": ..., "masuk_hi": ... }
                        
                        $masuk_sd_kemarin = isset($data['masuk_sd_kemarin']) ? $data['masuk_sd_kemarin'] : 0;
                        $masuk_hi         = isset($data['masuk_hi']) ? $data['masuk_hi'] : 0;

                        // 4. Simpan ke Database (Update jika ada, Create jika baru)
                        TransaksiApiBokar::updateOrCreate(
                            [
                                'tanggal'  => $currentDateStr,
                                'kode_api' => $kode
                            ],
                            [
                                'masuk_sd_kemarin' => $masuk_sd_kemarin,
                                'masuk_hi'         => $masuk_hi
                            ]
                        );

                        $this->info("✓ Sukses simpan: $kode");
                    } else {
                        $this->error("x Gagal request: $kode (Status: " . $response->status() . ")");
                    }

                } catch (\Exception $e) {
                    $this->error("x Error Exception: " . $e->getMessage());
                }
            }

            // Lanjut ke hari berikutnya
            $startDate->addDay();
        }

        $this->info("Sinkronisasi Selesai!");
    }
}