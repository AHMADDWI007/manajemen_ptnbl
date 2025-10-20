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
        Schema::create('bahan_proses', function (Blueprint $table) {
            $table->id();
            $table->string('uraian');
            $table->decimal('wip_masuk', 15, 2)->nullable();
            $table->decimal('wip_keluar', 15, 2)->nullable();
            $table->decimal('produksi_sir20', 15, 2)->nullable();
            $table->string('rekfif')->nullable();
            $table->decimal('saldo_akhir', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_proses');
    }
};
