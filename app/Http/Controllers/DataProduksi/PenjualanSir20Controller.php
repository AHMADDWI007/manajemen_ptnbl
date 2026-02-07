<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\ProduksiSir; 
use Illuminate\Http\Request;
use App\Models\KondisiPallet;
use App\Models\PenjualanSir20;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabSIR20; // Sumber data utama

class PenjualanSir20Controller extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // Ambil stok resmi Mutu Prima dari tabel Gudang & Mutu (Data Gudang)
        // Cari ID untuk "Mutu Prima" dulu
        $mutuPrima = Mutu::where('uraian', 'LIKE', '%Prima%')->first();
        $idMutuPrima = $mutuPrima ? $mutuPrima->id_mutu : 0;

        // Hitung Pallet yang: 
        // 1. Belum Terjual (tanggal_penjualan NULL)
        // 2. Mutu TERAKHIR-nya adalah Prima
        $mutuTersedia = 0;
        
        // Ambil semua pallet aktif
        $pallets = Pallet::whereNull('tanggal_penjualan')->get();
        
        foreach($pallets as $p) {
            // Cek kondisi terakhir pallet ini
            $lastKondisi = KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->orderBy('id_kondisi_pallet', 'desc')
                            ->first();
            
            // Jika kondisi terakhirnya Prima, hitung
            if ($lastKondisi && $lastKondisi->id_mutu == $idMutuPrima) {
                $mutuTersedia++;
            }
        }

        // Data Summary (Tabel V) - Logika tetap sama
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->where('is_summary', 1)->get()->keyBy('uraian');
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subDay())->where('is_summary', 1)->get()->keyBy('uraian');
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subMonth()->endOfMonth())->where('is_summary', 1)->get()->keyBy('uraian');

        $masterUraian = ['5.1' => 'SIR20 PTNBL', '5.2' => 'SIR20 PTPN4'];
        $tabelSummary = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB->get($uraian);
            $itemKemarin = $dataKemarin->get($uraian);
            $itemBulanLalu = $dataBulanLalu->get($uraian);

            $sd_bln_lalu = $itemToday ? $itemToday->sd_bulan_lalu : ($itemBulanLalu->total_sd_hari_ini ?? 0);
            $bln_ini_lalu = ($selectedDate->day == 1) ? 0 : ($itemToday ? $itemToday->bln_ini_lalu : (($itemKemarin->bln_ini_lalu ?? 0) + ($itemKemarin->hari_ini ?? 0)));
            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;

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

        return view('DataProduksi.penjualan-sir20', [
            'tabelSummary'    => $tabelSummary,
            'riwayatKontrak'  => $riwayatKontrak,
            'selected_date'   => $selectedDate->format('Y-m-d'),
            'mutuTersedia'    => $mutuTersedia,
            'headerBulanLalu' => $selectedDate->copy()->subMonth()->translatedFormat('F Y')
        ]);
    }

    /**
     * AJAX: Mengambil palet yang sudah TERUJI di Lab dan PRI >= 40
     */
    public function getAvailableStock(Request $request)
    {
        try {
            // 1. Cari nomor palet di Lab yang PRI >= 40 (Mutu Prima)
            // Sesuai gambar Hasil Uji Lab Anda (image_699332.png), kita ambil no_palet 2 dan 3.
            $palletsTestedPrima = HasilUjiLabSIR20::where('pri', '>=', 40)
                ->pluck('no_palet')
                ->toArray();

            // 2. Cari nomor palet yang SUDAH terjual
            $soldData = PenjualanSir20::where('is_summary', 0)
                ->whereNotNull('no_palet_list')
                ->get();

            $palletsSold = [];
            foreach ($soldData as $sale) {
                // Pastikan data diproses hanya jika tidak kosong
                if (!empty($sale->no_palet_list)) {
                    $exploded = explode(',', $sale->no_palet_list);
                    $palletsSold = array_merge($palletsSold, $exploded);
                }
            }

            // 3. Filter: Palet yang sudah teruji Prima DIKURANGI palet yang sudah terjual
            $availablePallets = array_diff($palletsTestedPrima, $palletsSold);
            
            // Urutkan nomor palet
            sort($availablePallets, SORT_NUMERIC);

            return response()->json([
                'list_pallet' => array_values($availablePallets),
                'count' => count($availablePallets)
            ]);
        } catch (\Exception $e) {
            // Kirim pesan error asli jika gagal agar bisa di-debug
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
            'hari_ini'   => 'required|numeric', // Nilai Kg Penjualan
            'harga'      => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
            $kgTerjual = $request->hari_ini;
            $palletTerjual = count($request->selected_pallets);

            // 1. Simpan Detail Penjualan (Untuk Arsip Invoice/Kontrak)
            PenjualanSir20::create([
                'tanggal'    => $tgl,
                'uraian'     => $request->uraian,
                'no_kontrak' => $request->no_kontrak,
                'no_invoice' => $request->no_invoice,
                'pallet'     => $palletTerjual,
                'hari_ini'   => $kgTerjual,
                'harga'      => $request->harga,
                'no_palet_list' => implode(',', $request->selected_pallets),
                'is_summary' => 0 
            ]);

            // 2. Sinkronisasi ke Summary Penjualan (Tabel V - Laporan Penjualan)
            // Bagian ini TETAP ADA agar Tabel V di halaman Penjualan terisi rekapnya
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $request->uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
            
            $this->recalculateAndSave($tgl, $request->uraian, $totalKgHariIni);

            // =========================================================================
            // 🔥 3. UPDATE STATUS PALLET MENJADI TERJUAL (CORE TRACKING SYSTEM) 🔥
            // =========================================================================
            // Inilah pengganti langkah 3 & 4 yang error tadi.
            // Cukup update kolom 'tanggal_penjualan' di tabel pallet.
            // Sistem Dashboard otomatis tidak akan menghitung pallet ini lagi sebagai stok.
            
            Pallet::whereIn('no_pallet', $request->selected_pallets)
                ->update([
                    'tanggal_penjualan' => $tgl
                ]);

            DB::commit();
            return redirect()->back()->with('success', 'Penjualan berhasil disimpan. Stok gudang otomatis terpotong!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
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
            ]
        );
    }

    public function destroy($id)
    {
        $data = PenjualanSir20::find($id);
        if ($data) { $data->delete(); return back()->with('success', 'Data berhasil dihapus.'); }
        return back();
    }
}