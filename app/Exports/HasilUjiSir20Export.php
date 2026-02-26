<?php

namespace App\Exports;

use App\Models\HasilUjiLabSIR20;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class HasilUjiSir20Export implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate, $endDate, $status;

    public function __construct($startDate = null, $endDate = null, $status = 'all') {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
    }

    public function collection() {
        $query = HasilUjiLabSIR20::query();
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tanggal', [$this->startDate, $this->endDate]);
        }
        if ($this->status === 'low') {
            $query->where(function($q) {
                $q->where('pri', '<', 40)->orWhere('po', '<', 30);
            });
        }
        return $query->orderBy('tanggal', 'desc')->get();
    }

    public function headings(): array {
        $periode = ($this->startDate && $this->endDate) 
            ? Carbon::parse($this->startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y') 
            : 'Semua Data';

        return [
            ['LAPORAN HASIL UJI SIR 20'],
            ['Periode: ' . $periode . ($this->status === 'low' ? ' (Hanya Low Mutu)' : '')],
            [],
            ['Tanggal', 'Jenis Kemasan', 'No. Palet', 'Po', 'Pa', 'PRI', 'Dirt (%)', 'Ash (%)', 'VM (%)', 'Mooney', 'Nitrogen (%)']
        ];
    }

    public function map($row): array {
        return [
            Carbon::parse($row->tanggal)->format('d-m-Y'),
            $row->jenis_kemasan, $row->no_palet,
            $row->po, $row->pa, $row->pri,
            $row->dirt, $row->ash, $row->vm, $row->money, $row->nitrogen
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A4:K' . $highestRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ]);
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'E2EFDA']], 'alignment' => ['horizontal' => 'center']]
        ];
    }
}