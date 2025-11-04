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
       Schema::create('maturasi', function (Blueprint $table) {
            $table->id();
            $table->string('uraian')->unique(); // "Di Bak Maturasi 1"
            $table->decimal('stok_akhir', 15, 2)->default(0);
            $table->date('tgl_masuk')->nullable(); // Tanggal stok saat ini mulai dihitung
            $table->integer('umur')->default(0);
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
        Schema::dropIfExists('maturasi');
    }
};
