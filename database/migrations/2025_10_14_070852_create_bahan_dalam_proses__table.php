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
        Schema::create('bahan_dalam_proses', function (Blueprint $table) {
            $table->id();
            $table->string('uraian')->unique(); // Kunci untuk mencocokkan data
            
            // Kolom WIP
            $table->decimal('wip_masuk', 10, 2)->nullable();
            $table->decimal('wip_keluar', 10, 2)->nullable();

            $table->decimal('produksi_sir20', 10, 2)->nullable();
            $table->string('rekfif')->nullable();
            $table->decimal('saldo_akhir', 10, 2)->nullable();
            $table->string('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_dalam_proses');
    }
};

