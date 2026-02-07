<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TransaksiApiBokar;
use App\Models\Pengaturan; // 🔥 PENTING: Import Model Pengaturan
use Carbon\Carbon;

class SyncBokarApi extends Command
{
    // Signature tetap sama (menerima input tanggal)
    protected $signature = 'bokar:sync {date? : Tanggal spesifik (Y-m-d)} {--start= : Opsi rentang awal} {--end= : Opsi rentang akhir}';

    protected $description = 'Tarik data API Bokar (Petani, Inhut, PTPN, Total) dan simpan ke DB';

    public function handle()
    {
        // ==========================================
        // 1. AMBIL KONFIGURASI URL DARI DATABASE
        // ==========================================
        $baseUrl = Pengaturan::where('kunci', 'url_api_bokar')->value('nilai');

        // Validasi: Jika admin belum isi di database, pakai URL default (Cadangan)
        if (empty($baseUrl)) {
            $this->warn("⚠️ URL API belum disetting di tabel 'pengaturan'. Menggunakan URL default hardcoded.");
            $baseUrl = "https://bokar.ptnb.co.id/get_bokar.php";
        } else {
            $this->info("ℹ️ Menggunakan API Endpoint: $baseUrl");
        }

        // ==========================================
        // 2. TENTUKAN RENTANG TANGGAL
        // ==========================================
        $dateArg = $this->argument('date');
        $startOpt = $this->option('start');
        $endOpt   = $this->option('end');

        if ($dateArg) {
            // Jika dipanggil via Web / Argumen Tunggal
            $startDate = Carbon::parse($dateArg);
            $endDate   = Carbon::parse($dateArg); 
        } elseif ($startOpt) {
            // Jika pakai Option Rentang via Terminal
            $startDate = Carbon::parse($startOpt);
            $endDate   = $endOpt ? Carbon::parse($endOpt) : Carbon::today();
        } else {
            // Default: Hari Ini
            $startDate = Carbon::today();
            $endDate   = Carbon::today();
        }

        $kodeList = ['petani', 'inhut', 'ptpn', 'total'];

        $this->info("🔄 Memulai sinkronisasi dari " . $startDate->format('Y-m-d') . " sampai " . $endDate->format('Y-m-d'));

        // ==========================================
        // 3. LOOPING PROSES
        // ==========================================
        $currentDate = $startDate->copy(); 

        while ($currentDate->lte($endDate)) {
            $currentDateStr = $currentDate->format('Y-m-d');
            
            $this->line("------------------------------------------------");
            $this->info("📅 Proses Tanggal: $currentDateStr");

            foreach ($kodeList as $kode) {
                
                // 🔥 MODIFIKASI: GUNAKAN BASE URL DARI DATABASE
                $url = "{$baseUrl}?tgl={$currentDateStr}&kode={$kode}";
                
                $this->getOutput()->write("   Requesting ($kode)... ");

                try {
                    // Kirim request ke API (Timeout 15 detik)
                    $response = Http::timeout(15)->get($url);

                    if ($response->successful()) {
                        $data = $response->json();

                        $masuk_sd_kemarin = $data['masuk_sd_kemarin'] ?? 0;
                        $masuk_hi         = $data['masuk_hi'] ?? 0;

                        // Simpan ke Database
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

                        $this->info("✔ OK");
                    } else {
                        $this->error("✘ Gagal (Status: " . $response->status() . ")");
                    }

                } catch (\Exception $e) {
                    $this->error("✘ Exception: " . $e->getMessage());
                }
            }

            // Lanjut ke hari berikutnya
            $currentDate->addDay();
        }

        $this->info("✅ Sinkronisasi Selesai!");
        return 0;
    }
}