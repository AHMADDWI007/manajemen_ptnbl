<?php

namespace App\Exports;

use App\Models\HasilUjiLabMaturasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class HasilUjiMaturasiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate, $endDate;

    public function __construct($startDate = null, $endDate = null) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection() {
        return HasilUjiLabMaturasi::with('maturasi')->when($this->startDate && $this->endDate, function($q) {
            $q->whereBetween('tanggal', [$this->startDate, $this->endDate]);
        })->orderBy('tanggal', 'desc')->get();
    }

    public function headings(): array {
        $periode = ($this->startDate && $this->endDate) 
            ? Carbon::parse($this->startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y') 
            : 'Semua Data';

        return [
            ['LAPORAN HASIL UJI MATURASI'],
            ['Periode: ' . $periode],
            [],
            ['Tanggal Uji', 'No. Kamar', 'K3 (%)', 'Po', 'Pa', 'PRI']
        ];
    }

    public function map($row): array {
        return [
            Carbon::parse($row->tanggal)->format('d-m-Y'),
            $row->maturasi->uraian ?? '-',
            $row->k3, $row->po, $row->pa, $row->pri
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A4:F' . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'E2EFDA']], 'alignment' => ['horizontal' => 'center']]
        ];
    }
}