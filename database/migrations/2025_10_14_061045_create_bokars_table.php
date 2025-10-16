<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bokar', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('no_kamar');
            $table->decimal('berat_awal', 10, 2)->nullable();
            $table->decimal('berat_truck', 10, 2)->nullable();
            $table->decimal('berat_basah', 10, 2)->nullable();
            $table->decimal('k3_lab', 8, 2)->nullable();
            $table->decimal('berat_kering', 10, 2)->nullable();
            // Kolom baru ditambahkan di sini
            $table->decimal('total_ds', 10, 2)->nullable();
            $table->decimal('total_pt', 10, 2)->nullable();
            $table->decimal('jumlah', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bokar');
    }
};
