<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\BahanProses;
use App\Models\Lokasi; // Tambahan Model Baru
use App\Models\Mutu;   // Tambahan Model Baru
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DummyLHPSeeder extends Seeder
{
    public function run()
    {
        // =================================================================
        // 1. BERSIHKAN DATABASE (METODE AMAN DARI ERROR #1701)
        // =================================================================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Maturasi::truncate();
        PengolahanBasah::truncate();
        PengolahanMaturasi::truncate();
        // TransaksiApiBokar::truncate(); // <-- DIHAPUS (Biar data bokar aman/manual)
        HasilUjiLabBokarDiolah::truncate();
        HasilUjiLabMaturasi::truncate();
        BahanProses::truncate();
        
        // Bersihkan Tabel Master Baru (Agar ID reset ke 1)
        Lokasi::truncate();
        Mutu::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Target Tanggal Laporan: 01 Januari 2026
        $targetDate = '2026-01-01'; 
        $this->command->info("Seeding LHP KHUSUS MATURASI (Total 162.395) - Target: $targetDate");

        // =================================================================
        // 1.B ISI MASTER LOKASI & MUTU (UNTUK SISTEM BARU TRACKING PALLET)
        // =================================================================
        $this->command->info('Seeding Master Lokasi & Mutu...');

        $lokasi = [
            'Di Gudang SIR',
            'Di Areal Press Bale',
            'Di Gudang TOH 1',
            'Di Gudang TOH 2',
            'Area Repacking', 
        ];
        foreach ($lokasi as $l) {
            Lokasi::create(['nama' => $l]);
        }

        $mutu = [
            'Mutu Prima (siap jual)',
            'PO / PRI Low',
            'WhiteSpot (WS)',
            'Kontaminasi',
            'Repacking On Hold',
        ];
        foreach ($mutu as $m) {
            Mutu::create(['uraian' => $m]);
        }

        // =================================================================
        // 2. SETUP 49 BAK KOSONG TERLEBIH DAHULU
        // =================================================================
        for ($i = 1; $i <= 49; $i++) {
            Maturasi::create([
                'uraian'       => "Di Bak Maturasi-$i",
                'stok_awal'    => 0, 'masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0,
                'stok_akhir'   => 0, 'umur' => 0, 'asal_bokar' => null,
                'keterangan'   => 'KOSONG'
            ]);
        }

        // =================================================================
        // 3. INPUT DATA REAL (SESUAI GAMBAR EXCEL - TOTAL 162.395)
        // =================================================================
        // Format: [Berat, Tgl Masuk, Asal Bokar]
        
        $dataReal = [
            // --- BARIS KIRI ---
            2  => [5654, '2025-12-26', 'CMP INHUT'],
            3  => [5629, '2025-12-26', 'CMP INHUT'],
            4  => [5468, '2025-12-26', 'CMP INHUT'],
            5  => [5528, '2025-12-25', 'CMP PT'],
            
            7  => [5455, '2025-12-25', 'CMP PT'],
            
            // ANOMALI (Saldo ada, tapi status KOSONG & Umur 0)
            14 => [-10,  null,         null], 
            16 => [13,   null,         null], 

            17 => [5289, '2025-12-24', 'CMP PT'],
            19 => [4875, '2025-12-25', 'CMP PT'],
            20 => [5671, '2025-12-22', 'CMP PT'],
            21 => [6091, '2025-12-31', 'DS'],
            22 => [5562, '2025-12-25', 'CMP PT'],
            23 => [2570, '2025-12-31', 'DS'],
            24 => [5449, '2025-12-25', 'CMP PT'],
            25 => [5548, '2025-12-25', 'CMP PT'],

            // --- BARIS KANAN ---
            33 => [5675, '2025-12-30', 'DS'],
            34 => [5333, '2025-12-18', 'CMP INHUT'],
            35 => [6251, '2025-12-31', 'DS'],
            36 => [5113, '2025-12-29', 'CMP PT'],
            37 => [5546, '2025-12-26', 'CMP PT'],
            38 => [5440, '2025-12-24', 'CMP PT'],
            39 => [3936, '2025-12-23', 'CMP PT'],
            40 => [5613, '2025-12-31', 'DS'],
            41 => [5409, '2025-12-22', 'CMP PT'],
            42 => [5535, '2025-12-26', 'CMP PT'],
            43 => [5665, '2025-12-29', 'CMP PT'],
            44 => [5672, '2025-12-30', 'DS'],
            45 => [5936, '2025-12-29', 'CMP PT'], 
            46 => [5744, '2025-12-29', 'CMP PT'],
            47 => [5914, '2025-12-31', 'DS'],
            48 => [5701, '2025-12-31', 'DS'],
            49 => [5120, '2025-12-13', 'CMP PT'],
        ];

        // Loop Update Data
        foreach ($dataReal as $noBak => $val) {
            $this->updateBakMaturasi($noBak, $val[0], $val[1], $val[2], $targetDate);
        }

        $this->command->info('SELESAI! Data Maturasi telah di-reset ke saldo awal tahun (162.395 Kg).');
        $this->command->info('Master Lokasi & Mutu telah diisi.');
    }

    // --- LOGIKA UPDATE ---
    private function updateBakMaturasi($noBak, $berat, $tglMasuk, $asal, $targetDate)
    {
        $bak = Maturasi::where('uraian', "Di Bak Maturasi-$noBak")->first();
        if (!$bak) return;

        // Hitung Umur (Target 1 Jan 2026)
        $umur = 0;
        $keterangan = 'KOSONG';
        $tglInject = '2025-12-31'; // Default untuk yang tgl kosong

        if ($tglMasuk) {
            $diff = Carbon::parse($tglMasuk)->diffInDays(Carbon::parse($targetDate));
            $umur = $diff; 
            $keterangan = Carbon::parse($tglMasuk)->format('d-M-y'); // Format spt Excel
            $tglInject = $tglMasuk;
        } else {
            // Kasus Bak 14 & 16
            $keterangan = 'KOSONG';
        }

        // A. Update Master Maturasi
        $bak->update([
            'stok_awal'  => $berat,
            'stok_akhir' => $berat,
            'tgl_masuk'  => $tglMasuk,
            'umur'       => $umur,
            'asal_bokar' => $asal,
            'keterangan' => $keterangan,
            'updated_at' => $targetDate
        ]);

        // B. Insert Log Pengolahan (PENTING UNTUK HISTORY)
        PengolahanMaturasi::create([
            'id_maturasi' => $bak->id_maturasi,
            'tgl_laporan' => $tglInject,
            'masuk_hi'    => $berat,
            'diolah'      => 0,
            'mutasi'      => 0,
            'keterangan'  => 'Saldo Awal Tahun'
        ]);
    }
}