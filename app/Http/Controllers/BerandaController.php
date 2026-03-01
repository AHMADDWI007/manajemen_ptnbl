<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pallet;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\TransaksiApiBokar;
use App\Models\PenjualanSir20;

class BerandaController extends Controller
{
   public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::today()->startOfMonth();
        $currentYear = Carbon::today()->year; 

        // =========================================================
        // 1. DATA UNTUK KARTU STATISTIK (STAT CARDS)
        // =========================================================
        
        // A. Bokar Masuk (Bulan Ini) (Ton)
        $totalBokarBulanIni = TransaksiApiBokar::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
                                ->sum('masuk_hi');
        $statBokar = $totalBokarBulanIni / 1000; // Ubah ke Ton

      // 🔥 PERBAIKAN: Menggunakan 'netto_kering' agar sama persis dengan tabel Ringkasan Stok
        $totalBokarDiolah = PengolahanBasah::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
                                ->sum('netto_kering'); 
        $statBokarDiolah = $totalBokarDiolah / 1000; // Ubah ke Ton
        // C. Stok Maturasi Saat Ini (Ton)
        $totalMaturasi = Maturasi::sum('stok_akhir');
        $statMaturasi = $totalMaturasi / 1000; // Ubah ke Ton
        
        // D. Stok Gudang SIR 20 (Siap Jual) (Ton)
        $totalGudangSir = Pallet::whereNull('tanggal_penjualan')
                                ->orWhere('tanggal_penjualan', '>', $today->format('Y-m-d'))
                                ->sum('berat');
        $statGudang = $totalGudangSir / 1000; // Ubah ke Ton

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
            'statBokar', 'statBokarDiolah', 'statMaturasi', 'statGudang', // 🔥 Update Variabel di sini
            'chartLabels', 'chartPenjualan', 'currentYear'
        ));
    }
}