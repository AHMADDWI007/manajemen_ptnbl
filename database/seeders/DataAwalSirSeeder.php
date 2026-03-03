<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\Lokasi; 
use App\Models\LokasiPallet;
use App\Models\KondisiPallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataAwalSirSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Database Dulu 
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Lokasi::truncate();
        Mutu::truncate();
        Pallet::truncate();
        LokasiPallet::truncate();
        KondisiPallet::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Insert Master LOKASI
        $locGudangSIR = Lokasi::create(['nama' => 'Di Gudang SIR']);
        $locPress     = Lokasi::create(['nama' => 'Di Areal Press Bale']);
        $locTOH1      = Lokasi::create(['nama' => 'Di Gudang TOH 1']);
        $locTOH2      = Lokasi::create(['nama' => 'Di Gudang TOH 2']);

        // 3. Insert Master MUTU
        $mutuPrima   = Mutu::create(['uraian' => 'Mutu Prima (siap jual)']);
        $mutuLow     = Mutu::create(['uraian' => 'PO / PRI Low']);
        $mutuWS      = Mutu::create(['uraian' => 'WhiteSpot (WS)']);
        $mutuKontam  = Mutu::create(['uraian' => 'Kontaminasi']);
        $mutuRepack  = Mutu::create(['uraian' => 'Repacking On Hold']);

        // =====================================================================
        // 🔥 GENERATE SALDO AWAL (CUT OFF 31 DESEMBER 2025) 🔥
        // =====================================================================
        
        $tanggalCutOff = '2025-12-31'; 
        $totalPallet   = 125;
        $beratPerPallet= 1260; // 157.500 / 125
        $totalKg       = $totalPallet * $beratPerPallet;

        $idProduksi = DB::table('produksi_sir')->insertGetId([
            'tanggal_produksi' => $tanggalCutOff,
            'kg'               => $totalKg,
            'pallet'           => $totalPallet,
            'keterangan'       => 'Saldo Awal Tahun 2026 (Cut Off)',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // 🔥 PERBAIKAN: Generate 125 Pallet Fisik dengan format PLT-YY-XXXX
        $tahunSingkat = Carbon::parse($tanggalCutOff)->format('y'); // Menghasilkan "25"

        for ($i = 1; $i <= $totalPallet; $i++) {
            // Hasil: PLT-25-0001, PLT-25-0002, dst
            $noPallet = $tahunSingkat . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            $pallet = Pallet::create([
                'id_produksi_sir'  => $idProduksi,
                'no_pallet'        => $noPallet,
                'berat'            => $beratPerPallet,
                'tanggal_produksi' => $tanggalCutOff,
            ]);

            LokasiPallet::create([
                'id_lokasi' => $locGudangSIR->id_lokasi,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggalCutOff
            ]);

            KondisiPallet::create([
                'id_mutu'   => $mutuPrima->id_mutu,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggalCutOff
            ]);
        }

        DB::table('stok_sir')->insert([
            'id_lokasi'  => $locGudangSIR->id_lokasi,
            'saldo_awal' => $totalKg,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info("BERHASIL: Saldo Awal(125 Pallet dengan format YY-XXXX) telah dibuat!");
    }
}