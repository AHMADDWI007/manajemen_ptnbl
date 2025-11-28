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
        Schema::create('rektifikasi_stok', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('jenis'); // PT, DS, INHUT
            // Nilai rektif (bisa minus atau plus)
            $table->decimal('berat', 15, 2)->default(0); 
            $table->text('keterangan')->nullable(); // Opsional, untuk catatan kenapa direktif
            $table->timestamps();

            // Index biar cepat saat rekap
            $table->index(['tanggal', 'jenis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rektifikasi_stok');
    }
};
