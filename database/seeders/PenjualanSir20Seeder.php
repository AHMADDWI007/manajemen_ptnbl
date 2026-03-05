<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PenjualanSir20;
use Illuminate\Support\Facades\DB;

class PenjualanSir20Seeder extends Seeder
{
    public function run()
    {
        // 1. Bersihkan data lama supaya tidak dobel saat di-seed ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PenjualanSir20::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =====================================================================
        // 🔥 DATA PENJUALAN JANUARI 2026 🔥
        // =====================================================================
        $tglJan = '2026-01-31';
        
        // Data Transaksi Real (is_summary = 0)
        PenjualanSir20::create([
            'tanggal'       => $tglJan,
            'uraian'        => 'SIR20 PTNBL',
            'no_kontrak'    => 'KTRK-JAN-001',
            'no_invoice'    => 'INV-JAN-001',
            'pallet'        => 480, // Asumsi 604.800 kg / 1260 kg per pallet
            'hari_ini'      => 604800,
            'harga'         => 20000, // Harga dummy
            'no_palet_list' => 'DUMMY-JAN-001',
            'is_summary'    => 0
        ]);

        // Data Summary Akhir Bulan (is_summary = 1)
        PenjualanSir20::create([
            'tanggal'           => $tglJan,
            'uraian'            => 'SIR20 PTNBL',
            'sd_bulan_lalu'     => 0,
            'bln_ini_lalu'      => 0,
            'hari_ini'          => 604800,
            'total_bln_ini'     => 604800,
            'total_sd_hari_ini' => 604800,
            'keterangan'        => 'Seeder Januari',
            'is_summary'        => 1
        ]);


        // =====================================================================
        // 🔥 DATA PENJUALAN FEBRUARI 2026 🔥
        // =====================================================================
        $tglFeb = '2026-02-28';
        
        // Data Transaksi Real (is_summary = 0)
        // Nilai hari_ini = 483.840 didapat dari (Target Total 1.088.640 - Saldo Jan 604.800)
        PenjualanSir20::create([
            'tanggal'       => $tglFeb,
            'uraian'        => 'SIR20 PTNBL',
            'no_kontrak'    => 'KTRK-FEB-001',
            'no_invoice'    => 'INV-FEB-001',
            'pallet'        => 384, // Asumsi 483.840 kg / 1260 kg per pallet
            'hari_ini'      => 483840,
            'harga'         => 20000, // Harga dummy
            'no_palet_list' => 'DUMMY-FEB-001',
            'is_summary'    => 0
        ]);

        // Data Summary Akhir Bulan (is_summary = 1)
        PenjualanSir20::create([
            'tanggal'           => $tglFeb,
            'uraian'            => 'SIR20 PTNBL',
            'sd_bulan_lalu'     => 604800,   // Ambil dari total Januari
            'bln_ini_lalu'      => 0,
            'hari_ini'          => 483840,   // Penjualan murni di Februari
            'total_bln_ini'     => 483840,
            'total_sd_hari_ini' => 1088640,  // 🔥 TOTAL AKHIR FEBRUARI (1.088.640)
            'keterangan'        => 'Seeder Februari',
            'is_summary'        => 1
        ]);


        // =====================================================================
        // 🔥 DATA SUMMARY KOSONG UNTUK PTPN4 (Biar tabel rapi) 🔥
        // =====================================================================
        PenjualanSir20::create([
            'tanggal'           => $tglFeb,
            'uraian'            => 'SIR20 PTPN4',
            'sd_bulan_lalu'     => 0,
            'bln_ini_lalu'      => 0,
            'hari_ini'          => 0,
            'total_bln_ini'     => 0,
            'total_sd_hari_ini' => 0,
            'keterangan'        => '-',
            'is_summary'        => 1
        ]);

        $this->command->info("BERHASIL: Data Penjualan SIR20 Januari & Februari telah dibuat. Total s/d Februari = 1.088.640 kg!");
    }
}