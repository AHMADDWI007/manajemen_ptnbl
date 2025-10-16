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
        Schema::create('produksi_sir20', function (Blueprint $table) {
            $table->id();
            $table->string('uraian')->unique(); // Kunci untuk mencocokkan data
            
            $table->decimal('saldo_awal', 12, 2)->nullable();
            $table->decimal('masuk', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            
            // Kolom Produksi Bulan Ini
            $table->decimal('produksi_bulan_lalu', 12, 2)->nullable();
            $table->decimal('produksi_sd_hi', 12, 2)->nullable();

            $table->decimal('pengiriman', 12, 2)->nullable();
            $table->decimal('saldo_akhir', 12, 2)->nullable();
            $table->decimal('pt_nb', 12, 2)->nullable();
            $table->decimal('total_100_persen', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_sir20');
    }
};
