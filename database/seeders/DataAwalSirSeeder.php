<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\Lokasi;
use App\Models\ProduksiSir; // Pastikan model ini ada (Header Produksi Gudang)
use App\Models\LokasiPallet;
use App\Models\KondisiPallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataAwalSirSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Database Dulu (Opsional, biar bersih saat testing)
        // Hati-hati, ini menghapus data lama!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Lokasi::truncate();
        Mutu::truncate();
        Pallet::truncate();
        LokasiPallet::truncate();
        KondisiPallet::truncate();
        // ProduksiSir::truncate(); // Uncomment jika perlu
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Insert Master LOKASI
        $locGudangSIR = Lokasi::create(['nama' => 'Di Gudang SIR']);
        $locPress     = Lokasi::create(['nama' => 'Di Areal Press Bale']);
        $locTOH1      = Lokasi::create(['nama' => 'Di Gudang TOH 1']);
        $locTOH2      = Lokasi::create(['nama' => 'Di Gudang TOH 2']);
        $locRepacking = Lokasi::create(['nama' => 'Area Repacking']);

        // 3. Insert Master MUTU
        $mutuPrima   = Mutu::create(['uraian' => 'Mutu Prima (siap jual)']);
        $mutuLow     = Mutu::create(['uraian' => 'PO / PRI Low']);
        $mutuWS      = Mutu::create(['uraian' => 'WhiteSpot (WS)']);
        $mutuKontam  = Mutu::create(['uraian' => 'Kontaminasi']);
        $mutuRepack  = Mutu::create(['uraian' => 'Repacking On Hold']);

        // =====================================================================
        // 🔥 GENERATE SALDO AWAL (CUT OFF 31 DESEMBER 2025) 🔥
        // =====================================================================
        // Target: 157.500 Kg / 125 Pallet
        // Lokasi: Di Gudang SIR
        // Mutu: Prima
        // =====================================================================
        
        $tanggalCutOff = '2025-12-31'; // Kita set tanggal kemarin agar jadi Saldo Awal besok
        $totalPallet   = 125;
        $beratPerPallet= 1260; // 157.500 / 125
        $totalKg       = $totalPallet * $beratPerPallet;

        // A. Buat Header Produksi SIR (Sebagai Induk/Arsip)
        // Kita masukkan langsung ke tabel produksi_sir (bukan produksi_sir20)
        // karena ini data cut-off gudang.
        $idProduksi = DB::table('produksi_sir')->insertGetId([
            'tanggal_produksi' => $tanggalCutOff,
            'kg'               => $totalKg,
            'pallet'           => $totalPallet,
            'keterangan'       => 'Saldo Awal Tahun 2026 (Cut Off)',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // B. Generate 125 Pallet Fisik
        for ($i = 1; $i <= $totalPallet; $i++) {
            // Generate Nomor Pallet (Misal: 20251231-0001)
            $noPallet = Carbon::parse($tanggalCutOff)->format('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            // 1. Insert Pallet
            $pallet = Pallet::create([
                'id_produksi_sir'  => $idProduksi,
                'no_pallet'        => $noPallet,
                'berat'            => $beratPerPallet,
                'tanggal_produksi' => $tanggalCutOff,
            ]);

            // 2. Insert Lokasi (Gudang SIR)
            LokasiPallet::create([
                'id_lokasi' => $locGudangSIR->id_lokasi,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggalCutOff
            ]);

            // 3. Insert Kondisi (Mutu Prima)
            KondisiPallet::create([
                'id_mutu'   => $mutuPrima->id_mutu,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggalCutOff
            ]);
        }

        // C. (Opsional) Isi Tabel stok_sir jika ingin dipakai kedepannya
        DB::table('stok_sir')->insert([
            'id_lokasi'  => $locGudangSIR->id_lokasi,
            'saldo_awal' => $totalKg,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("BERHASIL: Saldo Awal 157.500 Kg (125 Pallet) tgl 31/12/2025 telah dibuat!");
    }
}