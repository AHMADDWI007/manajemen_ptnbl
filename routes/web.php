<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BokarController;
use App\Http\Controllers\MaturasiController;
use App\Http\Controllers\BahanDalamProsesController;
use App\Http\Controllers\HasilUjiLabBokarController;
use App\Http\Controllers\HasilUjiMaturasiController;
use App\Http\Controllers\HasilUjiSir20Controller;
use App\Http\Controllers\LaporanHarianController;
use App\Http\Controllers\HasilUjiTroliController;
use App\Http\Controllers\ProduksiSir20Controller;
use App\Http\Controllers\PenjualanSir20Controller;
use App\Models\BahanDalamProses;
use App\Models\ProduksiSir20;

// Route halaman beranda
Route::get('/beranda', function () {
    return view('HalamanDepan.beranda');
})->name('beranda');

// Route resource untuk data Bokar (CRUD)
Route::resource('bokar', BokarController::class);
Route::resource('maturasi', MaturasiController::class);
Route::resource('produksi', BahanDalamProsesController::class);
Route::resource('hasil_uji_lab_bokar', HasilUjiLabBokarController::class);
Route::resource('produksi_sir20', ProduksiSir20Controller::class);
Route::resource('penjualan_sir20', PenjualanSir20Controller::class);
Route::get('/hasil_uji_sir_20', [HasilUjiSir20Controller::class, 'index'])->name('hasil_uji_sir_20.index');
Route::get('/hasil_uji_maturasi', [HasilUjiMaturasiController::class, 'index'])->name('hasil_uji_maturasi.index');
Route::get('laporan/harian', [LaporanHarianController::class,'index'])->name('laporan.harian');
Route::get('laporan/harian/export', [LaporanHarianController::class,'exportExcel'])->name('laporan.harian.excel');
Route::get('/hasil_uji_troli', [App\Http\Controllers\HasilUjiTroliController::class, 'index'])->name('hasil_uji_troli');






// Route halaman utama (welcome)
Route::get('/', function () {
    return view('welcome');
});

