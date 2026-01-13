<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_sir20s', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('uraian'); // Contoh: SIR20 PTNBL, SIR20 PTPN4
            
            // Kolom Data (Sesuai Foto)
            $table->decimal('sd_bulan_lalu', 15, 2)->default(0); // Kolom "s/d September 2025"
            $table->decimal('bln_ini_lalu', 15, 2)->default(0);  // Penjualan Bln Ini -> Yg lalu
            $table->decimal('hari_ini', 15, 2)->default(0);      // Penjualan Bln Ini -> Hari Ini (Inputan)
            
            // Kolom Kalkulasi (Disimpan agar history aman)
            $table->decimal('total_bln_ini', 15, 2)->default(0); // Total Bulan Ini
            $table->decimal('total_sd_hari_ini', 15, 2)->default(0); // Total Penjualan s/d Hari ini
            
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_sir20s');
    }
};