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

        $targetDate = '2026-03-01'; 
        $this->command->info("Seeding LHP KHUSUS MATURASI - Target: $targetDate");

        // =================================================================
        // 1.B ISI MASTER LOKASI & MUTU
        // =================================================================
        $lokasi = ['Di Gudang SIR', 'Di Areal Press Bale'];
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
        // 3. INPUT DATA REAL (Berdasarkan Bagian II Excel)
        // =================================================================
        $dataReal = [
            7  => [5379, '2026-02-24', 'CMP INHUT'],
            17 => [4893, '2026-02-28', 'CMP PT'],
            20 => [5260, '2026-02-24', 'CMP INHUT'],
            21 => [5569, '2026-02-23', 'CMP INHUT'],
            22 => [5209, '2026-02-26', 'CMP PT'],
            25 => [5373, '2026-02-26', 'CMP PT'],
            37 => [2746, '2026-02-28', 'CMP PT'],
            38 => [5519, '2026-02-28', 'CMP PT'],
            39 => [5234, '2026-02-27', 'CMP INHUT'],
            40 => [4931, '2026-02-27', 'CMP INHUT'],
            41 => [5253, '2026-02-26', 'CMP PT'],
            43 => [5448, '2026-02-27', 'CMP INHUT'],
            45 => [5248, '2026-02-26', 'CMP INHUT'],
            46 => [5316, '2026-02-25', 'CMP INHUT'],
            48 => [5398, '2026-02-25', 'CMP PT'],
            49 => [5141, '2026-02-28', 'CMP PT'],
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
        $tglInject = '2026-02-28';

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
            'keterangan'  => 'Saldo Awal Bulan Maret'
        ]);
    }
}