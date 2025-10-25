<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\{
    LoginController, UserController, BokarController, MaturasiController,
    BahanProsesController, HasilUjiBokarController, HasilUjiBokarOlahController, HasilUjiMaturasiController,
    HasilUjiSir20Controller, HasilUjiTroliController, ProduksiSir20Controller,
    PenjualanSir20Controller, LaporanHarianController
};

// Redirect root ke beranda jika login, atau ke login jika belum
Route::get('/', function () {
    // Lebih baik cek Auth di sini
    if (Auth::check()) {
        return redirect()->route('beranda');
    }
    return redirect()->route('login');
});

// --- LOGIN & LOGOUT ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
// Logout seharusnya hanya bisa diakses user yang login
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// --- RUTE YANG MEMERLUKAN LOGIN ---
Route::middleware(['auth'])->group(function () {

    // Beranda
    Route::get('/beranda', function () {
        return view('HalamanDepan.beranda'); // Pastikan path view benar
    })->name('beranda');

    // Data Pengguna
    Route::resource('users', UserController::class); // Standar CRUD
    Route::get('/data_pengguna', [UserController::class, 'index'])->name('data_pengguna'); // Ini mungkin redundan jika users.index cukup

    // Data Pengolahan
    Route::resource('bokar', BokarController::class);
    Route::resource('maturasi', MaturasiController::class);
    Route::get('/maturasi/get-previous-data', [MaturasiController::class, 'getPreviousDayData'])->name('maturasi.getPreviousData'); // Route spesifik
    Route::resource('bahan-proses', BahanProsesController::class); // Pilih nama resource yang konsisten
    // Hapus duplikat jika 'produksi' tidak dipakai:
    // Route::resource('produksi', BahanProsesController::class);

    // ==========================================================
    // Data Laboratorium - DIRAPIKAN
    // ==========================================================

    // Hasil Uji Lab Bokar: Gunakan resource dengan prefix URL yang benar
    Route::resource('hasil_uji_lab_bokar', HasilUjiBokarController::class);
    // HAPUS SEMUA DEFINISI MANUAL YANG KONFLIK/TIDAK LENGKAP DI BAWAH:
    // Route::get('/hasil_uji_lab_bokar', [HasilUjiLabBokarController::class, 'index'])->name('hasil_uji_lab_bokar.index');
    // Route::post('/hasil_uji_lab-bokar', [HasilUjiLabBokarController::class, 'store'])->name('hasil_uji_lab_bokar.store'); // Typo '-'
    // Route::put('/hasil_uji_lab_bokar/{hasilUjiLabBokar}', [HasilUjiLabBokarController::class, 'update'])->name('hasil_uji_lab_bokar.update');
    // Route::delete('/hasil_uji_lab_bokar/{hasilUjiLabBokar}', [HasilUjiLabBokarController::class, 'destroy'])->name('hasil_uji_lab_bokar.destroy');

    // Hasil Uji Maturasi: Jika hanya index, OK. Jika perlu modal AJAX, ganti jadi resource.
    Route::resource('hasil_uji_maturasi', HasilUjiMaturasiController::class);
    // Alternatif jika perlu CRUD Modal AJAX:
    // Route::resource('hasil_uji_maturasi', HasilUjiMaturasiController::class);

    // Hasil Uji Troli: Cukup resource saja.
    Route::resource('hasil_uji_troli', HasilUjiTroliController::class);
    // HAPUS DEFINISI MANUAL YANG REDUNDAN DI BAWAH:
    // Route::get('/hasil_uji_troli', [HasilUjiTroliController::class, 'index'])->name('hasil_uji_troli.index');
    // Route::post('/hasil_uji_troli', [HasilUjiTroliController::class, 'store'])->name('hasil_uji_troli.store');
    // Route::delete('/hasil_uji_troli/{hasilUjiTroli}', [HasilUjiTroliController::class, 'destroy'])->name('hasil_uji_troli.destroy');

    // Hasil Uji SIR 20: Definisi manual sudah OK, atau bisa diganti resource jika mau konsisten
    Route::resource('hasil_uji_sir_20', HasilUjiSir20Controller::class);
    // Alternatif jika mau konsisten pakai resource:
    // Route::resource('hasil_uji_sir_20', HasilUjiSir20Controller::class);
    // Hasil Uji Bokar Olah (Fitur Baru)
    Route::resource('hasil_uji_bokar_olah', HasilUjiBokarOlahController::class); // <--- TAMBAHKAN ROUTE INI

    // HAPUS RESOURCE DUPLIKAT DENGAN NAMA BERBEDA INI:
    // Route::resource('hasil-uji-lab', HasilUjiLabBokarController::class);
    // Route::resource('hasil-uji-maturasi', HasilUjiMaturasiController::class);
    // Route::resource('hasil-uji-sir20', HasilUjiSir20Controller::class);

    // ==========================================================
    // Akhir Data Laboratorium
    // ==========================================================

    // Data Produksi
    Route::resource('produksi_sir20', ProduksiSir20Controller::class);
    Route::resource('penjualan_sir20', PenjualanSir20Controller::class);
    Route::get('/bokar/get-stock/{bak}', [BokarController::class, 'getStock']); // Route spesifik OK

    // Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class,'index'])->name('laporan.harian');
    Route::get('/laporan-harian/export', [LaporanHarianController::class,'exportExcel'])->name('laporan.harian.excel');

}); // Akhir middleware auth