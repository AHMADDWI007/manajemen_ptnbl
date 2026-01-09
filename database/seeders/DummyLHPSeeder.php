<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\TransaksiApiBokar;
use App\Models\HasilUjiLabBokarDiolah; // 🔥 [PERBAIKAN MODEL]
use App\Models\HasilUjiLabMaturasi;    // 🔥 [PERBAIKAN MODEL]
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DummyLHPSeeder extends Seeder
{
    public function run()
    {
        // 1. BERSIHKAN TABEL TRANSAKSI
        Schema::disableForeignKeyConstraints();
        Maturasi::truncate();
        PengolahanBasah::truncate();
        PengolahanMaturasi::truncate();
        TransaksiApiBokar::truncate();
        HasilUjiLabBokarDiolah::truncate();
        HasilUjiLabMaturasi::truncate();
        Schema::enableForeignKeyConstraints();

        $this->command->info('Data lama dibersihkan. Memulai seeding data LHP 1 Oktober 2025...');

        // 2. SETUP MASTER MATURASI (49 BAK KOSONG)
        for ($i = 1; $i <= 49; $i++) {
            Maturasi::create([
                'uraian' => "Di Bak Maturasi-$i",
                'stok_awal' => 0, 'stok_akhir' => 0, 'umur' => 0, 'keterangan' => 'KOSONG'
            ]);
        }

        // =================================================================
        // BAGIAN I: PENGADAAN BOKAR
        // =================================================================
        
        // A. STOK AWAL
        $this->seedApiBokar('2025-09-30', 'petani', 13708);
        $this->seedApiBokar('2025-09-30', 'ptpn', 41165);
        $this->seedApiBokar('2025-09-30', 'inhut', 12598);

        // B. TRANSAKSI HARI INI (1 Okt)
        $this->seedApiBokar('2025-10-01', 'petani', 13460);
        $this->seedApiBokar('2025-10-01', 'ptpn', 963);

        // C. DIOLAH (Dummy ke Bak 1)
        // 🔥 [PERBAIKAN] Ambil ID Maturasi (PK Baru)
        $bakDummy = Maturasi::first()->id_maturasi;

        $this->seedPengolahanBasah('2025-10-01', 'DS', 12242, $bakDummy);
        $this->seedPengolahanBasah('2025-10-01', 'PT', 3060, $bakDummy);


        // =================================================================
        // BAGIAN II: MATURASI
        // =================================================================
        
        $stokMaturasi = [
            3  => [5075, '2025-09-24', 'INHUT'],
            6  => [4813, '2025-08-20', 'PT'],
            7  => [5084, '2025-09-30', 'CMP'],
            9  => [5090, '2025-09-30', 'CMP'],
            10 => [2566, '2025-08-20', 'PT'],
            11 => [5248, '2025-09-29', 'CMP'],
            12 => [5248, '2025-09-30', 'CMP'],
            17 => [4979, '2025-09-27', 'CMP'],
            22 => [5227, '2025-09-27', 'CMP'],
            23 => [2364, '2025-09-27', 'CMP'],
            24 => [4941, '2025-09-27', 'CMP'],
            28 => [4927, '2025-09-29', 'CMP'],
            37 => [5278, '2025-08-16', 'PT'],
            40 => [4915, '2025-08-16', 'PT'],
            41 => [5163, '2025-09-26', 'DS'],
            42 => [5183, '2025-09-26', 'DS'],
            43 => [2438, '2025-09-30', 'CMP'],
            44 => [5109, '2025-09-30', 'CMP'],
            45 => [5103, '2025-09-29', 'CMP'],
            49 => [5267, '2025-09-25', 'CMP'],
        ];

        foreach ($stokMaturasi as $noBak => $data) {
            $this->seedMaturasi($noBak, $data[0], $data[1], $data[2]);
        }

        $masukHiMaturasi = [
            19 => 2092, 20 => 5361, 21 => 5216, 43 => 2633,
        ];

        foreach ($masukHiMaturasi as $noBak => $kgMasuk) {
            $this->seedMaturasiMasukHi($noBak, $kgMasuk, '2025-10-01');
        }

        $this->command->info('SELESAI! Data Dummy LHP 1 Oktober 2025 berhasil digenerate.');
    }

    // --- HELPER FUNCTIONS ---

    private function seedApiBokar($tgl, $kode, $kg) {
        if ($kg <= 0) return;
        TransaksiApiBokar::create([
            'tanggal' => $tgl,
            'kode_api' => $kode,
            'masuk_hi' => $kg,
            'masuk_sd_kemarin' => 0 
        ]);
    }

    private function seedPengolahanBasah($tgl, $jenis, $kgKering, $idMaturasi) {
        if ($kgKering <= 0) return;
        
        $k3 = 100; 
        $nettoBasah = $kgKering; 

        // 🔥 [PERBAIKAN] FK: id_maturasi
        PengolahanBasah::create([
            'tanggal' => $tgl,
            'id_maturasi' => $idMaturasi, 
            'jenis' => $jenis,
            'berat_truck' => 5000,
            'berat_timbang' => 5000 + $nettoBasah,
            'netto_basah' => $nettoBasah,
            'k3' => $k3,
            'netto_kering' => $kgKering
        ]);
    }

    private function seedMaturasi($noBak, $kg, $tglMasuk, $asal) {
        $bak = Maturasi::where('uraian', "Di Bak Maturasi-$noBak")->first();
        if (!$bak) return;

        // 🔥 [PERBAIKAN] FK: id_maturasi
        PengolahanMaturasi::create([
            'id_maturasi' => $bak->id_maturasi,
            'tgl_laporan' => $tglMasuk,
            'masuk_hi' => $kg,
            'diolah' => 0, 'mutasi' => 0,
            'keterangan' => 'Stok Awal Seeder'
        ]);

        $bak->stok_awal = 0;
        $bak->masuk_hi = $kg;
        $bak->stok_akhir = $kg;
        $bak->tgl_masuk = $tglMasuk;
        $bak->umur = Carbon::parse('2025-10-01')->diffInDays(Carbon::parse($tglMasuk));
        $bak->asal_bokar = $asal;
        $bak->keterangan = strtoupper(Carbon::parse($tglMasuk)->format('d M Y'));
        $bak->save();
    }

    private function seedMaturasiMasukHi($noBak, $kg, $tgl) {
        $bak = Maturasi::where('uraian', "Di Bak Maturasi-$noBak")->first();
        if (!$bak) return;

        // 🔥 [PERBAIKAN] FK: id_maturasi
        PengolahanMaturasi::create([
            'id_maturasi' => $bak->id_maturasi,
            'tgl_laporan' => $tgl,
            'masuk_hi' => $kg,
            'diolah' => 0, 'mutasi' => 0,
            'keterangan' => 'Masuk HI Seeder'
        ]);

        $stokBaru = $bak->stok_akhir + $kg;
        
        $bak->stok_awal = $bak->stok_akhir;
        $bak->masuk_hi = $kg;
        $bak->stok_akhir = $stokBaru;
        $bak->tgl_masuk = $tgl;
        $bak->umur = 0;
        $bak->keterangan = strtoupper(Carbon::parse($tgl)->format('d M Y'));
        
        if(empty($bak->asal_bokar)) $bak->asal_bokar = 'CMP';
        elseif ($bak->asal_bokar != 'CMP') $bak->asal_bokar = 'CMP';

        $bak->save();
    }
}