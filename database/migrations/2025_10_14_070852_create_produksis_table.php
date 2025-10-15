<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->time('jam');
            $table->string('no_kamar');
            $table->string('no_palet');
            $table->enum('status', ['belum mulai', 'proses', 'selesai'])->default('belum mulai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi');
    }
};
