<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TransaksiApiBokar;
use App\Models\PengolahanBasah;

class PengolahanBasahSeeder extends Seeder
{
    public function run()
    {
        DB::table('transaksi_api_bokar')->truncate();

        $this->command->info('Menghitung Saldo Balancing (Metode Netto Kering)...');

        $tanggalSaldo = '2025-12-31';

        // 1. Target Saldo Akhir Excel
        $targetSaldoExcel = [
            'DS'    => 39185,
            'PT'    => 19520,
            'INHUT' => 9394,
        ];

        // 2. Hitung Penggunaan Real (Berdasarkan Netto Kering)
        // KARENA CONTROLLER MENGHITUNG STOK BERDASARKAN NETTO KERING
        $usageMaturasi = PengolahanBasah::select('jenis', DB::raw('SUM(netto_kering) as total_pakai'))
            ->groupBy('jenis')
            ->pluck('total_pakai', 'jenis')
            ->toArray();

        $config = [
            'DS'    => ['api' => 'petani', 'uraian' => 'Pembelian Bokar Rakyat / Petani'],
            'PT'    => ['api' => 'ptpn',   'uraian' => 'Pembelian Bokar PT.PN Kebun Batulicin'],
            'INHUT' => ['api' => 'inhut',  'uraian' => 'Pembelian Bokar PT. INHUTANI 1'],
        ];

        foreach ($config as $jenis => $meta) {
            $sisaDiGudang = $targetSaldoExcel[$jenis] ?? 0;
            $sudahDipakai = $usageMaturasi[$jenis] ?? 0;

            // RUMUS: Total Masuk = Sisa Target + Total Keluar (Kering)
            $totalInputMasuk = $sisaDiGudang + $sudahDipakai;

            TransaksiApiBokar::create([
                'tanggal'          => $tanggalSaldo,
                'kode_api'         => $meta['api'],
                'masuk_hi'         => $totalInputMasuk, 
                'masuk_sd_kemarin' => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $this->command->info("-> Jenis $jenis: Sisa ($sisaDiGudang) + Dipakai ($sudahDipakai) = INPUT ($totalInputMasuk)");
        }

        $this->command->info("BERHASIL! Data Input sudah disinkronkan dengan Controller.");
    }
}