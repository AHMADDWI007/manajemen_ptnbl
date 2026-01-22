<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Models\ProduksiSir; 
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\BahanProses;
use App\Models\HasilUjiLabSIR20; 
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DataProduksiSir20Controller extends Controller
{
public function index(Request $request)
{
    $selectedDate = $request->input('filter_tanggal') 
        ? Carbon::parse($request->input('filter_tanggal')) 
        : Carbon::today();

    // 1. Ambil Data Tepat Hari Ini (Untuk kolom Masuk & Pengiriman)
    $dataHarian = ProduksiSir::whereDate('created_at', $selectedDate)
        ->get()
        ->keyBy('uraian');

    // 2. Ambil Data Terakhir sebelum Hari Ini (Untuk Saldo Awal & Produksi Lalu)
    $prevDataDB = ProduksiSir::whereDate('created_at', '<', $selectedDate->startOfDay())
        ->orderBy('created_at', 'desc')
        ->get()
        ->unique('uraian')
        ->keyBy('uraian');

    // 3. Logic WIP (Bahan Proses)
    $totalBahanProses = BahanProses::whereDate('tanggal', $selectedDate)->sum('saldo_akhir');
    if ($totalBahanProses == 0 && BahanProses::whereDate('tanggal', $selectedDate)->count() == 0) {
        $lastBPDate = BahanProses::whereDate('tanggal', '<', $selectedDate)->max('tanggal');
        if ($lastBPDate) {
            $totalBahanProses = BahanProses::whereDate('tanggal', $lastBPDate)->sum('saldo_akhir');
        }
    }

    // --- TABEL IV: GUDANG ---
    $masterGudang = [
        '4.1' => 'Di Gudang SIR',
        '4.2' => 'Di Areal Press Bale',
        '4.3' => 'Di Gudang TOH 1',
        '4.4' => 'Di Gudang TOH 2',
    ];

    $tabelIV = new Collection();
    $totalSaldoGudang = 0;

    foreach ($masterGudang as $no => $namaGudang) {
        $itemToday = $dataHarian->get($namaGudang);
        $prevItem  = $prevDataDB->get($namaGudang);

        $saldo_awal  = $prevItem->saldo_akhir ?? 0;
        $masuk       = $itemToday->masuk ?? 0;
        $pengiriman  = $itemToday->pengiriman ?? 0;

        $total        = $saldo_awal + $masuk;
        $prod_yg_lalu = $prevItem->prod_sd_hi ?? 0; 
        $prod_sd_hi   = $prod_yg_lalu + $masuk;
        $saldo_akhir  = $total - $pengiriman;

        $totalSaldoGudang += $saldo_akhir;

        $tabelIV->push((object)[
            'id_produksi_sir' => $itemToday->id_produksi_sir ?? null,
            'no' => $no,
            'uraian' => $namaGudang,
            'saldo_awal' => $saldo_awal,
            'masuk' => $masuk,
            'total' => $total,
            'prod_bln_lalu' => $prod_yg_lalu,
            'prod_sd_hi' => $prod_sd_hi,
            'pengiriman' => $pengiriman,
            'saldo_akhir' => $saldo_akhir,
            'keterangan' => $itemToday->keterangan ?? '-',
        ]);
    }

    // --- PREVIEW STOK (BOLEH PAKAI LAB) ---
    $saldoKgKemarin = $prevDataDB->whereIn('uraian', $masterGudang)->sum('saldo_akhir');

    $prodHariIni = ProduksiSir20::whereDate('tanggal_produksi', $selectedDate)
        ->selectRaw('SUM(jumlah_pallet) as tp, SUM(kg_yang_dipress) as tk')
        ->first();

    $masukPalletHariIni = (int)($prodHariIni->tp ?? 0);
    $masukKgHariIni     = (float)($prodHariIni->tk ?? 0);

    $terjualP = \App\Models\PenjualanSir20::whereDate('tanggal', $selectedDate)
        ->where('is_summary', 0)->sum('pallet');

    $terjualK = \App\Models\PenjualanSir20::whereDate('tanggal', $selectedDate)
        ->where('is_summary', 0)->sum('hari_ini');

    $sisaPalletKemarin = ProduksiSir::whereIn('uraian', ['Mutu Prima (siap jual)', 'PO / PRI Low'])
        ->whereDate('created_at', '<', $selectedDate->startOfDay())
        ->orderBy('created_at', 'desc')
        ->get()
        ->unique('uraian')
        ->sum('pallet');

    $previewTotalPallet = ($sisaPalletKemarin + $masukPalletHariIni) - $terjualP;
    $previewTotalKg     = ($saldoKgKemarin + $masukKgHariIni) - $terjualK;

    // 🔧 LAB HANYA UNTUK INFO / PREVIEW
    $actualLowCount = \App\Models\HasilUjiLabSIR20::whereDate('tanggal', '<=', $selectedDate)
        ->where('pri', '<', 40)
        ->count();

    // --- TABEL VI: MUTU (STOK RESMI, TIDAK DIPENGARUHI LAB) ---
    $masterMutu = [
        '6.1' => 'Mutu Prima (siap jual)',
        '6.2' => 'PO / PRI Low',
        '6.3' => 'WhiteSpot (WS)',
        '6.4' => 'Kontaminasi',
        '6.5' => 'Repacking On Hold',
    ];

    $tabelVI = new Collection();

    foreach ($masterMutu as $no => $uraian) {

        // 🔧 AMBIL STOK RESMI TERAKHIR (BUKAN DARI LAB)
        $latestMutu = ProduksiSir::where('uraian', $uraian)
            ->whereDate('created_at', '<=', $selectedDate)
            ->orderBy('created_at', 'desc')
            ->first();

        $pallet = $latestMutu->pallet ?? 0;
        $kg     = $latestMutu->kg ?? ($pallet * 1260);

        $tabelVI->push((object)[
            'id_produksi_sir' => $latestMutu->id_produksi_sir ?? null,
            'no' => $no,
            'uraian' => $uraian,
            'kg' => $kg,
            'pallet' => $pallet,
            'keterangan' => $latestMutu->keterangan ?? '-',
        ]);
    }

    return view('DataProduksi.data-produksi-sir20', [
        'tabelIV' => $tabelIV,
        'tabelVI' => $tabelVI,
        'selected_date' => $selectedDate->format('Y-m-d'),
        'grandTotal' => $totalBahanProses + $totalSaldoGudang,
        'previewTotalPallet' => $previewTotalPallet,
        'previewTotalKg' => $previewTotalKg,
        'actualLowCount' => $actualLowCount // info lab saja
    ]);
}

public function getProductionToday(Request $request)
{
    $tgl = Carbon::parse($request->date)->startOfDay();

    // 1. Ambil Saldo Akhir Gudang Kemarin (Kg) - Estafet
    $saldoGudangKemarin = ProduksiSir::whereIn('uraian', [
            'Di Gudang SIR', 
            'Di Areal Press Bale', 
            'Di Gudang TOH 1', 
            'Di Gudang TOH 2'
        ])
        ->where('created_at', '<', $tgl)
        ->orderBy('created_at', 'desc')
        ->get()
        ->unique('uraian')
        ->sum('saldo_akhir');

    // 2. Ambil Saldo Pallet Kemarin (Prima + Low) - Estafet
    $saldoPalletKemarin = ProduksiSir::whereIn('uraian', ['Mutu Prima (siap jual)', 'PO / PRI Low'])
        ->where('created_at', '<', $tgl)
        ->orderBy('created_at', 'desc')
        ->get()
        ->unique('uraian')
        ->sum('pallet');

    // 3. Ambil Produksi Baru Hari Ini
    $produksi = ProduksiSir20::whereDate('tanggal_produksi', $tgl)
        ->selectRaw('SUM(kg_yang_dipress) as total_kg, SUM(jumlah_pallet) as total_p')
        ->first();
    $masukKgHariIni     = (float) ($produksi->total_kg ?? 0);
    $masukPalletHariIni = (int) ($produksi->total_p ?? 0);

    // 4. Ambil Total Penjualan/Pengiriman Hari Ini
    $terjualP = \App\Models\PenjualanSir20::whereDate('tanggal', $tgl)
        ->where('is_summary', 0)->sum('pallet');
    $terjualK = \App\Models\PenjualanSir20::whereDate('tanggal', $tgl)
        ->where('is_summary', 0)->sum('hari_ini');

    // 5. 🔥 HITUNG TOTAL LOW RIIL DARI DATABASE LAB (Real-time)
    // Menghitung seluruh palet dengan PRI < 40 dari awal sampai tanggal terpilih.
    // Jika data di lab dihapus, count() akan otomatis menghasilkan 0.
    $actualLowCount = \App\Models\HasilUjiLabSIR20::whereDate('tanggal', '<=', $tgl)
        ->where('pri', '<', 40)
        ->count();

    // --- LOGIKA KALKULASI UNTUK HEADER FORM ---
    // Sisa Stok = (Saldo Kemarin + Produksi Baru) - Pengiriman
    $totalP = ($saldoPalletKemarin + $masukPalletHariIni) - $terjualP;
    $totalK = ($saldoGudangKemarin + $masukKgHariIni) - $terjualK;

    // --- LOGIKA KALKULASI UNTUK RINCIAN MUTU ---
    // Palet Low langsung mengikuti data Lab
    $totalLowP   = $actualLowCount;
    // Palet Prima adalah sisa dari total palet yang ada
    $totalPrimaP = $totalP - $totalLowP;

    return response()->json([
        'total_target_pallet' => $totalP,
        'total_target_kg'     => $totalK, 
        'prima_pallet'        => $totalPrimaP,
        'prima_kg'            => $totalPrimaP * 1260, // Sesuai aturan Pallet x 1260
        'low_pri'             => $totalLowP,          // Sekarang otomatis 0 jika lab kosong
        'low_kg'              => $totalLowP * 1260    // Sesuai aturan Pallet x 1260
    ]);
}
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'uraian'  => 'required', // Pilihan Gudang dari Dropdown
        ]);

        $tgl = Carbon::parse($request->tanggal)->startOfDay();

        DB::beginTransaction();
        try {
            // 1. Ambil Data Produksi (Nilai 18.900 Kg)
            $produksi = ProduksiSir20::whereDate('tanggal_produksi', $tgl)
                ->selectRaw('SUM(jumlah_pallet) as tp, SUM(kg_yang_dipress) as tk')
                ->first();
            
            $totalPallet = (int) ($produksi->tp ?? 0);
            $totalKg     = (float) ($produksi->tk ?? 0);

            // 2. SIMPAN KE TABEL IV (GUDANG) - Mengisi kolom 'Masuk'
            $namaGudang = $request->uraian; 
            $prevGudang = ProduksiSir::where('uraian', $namaGudang)
                ->where('created_at', '<', $tgl)
                ->orderBy('created_at', 'desc')
                ->first();

            $saldoAwal = $prevGudang->saldo_akhir ?? 0;
            $prodLalu  = $prevGudang->prod_sd_hi ?? 0;

            ProduksiSir::updateOrCreate(
                ['uraian' => $namaGudang, 'created_at' => $tgl],
                [
                    'saldo_awal'    => $saldoAwal,
                    'masuk'         => $totalKg, // 18.900 masuk ke sini
                    'total'         => $saldoAwal + $totalKg,
                    'prod_bln_lalu' => $prodLalu,
                    'prod_sd_hi'    => $prodLalu + $totalKg,
                    'pengiriman'    => $request->pengiriman ?? 0,
                    'saldo_akhir'   => ($saldoAwal + $totalKg) - ($request->pengiriman ?? 0),
                    'keterangan'    => $request->keterangan ?? '-',
                ]
            );

            // 3. SIMPAN KE TABEL VI (MUTU)
            $lowPallet = HasilUjiLabSIR20::whereDate('tanggal', $tgl)
                ->where('pri', '<', 40)
                ->count();

            $lastPrima = ProduksiSir::where('uraian', 'Mutu Prima (siap jual)')
                ->where('created_at', '<', $tgl)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $kgPerPallet = $totalPallet > 0 ? ($totalKg / $totalPallet) : 0;
            $lowKg = $lowPallet * $kgPerPallet;

            // Update Mutu Prima
            ProduksiSir::updateOrCreate(
                ['uraian' => 'Mutu Prima (siap jual)', 'created_at' => $tgl],
                [
                    'pallet' => (($lastPrima->pallet ?? 0) + $totalPallet) - $lowPallet,
                    'kg'     => $totalKg - $lowKg,
                    'keterangan' => '-'
                ]
            );

            // Update PO / PRI Low
            ProduksiSir::updateOrCreate(
                ['uraian' => 'PO / PRI Low', 'created_at' => $tgl],
                ['pallet' => $lowPallet, 'kg' => $lowKg, 'keterangan' => '-']
            );

            DB::commit();
            return redirect()->back()->with('success', 'Data Gudang & Mutu berhasil disinkronkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}