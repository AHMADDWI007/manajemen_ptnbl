<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hasil_uji_sir_20', function (Blueprint $table) {
            $table->id();
            $table->string('no_palet')->unique();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            $table->decimal('dirt', 8, 2)->nullable();
            $table->decimal('ask', 8, 2)->nullable(); // 'ask' menjadi 'ash'
            $table->decimal('vm', 8, 2)->nullable();   // 'vm' menjadi 'volatile_matter'
            $table->decimal('money', 15, 2)->nullable(); // Menggunakan presisi lebih besar untuk uang
            $table->decimal('nitrogen', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_sir_20');
    }
};