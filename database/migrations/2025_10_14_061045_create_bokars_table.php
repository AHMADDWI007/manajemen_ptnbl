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
        Schema::create('bokar', function (Blueprint $table) {
            $table->id();
            $table->string('uraian');
            $table->decimal('stock_awal', 10, 2)->nullable();
            $table->decimal('penerimaan_harian', 10, 2)->nullable();
            $table->decimal('penerimaan_sd_hari_ini', 10, 2)->nullable();
            $table->decimal('jumlah_stock_bokar', 10, 2)->nullable();
            $table->decimal('bokar_diproses_harian', 10, 2)->nullable();
            $table->decimal('bokar_diproses_sd_hari_ini', 10, 2)->nullable();
            $table->decimal('sisa_stock', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bokar');
    }
};
