<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pallet; // 🔥 TAMBAHAN PENTING: Import model Pallet
use App\Models\PenjualanManualSir20;
use App\Models\PenjualanSir20;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
    // =========================================================================
    // [GET AVAILABLE STOCK] Ambil Pallet yang Belum Terjual (Tanpa Filter Lab)
    // =========================================================================
    public function getAvailableStock(Request $request)
    {
        try {
            // 1. Ambil pallet yang benar-benar belum terjual
            $availablePallets = Pallet::whereNull('tanggal_penjualan')
                ->get(['id_pallet', 'no_pallet', 'jenis_pallet']);

            // 2. 🔥 Ambil SEMUA ID pallet yang sudah ada di daftar booking (per baris)
            $bookedIds = DB::table('booking_pallet')->pluck('id_pallet')->toArray();

            // 3. Map data ke format object
            $listData = $availablePallets->map(function($p) use ($bookedIds) {
                return [
                    'no_pallet' => $p->no_pallet,
                    'jenis'     => $p->jenis_pallet ?? 'SW',
                    // 🔥 TANDAI: true jika ID ada di tabel booking
                    'is_booked' => in_array($p->id_pallet, $bookedIds) 
                ];
            })->toArray();

            // 4. Urutkan secara natural
            usort($listData, function($a, $b) {
                return strnatcmp($a['no_pallet'], $b['no_pallet']);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'list_pallet' => array_values($listData),
                    'count'       => count($listData)
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
            'tanggal' => 'required|date',
            'selected_pallets' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $tgl = Carbon::parse($request->tanggal)->format('Y-m-d');
            
            // Pastikan input adalah array
            $selectedPallets = is_array($request->selected_pallets) 
                ? $request->selected_pallets 
                : json_decode($request->selected_pallets, true);

            // 1. Bersihkan booking lama di tanggal tersebut (opsional, agar tidak duplikat)
            // DB::table('booking_pallet')->where('tanggal', $tgl)->delete();

            foreach ($selectedPallets as $noPallet) {
                // Cari ID Pallet berdasarkan Nomor Pallet (String) dari Mobile
                $pallet = Pallet::where('no_pallet', trim($noPallet))->first();
                
                if ($pallet) {
                    // 🔥 SIMPAN PER BARIS sesuai struktur tabel booking_pallet Maswi
                    DB::table('booking_pallet')->updateOrInsert(
                        [
                            'id_pallet' => $pallet->id_pallet,
                            'tanggal'   => $tgl
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Daftar ' . count($selectedPallets) . ' pallet berhasil di-booking!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal simpan: ' . $e->getMessage()
            ], 500);
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

    // [GET] Mengambil daftar hutang untuk ditampilkan di mobile
    public function getPendingDebts(Request $request)
    {
        try {
            $query = PenjualanManualSir20::with('penjualan');

            // Jika ada filter status dari mobile (misal: ?status=Pending)
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $debts = $query->orderBy('created_at', 'desc')->get()->map(function($item) {
                return [
                    'id_penjualan_manual' => $item->id_penjualan_manual,
                    'id_penjualan_sir20' => $item->id_penjualan_sir20,
                    'tanggal'            => $item->tanggal,
                    'pallet_manual'      => $item->pallet_manual,
                    'status'             => $item->status, // 🔥 Kirim status ke mobile
                    'no_kontrak'         => $item->penjualan->no_kontrak ?? '-',
                    'no_palet_list'      => $item->penjualan->no_palet_list ?? '',
                ];
            });

            return response()->json(['success' => true, 'data' => $debts]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // [POST] Melunasi hutang dengan menambahkan pallet baru ke invoice lama
    public function fulfillDebt(Request $request, $id_manual)
    {
        $request->validate(['selected_pallets' => 'required|array']);

        DB::beginTransaction();
        try {
            // 1. Ambil data hutang
            $hutang = DB::table('penjualan_manual_sir20')->where('id_penjualan_manual', $id_manual)->first();
            if (!$hutang) return response()->json(['success' => false, 'message' => 'Data hutang tidak ditemukan']);

            // 2. Ambil Invoice Utama
            $invoice = PenjualanSir20::findOrFail($hutang->id_penjualan_sir20);
            
            // 3. Ambil data dari mobile & list pallet yang sudah ada di web
            $palletInputMobile = array_map('trim', $request->selected_pallets);
            $listLama = array_filter(explode(',', $invoice->no_palet_list), 'strlen');
            
            // 4. 🔥 LOGIKA PEMBATAS KRUSIAL
            // Kita hitung berdasarkan kolom 'pallet' di tabel penjualan_sir20 (Target Total)
            $targetTotal = (int)$invoice->pallet; 
            $sudahAda = count($listLama);
            $sisaButuh = $targetTotal - $sudahAda;

            // Jika sisaButuh <= 0 artinya invoice sebenarnya sudah penuh
            $jumlahDiambil = max(0, min(count($palletInputMobile), $sisaButuh));

            // Potong array input mobile
            $untukHutang = array_slice($palletInputMobile, 0, $jumlahDiambil); 
            $untukBookingSisa = array_slice($palletInputMobile, $jumlahDiambil); 

            // 5. --- PROSES KONTRAK LAMA (Update Rincian & Tandai Terjual) ---
            if (!empty($untukHutang)) {
                $listLengkap = array_values(array_merge($listLama, $untukHutang));
                
                // Update nomor pallet di invoice
                $invoice->update([
                    'no_palet_list' => implode(',', $listLengkap)
                ]);

                // Tandai pallet pelunas sebagai TERJUAL (Ikut tanggal invoice lama)
                Pallet::whereIn('no_pallet', $untukHutang)->update([
                    'tanggal_penjualan' => $hutang->tanggal
                ]);
            }

            // 6. Cek apakah sudah lunas semua?
            $totalFisikSekarang = count(array_filter(explode(',', $invoice->no_palet_list), 'strlen'));
            if ($totalFisikSekarang >= $targetTotal) {
                DB::table('penjualan_manual_sir20')
                    ->where('id_penjualan_manual', $id_manual)
                    ->update(['status' => 'Settled']);
            }

            // 7. --- PROSES SISA (Kembalikan ke Stok Ready & Masuk Booking) ---
            if (!empty($untukBookingSisa)) {
                // 🔥 PASTIKAN SISA TIDAK TERJUAL
                Pallet::whereIn('no_pallet', $untukBookingSisa)->update([
                    'tanggal_penjualan' => null
                ]);

                foreach ($untukBookingSisa as $noPallet) {
                    $p = Pallet::where('no_pallet', $noPallet)->first();
                    if ($p) {
                        // Masukkan ke tabel booking agar muncul centang di web
                        DB::table('booking_pallet')->updateOrInsert(
                            ['id_pallet' => $p->id_pallet, 'tanggal' => date('Y-m-d')],
                            ['updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Berhasil! ' . count($untukHutang) . ' pallet melunasi hutang, ' . count($untukBookingSisa) . ' pallet menjadi booking baru.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function lunasinHutang(Request $request, $id_manual)
    {
        $request->validate(['selected_pallets' => 'required|array']);

        DB::beginTransaction();
        try {
            $hutang = DB::table('penjualan_manual_sir20')->where('id_penjualan_manual', $id_manual)->first();
            if (!$hutang) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);

            $invoice = PenjualanSir20::findOrFail($hutang->id_penjualan_sir20);
            $palletInputMobile = array_map('trim', $request->selected_pallets);
            
            // 🔥 LOGIKA PEMBATAS: Cek berapa pallet lagi yang benar-benar dibutuhkan
            // Hitung fisik yang sudah ada di invoice lama
            $listLama = array_filter(explode(',', $invoice->no_palet_list), 'strlen');
            $fisikSudahAda = count($listLama);
            $totalTargetInvoice = (int)$invoice->pallet;
            $butuhBerapaLagi = $totalTargetInvoice - $fisikSudahAda;

            // Potong array input dari mobile
            $untukHutang = array_slice($palletInputMobile, 0, $butuhBerapaLagi); 
            $untukBookingSisa = array_slice($palletInputMobile, $butuhBerapaLagi); 

            // --- PROSES 1: PELUNASAN KONTRAK LAMA ---
            $listLengkap = array_values(array_merge($listLama, $untukHutang));
            $invoice->no_palet_list = implode(',', $listLengkap);
            $invoice->save();

            // Tandai pallet pelunas sebagai TERJUAL (Ikut tanggal kontrak lama)
            if (!empty($untukHutang)) {
                Pallet::whereIn('no_pallet', $untukHutang)->update([
                    'tanggal_penjualan' => $hutang->tanggal
                ]);
            }

            // Jika sudah terpenuhi semua target pallet di invoice, set status Settled
            if (count($listLengkap) >= $totalTargetInvoice) {
                DB::table('penjualan_manual_sir20')
                    ->where('id_penjualan_manual', $id_manual)
                    ->update(['status' => 'Settled']);
            }

            // --- PROSES 2: SISA PALLET (JADI BOOKING BARU) ---
            if (!empty($untukBookingSisa)) {
                foreach ($untukBookingSisa as $noPallet) {
                    $p = Pallet::where('no_pallet', $noPallet)->first();
                    if ($p) {
                        // 🔥 Pastikan tanggal_penjualan NULL (karena dia booking/stok ready)
                        $p->update(['tanggal_penjualan' => null]);

                        // Masukkan ke tabel booking agar muncul centang di web
                        DB::table('booking_pallet')->updateOrInsert(
                            ['id_pallet' => $p->id_pallet, 'tanggal' => date('Y-m-d')],
                            ['updated_at' => now()]
                        );
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Hutang Lunas! ' . count($untukBookingSisa) . ' pallet sisa dialihkan ke booking.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // [DELETE] Membatalkan booking pallet tertentu (Sapu bersih per pallet)
    public function cancelBooking(Request $request)
    {
        $request->validate(['no_pallet' => 'required']);

        try {
            $pallet = Pallet::where('no_pallet', $request->no_pallet)->first();
            
            if ($pallet) {
                DB::table('booking_pallet')->where('id_pallet', $pallet->id_pallet)->delete();
                return response()->json(['success' => true, 'message' => 'Booking dibatalkan!']);
            }
            
            return response()->json(['success' => false, 'message' => 'Pallet tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}