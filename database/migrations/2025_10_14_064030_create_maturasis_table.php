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
        // Membuat tabel 'maturasis' (plural) sesuai konvensi Laravel
        Schema::create('maturasis', function (Blueprint $table) {
            $table->id();
            $table->string('uraian_proses')->unique(); // Kunci utama untuk mencocokkan data
            
            // Kolom Stock Awal
            $table->decimal('kg_kk', 10, 2)->nullable();
            $table->date('tgl')->nullable();
            $table->string('umur')->nullable();

            // Kolom Diproses HI
            $table->decimal('diolah', 10, 2)->nullable();
            $table->decimal('mutasi', 10, 2)->nullable();

            // Kolom Masuk
            $table->decimal('masuk_hi', 10, 2)->nullable();
            
            // Kolom Quality
            $table->decimal('k3', 8, 2)->nullable();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();

            // Kolom sisa
            $table->decimal('stock_akhir', 10, 2)->nullable();
            $table->string('asal_bokar')->nullable();
            $table->string('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maturasis');
    }
};

