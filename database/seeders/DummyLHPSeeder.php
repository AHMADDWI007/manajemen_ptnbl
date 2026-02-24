<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah; // Pastikan Model ini ada
use App\Models\HasilUjiLabMaturasi;
use App\Models\BahanProses;
use App\Models\Lokasi;
use App\Models\Mutu;
use Carbon\Carbon;

class DummyLHPSeeder extends Seeder
{
    public function run()
    {
        // =================================================================
        // 1. BERSIHKAN DATABASE
        // =================================================================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Maturasi::truncate();
        PengolahanBasah::truncate();
        PengolahanMaturasi::truncate();
        HasilUjiLabBokarDiolah::truncate(); // Kita akan isi ini agar history terbaca
        HasilUjiLabMaturasi::truncate();
        BahanProses::truncate();
        Lokasi::truncate();
        Mutu::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $targetDate = '2026-01-01'; 
        $this->command->info("Seeding LHP KHUSUS MATURASI - Target: $targetDate");

        // =================================================================
        // 1.B ISI MASTER LOKASI & MUTU
        // =================================================================
        $lokasi = ['Di Gudang SIR', 'Di Areal Press Bale', 'Di Gudang TOH 1', 'Di Gudang TOH 2', 'Area Repacking'];
        foreach ($lokasi as $l) Lokasi::create(['nama' => $l]);

        $mutu = ['Mutu Prima (siap jual)', 'PO / PRI Low', 'WhiteSpot (WS)', 'Kontaminasi', 'Repacking On Hold'];
        foreach ($mutu as $m) Mutu::create(['uraian' => $m]);

        // =================================================================
        // 2. SETUP 49 BAK KOSONG
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
        // 3. INPUT DATA REAL
        // =================================================================
        // Kita gunakan kode singkat, nanti fungsi updateBakMaturasi yang menerjemahkannya
        $dataReal = [
            // --- BARIS KIRI ---
            2  => [5654, '2025-12-26', 'CMP INHUT'], // Campuran DS & INHUT
            3  => [5629, '2025-12-26', 'CMP INHUT'],
            4  => [5468, '2025-12-26', 'CMP INHUT'],
            5  => [5528, '2025-12-25', 'CMP PT'],    // Campuran DS & PT
            7  => [5455, '2025-12-25', 'CMP PT'],
            
            // ANOMALI
            14 => [-10,  null,         null], 
            16 => [13,   null,         null], 

            17 => [5289, '2025-12-24', 'CMP PT'],
            19 => [4875, '2025-12-25', 'CMP PT'],
            20 => [5671, '2025-12-22', 'CMP PT'],
            21 => [6091, '2025-12-31', 'DS'],        // Murni DS
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

        foreach ($dataReal as $noBak => $val) {
            $this->updateBakMaturasi($noBak, $val[0], $val[1], $val[2], $targetDate);
        }

        $this->command->info('SELESAI! Data Maturasi & History Transaksi telah dibuat.');
    }

    // --- LOGIKA UTAMA ---
    // --- LOGIKA UTAMA (UPDATE V3: ISI K3 & NETTO KERING) ---
    // --- LOGIKA UTAMA (UPDATE FINAL: PAKSA KERING = BASAH UNTUK SALDO) ---
    private function updateBakMaturasi($noBak, $berat, $tglMasuk, $kodeAsal, $targetDate)
    {
        $bak = Maturasi::where('uraian', "Di Bak Maturasi-$noBak")->first();
        if (!$bak) return;

        // 1. Tentukan Komponen
        $komponen = []; 
        $labelAkhir = null;

        if ($kodeAsal === 'CMP PT') {
            $komponen = ['DS', 'PT'];
            $labelAkhir = 'CMP (DS, PT)';
        } elseif ($kodeAsal === 'CMP INHUT') {
            $komponen = ['DS', 'INHUT'];
            $labelAkhir = 'CMP (DS, INHUT)';
        } elseif ($kodeAsal === 'DS') {
            $komponen = ['DS'];
            $labelAkhir = 'DS';
        }

        // 2. Hitung Umur & Tanggal
        $umur = 0;
        $keterangan = 'KOSONG';
        $tglInject = '2025-12-31';

        if ($tglMasuk) {
            $diff = Carbon::parse($tglMasuk)->diffInDays(Carbon::parse($targetDate));
            $umur = $diff; 
            $keterangan = strtoupper(Carbon::parse($tglMasuk)->format('d M Y'));
            $tglInject = $tglMasuk;
        }

        // 3. 🔥 SIMULASI TRANSAKSI (TEKNIK BALANCING)
        // Agar hitungan stok pas, kita set Netto Kering = Netto Basah (K3 100%)
        // Khusus untuk data inisialisasi ini saja.
        if (!empty($komponen) && $berat > 0) {
            $beratPerBagian = $berat / count($komponen);
            
            foreach ($komponen as $jenis) {
                // A. Buat Induk (PengolahanBasah)
                $induk = PengolahanBasah::create([
                    'tanggal'       => $tglInject,
                    'id_maturasi'   => $bak->id_maturasi,
                    'jenis'         => $jenis,
                    'berat_truck'   => 0, 
                    'berat_timbang' => $beratPerBagian, 
                    'netto_basah'   => $beratPerBagian,
                    'k3'            => 100,             // 🔥 SET 100% AGAR HITUNGAN MUDAH
                    'netto_kering'  => $beratPerBagian, // 🔥 KERING = BASAH (KUNCI BALANCING)
                ]);

                // B. Buat Data Lab
                HasilUjiLabBokarDiolah::create([
                    'id_pengolahan_basah' => $induk->id_pengolahan_basah, 
                    'id_maturasi'         => $bak->id_maturasi,
                    'tanggal'             => $tglInject,
                    'jenis'               => $jenis, 
                    'netto_basah'         => $beratPerBagian, 
                    'netto_kering'        => $beratPerBagian, // 🔥 KERING = BASAH
                    'k3'                  => 100, 
                ]);
            }
        }

        // 4. Update Master Maturasi
        $bak->update([
            'stok_awal'    => $berat,
            'stok_akhir'   => $berat,
            'tgl_masuk'    => $tglInject, 
            'umur'         => $umur,
            'asal_bokar'   => $labelAkhir, 
            'keterangan'   => $keterangan,
            'updated_at'   => $targetDate
        ]);

        // 5. Insert Log Harian
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