<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PengolahanBasah;
use App\Models\HasilUjiLabBokarDiolah; // 🔥 [PERBAIKAN MODEL]
use App\Models\HasilUjiLabMaturasi;    // 🔥 [PERBAIKAN MODEL]
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DummyPengolahanBasahSeeder extends Seeder
{
    private $dataMasuk = [
        '2025-10-01' => ['DS' => 38886, 'PT' => 2328,  'INHUT' => 0],
        '2025-10-02' => ['DS' => 23297, 'PT' => 2573,  'INHUT' => 0],
    ];

    public function run()
    {
        // 1. BERSIHKAN DATA LAMA
        Schema::disableForeignKeyConstraints();
        PengolahanBasah::truncate();
        HasilUjiLabBokarDiolah::truncate();
        HasilUjiLabMaturasi::truncate();
        PengolahanMaturasi::truncate();
        
        // Reset Stok Master Maturasi
        Maturasi::query()->update([
            'stok_awal' => 0, 'tgl_masuk' => null, 'umur' => 0,
            'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
            'stok_akhir' => 0, 'asal_bokar' => null, 'keterangan' => 'KOSONG'
        ]);
        Schema::enableForeignKeyConstraints();

        $this->command->info("Memulai simulasi data (Relational ID Mode)...");

        // 2. SIMULASI TRANSAKSI
        $startDate = Carbon::create(2025, 10, 1);
        $endDate   = Carbon::now();

        while ($startDate->lte($endDate)) {
            $dateStr = $startDate->format('Y-m-d');
            
            // Skip Minggu (Opsional)
            if ($startDate->isSunday()) {
                $startDate->addDay();
                continue;
            }

            // Ambil data dummy harian
            $masukHariIni = $this->dataMasuk[$dateStr] ?? ['DS' => rand(1000, 5000), 'PT' => rand(500, 2000), 'INHUT' => 0];

            foreach ($masukHariIni as $jenis => $jumlahNettoKering) {
                if ($jumlahNettoKering > 0) {
                    $this->createTransaction($startDate->copy(), $jenis, $jumlahNettoKering);
                }
            }
            
            $startDate->addDay();
        }
    }

    private function createTransaction($date, $jenis, $targetNettoKering)
    {
        // === CARI ID BAK ===
        $noBak = rand(1, 49);
        $uraianMaturasi = "Di Bak Maturasi-" . $noBak; 
        $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
        if (!$maturasi) return;

        // --- HITUNGAN ANGKA ---
        $k3_masuk = rand(5000, 6000) / 100; // 50.00% - 60.00%
        $k3_olah  = $k3_masuk + (rand(-50, 100) / 100); 

        $nettoBasah = $targetNettoKering / ($k3_masuk / 100);
        $beratTruck = rand(3500, 4500); 
        $beratTimbang = $beratTruck + $nettoBasah;

        // 1. SIMPAN PENGOLAHAN BASAH (Pakai id_maturasi)
        $pb = PengolahanBasah::create([
            'tanggal'       => $date->format('Y-m-d'),
            'id_maturasi'   => $maturasi->id_maturasi, // 🔥 [PERBAIKAN PK]
            'jenis'         => $jenis,
            'berat_truck'   => $beratTruck,
            'berat_timbang' => $beratTimbang,
            'netto_basah'   => $nettoBasah,
            'k3'            => $k3_masuk,
            'netto_kering'  => $targetNettoKering, 
        ]);

        // 2. SIMPAN LOG K3 (Pakai id_pengolahan_basah)
        HasilUjiLabBokarDiolah::create([
            'id_pengolahan_basah' => $pb->id_pengolahan_basah, // 🔥 [PERBAIKAN PK]
            'id_maturasi'         => $maturasi->id_maturasi,   // 🔥 [PERBAIKAN PK]
            'tanggal'             => $date->format('Y-m-d'),
            'jenis'               => $jenis,
            'netto_basah'         => $nettoBasah,
            'k3'                  => $k3_masuk,
            'netto_kering'        => $targetNettoKering,
        ]);

        // 3. SIMPAN HASIL UJI MATURASI (Pakai id_maturasi)
        HasilUjiLabMaturasi::create([
            'tanggal'     => $date->format('Y-m-d'),
            'id_maturasi' => $maturasi->id_maturasi, // 🔥 [PERBAIKAN PK]
            'k3'          => $k3_olah,
            'po'          => rand(30, 50),
            'pri'         => rand(60, 90),
        ]);

        // 4. UPDATE STOK
        $this->updateStokMaturasi($maturasi, $targetNettoKering, $date, $jenis);
    }

    private function updateStokMaturasi($maturasi, $masuk_hi, $tanggal, $asal_bokar)
    {
        // Update Log Harian (Pakai id_maturasi)
        PengolahanMaturasi::create([
            'id_maturasi' => $maturasi->id_maturasi, // 🔥 [PERBAIKAN PK]
            'tgl_laporan' => $tanggal,
            'diolah'      => 0, 
            'mutasi'      => 0,
            'masuk_hi'    => $masuk_hi,
            'keterangan'  => 'Seeder Otomatis'
        ]);

        // Update Master
        $maturasi->stok_akhir += $masuk_hi;
        $maturasi->tgl_masuk = $tanggal;
        $maturasi->asal_bokar = $asal_bokar;
        $maturasi->save();
    }
}