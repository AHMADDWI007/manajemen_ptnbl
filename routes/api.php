<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HasilUjiLabBokarApiController;
use App\Http\Controllers\Api\HasilUjiMaturasiApiController;
use App\Http\Controllers\Api\HasilUjiTroliApiController;
use App\Http\Controllers\Api\HasilUjiSir20ApiController;
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

