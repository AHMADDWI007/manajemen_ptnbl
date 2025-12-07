<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\{
    LoginController, UserController, MaturasiController,
    BahanProsesController, HasilUjiLabBokarController, HasilUjiMaturasiController,
    HasilUjiSir20Controller, HasilUjiTroliController, ProduksiSir20Controller,
    PenjualanSir20Controller, LaporanHarianController, HasilUjiBokarDiolahController, PengolahanBasahController,
    ProduksiSirController
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
    
    

    // --- PERBAIKAN DI SINI ---
    // Route khusus untuk AJAX get data terakhir.
    // INI HARUS DITEMPATKAN SEBELUM Route::resource
    
// Rute KHUSUS untuk AJAX 'getPreviousData' dari Modal Tambah Anda
    Route::get('/maturasi-get-previous-data', [MaturasiController::class, 'getPreviousData'])->name('maturasi.getPreviousData');

    // Route Maturasi
    Route::get('/maturasi/cetak', [MaturasiController::class, 'cetakPdf'])->name('maturasi.cetak'); // ✅ Tambahkan ini
    Route::get('/maturasi-get-previous-data', [MaturasiController::class, 'getPreviousData'])->name('maturasi.getPreviousData');
    Route::resource('maturasi', MaturasiController::class);
    Route::post('/maturasi/{maturasi}/reset', [MaturasiController::class, 'reset'])->name('maturasi.reset');

    // Rute resource untuk index, store, update, destroy
    Route::resource('maturasi', MaturasiController::class);
    Route::post('/maturasi/{maturasi}/reset', [MaturasiController::class, 'reset'])->name('maturasi.reset');
    Route::resource('produksi', BahanProsesController::class);
    Route::resource('bahan-proses', BahanProsesController::class);

     // Data Laboratorium
    Route::resource('hasil_uji_lab_bokar', HasilUjiLabBokarController::class);
    Route::resource('hasil_uji_bokar_diolah', HasilUjiBokarDiolahController::class);
    Route::resource('hasil_uji_maturasi', HasilUjiMaturasiController::class);
    Route::resource('hasil_uji_troli', HasilUjiTroliController::class);
    Route::resource('hasil_uji_sir_20', HasilUjiSir20Controller::class);

    // Data Produksi
    Route::get('/pengolahan_basah/rekap', [PengolahanBasahController::class, 'rekap'])->name('pengolahan_basah.rekap');
    // Route baru untuk menyimpan nilai rektif harian
    Route::post('/pengolahan_basah/update_rektif', [PengolahanBasahController::class, 'updateRektif'])->name('pengolahan_basah.updateRektif');
    Route::post('/sync-bokar-manual', function () {
        try {
            // Menjalankan perintah artisan 'bokar:sync'
            Artisan::call('bokar:sync');
            
            return response()->json([
                'success' => true, 
                'message' => 'Data API berhasil disinkronisasi!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    })->name('bokar.sync.manual');

    Route::resource('pengolahan_basah', PengolahanBasahController::class);
    Route::resource('produksi_sir20', ProduksiSir20Controller::class);
    Route::resource('penjualan_sir20', PenjualanSir20Controller::class);
    Route::resource('produksi-sir', ProduksiSirController::class);

    

    // Laporan Harian
    Route::get('/laporan-harian', [LaporanHarianController::class,'index'])->name('laporan.harian');
    Route::get('/laporan-harian/export', [LaporanHarianController::class,'exportExcel'])->name('laporan.harian.excel');
});

Route::resource('users', UserController::class);
    Route::get('/data_pengguna', [UserController::class, 'index'])->name('data_pengguna');