<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BahanProsesApiController;
use App\Http\Controllers\Api\GudangSirApiController;
use App\Http\Controllers\Api\HasilUjiBokarOlahApiController;
use App\Http\Controllers\Api\HasilUjiLabBokarApiController;
use App\Http\Controllers\Api\HasilUjiMaturasiApiController;
use App\Http\Controllers\Api\HasilUjiSir20ApiController;
use App\Http\Controllers\Api\HasilUjiTroliApiController;
use App\Http\Controllers\Api\LaporanApiController;
use App\Http\Controllers\Api\MaturasiApiController;
use App\Http\Controllers\Api\PenjualanSirApiController;
use App\Http\Controllers\Api\ProduksiSir20ApiController;
use App\Http\Controllers\Api\TimbangBokarApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute API
|--------------------------------------------------------------------------
*/

// Rute login HARUS di luar middleware 'auth'
Route::post('/login', [AuthController::class, 'login']);

// ✅ PERBAIKAN: Bungkus SEMUA rute lain di dalam middleware 'auth:sanctum'
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/users', [AuthController::class, 'getUsers']);

    // --- Rute Uji Bokar (Standar) ---
    Route::post('/uji-bokar', [HasilUjiLabBokarApiController::class, 'store'])->name('api.uji-bokar.store');
    Route::get('/hasil-uji-lab-bokar', [HasilUjiLabBokarApiController::class, 'index']);
    Route::get('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'show']);
    Route::put('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'update']);
    Route::delete('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'destroy']);

    // --- Rute Uji Maturasi ---
    Route::post('/uji-maturasi', [HasilUjiMaturasiApiController::class, 'store'])->name('api.uji-maturasi.store');
    Route::get('/hasil-uji-lab-maturasi', [HasilUjiMaturasiApiController::class, 'index']);
    Route::get('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'show']);
    Route::put('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'update']);
    Route::delete('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'destroy']);
    // (Anda mungkin perlu rute GET untuk spinner maturasi di sini nanti)

    // --- Rute Uji Troli ---
    Route::post('/uji-troli', [HasilUjiTroliApiController::class, 'store'])->name('api.uji-troli.store');
    Route::get('/hasil-uji-lab-troli', [HasilUjiTroliApiController::class, 'index']);
    Route::get('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'show']);
    Route::put('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'update']);
    Route::delete('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'destroy']);

    // --- Rute Uji SIR 20 ---
    Route::get('/uji-sir20/available-pallets', [HasilUjiSir20ApiController::class, 'getAvailablePallets']);
    Route::post('/uji-sir20', [HasilUjiSir20ApiController::class, 'store'])->name('api.uji-sir20.store');
    Route::get('/hasil-uji-lab-sir20', [HasilUjiSir20ApiController::class, 'index']);
    Route::get('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'show']);
    Route::put('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'update']);
    Route::delete('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'destroy']);


    // ================================================================
    // Rute untuk Timbang Bokar & Uji Bokar Olah (Form 1 & 2)
    // (Controller: TimbangBokarApiController)
    // ================================================================
    
    // ✅ PERBAIKAN: Rute spesifik HARUS di atas rute wildcard {id}
    
    // [FORM 2 - Spinner] Mengambil daftar Bak yang K3-nya masih NULL
    Route::get('/timbang-bokar/pending-k3', [HasilUjiBokarOlahApiController::class, 'getPendingK3']);
    
    // [TABEL 1] Mengambil SEMUA data Timbang Bokar
    Route::get('/hasil-timbang-bokar', [TimbangBokarApiController::class, 'index']);

    // [FORM 2 - Simpan] Memperbarui/menyimpan HANYA K3
    Route::put('/timbang-bokar/update-k3/{id}', [TimbangBokarApiController::class, 'updateK3']);

    // [FORM 1] Menyimpan data Timbang Bokar BARU
    Route::post('/timbang-bokar', [TimbangBokarApiController::class, 'store']);
    
    // [FORM 1 - Edit] Mengambil detail Timbang Bokar (Wildcard {id})
    Route::get('/timbang-bokar/{id}', [TimbangBokarApiController::class, 'show']);
    
    // [FORM 1 - Edit] Mengirim UPDATE data Timbang Bokar (Wildcard {id})
    Route::put('/timbang-bokar/{id}', [TimbangBokarApiController::class, 'update']);
    
    // [TABEL 1 - Hapus] Menghapus data Timbang Bokar (Wildcard {id})
    Route::delete('/timbang-bokar/{id}', [TimbangBokarApiController::class, 'destroy']);
    // ✅ AKHIR PERBAIKAN URUTAN


    // ================================================================
    // Rute untuk Data Uji Bokar Olah (Tabel 2)
    // (Controller: HasilUjiBokarOlahApiController)
    // ================================================================
    
    // [FORM 2 - Simpan] (LANGKAH 2) Mengirim data BARU ke tabel hasil_uji_bokar_diolah
    Route::post('/hasil-uji-bokar-olah', [HasilUjiBokarOlahApiController::class, 'store']);
    
    // [TABEL 2] Mengambil data Uji Bokar Olah (Data yg K3-nya SUDAH diisi)
    Route::get('/hasil-uji-bokar-olah', [HasilUjiBokarOlahApiController::class, 'index']);
    
    // [TABEL 2 - Hapus] Menghapus data Uji Bokar Olah (Reset K3)
    Route::delete('/uji-bokar-olah/{id}', [HasilUjiBokarOlahApiController::class, 'destroy']);

    // ==========================================================
    // ✅ PERBAIKAN: TAMBAHKAN RUTE BARU INI UNTUK EDIT K3
    // {id} di sini adalah ID dari 'pengolahan_basah' (Tabel 1)
    // ==========================================================
    Route::put('/hasil-uji-bokar-olah/update-k3/{id}', [HasilUjiBokarOlahApiController::class, 'updateK3']);


    // ✅ PERBAIKAN: Tambahkan rute untuk Controller Maturasi BARU
    // ================================================================
    // Rute untuk Data Pengolahan Maturasi (Tabel Status)
    // (Controller: MaturasiApiController)
    // ================================================================
    
    Route::get('/maturasi/all', [MaturasiApiController::class, 'getAll']);

    // [TABEL MATURASI] Mengambil data status maturasi
    Route::get('/pengolahan-maturasi', [MaturasiApiController::class, 'index']);

    // [FORM 2 - SPINNER] Mengambil daftar Bak Maturasi yang stoknya > 0
    Route::get('/maturasi/list-available', [MaturasiApiController::class, 'getListAvailable']);
    // ✅ AKHIR PERBAIKAN

    // ✅ PERBAIKAN: Tambahkan rute STORE untuk Olah Harian
    Route::post('/pengolahan-maturasi/store', [MaturasiApiController::class, 'store']);
    // 🔥 TAMBAHKAN 2 RUTE INI UNTUK EDIT MUTASI
    Route::get('/pengolahan-maturasi/{id}/edit', [MaturasiApiController::class, 'edit']);
    Route::put('/pengolahan-maturasi/{id}', [MaturasiApiController::class, 'update']);
    // ✅ AKHIR PERBAIKAN

    // ✅ PERBAIKAN: Rute BAHAN DALAM PROSES (WIP)
    Route::get('/bahan-proses', [BahanProsesApiController::class, 'index']);
    Route::post('/bahan-proses', [BahanProsesApiController::class, 'store']);
    // 🔥 TAMBAHAN WAJIB AGAR EDIT BERJALAN:
    Route::put('/bahan-proses/{id}', [BahanProsesApiController::class, 'update']);
    // ✅ AKHIR PERBAIKAN

    // [GUDANG SIR]
    Route::get('gudang-sir', [GudangSirApiController::class, 'index']);
    Route::post('gudang-sir', [GudangSirApiController::class, 'store']);
    // 🔥 TAMBAHKAN 3 ROUTE INI 🔥
    Route::get('gudang-sir/pallets', [GudangSirApiController::class, 'getPalletsByLocation']);
    Route::post('gudang-sir/pindah-lokasi', [GudangSirApiController::class, 'pindahLokasi']);
    Route::post('gudang-sir/update-mutu', [GudangSirApiController::class, 'updateStatusMutu']);

    // [PENJUALAN SIR]
    Route::get('penjualan-sir20/available-stock', [PenjualanSirApiController::class, 'getAvailableStock']);
    Route::get('penjualan-sir20', [PenjualanSirApiController::class, 'index']);
    Route::post('penjualan-sir20', [PenjualanSirApiController::class, 'store']);
    Route::get('penjualan-sir20/cek-gudang', [PenjualanSirApiController::class, 'getPengirimanGudang']);
    Route::delete('penjualan-sir20/{id}', [PenjualanSirApiController::class, 'destroy']);
    Route::get('penjualan-sir20/pending-debts', [PenjualanSirApiController::class, 'getPendingDebts']);
    Route::post('penjualan-sir20/fulfill-debt/{id_manual}', [PenjualanSirApiController::class, 'fulfillDebt']);

    // ==========================================
    // [PRODUKSI SIR 20] - MODUL BARU
    // ==========================================
    
    // 1. Ambil List Data (History di Android)
    Route::get('/produksi-sir20', [ProduksiSir20ApiController::class, 'index']);

    // 2. Simpan Data Baru (Header + 3 Child Table + Potong Stok Maturasi)
    Route::post('/produksi-sir20', [ProduksiSir20ApiController::class, 'store']);

    // 3. Update Data (Header + Reset Child + Potong Ulang Stok)
    Route::put('/produksi-sir20/{id}', [ProduksiSir20ApiController::class, 'update']);

    // 4. Hapus Data (Hapus + Kembalikan Stok Maturasi)
    Route::delete('/produksi-sir20/{id}', [ProduksiSir20ApiController::class, 'destroy']);
    
    // 5. Tambahan: Ambil Nomor Batch Terakhir (Untuk Auto Number di Android)
    Route::get('/produksi-sir20/last-number', [ProduksiSir20ApiController::class, 'getLastNumber']);

    // Route untuk mengambil daftar maturasi aktif di form input mobile
    Route::get('/produksi-sir20/maturasi-aktif', [ProduksiSir20ApiController::class, 'getActiveMaturasi']);

    // Route API untuk Cetak PDF Laporan
    
});
// ✅ AKHIR GROUP MIDDLEWARE

    // ==========================================
    // 🔥 ROUTE LAPORAN HARIAN (ANDROID)
    // ==========================================
    Route::get('laporan-harian', [LaporanApiController::class, 'index']);