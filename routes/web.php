<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// ==============================================================================
//  IMPORT CONTROLLERS
// ==============================================================================

// --- UTAMA ---
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LaporanHarianController;

// --- DATA LABORATORIUM ---
use App\Http\Controllers\DataLaboratorium\HasilUjiBokarController;
use App\Http\Controllers\DataLaboratorium\HasilUjiBokarDiolahController;
use App\Http\Controllers\DataLaboratorium\HasilUjiMaturasiController;
use App\Http\Controllers\DataLaboratorium\HasilUjiSir20Controller;
use App\Http\Controllers\DataLaboratorium\HasilUjiTroliController;

// --- DATA PENGOLAHAN ---
use App\Http\Controllers\DataPengolahan\PengolahanBasahController;
use App\Http\Controllers\DataPengolahan\MaturasiController;
use App\Http\Controllers\DataPengolahan\BahanProsesController;

// --- DATA PRODUKSI ---
use App\Http\Controllers\DataProduksi\DataProduksiSir20Controller; // Gudang & Mutu
use App\Http\Controllers\DataProduksi\ProduksiSir20Controller;     // Proses Produksi
use App\Http\Controllers\DataProduksi\PenjualanSir20Controller;    // Penjualan

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke beranda jika login
Route::get('/', function () {
    return redirect()->route('beranda');
})->middleware('auth');

// --- AUTHENTICATION ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ==============================================================================
//  PROTECTED ROUTES (Harus Login)
// ==============================================================================
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD & USER ---
    Route::get('/beranda', function () {
        return view('HalamanDepan.beranda');
    })->name('beranda');

    Route::resource('users', UserController::class);
    Route::get('/data-pengguna', [UserController::class, 'index'])->name('data_pengguna');


    // ====================================================
    // 🟢 MODUL: DATA PENGOLAHAN
    // ====================================================
    
    // 1. Pengolahan Basah
    Route::get('/pengolahan-basah/rekap', [PengolahanBasahController::class, 'rekap'])->name('pengolahan-basah.rekap');
    Route::post('/pengolahan-basah/update-rektif', [PengolahanBasahController::class, 'updateRektif'])->name('pengolahan-basah.updateRektif');
    Route::resource('pengolahan-basah', PengolahanBasahController::class);

    // 2. Maturasi
    Route::get('/maturasi-get-previous-data', [MaturasiController::class, 'getPreviousData'])->name('maturasi.getPreviousData');
    Route::get('/maturasi/cetak', [MaturasiController::class, 'cetakPdf'])->name('maturasi.cetak');
    Route::post('/maturasi/{maturasi}/reset', [MaturasiController::class, 'reset'])->name('maturasi.reset');
    Route::resource('maturasi', MaturasiController::class);

    // 3. Bahan Proses
    Route::resource('bahan-proses', BahanProsesController::class);


    // ====================================================
    // 🟡 MODUL: DATA LABORATORIUM
    // ====================================================
    
    Route::resource('hasil-uji-bokar', HasilUjiBokarController::class);
    Route::resource('hasil-uji-bokar-diolah', HasilUjiBokarDiolahController::class);
    Route::resource('hasil-uji-maturasi', HasilUjiMaturasiController::class);
    Route::resource('hasil-uji-troli', HasilUjiTroliController::class);
    Route::get('/get-available-pallets', [HasilUjiSIR20Controller::class, 'getAvailablePallets'])->name('uji-sir20.get-pallets');
    Route::resource('hasil-uji-sir20', HasilUjiSir20Controller::class);


    // ====================================================
    // 🔵 MODUL: DATA PRODUKSI
    // ====================================================

    // 1. Data Gudang & Mutu
    Route::get('/data-sir/get-production', [DataProduksiSir20Controller::class, 'getProductionToday'])->name('data-sir.getProductionToday');
    Route::resource('data-sir', DataProduksiSir20Controller::class);

    // 2. Proses Produksi Harian
    Route::post('/produksi-sir20/get-maturasi-details', [ProduksiSir20Controller::class, 'getMaturasiDetails'])->name('produksi.getMaturasiDetails');
    Route::resource('produksi-sir20', ProduksiSir20Controller::class);

    // 3. Penjualan
     Route::get('/penjualan-sir20/get-available-stock', [PenjualanSir20Controller::class, 'getAvailableStock'])->name('penjualan-sir20.getAvailableStock');
    Route::get('/penjualan-sir20/get-pengiriman', [PenjualanSir20Controller::class, 'getPengirimanGudang'])->name('penjualan-sir20.getPengiriman');
    Route::resource('penjualan-sir20', PenjualanSir20Controller::class)->parameters(['penjualan-sir20' => 'id']);
   
    // ====================================================
    // ⚪ LAIN - LAIN
    // ====================================================

    // Sync Manual (API Bokar)
    Route::post('/sync-bokar-manual', function () {
        try {
            Artisan::call('bokar:sync');
            return response()->json(['success' => true, 'message' => 'Data API berhasil disinkronisasi!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal sinkronisasi: ' . $e->getMessage()], 500);
        }
    })->name('bokar.sync.manual');

    // Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class, 'index'])->name('laporan.harian');
    Route::get('/laporan-harian/export', [LaporanHarianController::class, 'exportExcel'])->name('laporan.harian.excel');

});