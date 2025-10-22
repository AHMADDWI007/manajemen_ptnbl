<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    LoginController, UserController, BokarController, MaturasiController,
    BahanProsesController, HasilUjiLabBokarController, HasilUjiMaturasiController,
    HasilUjiSir20Controller, HasilUjiTroliController, ProduksiSir20Controller,
    PenjualanSir20Controller, LaporanHarianController
};

// Redirect root ke beranda jika login, atau ke login jika belum
Route::get('/', function () {
    return redirect()->route('beranda');
})->middleware('auth');

// --- LOGIN & LOGOUT ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- RUTE YANG MEMERLUKAN LOGIN ---
Route::middleware(['auth'])->group(function () {

    // Beranda
    Route::get('/beranda', function () {
        return view('HalamanDepan.beranda');
    })->name('beranda');

    // Data Pengguna
    
    // Data Pengolahan
    Route::resource('bokar', BokarController::class);

    // --- PERUBAHAN DI SINI ---
    // Route resource untuk Maturasi (sudah ada)
    Route::resource('maturasi', MaturasiController::class);
    // Tambahkan route khusus untuk AJAX get data terakhir.
    Route::get('/maturasi/get-previous-data', [MaturasiController::class, 'getPreviousDayData'])->name('maturasi.getPreviousData');
    Route::resource('produksi', BahanProsesController::class);
    Route::resource('bahan-proses', BahanProsesController::class);

    // Data Laboratorium
    Route::get('/hasil_uji_lab_bokar', [HasilUjiLabBokarController::class, 'index'])->name('hasil_uji_lab_bokar.index');
    Route::post('/hasil_uji_lab-bokar', [HasilUjiLabBokarController::class, 'store'])->name('hasil_uji_lab_bokar.store');
    Route::put('/hasil_uji_lab_bokar/{hasilUjiLabBokar}', [HasilUjiLabBokarController::class, 'update'])->name('hasil_uji_lab_bokar.update');
    Route::delete('/hasil_uji_lab_bokar/{hasilUjiLabBokar}', [HasilUjiLabBokarController::class, 'destroy'])->name('hasil_uji_lab_bokar.destroy');
    
    Route::get('/hasil_uji_maturasi', [HasilUjiMaturasiController::class, 'index'])->name('hasil_uji_maturasi.index');
    
    Route::get('/hasil_uji_troli', [HasilUjiTroliController::class, 'index'])->name('hasil_uji_troli.index');
    Route::resource('hasil_uji_troli', HasilUjiTroliController::class);
    Route::post('/hasil_uji_troli', [HasilUjiTroliController::class, 'store'])->name('hasil_uji_troli.store');
    Route::delete('/hasil_uji_troli/{hasilUjiTroli}', [HasilUjiTroliController::class, 'destroy'])->name('hasil_uji_troli.destroy');
    
    Route::get('/hasil_uji_sir_20', [HasilUjiSir20Controller::class, 'index'])->name('hasil_uji_sir_20.index');
    Route::post('/hasil_uji_sir_20', [HasilUjiSir20Controller::class, 'store'])->name('hasil_uji_sir_20.store');
    Route::get('/hasil_uji_sir_20/{hasilUjiSir20}', [HasilUjiSir20Controller::class, 'show'])->name('hasil_uji_sir_20.show'); // Rute untuk Detail
    Route::get('/hasil_uji_sir_20/{hasilUjiSir20}/edit', [HasilUjiSir20Controller::class, 'edit'])->name('hasil_uji_sir_20.edit'); // Rute untuk Edit
    Route::put('/hasil_uji_sir_20/{hasilUjiSir20}', [HasilUjiSir20Controller::class, 'update'])->name('hasil_uji_sir_20.update');
    
    Route::delete('/hasil_uji_sir_20/{hasilUjiSir20}', [HasilUjiSir20Controller::class, 'destroy'])->name('hasil_uji_sir_20.destroy');
    Route::resource('hasil-uji-lab', HasilUjiLabBokarController::class);
    Route::resource('hasil-uji-maturasi', HasilUjiMaturasiController::class);
    Route::resource('hasil-uji-sir20', HasilUjiSir20Controller::class);
    
    // Data Produksi
    Route::resource('produksi_sir20', ProduksiSir20Controller::class);
    Route::resource('penjualan_sir20', PenjualanSir20Controller::class);
    Route::get('/bokar/get-stock/{bak}', [BokarController::class, 'getStock']);
   



    // Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class,'index'])->name('laporan.harian');
    Route::get('/laporan-harian/export', [LaporanHarianController::class,'exportExcel'])->name('laporan.harian.excel');
});

Route::resource('users', UserController::class);
    Route::get('/data_pengguna', [UserController::class, 'index'])->name('data_pengguna');

