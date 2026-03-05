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

            // 1. Ambil pallet yang belum terjual beserta kolom jenis_pallet
            $available = Pallet::whereNull('tanggal_penjualan')->get(['id_pallet', 'no_pallet', 'jenis_pallet']);

            // 2. Ambil ID pallet yang di-booking (per baris)
            $bookedIds = DB::table('booking_pallet')
                ->where('tanggal', $tgl)
                ->pluck('id_pallet')
                ->toArray();

            $listData = [];
            foreach($available as $p) {
                $listData[] = [
                    'no_pallet' => $p->no_pallet,
                    'jenis'     => $p->jenis_pallet ?? 'SW', // Ambil jenis pallet
                    'is_booked' => in_array($p->id_pallet, $bookedIds) 
                ];
            }

            // Urutkan secara natural berdasarkan nomor pallet
            usort($listData, function($a, $b) {
                return strnatcmp($a['no_pallet'], $b['no_pallet']);
            });

            return response()->json([
                'list_pallet' => $listData, // Kirim object lengkap, bukan hanya nomor
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

            // 1. Simpan Invoice Utama
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

            // 2. Update status pallet menjadi TERJUAL
            Pallet::whereIn('no_pallet', $selectedPallets)->update(['tanggal_penjualan' => $tgl]);

            // =========================================================================
            // 🔥 LOGIKA SAPU JAGAT: Bersihkan Booking yang Terpakai & yang Batal 🔥
            // =========================================================================
            
            // A. Ambil semua ID Pallet yang terpilih untuk dijual
            $selectedIds = Pallet::whereIn('no_pallet', $selectedPallets)->pluck('id_pallet')->toArray();

            // B. Ambil SEMUA ID Pallet yang saat ini terdaftar di booking_pallet
            $bookedIdsBefore = DB::table('booking_pallet')->pluck('id_pallet')->toArray();

            // C. Gabungkan ID yang dipilih dan ID yang tadinya ter-booking untuk dibersihkan total
            // Dengan begini, yang tidak dipilih oleh Admin akan otomatis terhapus dari daftar booking.
            $idsToClean = array_unique(array_merge($selectedIds, $bookedIdsBefore));

            DB::table('booking_pallet')->whereIn('id_pallet', $idsToClean)->delete();

            // =========================================================================

            // 3. Jika ada input manual, buat baris hutang
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
            $tgl = $data->tanggal;
            $uraian = $data->uraian;
            
            // 1. Ambil daftar pallet yang terjual di invoice ini
            $palletsToRestore = array_filter(explode(',', $data->no_palet_list), 'strlen');
            
            if (!empty($palletsToRestore)) {
                // A. Kembalikan status pallet menjadi READY (tanggal_penjualan NULL)
                Pallet::whereIn('no_pallet', $palletsToRestore)->update([
                    'tanggal_penjualan' => null
                ]);

                // B. 🔥 TAMBAHAN: Kembalikan ke daftar BOOKING agar di modal web otomatis tercentang lagi
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

            // 2. 🔥 TAMBAHAN: Hapus data HUTANG terkait jika ada
            DB::table('penjualan_manual_sir20')->where('id_penjualan_sir20', $id)->delete();

            // 3. Hapus invoice utama
            $data->delete();

            // 4. Hitung ulang sisa penjualan murni hari ini (Sinkronisasi Tabel V)
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