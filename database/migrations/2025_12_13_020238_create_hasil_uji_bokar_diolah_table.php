<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_bokar_diolah', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('bak_maturasi'); // Contoh: "Bak Maturasi 1"
            $table->decimal('k3', 8, 2)->nullable();
            
            // Kolom pelengkap (nanti diisi otomatis dari modul Pengolahan Basah)
            $table->string('jenis')->nullable(); 
            $table->decimal('netto_basah', 15, 2)->nullable();
            $table->decimal('netto_kering', 15, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_bokar_diolah');
    }
};