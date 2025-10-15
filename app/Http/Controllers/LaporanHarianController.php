<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanHarianExport;

class LaporanHarianController extends Controller
{
    public function index(Request $request)
    {
        $tgl_awal = $request->tgl_awal ?? date('Y-m-d');
        $tgl_akhir = $request->tgl_akhir ?? date('Y-m-d');

        $laporan = LaporanHarian::whereBetween('tanggal', [$tgl_awal, $tgl_akhir])
                    ->orderBy('tanggal', 'desc')
                    ->get();

        return view('laporan.harian', compact('laporan','tgl_awal','tgl_akhir'));
    }

    public function exportExcel(Request $request)
    {
        $tgl_awal = $request->tgl_awal ?? date('Y-m-d');
        $tgl_akhir = $request->tgl_akhir ?? date('Y-m-d');

        return Excel::download(new LaporanHarianExport($tgl_awal,$tgl_akhir),'laporan_harian.xlsx');
    }
}
