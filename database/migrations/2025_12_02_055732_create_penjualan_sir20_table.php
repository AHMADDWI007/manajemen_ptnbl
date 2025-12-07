<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_sir20', function (Blueprint $table) {
            $table->id();
            
            // ✅ PERBAIKAN 1: Tambahkan kolom tanggal
            $table->date('tanggal')->index(); 
            
            // ✅ PERBAIKAN 2: Hapus ->unique() agar bisa input berulang tiap hari
            $table->string('uraian'); 

            $table->decimal('sd_bulan_lalu', 15, 2)->default(0);
            $table->decimal('penjualan_bulan_ini_yg_lalu', 15, 2)->default(0);
            
            // Input Hari Ini
            $table->decimal('penjualan_bulan_ini_hari_ini', 15, 2)->default(0); // Ini 'hari_ini'

            $table->decimal('total_bulan_ini', 15, 2)->default(0);
            $table->decimal('total_penjualan_sd_hari_ini', 15, 2)->default(0);
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_sir20');
    }
};