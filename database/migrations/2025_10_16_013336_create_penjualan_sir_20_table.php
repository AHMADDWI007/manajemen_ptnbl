<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penjualan_sir20', function (Blueprint $table) {
            $table->id();
            $table->string('uraian')->unique();

            $table->decimal('sd_bulan_lalu', 12, 2)->nullable();
            
            // Penjualan Bulan Ini
            $table->decimal('penjualan_bulan_ini_yg_lalu', 12, 2)->nullable();
            $table->decimal('penjualan_bulan_ini_hari_ini', 12, 2)->nullable();

            $table->decimal('total_bulan_ini', 12, 2)->nullable();
            $table->decimal('total_penjualan_sd_hari_ini', 12, 2)->nullable();
            $table->string('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_sir20');
    }
};
