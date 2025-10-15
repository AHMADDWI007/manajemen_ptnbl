<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BokarController;
use App\Http\Controllers\MaturasiController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\HasilUjiLabBokarController;
use App\Http\Controllers\HasilUjiMaturasiController;


// Route halaman beranda
Route::get('/beranda', function () {
    return view('HalamanDepan.beranda');
})->name('beranda');

// Route resource untuk data Bokar (CRUD)
Route::resource('bokar', BokarController::class);
Route::resource('maturasi', MaturasiController::class);
Route::resource('produksi', ProduksiController::class);
Route::resource('hasil_uji_lab_bokar', HasilUjiLabBokarController::class);
Route::get('/hasil_uji_maturasi', [HasilUjiMaturasiController::class, 'index'])->name('hasil_uji_maturasi.index');



// Route halaman utama (welcome)
Route::get('/', function () {
    return view('welcome');
});

