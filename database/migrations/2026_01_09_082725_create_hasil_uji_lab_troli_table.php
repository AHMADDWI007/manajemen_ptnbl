<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan nama tabel di sini adalah 'hasil_uji_lab_troli'
        Schema::create('hasil_uji_lab_troli', function (Blueprint $table) {
            
            // Primary Key Custom
            $table->id('id_hasil_uji_lab_troli'); 
            
            $table->date('tanggal');
            $table->string('no_trolly');
            
            $table->decimal('k3', 8, 2)->nullable();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            
            $table->time('jam_sample')->nullable();
            $table->string('lama_pengeringan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_troli');
    }
};