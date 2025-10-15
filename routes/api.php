<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HasilUjiLabBokarApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ✅ Rute API untuk aplikasi mobile (uji-bokar)
Route::post('/uji-bokar', [HasilUjiLabBokarApiController::class, 'store'])
    ->name('api.uji-bokar.store');
