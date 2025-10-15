<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_lab_bokar', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('no_kamar');
            $table->string('hasil_uji')->nullable();
            $table->string('status')->default('Menunggu Hasil');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_bokar');
    }
};
