<?php

namespace App\Http\Controllers;

use App\Models\PenjualanSir20;
use App\Models\ProduksiSir; // PENTING: Import Model Data SIR
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PenjualanSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        $headerBulanLalu = $selectedDate->copy()->subMonth()->translatedFormat('F Y');

        // =========================================================================
        // SIAPKAN DATA PENJUALAN
        // =========================================================================
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

            // --- A. Hitung s/d Bulan Lalu ---
            if ($itemToday) {
                $sd_bulan_lalu = $itemToday->sd_bulan_lalu;
            } else {
                $sd_bulan_lalu = $itemBulanLalu ? $itemBulanLalu->total_sd_hari_ini : 0;
            }

            // --- B. Hitung Yg Lalu (Bulan Ini) ---
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0;
            } else {
                if ($itemToday) {
                    $bln_ini_lalu = $itemToday->bln_ini_lalu;
                } else {
                    $bln_ini_lalu = $itemKemarin ? ($itemKemarin->bln_ini_lalu + $itemKemarin->hari_ini) : 0;
                }
            }

            // --- C. HARI INI (INPUTAN) ---
            // Logika auto-fill dari Data SIR dihapus sesuai permintaan.
            // Sekarang murni inputan manual di menu ini.
            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;

            // --- D. Hitung Total ---
            $total_bln_ini = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

            $tabelData->push((object)[
                'id' => $itemToday->id ?? null,
                'no' => $no,
                'uraian' => $uraian,
                'sd_bulan_lalu' => $sd_bulan_lalu,
                'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini,
                'total_bln_ini' => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $itemToday->keterangan ?? '-',
            ]);
        }

        return view('Pengolahan.penjualan_sir20', [
            'tabelData' => $tabelData,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'headerBulanLalu' => $headerBulanLalu
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'hari_ini' => 'required|numeric|min:0',
        ]);

        $tgl = Carbon::parse($request->tanggal);
        $uraian = $request->uraian;
        $hari_ini = $request->hari_ini;

        // 1. Cari Data Akhir Bulan Lalu
        $lastMonthDate = $tgl->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)
                            ->whereDate('tanggal', $lastMonthDate)
                            ->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;

        // 2. Cari Data Kemarin
        $yesterday = $tgl->copy()->subDay();
        $dataKemarin = PenjualanSir20::where('uraian', $uraian)
                        ->whereDate('tanggal', $yesterday)
                        ->first();
        
        if ($tgl->day == 1) {
            $bln_ini_lalu = 0; 
        } else {
            $bln_ini_lalu = $dataKemarin ? ($dataKemarin->bln_ini_lalu + $dataKemarin->hari_ini) : 0;
        }

        // 3. Kalkulasi Total
        $total_bln_ini = $bln_ini_lalu + $hari_ini;
        $total_sd_hari_ini = $sd_bulan_lalu + $total_bln_ini;

        // 4. Simpan Data Penjualan
        PenjualanSir20::updateOrCreate(
            [
                'tanggal' => $tgl->format('Y-m-d'),
                'uraian' => $uraian
            ],
            [
                'sd_bulan_lalu' => $sd_bulan_lalu,
                'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini,
                'total_bln_ini' => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $request->keterangan
            ]
        );

        // =========================================================================
        // 5. UPDATE OTOMATIS KE DATA SIR (GUDANG) - BAGIAN PENGIRIMAN
        // =========================================================================
        
        // A. Hitung Total Semua Penjualan Hari Ini (PTNBL + PTPN4)
        $totalPenjualanHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                                    ->sum('hari_ini');

        // B. Cari Data Gudang SIR pada tanggal tersebut
        // Kita asumsikan semua penjualan mengurangi stok "Di Gudang SIR"
        $gudangSir = ProduksiSir::where('uraian', 'Di Gudang SIR')
                        ->whereDate('created_at', $tgl)
                        ->first();

        if ($gudangSir) {
            // Jika data gudang sudah ada, update kolom pengiriman dengan total penjualan
            $gudangSir->pengiriman = $totalPenjualanHariIni;
            
            // Hitung ulang saldo akhir di Gudang SIR
            $gudangSir->saldo_akhir = $gudangSir->total - $gudangSir->pengiriman;
            
            $gudangSir->save();
        } else {
            // Jika data gudang hari ini BELUM diinput sama sekali
            // Kita coba buatkan data gudang otomatis agar stok tetap sinkron
            
            // Ambil saldo akhir kemarin sebagai saldo awal
            $prevGudang = ProduksiSir::where('uraian', 'Di Gudang SIR')
                            ->whereDate('created_at', '<', $tgl)
                            ->orderBy('created_at', 'desc')
                            ->first();
            
            $saldoAwal = $prevGudang ? $prevGudang->saldo_akhir : 0;
            $prodBlnLalu = $prevGudang ? $prevGudang->prod_sd_hi : 0;
            
            $masuk = 0; // Belum diinput
            $total = $saldoAwal + $masuk;
            
            ProduksiSir::create([
                'uraian' => 'Di Gudang SIR',
                'created_at' => $tgl->format('Y-m-d H:i:s'),
                'saldo_awal' => $saldoAwal,
                'masuk' => 0,
                'total' => $total,
                'prod_bln_lalu' => $prodBlnLalu,
                'prod_sd_hi' => $prodBlnLalu, 
                'pengiriman' => $totalPenjualanHariIni, // Isi dengan nilai penjualan
                'saldo_akhir' => $total - $totalPenjualanHariIni,
                'keterangan' => 'Auto Sync Penjualan'
            ]);
        }

        return redirect()->route('penjualan_sir20.index', ['filter_tanggal' => $tgl->format('Y-m-d')])
                         ->with('success', 'Data Penjualan berhasil disimpan dan Data Gudang diperbarui.');
    }

    public function destroy($id)
    {
        $data = PenjualanSir20::find($id);
        
        if ($data) {
            $tgl = $data->tanggal;
            $data->delete();

            // SINKRONISASI SAAT HAPUS/RESET
            // Hitung ulang total penjualan hari ini setelah data dihapus
            $totalPenjualanHariIni = PenjualanSir20::whereDate('tanggal', $tgl)->sum('hari_ini');

            // Update Gudang SIR agar pengiriman berkurang sesuai data yang dihapus
            $gudangSir = ProduksiSir::where('uraian', 'Di Gudang SIR')
                            ->whereDate('created_at', $tgl)
                            ->first();
            
            if ($gudangSir) {
                $gudangSir->pengiriman = $totalPenjualanHariIni;
                $gudangSir->saldo_akhir = $gudangSir->total - $gudangSir->pengiriman;
                $gudangSir->save();
            }

            return back()->with('success', 'Data berhasil di-reset.');
        }
        return back();
    }
}