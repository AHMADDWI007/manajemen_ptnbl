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
        Schema::create('hasil_uji_bokar_diolah', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('bak_maturasi');
            $table->decimal('k3', 8, 2)->nullable();
            
            // Field-field ini akan diisi dari menu lain, jadi kita buat nullable
            $table->string('jenis')->nullable();
            $table->decimal('netto_basah', 10, 2)->nullable();
            $table->decimal('netto_kering', 10, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_bokar_diolah');
    }
};