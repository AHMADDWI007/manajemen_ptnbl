<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Pallet;
use App\Models\Maturasi;
use App\Models\ProduksiSir20;
use App\Models\TransaksiApiBokar;

class BerandaController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::today()->startOfMonth();

        // 1. DATA UNTUK KARTU STATISTIK (STAT CARDS)
        // A. Total Penerimaan Bokar Bulan Ini (Ton)
        $totalBokarBulanIni = TransaksiApiBokar::whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
                                ->sum('masuk_hi');
        $statBokar = $totalBokarBulanIni / 1000; // Ubah ke Ton

        // B. Total Stok Maturasi Saat Ini (Ton)
        $totalMaturasi = Maturasi::sum('stok_akhir');
        $statMaturasi = $totalMaturasi / 1000; // Ubah ke Ton
        
        // C. Total Stok Gudang SIR 20 (Siap Jual) (Ton)
        // Menghitung jumlah berat pallet yang belum terjual
        $totalGudangSir = Pallet::whereNull('tanggal_penjualan')
                                ->orWhere('tanggal_penjualan', '>', $today->format('Y-m-d'))
                                ->sum('berat');
        $statGudang = $totalGudangSir / 1000; // Ubah ke Ton

        // D. Total Karyawan Aktif
        $statKaryawan = User::count();

        // =========================================================

        // 2. DATA UNTUK GRAFIK (7 HARI TERAKHIR)
        $chartLabels = [];
        $chartBokar = [];
        $chartProduksi = [];

        // Looping mundur dari H-6 sampai H-0 (Hari Ini)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // Label Hari (Contoh: "Senin", "Selasa")
            $chartLabels[] = $date->translatedFormat('l');

            // Data Bokar Masuk per Hari (Kg)
            $bokarMasuk = TransaksiApiBokar::whereDate('tanggal', $date->format('Y-m-d'))->sum('masuk_hi');
            $chartBokar[] = $bokarMasuk;

            // Data Produksi SIR 20 per Hari (Kg)
            $produksiSir = ProduksiSir20::whereDate('tanggal_produksi', $date->format('Y-m-d'))->sum('kg_yang_dipress');
            $chartProduksi[] = $produksiSir;
        }

        return view('HalamanDepan.beranda', compact(
            'statBokar', 'statMaturasi', 'statGudang', 'statKaryawan',
            'chartLabels', 'chartBokar', 'chartProduksi'
        ));
    }
}