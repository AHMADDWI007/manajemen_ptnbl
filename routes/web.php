<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DataLaboratorium\HasilUjiBokarController;
use App\Http\Controllers\DataLaboratorium\HasilUjiBokarDiolahController;
use App\Http\Controllers\DataLaboratorium\HasilUjiMaturasiController;

// ==============================================================================
//  IMPORT CONTROLLERS
// ==============================================================================

// --- UTAMA ---
use App\Http\Controllers\DataLaboratorium\HasilUjiSir20Controller;
use App\Http\Controllers\DataLaboratorium\HasilUjiTroliController;
use App\Http\Controllers\DataPengolahan\BahanProsesController;
use App\Http\Controllers\DataPengolahan\MaturasiController;

// --- DATA PENGOLAHAN ---
use App\Http\Controllers\DataPengolahan\PengolahanBasahController;
use App\Http\Controllers\DataProduksi\DataProduksiSir20Controller; // Gudang & Mutu
use App\Http\Controllers\DataProduksi\PenjualanSir20Controller;    // Penjualan

// --- DATA LABORATORIUM ---
use App\Http\Controllers\DataProduksi\ProduksiSir20Controller;     // Proses Produksi
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\UserController;

// --- DATA PRODUKSI ---
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==============================================================================
//  ROOT & AUTHENTICATION
// ==============================================================================

// Redirect root: Jika sudah login ke beranda, jika belum ke login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('beranda') : redirect()->route('login');
});

// Login & Logout
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ==============================================================================
//  PROTECTED ROUTES (Harus Login)
// ==============================================================================
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD ---
    Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');

    // --- USER MANAGEMENT ---
    Route::resource('users', UserController::class);
    Route::get('/data-pengguna', [UserController::class, 'index'])->name('data_pengguna');


    // ====================================================
    // 🟢 MODUL: DATA PENGOLAHAN
    // ====================================================
    
    // 1. Pengolahan Basah
    Route::delete('/pengolahan-basah/destroy-group', [PengolahanBasahController::class, 'destroyGroup'])->name('pengolahan-basah.destroy-group');
    Route::get('/pengolahan-basah/rekap', [PengolahanBasahController::class, 'rekap'])->name('pengolahan-basah.rekap');
    Route::post('/pengolahan-basah/update-rektif', [PengolahanBasahController::class, 'updateRektif'])->name('pengolahan-basah.updateRektif');
    Route::post('pengolahan-basah/pecah', [PengolahanBasahController::class, 'pecahStore'])->name('pengolahan-basah.pecahStore');
    Route::resource('pengolahan-basah', PengolahanBasahController::class);

    // 2. Maturasi
    Route::get('/maturasi-get-previous-data', [MaturasiController::class, 'getPreviousData'])->name('maturasi.getPreviousData');
    Route::get('/maturasi/cetak', [MaturasiController::class, 'cetakPdf'])->name('maturasi.cetak');
    Route::post('/maturasi/{maturasi}/reset', [MaturasiController::class, 'reset'])->name('maturasi.reset');
    Route::resource('maturasi', MaturasiController::class);

    // 3. Bahan Proses
    Route::post('/bahan-proses/check-stock', [BahanProsesController::class, 'checkStock'])->name('bahan-proses.check-stock');
    Route::resource('bahan-proses', BahanProsesController::class);


    // ====================================================
    // 🟡 MODUL: DATA LABORATORIUM
    // ====================================================
    
    Route::get('/hasil-uji-bokar/export', [HasilUjiBokarController::class, 'exportExcel'])->name('hasil-uji-bokar.export');
    Route::resource('hasil-uji-bokar', HasilUjiBokarController::class);
    Route::get('/hasil-uji-bokar-diolah/export', [HasilUjiBokarDiolahController::class, 'exportExcel'])->name('hasil-uji-bokar-diolah.export');
    Route::resource('hasil-uji-bokar-diolah', HasilUjiBokarDiolahController::class);
    Route::get('/hasil-uji-maturasi/export', [HasilUjiMaturasiController::class, 'exportExcel'])->name('hasil-uji-maturasi.export');
    Route::resource('hasil-uji-maturasi', HasilUjiMaturasiController::class);
    Route::get('/hasil-uji-troli/export', [HasilUjiTroliController::class, 'exportExcel'])->name('hasil-uji-troli.export');
    Route::resource('hasil-uji-troli', HasilUjiTroliController::class);
    
    // Hasil Uji SIR 20
    Route::get('/hasil-uji-sir20/export', [HasilUjiSir20Controller::class, 'exportExcel'])->name('hasil-uji-sir20.export');
    Route::get('/get-available-pallets', [HasilUjiSir20Controller::class, 'getAvailablePallets'])->name('uji-sir20.get-pallets');
    Route::resource('hasil-uji-sir20', HasilUjiSir20Controller::class);


    // ====================================================
    // 🔵 MODUL: DATA PRODUKSI
    // ====================================================

    // 1. Data Gudang & Mutu
    Route::get('/data-sir/get-production', [DataProduksiSir20Controller::class, 'getProductionToday'])->name('data-sir.getProductionToday');
    Route::resource('data-sir', DataProduksiSir20Controller::class);
    // API untuk ambil pallet di modal
    Route::get('/get-pallets-location', [DataProduksiSir20Controller::class, 'getPalletsByLocation'])->name('data-sir.getPalletsByLocation');
    
    // Action untuk proses pindah
    Route::post('/pindah-lokasi', [DataProduksiSir20Controller::class, 'pindahLokasi'])->name('data-sir.pindahLokasi');
    Route::post('/update-mutu', [DataProduksiSir20Controller::class, 'updateStatusMutu'])->name('data-sir.updateMutu');

    // 2. Proses Produksi Harian
    Route::post('/produksi-sir20/get-maturasi-details', [ProduksiSir20Controller::class, 'getMaturasiDetails'])->name('produksi.getMaturasiDetails');

    // 1. Letakkan route custom di atas resource
    Route::get('/produksi-sir20/cetak-harian', [ProduksiSir20Controller::class, 'cetakHarian'])->name('produksi-sir20.cetak-harian');
    Route::get('/produksi-sir20/{id}/cetak', [ProduksiSir20Controller::class, 'cetakPdf'])->name('produksi-sir20.cetak');
    Route::resource('produksi-sir20', ProduksiSir20Controller::class);

    // 3. Penjualan
    Route::get('/penjualan-sir20/get-available-stock', [PenjualanSir20Controller::class, 'getAvailableStock'])->name('penjualan-sir20.getAvailableStock');
    Route::get('/penjualan-sir20/get-pengiriman', [PenjualanSir20Controller::class, 'getPengirimanGudang'])->name('penjualan-sir20.getPengiriman');
    Route::resource('penjualan-sir20', PenjualanSir20Controller::class)->parameters(['penjualan-sir20' => 'id']);


    // ====================================================
    // ⚙️ PENGATURAN SISTEM
    // ====================================================
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/update', [PengaturanController::class, 'update'])->name('pengaturan.update');


    // ====================================================
    // 📊 LAPORAN HARIAN (PUSAT DATA)
    // ====================================================
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    // Route untuk Export Harian 
    Route::get('/laporan/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.exportExcel');
    // Route untuk Export Bulanan
    Route::get('/laporan/export-bulanan', [LaporanController::class, 'exportExcelBulanan'])->name('laporan.exportBulanan');


    // ====================================================
    // ⚪ LAIN - LAIN (UTILITIES)
    // ====================================================

    // Sync Manual (API Bokar)
    Route::post('/sync-bokar-manual', function (Request $request) {
        try {
            $tanggal = $request->input('tanggal'); 
            
            if ($tanggal) {
                Artisan::call('bokar:sync', ['date' => $tanggal]);
            } else {
                Artisan::call('bokar:sync');
            }

            return response()->json([
                'success' => true, 
                'message' => 'Data API berhasil disinkronisasi untuk tanggal: ' . ($tanggal ?? 'Hari Ini')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal sinkronisasi: ' . $e->getMessage()], 500);
        }
    })->name('bokar.sync.manual');

});

    Route::get('/laporan/preview-cetak', [LaporanController::class, 'previewCetak'])->name('laporan.previewCetak');