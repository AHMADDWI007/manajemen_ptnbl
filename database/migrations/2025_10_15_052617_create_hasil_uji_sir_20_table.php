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
        Schema::create('hasil_uji_sir_20s', function (Blueprint $table) {
            $table->id();
            $table->string('no_palet');
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            $table->decimal('dirt', 8, 4)->nullable();
            $table->decimal('ash', 8, 4)->nullable();
            $table->decimal('vm', 8, 4)->nullable();
            $table->decimal('money', 15, 2)->nullable();
            $table->decimal('nitrogen', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_sir_20s');
    }
};