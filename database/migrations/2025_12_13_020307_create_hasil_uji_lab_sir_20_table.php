<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_lab_sir_20', function (Blueprint $table) {
            $table->id('id_hasil_uji_lab_sir_20');
            $table->date('tanggal');
            $table->string('jenis_kemasan')->nullable();
            $table->string('no_palet')->unique();
            
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            $table->decimal('dirt', 8, 3)->nullable();
            $table->decimal('ash', 8, 2)->nullable();
            $table->decimal('vm', 8, 2)->nullable();
            $table->decimal('money', 8, 2)->nullable();
            $table->decimal('nitrogen', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_sir_20');
    }
};