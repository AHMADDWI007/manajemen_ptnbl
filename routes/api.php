<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TimbangBokarApiController;
use App\Http\Controllers\Api\HasilUjiSir20ApiController;
use App\Http\Controllers\Api\HasilUjiTroliApiController;
use App\Http\Controllers\Api\HasilUjiLabBokarApiController;
use App\Http\Controllers\Api\HasilUjiMaturasiApiController;
use App\Http\Controllers\Api\HasilUjiBokarOlahApiController;
use App\Http\Controllers\Api\AuthController; // Import controller


// Tambahkan rute ini untuk login
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ✅ Rute API untuk aplikasi mobile (uji-bokar)
Route::post('/uji-bokar', [HasilUjiLabBokarApiController::class, 'store'])
    ->name('api.uji-bokar.store');

// ✅ Rute API untuk MENGAMBIL data dari server (uji-bokar)
Route::get('/hasil-uji-lab-bokar', [HasilUjiLabBokarApiController::class, 'index']);

// KETERANGAN: TAMBAHKAN 3 RUTE BARU DI BAWAH INI UNTUK BOKAR
Route::get('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'show']);       // Read (One)
Route::put('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'update']);     // Update
Route::delete('/uji-bokar/{id}', [HasilUjiLabBokarApiController::class, 'destroy']); // Delete

// ✅ Rute API untuk aplikasi mobile (uji-maturasi)
Route::post('/uji-maturasi', [HasilUjiMaturasiApiController::class, 'store'])
    ->name('api.uji-maturasi.store');

// ✅ Rute API untuk MENGAMBIL data dari server (uji-maturasi)
Route::get('/hasil-uji-lab-maturasi', [HasilUjiMaturasiApiController::class, 'index']);

// KETERANGAN: TAMBAHKAN 3 RUTE BARU DI BAWAH INI UNTUK MATURASI
Route::get('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'show']);       // Read (One)
Route::put('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'update']);     // Update
Route::delete('/uji-maturasi/{id}', [HasilUjiMaturasiApiController::class, 'destroy']); // Delete

// ✅ Tambahkan ini untuk uji troli
Route::post('/uji-troli', [HasilUjiTroliApiController::class, 'store'])
    ->name('api.uji-troli.store');

// ✅ Rute API untuk MENGAMBIL data dari server (uji-troli)
Route::get('/hasil-uji-lab-troli', [HasilUjiTroliApiController::class, 'index']);

// KETERANGAN: TAMBAHKAN 3 RUTE BARU DI BAWAH INI UNTUK TROLI
Route::get('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'show']);             // Read (One)
Route::put('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'update']);           // Update
Route::delete('/uji-troli/{id}', [HasilUjiTroliApiController::class, 'destroy']);       // Delete

// ✅ Tambahkan ini untuk uji sir20
Route::post('/uji-sir20', [HasilUjiSir20ApiController::class, 'store'])
    ->name('api.uji-sir20.store');

// ✅ Rute API untuk MENGAMBIL data dari server (uji-sir20)
Route::get('/hasil-uji-lab-sir20', [HasilUjiSir20ApiController::class, 'index']);

// KETERANGAN: TAMBAHKAN 3 RUTE BARU DI BAWAH INI UNTUK SIR20
Route::get('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'show']);             // Read (One)
Route::put('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'update']);           // Update
Route::delete('/uji-sir20/{id}', [HasilUjiSir20ApiController::class, 'destroy']);       // Delete

// Endpoint ini menangani Form 1 (CRUD Timbang) dan Form 2 (Input K3)
Route::prefix('timbang-bokar')->controller(TimbangBokarApiController::class)->group(function () {
    Route::get('/pending-k3', 'getListPendingK3'); // [Form 2] GET timbang-bokar/pending-k3
    Route::put('/update-k3/{id}', 'updateK3');     // [Form 2] PUT timbang-bokar/update-k3/{id}
});

// [Tabel 1] Endpoint untuk Tabel Data Timbang Bokar (menampilkan semua)
Route::get('hasil-timbang-bokar', [TimbangBokarApiController::class, 'index']);

// [Form 1] Endpoint standar (CRUD) untuk Form Timbang Bokar
Route::apiResource('timbang-bokar', TimbangBokarApiController::class)->except(['index']);


// 2. Endpoint untuk HasilUjiBokarOlahApiController
// Endpoint ini HANYA menangani Tabel 2 (Data Uji Olah yang SUDAH jadi)

// [Tabel 2] Mengambil data yang K3-nya TIDAK NULL
Route::get('hasil-uji-bokar-olah', [HasilUjiBokarOlahApiController::class, 'index']);

// [Tabel 2] Mereset/Menghapus K3 (mengembalikan ke pending)
Route::delete('uji-bokar-olah/{id}', [HasilUjiBokarOlahApiController::class, 'destroy']);

