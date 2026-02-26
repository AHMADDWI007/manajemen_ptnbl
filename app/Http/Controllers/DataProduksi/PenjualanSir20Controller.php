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
        // Cari ID untuk "Mutu Prima" dulu
        $mutuPrima = Mutu::where('uraian', 'LIKE', '%Prima%')->first();
        $idMutuPrima = $mutuPrima ? $mutuPrima->id_mutu : 0;

        // Hitung Pallet yang: 
        // 1. Belum Terjual (tanggal_penjualan NULL)
        // 2. Mutu TERAKHIR-nya adalah Prima
        $mutuTersedia = Pallet::whereNull('tanggal_penjualan')->count();
        
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
            $tgl = $request->query('date');

            // 1. Ambil pallet yang benar-benar belum terjual (tanggal_penjualan is NULL)
            $availablePallets = Pallet::whereNull('tanggal_penjualan')
                ->pluck('no_pallet')
                ->toArray();

            // 2. Ambil list pallet yang di-booking dari mobile untuk tanggal tersebut
            $bookedData = DB::table('booking_pallet')->where('tanggal', $tgl)->first();
            $bookedPallets = $bookedData ? explode(',', $bookedData->no_palet_list) : [];

            sort($availablePallets, SORT_NATURAL);

            return response()->json([
                'list_pallet' => array_values($availablePallets),
                'booked_pallets' => $bookedPallets, // Daftar ID untuk dicentang otomatis oleh JS
                'count' => count($availablePallets)
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
                'tanggal'     => $tgl,
                'uraian'      => $request->uraian,
                'no_kontrak'  => $request->no_kontrak,
                'no_invoice'  => $request->no_invoice,
                'pallet'      => $palletTerjual,
                'hari_ini'    => $kgTerjual,
                'harga'       => $request->harga,
                'no_palet_list' => implode(',', $request->selected_pallets),
                'is_summary'  => 0 
            ]);

            // 2. Sinkronisasi ke Summary Penjualan (Tabel V - Laporan Penjualan)
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $request->uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
            
            $this->recalculateAndSave($tgl, $request->uraian, $totalKgHariIni);

            // 3. UPDATE STATUS PALLET MENJADI TERJUAL
            Pallet::whereIn('no_pallet', $request->selected_pallets)
                ->update([
                    'tanggal_penjualan' => $tgl
                ]);

            // =========================================================================
            // 🔥 TAMBAHAN PERBAIKAN: HAPUS DATA BOOKING DARI MOBILE 🔥
            // =========================================================================
            // Setelah sukses jadi penjualan resmi, hapus draft booking di tabel sementara
            // agar tidak membingungkan atau tercentang lagi di masa depan.
            DB::table('booking_pallet')->where('tanggal', $tgl)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Penjualan berhasil disimpan. Stok gudang terpotong & data booking dibersihkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 FUNGSI RECALCULATE (Sinkronisasi Tabel V) 🔥
    // =========================================================================
    public function recalculateAndSave($tgl, $uraian, $hari_ini, $keterangan = null)
    {
        $tglCarbon = Carbon::parse($tgl);
        
        // 1. Ambil Total s/d Bulan Lalu (Dari hari terakhir bulan sebelumnya)
        $lastMonthDate = $tglCarbon->copy()->subMonth()->endOfMonth();
        $dataBulanLalu = PenjualanSir20::where('uraian', $uraian)
            ->whereDate('tanggal', $lastMonthDate)
            ->where('is_summary', 1)
            ->first();
        
        $sd_bulan_lalu = $dataBulanLalu ? $dataBulanLalu->total_sd_hari_ini : 0;

        // 2. 🔥 PERBAIKAN: Hitung Ulang Total Penjualan Bulan Ini secara Murni!
        // Alih-alih bergantung pada H-1 yang rawan putus saat dihapus,
        // Kita jumlahkan langsung SEMUA invoice (is_summary = 0) dari tgl 1 sampai sebelum HARI INI.
        $startOfMonth = $tglCarbon->copy()->startOfMonth();
        
        $bln_ini_lalu = PenjualanSir20::where('uraian', $uraian)
            ->where('is_summary', 0)
            ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $tglCarbon->copy()->subDay()->format('Y-m-d')])
            ->sum('hari_ini');

        // 3. Simpan atau Update Baris Summary (Tabel V) HARI INI
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
    // 🔥 FUNGSI UPDATE (Hanya untuk mengedit Info Administratif) 🔥
    // =========================================================================
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
            
            // Kita hanya update info administratifnya saja
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

    // =========================================================================
    // 🔥 FUNGSI HAPUS (Membatalkan Penjualan & Mengembalikan Stok Pallet) 🔥
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = PenjualanSir20::findOrFail($id);
            
            $palletsToRestore = explode(',', $data->no_palet_list);
            
            // 1. Kembalikan status pallet di gudang menjadi 'Belum Terjual'
            if (!empty($palletsToRestore) && $data->no_palet_list != null) {
                Pallet::whereIn('no_pallet', $palletsToRestore)->update([
                    'tanggal_penjualan' => null
                ]);
            }

            // 2. Simpan info untuk di-recalculate sebelum data dihapus
            $tgl = $data->tanggal;
            $uraian = $data->uraian;
            
            // 3. Hapus data penjualan (invoice) tersebut
            $data->delete();

            // 4. 🔥 PERBAIKAN: Hitung ulang sisa penjualan murni HARI INI
            $totalKgHariIni = PenjualanSir20::whereDate('tanggal', $tgl)
                ->where('uraian', $uraian)
                ->where('is_summary', 0)
                ->sum('hari_ini');
            
            // Panggil fungsi sinkronisasi
            $this->recalculateAndSave($tgl, $uraian, $totalKgHariIni);

            DB::commit();
            return back()->with('success', 'Penjualan dibatalkan! Pallet telah dikembalikan ke stok gudang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}