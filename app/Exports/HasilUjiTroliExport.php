<?php

namespace App\Exports;

use App\Models\HasilUjiLabTroli;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class HasilUjiTroliExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate, $endDate;

    public function __construct($startDate = null, $endDate = null) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection() {
        return HasilUjiLabTroli::when($this->startDate && $this->endDate, function($q) {
            $q->whereBetween('tanggal', [$this->startDate, $this->endDate]);
        })->orderBy('tanggal', 'desc')->get();
    }

    public function headings(): array {
        $periode = ($this->startDate && $this->endDate) 
            ? Carbon::parse($this->startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y') 
            : 'Semua Data';

        return [
            ['LAPORAN HASIL UJI TROLI'],
            ['Periode: ' . $periode],
            [],
            ['Tanggal', 'No. Trolly', 'K3 (%)', 'Po', 'Pa', 'PRI', 'Jam Sample']
        ];
    }

    public function map($row): array {
        return [
            Carbon::parse($row->tanggal)->format('d-m-Y'),
            $row->no_trolly,
            $row->k3, $row->po, $row->pa, $row->pri,
            $row->jam_sample
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A4:G' . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'E2EFDA']], 'alignment' => ['horizontal' => 'center']]
        ];
    }
}