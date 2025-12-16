<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PenjualanSir20;
use Carbon\Carbon;

class PenjualanAwalSeeder extends Seeder
{
    public function run()
    {
        // KITA SETTING PER TANGGAL AKHIR NOVEMBER
        // Agar menjadi saldo awal untuk bulan Desember
        $tanggalSetting = Carbon::create(2025, 11, 30); 

        // HITUNGAN MANUAL ANDA:
        // 705600+604800+504000+504000+604800+604800+604800+403200+423360
        $totalPtnbl = 4959360; 

        // 1. Data SIR20 PTNBL
        PenjualanSir20::updateOrCreate(
            [
                'tanggal' => $tanggalSetting->format('Y-m-d'),
                'uraian'  => 'SIR20 PTNBL'
            ],
            [
                'sd_bulan_lalu'     => 0, // Tidak perlu diisi krn ini data cut-off
                'bln_ini_lalu'      => 0,
                'hari_ini'          => 0,
                'total_bln_ini'     => 0,
                
                // INI KUNCINYA: Total s/d Hari Ini (Akhir Nov)
                // Angka ini yang akan diambil sistem sebagai "s/d Bulan Lalu" saat input Desember
                'total_sd_hari_ini' => $totalPtnbl, 
                
                'keterangan'        => 'Saldo Awal via Seeder (s/d Nov)'
            ]
        );

        // 2. Data SIR20 PTPN4 (Kita buat 0 dulu agar tidak error/kosong)
        PenjualanSir20::updateOrCreate(
            [
                'tanggal' => $tanggalSetting->format('Y-m-d'),
                'uraian'  => 'SIR20 PTPN4'
            ],
            [
                'total_sd_hari_ini' => 0, 
                'keterangan'        => 'Saldo Awal 0'
            ]
        );
    }
}