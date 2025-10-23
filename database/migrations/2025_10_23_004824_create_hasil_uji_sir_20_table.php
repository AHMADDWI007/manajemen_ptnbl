<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('hasil_uji_sir_20', function (Blueprint $table) {
            $table->id();
            
            // Kolom 'tanggal' DIHAPUS sesuai permintaan Anda
            
            // Kolom 'jenis_kemasan' ditambahkan (tanpa ->after())
            $table->string('jenis_kemasan')->nullable(); 
            
            $table->string('no_palet')->unique();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            $table->decimal('dirt', 8, 2)->nullable();
            $table->decimal('ash', 8, 2)->nullable(); // Sesuai kode Anda
            $table->decimal('vm', 8, 2)->nullable();
            $table->decimal('money', 15, 2)->nullable(); // Sesuai kode Anda
            $table->decimal('nitrogen', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_sir_20');
    }
};