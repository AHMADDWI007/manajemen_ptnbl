<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;
use App\Http\Controllers\LaporanController;

class LaporanBulananExport implements WithMultipleSheets
{
    protected $bulan;
    protected $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function sheets(): array
    {
        $sheets = [];
        $startDate = Carbon::createFromDate($this->tahun, $this->bulan, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $controller = new LaporanController();

        // Looping harian untuk membuat Sheet
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $tglString = $date->format('Y-m-d');
            
            // 1. Buat Judul Sheet (Contoh: 01 Jan 2026)
            // Pakai 'j M Y' agar ringkas dan muat di tab Excel
            $judulSheet = $date->translatedFormat('j M Y');

            // 2. Ambil data harian melalui controller
            // Gunakan @ jika ingin menekan error visual di editor, tapi pastikan method-nya PUBLIC
            $dataHarian = $controller->getDataLaporan($tglString);
            
            // 3. Masukkan ke dalam array sheets dengan judul baru
            $sheets[] = new LaporanHarianSheet($dataHarian, $judulSheet);
        }

        return $sheets;
    }
}