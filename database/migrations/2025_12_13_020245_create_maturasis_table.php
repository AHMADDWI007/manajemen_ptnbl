<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maturasis', function (Blueprint $table) {
            $table->id();
            $table->string('uraian')->unique(); // Contoh: "Di Bak Maturasi-1"
            
            // Data Stok
            $table->decimal('stok_awal', 15, 2)->default(0);
            $table->date('tgl_masuk')->nullable();
            $table->integer('umur')->default(0); // Umur dalam hari
            
            // Mutasi Stok
            $table->decimal('diolah', 15, 2)->default(0);
            $table->decimal('mutasi', 15, 2)->default(0);
            $table->decimal('masuk_hi', 15, 2)->default(0);
            $table->decimal('stok_akhir', 15, 2)->default(0);
            
            $table->string('asal_bokar')->nullable();
            $table->text('keterangan')->nullable();
            
            // Relasi ke Hasil Uji Maturasi (ID Saja)
            $table->unsignedBigInteger('id_hasil_uji_maturasi')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maturasis');
    }
};