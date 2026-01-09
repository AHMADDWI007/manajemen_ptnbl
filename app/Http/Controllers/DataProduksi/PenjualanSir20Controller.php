<?php

namespace App\Http\Controllers\DataProduksi;

use App\Http\Controllers\Controller;
use App\Models\PenjualanSir20;
use App\Models\ProduksiSir; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PenjualanSir20Controller extends Controller
{
    // --- FUNGSI RECALCULATE DAN SIMPAN (DIPANGGIL DARI GUDANG CONTROLLER) ---
    public function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
    {
        $tglCarbon = Carbon::parse($tgl);
        
        // 1. Ambil Data Akhir Bulan Lalu (untuk saldo awal bulan ini)
        $lastMonthDate = $tglCarbon->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)
                                       ->whereDate('tanggal', $lastMonthDate)
                                       ->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;

        // 2. Ambil Data Kemarin (untuk akumulasi bulan ini)
        $yesterday = $tglCarbon->copy()->subDay();
        $dataKemarin = PenjualanSir20::where('uraian', $uraian)
                                     ->whereDate('tanggal', $yesterday)
                                     ->first();
                                     
        // Jika tanggal 1, akumulasi bulan ini direset jadi 0
        $bln_ini_lalu = ($tglCarbon->day == 1) ? 0 : ($dataKemarin ? ($dataKemarin->bln_ini_lalu + $dataKemarin->hari_ini) : 0);

        // 3. Hitung Total Baru
        $total_bln_ini = $bln_ini_lalu + $hari_ini;
        $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

        // 4. Simpan ke Database
        PenjualanSir20::updateOrCreate(
            ['tanggal' => $tglCarbon->format('Y-m-d'), 'uraian' => $uraian],
            [
                'sd_bulan_lalu'     => $sd_bulan_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan'        => $keterangan
            ]
        );
    }
    
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        $headerBulanLalu = $selectedDate->copy()->subMonth()->translatedFormat('F Y');
        
        // Ambil data hari ini, kemarin, dan akhir bulan lalu untuk kalkulasi tampilan
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');
        
        $yesterday = $selectedDate->copy()->subDay();
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');
        
        $lastMonthDate = $selectedDate->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonthDate)->get()->keyBy('uraian');

        $masterUraian = [
            '5.1' => 'SIR20 PTNBL',
            '5.2' => 'SIR20 PTPN4',
        ];

        $tabelData = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB[$uraian] ?? null;
            $itemKemarin = $dataKemarin[$uraian] ?? null;
            $itemBulanLalu = $dataBulanLalu[$uraian] ?? null;

            // Logika Saldo Awal (s/d Bulan Lalu)
            if ($itemToday) {
                $sd_bulan_lalu = $itemToday->sd_bulan_lalu;
            } else {
                $sd_bulan_lalu = $itemBulanLalu ? $itemBulanLalu->total_sd_hari_ini : 0;
            }

            // Logika Akumulasi Bulan Ini (Yg Lalu)
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0;
            } else {
                if ($itemToday) {
                    $bln_ini_lalu = $itemToday->bln_ini_lalu;
                } else {
                    $bln_ini_lalu = $itemKemarin ? ($itemKemarin->bln_ini_lalu + $itemKemarin->hari_ini) : 0;
                }
            }

            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;
            $total_bln_ini = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

            $tabelData->push((object)[
                'id_penjualan_sir20' => $itemToday->id_penjualan_sir20 ?? null, // 🔥 PK Baru
                'no'                => $no,
                'uraian'            => $uraian,
                'sd_bulan_lalu'     => $sd_bulan_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan'        => $itemToday->keterangan ?? '-',
            ]);
        }

        return view('DataProduksi.penjualan-sir20', [
            'tabelData'       => $tabelData,
            'selected_date'   => $selectedDate->format('Y-m-d'),
            'headerBulanLalu' => $headerBulanLalu
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'  => 'required|date',
            'uraian'   => 'required|string',
            'hari_ini' => 'required|numeric|min:0',
        ]);

        $this->recalculateAndSave(
            $request->tanggal, 
            $request->uraian, 
            $request->hari_ini, 
            $request->keterangan
        );
        
        return redirect()->route('penjualan-sir20.index', ['filter_tanggal' => Carbon::parse($request->tanggal)->format('Y-m-d')])
                         ->with('success', 'Data Penjualan berhasil disimpan.');
    }

    // --- FUNGSI BARU UNTUK AJAX (Ambil data dari Gudang) ---
    public function getPengirimanGudang(Request $request)
    {
        $date = $request->date;
        
        // Ambil data pengiriman dari Tabel IV (Gudang SIR) di hari yang sama
        $gudangSir = ProduksiSir::where('uraian', 'Di Gudang SIR')
                                ->whereDate('created_at', $date)
                                ->first();
                        
        $pengiriman = $gudangSir ? $gudangSir->pengiriman : 0;
        
        return response()->json(['pengiriman' => $pengiriman]);
    }

    public function destroy($id)
    {
        // 🔥 [PERBAIKAN] Cari menggunakan PK Baru
        $data = PenjualanSir20::find($id); // find() otomatis cari di PK model
        
        if ($data) {
            $data->delete();
            return back()->with('success', 'Data berhasil di-reset.');
        }
        return back();
    }
}