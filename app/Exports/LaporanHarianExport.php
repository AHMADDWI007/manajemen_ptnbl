<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView; // Pastikan pakai FromView
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Perhatikan: implements FromView (BUKAN FromCollection)
class LaporanHarianExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // Kita pakai fungsi view(), bukan collection()
    public function view(): View
    {
        return view('Cetak.cetak-excel', $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Menebalkan huruf di baris 1 sampai 7 (Area Header)
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            // Tambahkan baris lain jika ingin bold juga
        ];
    }
}