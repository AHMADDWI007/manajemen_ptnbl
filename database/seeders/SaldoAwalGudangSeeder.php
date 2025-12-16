<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProduksiSir;
use Carbon\Carbon;

class SaldoAwalGudangSeeder extends Seeder
{
    public function run()
    {
        // Tentukan tanggal "Masa Lalu" (Misal: Kemarin)
        // Agar saat kita input hari ini, data ini sudah dianggap 'Lalu'
        $tanggalSetting = Carbon::yesterday(); 
        
        // ISI STOK AWAL ANDA DI SINI (Contoh Data)
        $stokAwal = [
            'Di Gudang SIR'       => 456.00,   // Stok fisik Gudang SIR
            'Di Areal Press Bale' => 200.00,   // Stok fisik Press Bale
            'Di Gudang TOH 1'     => 0.00,
            'Di Gudang TOH 2'     => 0.00,
        ];

        foreach ($stokAwal as $uraian => $jumlahStok) {
            ProduksiSir::create([
                'uraian'        => $uraian,
                'saldo_awal'    => 0,
                'masuk'         => 0,
                'total'         => 0,
                'pengiriman'    => 0,
                
                // KUNCI: Kita isi Saldo Akhir-nya. 
                // Karena logika sistem: Saldo Awal Hari Ini = Saldo Akhir Kemarin.
                'saldo_akhir'   => $jumlahStok, 
                
                'created_at'    => $tanggalSetting,
                'updated_at'    => $tanggalSetting,
                'keterangan'    => 'Setup Saldo Awal Sistem'
            ]);
        }
    }
}