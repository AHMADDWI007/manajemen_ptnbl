<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_troli', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('no_trolly');
            
            $table->decimal('k3', 8, 2)->nullable();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            
            $table->time('jam_sample')->nullable();
            $table->string('lama_pengeringan')->nullable(); // String karena bisa input teks "3 Jam"
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_troli');
    }
};