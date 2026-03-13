<?php

namespace App\Http\Controllers;

use App\Models\BahanProses;
use App\Models\Maturasi;
use App\Models\Pallet;
use App\Models\Information;
use App\Models\PengolahanBasah;
use App\Models\PenjualanSir20;
use App\Models\RektifikasiStok;
use App\Models\TransaksiApiBokar;
use Carbon\Carbon;

class BerandaController extends Controller
{
   public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::today()->startOfMonth();
        $currentYear = Carbon::today()->year; 


        $infoPenting = Information::where('status', 'unhide')
                            ->orderBy('tanggal', 'desc')
                            ->get();
        // =========================================================
        // 1. DATA UNTUK KARTU STATISTIK (STAT CARDS)
        // =========================================================
        
        // A. Bokar Masuk (Bulan Ini) (Ton)
        $totalBokarBulanIni = TransaksiApiBokar::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
                                ->where('kode_api', 'total')
                                ->sum('masuk_hi');
        $statBokar = $totalBokarBulanIni; // Ubah ke Ton

      // ðŸ”¥ PERBAIKAN: Menggunakan 'netto_kering' agar sama persis dengan tabel Ringkasan Stok
        $totalBokarDiolah = PengolahanBasah::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
                                ->sum('netto_kering'); 
        $statBokarDiolah = $totalBokarDiolah; // Ubah ke Ton
        // C. Stok Maturasi Saat Ini (Ton)
        $totalMaturasi = Maturasi::sum('stok_akhir');
        $statMaturasi = $totalMaturasi; // Ubah ke Ton
        
        // D. Stok Gudang SIR 20 (Siap Jual) (Ton)
        $totalGudangSir = Pallet::whereNull('tanggal_penjualan')
                                ->orWhere('tanggal_penjualan', '>', $today->format('Y-m-d'))
                                ->sum('berat');
        $statGudang = $totalGudangSir; // Ubah ke Ton
        
        // E. 🔥 TAMBAHAN 1: Stok Bokar (Mentah) Saat Ini (Ton)
        // Stok Bokar = Total Masuk (All Time) - Total Diolah (All Time) + Total Rektifikasi (All Time)
        $totalMasukAll = TransaksiApiBokar::where('kode_api', '!=', 'total')
                                ->sum('masuk_hi');
        $totalDiolahAll = PengolahanBasah::sum('netto_kering');
        $totalRektifAll = RektifikasiStok::sum('berat');
        
        // 1. Cari dulu tanggal terbarunya
        $tanggalTerbaru = BahanProses::max('tanggal');
        
        // 2. Jumlahkan saldo_akhir yang tanggalnya sama dengan tanggal terbaru tersebut
        $totalWipAll = BahanProses::whereDate('tanggal', $tanggalTerbaru)->sum('saldo_akhir') ?? 0;
        
        // $totalStokBokar = max(0, $totalMasukAll - $totalDiolahAll - $totalRektifAll);
        $totalStokBokar = $totalMasukAll - $totalDiolahAll + $totalRektifAll;
        $statStokBokar = $totalStokBokar; // Ubah ke Ton
        
        // F. 🔥 TAMBAHAN 2: Total Stok Keseluruhan (Pabrik) (Ton)
        // Menggabungkan semua stok yang saat ini ada di pabrik (Bokar + Maturasi + WIP + Gudang)
        // $totalWipAll = BahanProses::whereDate('tanggal', $today->format('Y-m-d'))->sum('saldo_akhir');
        
        $statTotalStokKeseluruhan = $statStokBokar + $statMaturasi + $totalWipAll + $statGudang;

        // =========================================================
        // 2. DATA UNTUK GRAFIK PENJUALAN BULANAN (TAHUN INI)
        // =========================================================
        
        $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartPenjualan = array_fill(0, 12, 0); 

        $penjualanData = PenjualanSir20::whereYear('tanggal', $currentYear)
            ->where('is_summary', 0) 
            ->selectRaw('MONTH(tanggal) as bulan, SUM(hari_ini) as total')
            ->groupBy('bulan')
            ->get();

        foreach ($penjualanData as $data) {
            $chartPenjualan[$data->bulan - 1] = $data->total;
        }

        return view('HalamanDepan.beranda', compact(
            'statBokar', 'statBokarDiolah', 'statMaturasi', 'statGudang',
            'statStokBokar', 'statTotalStokKeseluruhan', 'infoPenting',
            'chartLabels', 'chartPenjualan', 'currentYear'
        ));
    }
}