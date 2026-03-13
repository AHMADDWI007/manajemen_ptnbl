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
        DB::table('produksi_sir20')->truncate(); // 🔥 Tambahkan ini agar bersih
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
        // 🔥 GENERATE SALDO AWAL (CUT OFF 28 FEBRUARI 2026) 🔥
        // =====================================================================
        
        $tanggalCutOff = '2026-02-28'; 
        $totalPallet   = 83;
        $beratPerPallet= 1260; // 104.580 / 83
        $totalKg       = $totalPallet * $beratPerPallet;

        $idProduksi = DB::table('produksi_sir')->insertGetId([
            'tanggal_produksi' => $tanggalCutOff,
            'kg'               => $totalKg,
            'pallet'           => $totalPallet,
            'keterangan'       => 'Saldo Awal Bulan Maret 2026 (Cut Off)',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // =====================================================================
        // 🚀 DATA PANCINGAN: AGAR MARET MULAI DARI 823 🚀
        // =====================================================================
        DB::table('produksi_sir20')->insert([
            'tanggal_produksi'  => $tanggalCutOff,
            'shift_kerja'       => 'Shift 1',
            'nomor_start'       => 1,
            'nomor_end'         => 822,
            'total_nomor_akhir' => 822, // INI KUNCINYA, SISTEM AKAN MEMBACA INI!
            'keterangan'        => 'Data Pancingan (Setup Saldo Awal)',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // 🔥 PERBAIKAN: Generate 83 Pallet Fisik dengan format YY-XXXX
        $tahunSingkat = Carbon::parse($tanggalCutOff)->format('y'); // Menghasilkan "26"

        for ($i = 1; $i <= $totalPallet; $i++) {
            // Hasil: 26-0001, 26-0002, dst
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

        $this->command->info("BERHASIL: Saldo Awal(83 Pallet dengan format YY-XXXX) telah dibuat!");
    }
}