<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_sir_20', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal'); // Wajib ada untuk laporan
            $table->string('jenis_kemasan')->nullable(); // MB5 / SW
            $table->string('no_palet')->unique();
            
            // Parameter Mutu Lengkap
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            $table->decimal('dirt', 8, 3)->nullable(); // 3 desimal untuk presisi tinggi
            $table->decimal('ash', 8, 2)->nullable();
            $table->decimal('vm', 8, 2)->nullable();
            $table->decimal('money', 8, 2)->nullable();
            $table->decimal('nitrogen', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_sir_20');
    }
};