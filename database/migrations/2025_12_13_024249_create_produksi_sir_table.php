<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_sir', function (Blueprint $table) {
            $table->id();
            
            // Kolom Tanggal kita gunakan created_at (bawaan timestamps) 
            // agar sinkron dengan logika: whereDate('created_at', ...)
            
            $table->string('uraian')->index(); // Index biar pencarian cepat
            
            // Data Gudang (Tabel IV)
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('masuk', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0); // [PENTING] Ditambahkan
            $table->decimal('prod_bln_lalu', 15, 2)->default(0);
            $table->decimal('prod_sd_hi', 15, 2)->default(0);
            $table->decimal('pengiriman', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            
            // Data Mutu (Tabel VI)
            $table->decimal('kg', 15, 2)->default(0);
            $table->integer('pallet')->default(0);
            
            $table->text('keterangan')->nullable();
            
            $table->timestamps(); // Ini akan membuat kolom created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_sir');
    }
};