<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PenjualanSir20;
use App\Models\HasilUjiLabSIR20;
use App\Http\Controllers\Controller;
use App\Models\ProduksiSir;
use Illuminate\Support\Facades\DB; // Tambahkan ini

class PenjualanSirApiController extends Controller
{
    // [INDEX] Ambil Data List
    public function index(Request $request)
    {
        $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($dateStr);

        // --- DATA 1: SUMMARY (TABEL ATAS) ---
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->get()->keyBy('uraian');
        $yesterday = $selectedDate->copy()->subDay();
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->get()->keyBy('uraian');
        $lastMonthDate = $selectedDate->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonthDate)->get()->keyBy('uraian');

        $masterUraian = ['5.1' => 'SIR20 PTNBL', '5.2' => 'SIR20 PTPN4'];
        $listSummary = [];

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB[$uraian] ?? null;
            $itemKemarin = $dataKemarin[$uraian] ?? null;
            $itemBulanLalu = $dataBulanLalu[$uraian] ?? null;

            $sd_bulan_lalu = $itemToday ? $itemToday->sd_bulan_lalu : ($itemBulanLalu ? $itemBulanLalu->total_sd_hari_ini : 0);
            $bln_ini_lalu = ($selectedDate->day == 1) ? 0 : ($itemToday ? $itemToday->bln_ini_lalu : ($itemKemarin ? ($itemKemarin->bln_ini_lalu + $itemKemarin->hari_ini) : 0));
            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;
            $total_sd_hari_ini = $sd_bulan_lalu + $bln_ini_lalu + $hari_ini;

            $listSummary[] = [
                'id_penjualan_sir20' => $itemToday->id_penjualan_sir20 ?? null,
                'no' => $no,
                'uraian' => $uraian,
                'sd_bulan_lalu' => (float)$sd_bulan_lalu,
                'bln_ini_lalu' => (float)$bln_ini_lalu,
                'hari_ini' => (float)$hari_ini,
                'total_bln_ini' => (float)($bln_ini_lalu + $hari_ini), // Tambahan
                'total_sd_hari_ini' => (float)$total_sd_hari_ini,
            ];
        }

        // --- DATA 2: RIWAYAT (TABEL BAWAH) ---
        $listRiwayat = PenjualanSir20::where('is_summary', 0)
            ->orderBy('tanggal', 'desc')
            ->take(50) // Limit biar gak berat
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $listSummary,
                'riwayat' => $listRiwayat
            ]
        ]);
    }

    // [STORE] Simpan Data dengan Perhitungan Ulang
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian' => 'required|string',
            'hari_ini' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
            $kgTerjual = $request->hari_ini;
            
            // Handle array selected_pallets dari Android
            $selectedPallets = $request->input('selected_pallets', []);
            // Jika dikirim sebagai string JSON (kadang retrofit kirim gitu), decode dulu
            if (is_string($selectedPallets)) {
                $selectedPallets = json_decode($selectedPallets, true) ?? [];
            }
            $palletTerjual = count($selectedPallets);

            // 1. Simpan Detail Penjualan (Non-Summary)
            PenjualanSir20::create([
                'tanggal'    => $tgl,
                'uraian'     => $request->uraian,
                'no_kontrak' => $request->no_kontrak,
                'no_invoice' => $request->no_invoice,
                'pallet'     => $palletTerjual,
                'hari_ini'   => $kgTerjual,
                'harga'      => $request->harga,
                'no_palet_list' => implode(',', $selectedPallets),
                'is_summary' => 0 
            ]);

            // 2. Update Summary Penjualan (Panggil method lokal private)
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $request->uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
                
            $this->recalculateAndSave($tgl, $request->uraian, $totalKgHariIni, $request->keterangan);

            // 3. Sinkronisasi ke Gudang (Tabel IV)
            $this->syncToGudang($tgl, $totalKgHariIni);

            // 4. Sinkronisasi ke Mutu (Tabel VI)
            $this->syncToMutu($tgl, $palletTerjual, $kgTerjual);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Penjualan Tersimpan & Stok Terupdate']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Method Private Lokal (Pengganti Web Controller)
    private function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
    {
        $tglCarbon = Carbon::parse($tgl);
        $lastMonthDate = $tglCarbon->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)->whereDate('tanggal', $lastMonthDate)->where('is_summary', 1)->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;
        
        $yesterday = $tglCarbon->copy()->subDay();
        $dataKemarin = PenjualanSir20::where('uraian', $uraian)->whereDate('tanggal', $yesterday)->where('is_summary', 1)->first();
        $bln_ini_lalu = ($tglCarbon->day == 1) ? 0 : ($dataKemarin ? ($dataKemarin->bln_ini_lalu + $dataKemarin->hari_ini) : 0);
        
        PenjualanSir20::updateOrCreate(
            ['tanggal' => $tglCarbon->format('Y-m-d'), 'uraian' => $uraian, 'is_summary' => 1],
            [
                'sd_bulan_lalu'     => $sd_bulan_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $bln_ini_lalu + $hari_ini,
                'total_sd_hari_ini' => $sd_bulan_lalu + ($bln_ini_lalu + $hari_ini),
                'keterangan'        => $keterangan
            ]
        );
    }

    private function syncToGudang($tgl, $totalKgPenjualan)
    {
        $prevGudang = ProduksiSir::where('uraian', 'Di Gudang SIR')
            ->whereDate('created_at', '<', $tgl)
            ->orderBy('created_at', 'desc')->first();

        $saldoAwal = $prevGudang->saldo_akhir ?? 0;
        $prodLalu  = $prevGudang->prod_sd_hi ?? 0;

        $dataToday = ProduksiSir::where('uraian', 'Di Gudang SIR')->whereDate('created_at', $tgl)->first();
        $masukHariIni = $dataToday->masuk ?? 0;

        ProduksiSir::updateOrCreate(
            ['uraian' => 'Di Gudang SIR', 'created_at' => $tgl],
            [
                'saldo_awal' => $saldoAwal,
                'masuk'      => $masukHariIni,
                'total'      => $saldoAwal + $masukHariIni,
                'prod_bln_lalu' => $prodLalu,
                'prod_sd_hi'    => $prodLalu + $masukHariIni,
                'pengiriman'    => $totalKgPenjualan,
                'saldo_akhir'   => ($saldoAwal + $masukHariIni) - $totalKgPenjualan
            ]
        );
    }

    private function syncToMutu($tgl, $palletTerjual, $kgTerjual)
    {
        $mutu = ProduksiSir::where('uraian', 'Mutu Prima (siap jual)')
            ->whereDate('created_at', $tgl)
            ->first();

        if ($mutu) {
            $mutu->update([
                'pallet' => max(0, $mutu->pallet - $palletTerjual),
                'kg'     => max(0, $mutu->kg - $kgTerjual)
            ]);
        }
    }

    public function getAvailableStock(Request $request)
    {
        try {
            $palletsTestedPrima = HasilUjiLabSIR20::where('pri', '>=', 40)->pluck('no_palet')->toArray();
            $soldData = PenjualanSir20::where('is_summary', 0)->whereNotNull('no_palet_list')->get();

            $palletsSold = [];
            foreach ($soldData as $sale) {
                if (!empty($sale->no_palet_list)) {
                    $exploded = explode(',', $sale->no_palet_list);
                    $palletsSold = array_merge($palletsSold, $exploded);
                }
            }

            $availablePallets = array_diff($palletsTestedPrima, $palletsSold);
            sort($availablePallets, SORT_NUMERIC);

            return response()->json([
                'success' => true,
                'data' => [  // 🔥 Wajib dibungkus 'data' agar terbaca oleh StockResponse di Android
                    'list_pallet' => array_values($availablePallets),
                    'count' => count($availablePallets)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}