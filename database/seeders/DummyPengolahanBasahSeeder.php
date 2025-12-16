<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PengolahanBasah;
use App\Models\HasilUjiBokarDiolah;
use App\Models\HasilUjiMaturasi; // WAJIB IMPORT MODEL INI
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DummyPengolahanBasahSeeder extends Seeder
{
    // Data Masuk Real dari SQL Transaksi API Bokar Anda
    private $dataMasuk = [
        '2025-12-01' => ['DS' => 38886, 'PT' => 2328,  'INHUT' => 0],
        '2025-12-02' => ['DS' => 23297, 'PT' => 2573,  'INHUT' => 0],
        '2025-12-03' => ['DS' => 11270, 'PT' => 3848,  'INHUT' => 0],
        '2025-12-04' => ['DS' => 73445, 'PT' => 7198,  'INHUT' => 0],
        '2025-12-05' => ['DS' => 17209, 'PT' => 1705,  'INHUT' => 0],
        '2025-12-06' => ['DS' => 2470,  'PT' => 4420,  'INHUT' => 0],
        '2025-12-07' => ['DS' => 0,     'PT' => 0,     'INHUT' => 0],
        '2025-12-08' => ['DS' => 41003, 'PT' => 5890,  'INHUT' => 0],
        '2025-12-09' => ['DS' => 14308, 'PT' => 6366,  'INHUT' => 0],
        '2025-12-10' => ['DS' => 18777, 'PT' => 1822,  'INHUT' => 0],
        '2025-12-11' => ['DS' => 58820, 'PT' => 5241,  'INHUT' => 0],
        '2025-12-12' => ['DS' => 23768, 'PT' => 6023,  'INHUT' => 0],
        '2025-12-13' => ['DS' => 1960,  'PT' => 1754,  'INHUT' => 5508],
        '2025-12-14' => ['DS' => 0,     'PT' => 0,     'INHUT' => 0],
        '2025-12-15' => ['DS' => 11270, 'PT' => 3566,  'INHUT' => 0],
    ];

    public function run()
    {
        // 1. BERSIHKAN DATA
        Schema::disableForeignKeyConstraints();
        PengolahanBasah::truncate();
        HasilUjiBokarDiolah::truncate();
        HasilUjiMaturasi::truncate(); // TRUNCATE INI JUGA
        PengolahanMaturasi::truncate();
        
        Maturasi::query()->update([
            'stok_awal' => 0, 'tgl_masuk' => null, 'umur' => 0,
            'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
            'stok_akhir' => 0, 'asal_bokar' => null, 'keterangan' => 'KOSONG',
            'id_hasil_uji_maturasi' => null
        ]);
        Schema::enableForeignKeyConstraints();

        $this->command->info("Memulai simulasi cerdas (Lengkap dengan Uji Lab Maturasi)...");

        // 2. INISIALISASI STOK BERJALAN
        $runningStock = ['PT' => 0, 'DS' => 0, 'INHUT' => 0];

        // 3. LOOP TANGGAL
        $startDate = Carbon::create(2025, 12, 1);
        $endDate   = Carbon::now();

        while ($startDate->lte($endDate)) {
            $dateStr = $startDate->format('Y-m-d');

            if ($startDate->isSunday()) {
                $startDate->addDay();
                continue;
            }

            // A. Update Stok Berjalan
            $masukHariIni = $this->dataMasuk[$dateStr] ?? ['DS' => 0, 'PT' => 0, 'INHUT' => 0];
            foreach ($masukHariIni as $jenis => $jumlah) {
                $runningStock[$jenis] += $jumlah;
            }

            // B. Proses Produksi (Hanya jika stok cukup)
            foreach (['DS', 'PT', 'INHUT'] as $jenis) {
                if ($runningStock[$jenis] > 3000) {
                    
                    $persenOlah = rand(60, 90) / 100; 
                    $targetNettoKering = $runningStock[$jenis] * $persenOlah;

                    $this->createSmartTransaction($startDate->copy(), $jenis, $targetNettoKering);

                    $runningStock[$jenis] -= $targetNettoKering;
                }
            }

            $this->command->info("Processed: $dateStr | Sisa Stok PT: " . number_format($runningStock['PT']));
            $startDate->addDay();
        }
    }

    private function createSmartTransaction($date, $jenis, $targetNettoKering)
    {
        // LOGIKA BAK & STRING SINKRONISASI
        $noBak = rand(1, 49);
        $namaBakMaturasi = "Di Bak Maturasi-" . $noBak; 
        $namaBakInput    = "Bak Maturasi " . $noBak; 

        // K3 Masuk (Bokar)
        $k3_masuk = rand(5000, 6000) / 100; 

        // K3 Olah (Maturasi) - Biasanya sedikit beda/naik dari masuk
        $k3_olah  = $k3_masuk + (rand(-50, 100) / 100); 

        // Hitung Berat Basah berdasarkan K3 Masuk
        $nettoBasah = $targetNettoKering / ($k3_masuk / 100);
        $beratTruck = rand(3500, 4500); 
        $beratTimbang = $beratTruck + $nettoBasah;

        // 1. SIMPAN PENGOLAHAN BASAH
        PengolahanBasah::create([
            'tanggal'       => $date->format('Y-m-d'),
            'bak_maturasi'  => $namaBakInput,
            'jenis'         => $jenis,
            'berat_truck'   => $beratTruck,
            'berat_timbang' => $beratTimbang,
            'netto_basah'   => $nettoBasah,
            'k3'            => $k3_masuk,
            'netto_kering'  => $targetNettoKering, 
            'created_at'    => $date,
            'updated_at'    => $date,
        ]);

        // 2. SIMPAN HASIL UJI BOKAR DIOLAH (Sumber K3 Masuk)
        HasilUjiBokarDiolah::create([
            'tanggal'       => $date->format('Y-m-d'),
            'bak_maturasi'  => $namaBakInput,
            'jenis'         => $jenis,
            'netto_basah'   => $nettoBasah,
            'k3'            => $k3_masuk,
            'netto_kering'  => $targetNettoKering,
            'created_at'    => $date,
            'updated_at'    => $date,
        ]);

        // 3. SIMPAN HASIL UJI MATURASI (Sumber K3 Olah) --> INI YANG KEMARIN KURANG
        // Pastikan nama kolom di DB Anda sesuai (no_kamar atau bak_maturasi)
        // Di sini saya asumsikan 'no_kamar' sesuai standar maturasi
        HasilUjiMaturasi::create([
            'tanggal'       => $date->format('Y-m-d'),
            'no_kamar'      => $namaBakMaturasi, // "Di Bak Maturasi-X"
            'k3'            => $k3_olah,
            'po'            => rand(30, 50), // Nilai Po Random
            'pri'           => rand(60, 90), // Nilai PRI Random
            'created_at'    => $date,
            'updated_at'    => $date,
        ]);

        // 4. UPDATE STOK MATURASI
        $this->updateMaturasi($namaBakMaturasi, $targetNettoKering, $date, $jenis);
    }

    private function updateMaturasi($uraianMaturasi, $masuk_hi, $tanggal, $asal_bokar)
    {
        $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
        if (!$maturasi) $maturasi = Maturasi::create(['uraian' => $uraianMaturasi]);

        $lastUpdate = $maturasi->updated_at ? Carbon::parse($maturasi->updated_at) : null;
        $isSameDay = $lastUpdate && $lastUpdate->isSameDay($tanggal);

        if ($isSameDay) {
            $maturasi->masuk_hi += $masuk_hi;
            $maturasi->stok_akhir = $maturasi->stok_awal - $maturasi->diolah - $maturasi->mutasi + $maturasi->masuk_hi;
        } else {
            $maturasi->stok_awal = $maturasi->stok_akhir;
            $maturasi->diolah = 0; 
            $maturasi->mutasi = 0;
            $maturasi->masuk_hi = $masuk_hi; 
            $maturasi->stok_akhir = $maturasi->stok_awal + $masuk_hi;
        }

        if ($maturasi->stok_akhir > 0) {
            $maturasi->tgl_masuk = $tanggal;
            $maturasi->umur = 0;
            $maturasi->keterangan = strtoupper($tanggal->format('d M Y'));
            
            if (empty($maturasi->asal_bokar) || $maturasi->stok_awal <= 0) {
                $maturasi->asal_bokar = $asal_bokar;
            } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
                $maturasi->asal_bokar = 'CMP';
            }
        }

        $maturasi->updated_at = $tanggal;
        $maturasi->save();

        PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $tanggal,
            'diolah'      => 0, 
            'mutasi'      => 0,
            'masuk_hi'    => $masuk_hi,
            'keterangan'  => 'Seeder Otomatis'
        ]);
    }
}