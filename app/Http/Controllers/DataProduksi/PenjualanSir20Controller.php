<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Pallet;
use Illuminate\Http\Request;
use App\Models\KondisiPallet;
use App\Models\PenjualanSir20;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PenjualanSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // Ambil stok resmi Mutu Prima dari tabel Gudang & Mutu (Data Gudang)
        $mutuPrima = Mutu::where('uraian', 'LIKE', '%Prima%')->first();
        $idMutuPrima = $mutuPrima ? $mutuPrima->id_mutu : 0;

        // Hitung Pallet yang Belum Terjual & Mutu TERAKHIR-nya adalah Prima
        $mutuTersedia = Pallet::whereNull('tanggal_penjualan')->count();
        $pallets = Pallet::whereNull('tanggal_penjualan')->get();
        
        foreach($pallets as $p) {
            $lastKondisi = KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->orderBy('id_kondisi_pallet', 'desc')
                            ->first();
            
            if ($lastKondisi && $lastKondisi->id_mutu == $idMutuPrima) {
                $mutuTersedia++;
            }
        }

        // =====================================================================
        // 🔥 LOGIKA BARU: HITUNG AKUMULASI TAHUNAN (RESET SAAT JANUARI) 🔥
        // =====================================================================
        $startOfYear = $selectedDate->copy()->startOfYear(); // 1 Januari tahun berjalan
        $startOfMonth = $selectedDate->copy()->startOfMonth(); // Tanggal 1 bulan berjalan
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->where('is_summary', 1)->get()->keyBy('uraian');

        $masterUraian = ['5.1' => 'SIR20 PTNBL', '5.2' => 'SIR20 PTPN4'];
        $tabelSummary = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB->get($uraian);

            // 1. Total S/D Bulan Lalu 
            // Jika Januari, otomatis 0. Jika bukan, sum dari 1 Januari s/d akhir bulan lalu.
            if ($selectedDate->month == 1) {
                $sd_bln_lalu = 0;
            } else {
                $sd_bln_lalu = PenjualanSir20::where('uraian', $uraian)
                    ->where('is_summary', 0)
                    ->whereBetween('tanggal', [
                        $startOfYear->format('Y-m-d'), 
                        $startOfMonth->copy()->subDay()->format('Y-m-d')
                    ])
                    ->sum('hari_ini');
            }

            // 2. Total Bulan Ini Yg Lalu = SUM dari tgl 1 bulan ini sampai H-1
            $bln_ini_lalu = PenjualanSir20::where('uraian', $uraian)
                ->where('is_summary', 0)
                ->whereBetween('tanggal', [
                    $startOfMonth->format('Y-m-d'), 
                    $selectedDate->copy()->subDay()->format('Y-m-d')
                ])
                ->sum('hari_ini');

            // 3. Hari Ini
            $hari_ini = PenjualanSir20::where('uraian', $uraian)
                ->where('is_summary', 0)
                ->whereDate('tanggal', $selectedDate)
                ->sum('hari_ini');

            $tabelSummary->push((object)[
                'id_penjualan_sir20' => $itemToday->id_penjualan_sir20 ?? null,
                'no'                => $no,
                'uraian'            => $uraian,
                'sd_bulan_lalu'     => $sd_bln_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $bln_ini_lalu + $hari_ini,
                'total_sd_hari_ini' => $sd_bln_lalu + ($bln_ini_lalu + $hari_ini),
                'keterangan'        => $itemToday->keterangan ?? '-',
            ]);
        }

        $riwayatKontrak = PenjualanSir20::where('is_summary', 0)->orderBy('tanggal', 'desc')->get();

        // Header dinamis: Jika bulan Januari, tampilkan string kosong atau '-' agar tidak muncul 's/d Desember'
        if ($selectedDate->month == 1) {
            $headerBulanLalu = $selectedDate->translatedFormat('F Y'); // Menghasilkan: Januari 2026
        } else {
            $headerBulanLalu = $selectedDate->copy()->subMonth()->translatedFormat('F Y'); // Menghasilkan bulan lalu
        }

        return view('DataProduksi.penjualan-sir20', [
            'tabelSummary'    => $tabelSummary,
            'riwayatKontrak'  => $riwayatKontrak,
            'selected_date'   => $selectedDate->format('Y-m-d'),
            'mutuTersedia'    => $mutuTersedia,
            'headerBulanLalu' => $headerBulanLalu
        ]);
    }

    public function getAvailableStock(Request $request)
    {
        try {
            $tgl = $request->query('date');

            $available = Pallet::whereNull('tanggal_penjualan')->get(['id_pallet', 'no_pallet', 'jenis_pallet']);

            $bookedIds = DB::table('booking_pallet')
                ->where('tanggal', $tgl)
                ->pluck('id_pallet')
                ->toArray();

            $listData = [];
            foreach($available as $p) {
                $listData[] = [
                    'no_pallet' => $p->no_pallet,
                    'jenis'     => $p->jenis_pallet ?? 'SW', 
                    'is_booked' => in_array($p->id_pallet, $bookedIds) 
                ];
            }

            usort($listData, function($a, $b) {
                return strnatcmp($a['no_pallet'], $b['no_pallet']);
            });

            return response()->json([
                'list_pallet' => $listData, 
                'booked_pallets' => array_column(array_filter($listData, function($i){return $i['is_booked'];}), 'no_pallet'),
                'count' => count($listData)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_kontrak' => 'required',
            'no_invoice' => 'required',
            'tanggal'    => 'required|date',
            'uraian'     => 'required', 
            'selected_pallets' => 'required|array',
            'hari_ini'   => 'required|numeric', 
            'harga'      => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
            $selectedPallets = $request->selected_pallets; 

            $invoice = PenjualanSir20::create([
                'tanggal'      => $tgl,
                'uraian'       => $request->uraian,
                'no_kontrak'   => $request->no_kontrak,
                'no_invoice'   => $request->no_invoice,
                'pallet'       => count($selectedPallets) + (int)($request->pallet_manual ?? 0),
                'hari_ini'     => $request->hari_ini,
                'harga'        => $request->harga,
                'no_palet_list' => implode(',', $selectedPallets),
                'is_summary'   => 0 
            ]);

            Pallet::whereIn('no_pallet', $selectedPallets)->update(['tanggal_penjualan' => $tgl]);

            $selectedIds = Pallet::whereIn('no_pallet', $selectedPallets)->pluck('id_pallet')->toArray();
            $bookedIdsBefore = DB::table('booking_pallet')->pluck('id_pallet')->toArray();
            $idsToClean = array_unique(array_merge($selectedIds, $bookedIdsBefore));

            DB::table('booking_pallet')->whereIn('id_pallet', $idsToClean)->delete();

            if ((int)$request->pallet_manual > 0) {
                DB::table('penjualan_manual_sir20')->insert([
                    'id_penjualan_sir20' => $invoice->id_penjualan_sir20,
                    'tanggal'            => $tgl,
                    'pallet_manual'      => $request->pallet_manual,
                    'status'             => 'Pending',
                    'created_at' => now(), 'updated_at' => now()
                ]);
            }

            $this->recalculateAndSave($tgl, $request->uraian, $request->hari_ini);

            DB::commit();
            return redirect()->back()->with('success', 'Penjualan Berhasil & Antrian Booking Dibersihkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 FUNGSI RECALCULATE JUGA DIUPDATE RESET TAHUNANNYA 🔥
    // =========================================================================
    public function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
    {
        $tglCarbon = Carbon::parse($tgl);
        $startOfYear = $tglCarbon->copy()->startOfYear();
        $startOfMonth = $tglCarbon->copy()->startOfMonth();

        // 1. S/d Bulan Lalu (Reset 0 jika bulan Januari)
        if ($tglCarbon->month == 1) {
            $sd_bulan_lalu = 0;
        } else {
            $sd_bulan_lalu = PenjualanSir20::where('uraian', $uraian)
                ->where('is_summary', 0)
                ->whereBetween('tanggal', [
                    $startOfYear->format('Y-m-d'), 
                    $startOfMonth->copy()->subDay()->format('Y-m-d')
                ])
                ->sum('hari_ini');
        }

        // 2. Penjualan dari tgl 1 sampai H-1
        $bln_ini_lalu = PenjualanSir20::where('uraian', $uraian)
            ->where('is_summary', 0)
            ->whereBetween('tanggal', [
                $startOfMonth->format('Y-m-d'), 
                $tglCarbon->copy()->subDay()->format('Y-m-d')
            ])
            ->sum('hari_ini');

        // 3. Simpan Update Baris Summary (Tabel V) HARI INI
        PenjualanSir20::updateOrCreate(
            ['tanggal' => $tglCarbon->format('Y-m-d'), 'uraian' => $uraian, 'is_summary' => 1],
            [
                'sd_bulan_lalu'     => $sd_bulan_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $bln_ini_lalu + $hari_ini,
                'total_sd_hari_ini' => $sd_bulan_lalu + ($bln_ini_lalu + $hari_ini),
                'keterangan'        => $keterangan ?? '-'
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'no_kontrak' => 'required',
            'no_invoice' => 'required',
            'harga'      => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $penjualan = PenjualanSir20::findOrFail($id);
            
            $penjualan->update([
                'no_kontrak' => $request->no_kontrak,
                'no_invoice' => $request->no_invoice,
                'harga'      => $request->harga,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data Penjualan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = PenjualanSir20::findOrFail($id);
            $tgl = $data->tanggal;
            $uraian = $data->uraian;
            
            $palletsToRestore = array_filter(explode(',', $data->no_palet_list), 'strlen');
            
            if (!empty($palletsToRestore)) {
                Pallet::whereIn('no_pallet', $palletsToRestore)->update([
                    'tanggal_penjualan' => null
                ]);

                foreach ($palletsToRestore as $noPallet) {
                    $p = Pallet::where('no_pallet', $noPallet)->first();
                    if ($p) {
                        DB::table('booking_pallet')->updateOrInsert(
                            ['id_pallet' => $p->id_pallet, 'tanggal' => $tgl],
                            ['updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            }

            DB::table('penjualan_manual_sir20')->where('id_penjualan_sir20', $id)->delete();
            $data->delete();

            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
            
            $this->recalculateAndSave($tgl, $uraian, $totalKgHariIni);

            DB::commit();
            return back()->with('success', 'Penjualan dibatalkan! Pallet kembali ke stok booking.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}