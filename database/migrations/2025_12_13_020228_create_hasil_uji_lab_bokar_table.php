<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_lab_bokar', function (Blueprint $table) {
            $table->id('id_hasil_uji_lab_bokar'); // Ganti id
            $table->date('tanggal');
            $table->string('suplier');
            $table->string('no_sampel')->unique();
            
            $table->decimal('k3', 8, 2)->nullable();
            $table->decimal('dirt', 8, 2)->nullable();
            $table->decimal('ask', 8, 2)->nullable();
            $table->decimal('po', 8, 2)->nullable();
            $table->decimal('pa', 8, 2)->nullable();
            $table->decimal('pri', 8, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_bokar');
    }
};