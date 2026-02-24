<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanHarianSheet implements FromView, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $judul;

    /**
     * @param array $data   Data laporan harian dari controller
     * @param string $judul Judul untuk nama tab Excel (Contoh: "1 Jan 2026")
     */
    public function __construct($data, $judul)
    {
        $this->data  = $data;
        $this->judul = $judul;
    }

    public function view(): View
    {
        // Tetap menggunakan view yang sama agar konsisten
        return view('Cetak.cetak-excel', $this->data);
    }

    public function title(): string
    {
        // Mengembalikan judul yang sudah dibuat di LaporanBulananExport
        return $this->judul;
    }
}