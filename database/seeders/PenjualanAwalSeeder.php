<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PenjualanSir20;
use Carbon\Carbon;

class PenjualanAwalSeeder extends Seeder
{
    public function run()
    {
        $tanggalSetting = Carbon::create(2025, 11, 30); 
        $totalPtnbl = 4959360; 

        // 1. Data SIR20 PTNBL
        PenjualanSir20::updateOrCreate(
            [
                'tanggal' => $tanggalSetting->format('Y-m-d'),
                'uraian'  => 'SIR20 PTNBL'
            ],
            [
                'sd_bulan_lalu'     => 0,
                'bln_ini_lalu'      => 0,
                'hari_ini'          => 0,
                'total_bln_ini'     => 0,
                'total_sd_hari_ini' => $totalPtnbl, 
                'keterangan'        => 'Saldo Awal via Seeder (s/d Nov)'
            ]
        );

        // 2. Data SIR20 PTPN4
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