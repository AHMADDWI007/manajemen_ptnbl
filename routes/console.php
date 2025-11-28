<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // <--- 1. TAMBAHKAN BARIS INI DI ATAS

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==========================================
// JADWAL SINKRONISASI DATABASE
// ==========================================

// 2. TAMBAHKAN KODE INI DI PALING BAWAH:
// Ini akan menjalankan perintah bokar:sync setiap jam (jam 8, jam 9, jam 10, dst)
Schedule::command('bokar:sync')->hourly();