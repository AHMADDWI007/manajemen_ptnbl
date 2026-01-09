<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_sir20', function (Blueprint $table) {
            $table->id('id_penjualan_sir20');
            $table->date('tanggal')->index();
            $table->string('uraian');
            
            $table->decimal('sd_bulan_lalu', 15, 2)->default(0);
            $table->decimal('bln_ini_lalu', 15, 2)->default(0);
            $table->decimal('hari_ini', 15, 2)->default(0);
            $table->decimal('total_bln_ini', 15, 2)->default(0);
            $table->decimal('total_sd_hari_ini', 15, 2)->default(0);
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_sir20');
    }
};