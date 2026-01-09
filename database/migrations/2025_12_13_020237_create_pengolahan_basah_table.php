<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengolahan_basah', function (Blueprint $table) {
            $table->id('id_pengolahan_basah');
            $table->date('tanggal');
            
            // Relasi ke Maturasi
            $table->foreignId('id_maturasi')
                  ->constrained('maturasi', 'id_maturasi') // Tentukan nama tabel dan kolom induk
                  ->onDelete('cascade'); 
            
            $table->string('jenis');
            $table->decimal('berat_truck', 10, 2);
            $table->decimal('berat_timbang', 10, 2);
            $table->decimal('netto_basah', 10, 2);
            
            $table->decimal('k3', 5, 2)->nullable();
            $table->decimal('netto_kering', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengolahan_basah');
    }
};