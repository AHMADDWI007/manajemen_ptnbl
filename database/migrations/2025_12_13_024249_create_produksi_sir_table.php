<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GUNAKAN Schema::create (Bukan Schema::table)
        // Karena saat migrate:fresh, tabel ini belum ada.
        Schema::create('produksi_sir', function (Blueprint $table) {
            $table->id('id_produksi_sir');
            
            // HANYA Tanggal Laporan (Tanpa Shift)
            $table->date('tanggal_produksi');
            
            // Total Hasil Produksi Hari Ini (Rekap)
            $table->decimal('kg', 15, 2)->default(0); 
            $table->integer('pallet')->default(0);
            
            $table->text('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_sir');
    }
};