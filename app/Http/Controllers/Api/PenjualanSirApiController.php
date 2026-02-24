<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PenjualanSir20;
use App\Models\HasilUjiLabSIR20;
use App\Http\Controllers\Controller;
use App\Models\Pallet; // 🔥 TAMBAHAN PENTING: Import model Pallet
use Illuminate\Support\Facades\DB;

class PenjualanSirApiController extends Controller
{
    // =========================================================================
    // [INDEX] Ambil Data List Summary & Riwayat
    // =========================================================================
    public function index(Request $request)
    {
        try {
            $dateStr = $request->query('date', Carbon::today()->format('Y-m-d'));
            $selectedDate = Carbon::parse($dateStr);

            // --- DATA 1: SUMMARY (TABEL ATAS) ---
            $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->where('is_summary', 1)->get()->keyBy('uraian');
            $yesterday = $selectedDate->copy()->subDay();
            $dataKemarin = PenjualanSir20::whereDate('tanggal', $yesterday)->where('is_summary', 1)->get()->keyBy('uraian');
            $lastMonthDate = $selectedDate->copy()->subMonth()->endOfMonth();
            $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $lastMonthDate)->where('is_summary', 1)->get()->keyBy('uraian');

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
                    'no'                 => $no,
                    'uraian'             => $uraian,
                    'sd_bulan_lalu'      => (float)$sd_bulan_lalu,
                    'bln_ini_lalu'       => (float)$bln_ini_lalu,
                    'hari_ini'           => (float)$hari_ini,
                    'total_bln_ini'      => (float)($bln_ini_lalu + $hari_ini),
                    'total_sd_hari_ini'  => (float)$total_sd_hari_ini,
                ];
            }

            // --- DATA 2: RIWAYAT (TABEL BAWAH) ---
            $listRiwayat = PenjualanSir20::where('is_summary', 0)
                ->orderBy('tanggal', 'desc')
                ->take(50) // Limit biar enteng
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $listSummary,
                    'riwayat' => $listRiwayat
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // [GET AVAILABLE STOCK] Ambil Pallet Siap Jual (PRI >= 40 & Belum Terjual)
    // =========================================================================
    public function getAvailableStock(Request $request)
    {
        try {
            // 1. Cari pallet yang sudah uji lab dan PRI >= 40
            $palletsTestedPrima = HasilUjiLabSIR20::where('pri', '>=', 40)->pluck('no_palet')->toArray();
            
            // 2. Cari pallet yang sudah terjual dari Riwayat Penjualan
            $soldData = PenjualanSir20::where('is_summary', 0)->whereNotNull('no_palet_list')->get();

            $palletsSold = [];
            foreach ($soldData as $sale) {
                if (!empty($sale->no_palet_list)) {
                    $exploded = explode(',', $sale->no_palet_list);
                    $palletsSold = array_merge($palletsSold, $exploded);
                }
            }

            // 3. Sisa Pallet = Tested - Sold
            $availablePallets = array_diff($palletsTestedPrima, $palletsSold);
            sort($availablePallets, SORT_NUMERIC);

            return response()->json([
                'success' => true,
                'data' => [
                    'list_pallet' => array_values($availablePallets),
                    'count'       => count($availablePallets)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // [STORE] Simpan Penjualan & Update Status Pallet
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'  => 'required|date',
            'uraian'   => 'required|string',
            'hari_ini' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
            $kgTerjual = $request->hari_ini;
            
            // Handle array selected_pallets dari Android
            $selectedPallets = $request->input('selected_pallets', []);
            if (is_string($selectedPallets)) {
                $selectedPallets = json_decode($selectedPallets, true) ?? [];
            }
            $palletTerjual = count($selectedPallets);

            // 1. Simpan Detail Penjualan (Non-Summary / Bukti Invoice)
            PenjualanSir20::create([
                'tanggal'       => $tgl,
                'uraian'        => $request->uraian,
                'no_kontrak'    => $request->no_kontrak,
                'no_invoice'    => $request->no_invoice,
                'pallet'        => $palletTerjual,
                'hari_ini'      => $kgTerjual,
                'harga'         => $request->harga,
                'no_palet_list' => implode(',', $selectedPallets),
                'is_summary'    => 0 
            ]);

            // 2. Update Summary Penjualan (Rekap Bulanan)
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $request->uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
                
            $this->recalculateAndSave($tgl, $request->uraian, $totalKgHariIni, $request->keterangan);

            // =========================================================================
            // 🔥 3. UPDATE STATUS PALLET MENJADI TERJUAL (SAMA PERSIS DGN WEB) 🔥
            // =========================================================================
            if (!empty($selectedPallets)) {
                Pallet::whereIn('no_pallet', $selectedPallets)->update([
                    'tanggal_penjualan' => $tgl
                ]);
            }

            // (Catatan: Fungsi syncToGudang & syncToMutu sudah dihapus dari sini)

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Penjualan Tersimpan & Stok Gudang Terpotong.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPER: Recalculate Summary Penjualan (Tabel V) - SISTEM SAPU JAGAT
    // =========================================================================
    private function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
    {
        $tglCarbon = Carbon::parse($tgl);
        
        // 1. Ambil Total s/d Bulan Lalu 
        $lastMonthDate = $tglCarbon->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)->whereDate('tanggal', $lastMonthDate)->where('is_summary', 1)->first();
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;
        
        // 2. 🔥 SINKRON WEB: Hitung Ulang Total Penjualan Bulan Ini secara Murni!
        $startOfMonth = $tglCarbon->copy()->startOfMonth();
        $bln_ini_lalu = PenjualanSir20::where('uraian', $uraian)
            ->where('is_summary', 0)
            ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $tglCarbon->copy()->subDay()->format('Y-m-d')])
            ->sum('hari_ini');
        
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

    // =========================================================================
    // [DESTROY] Hapus Penjualan & Kembalikan Stok Pallet
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = PenjualanSir20::findOrFail($id);
            $tgl = $penjualan->tanggal;
            $uraian = $penjualan->uraian;

            // Bagian perbaikan kecil di destroy:
            $palletsToRestore = array_filter(explode(',', $penjualan->no_palet_list)); // array_filter menghapus elemen kosong

            if (!empty($palletsToRestore)) {
                Pallet::whereIn('no_pallet', $palletsToRestore)->update([
                    'tanggal_penjualan' => null
                ]);
            }

            // 2. Hapus data penjualan
            $penjualan->delete();

            // 3. 🔥 SINKRON WEB: Hitung ulang sisa penjualan murni HARI INI
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
                
            $this->recalculateAndSave($tgl, $uraian, $totalKgHariIni, "Koreksi Hapus");

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data dihapus & Stok Gudang dikembalikan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}