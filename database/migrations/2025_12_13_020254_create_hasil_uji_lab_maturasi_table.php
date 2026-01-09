<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_lab_maturasi', function (Blueprint $table) {
            $table->id('id_hasil_uji_lab_maturasi');
            $table->date('tanggal');
            
            $table->foreignId('id_maturasi')
                  ->constrained('maturasi', 'id_maturasi')
                  ->onDelete('cascade');
            
            $table->decimal('k3', 8, 2)->nullable();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_maturasi');
    }
};