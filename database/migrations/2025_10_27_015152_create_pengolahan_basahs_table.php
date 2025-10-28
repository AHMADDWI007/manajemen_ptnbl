<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengolahan_basah', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('bak_maturasi');
            $table->string('jenis'); // MB5, SW
            $table->decimal('berat_truck', 10, 2);
            $table->decimal('berat_timbang', 10, 2);
            $table->decimal('netto_basah', 10, 2); // (timbang - truck)
            $table->decimal('k3', 5, 2)->nullable(); // <-- TAMBAH INI
            $table->decimal('netto_kering', 10, 2)->nullable(); // <-- TAMBAH INI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengolahan_basah');
    }
};