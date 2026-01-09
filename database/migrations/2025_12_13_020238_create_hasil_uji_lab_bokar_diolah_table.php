<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_uji_lab_bokar_diolah', function (Blueprint $table) {
            $table->id('id_hasil_uji_lab_bokar_diolah');
            $table->date('tanggal');
            
            $table->foreignId('id_pengolahan_basah')
                  ->constrained('pengolahan_basah', 'id_pengolahan_basah')
                  ->onDelete('cascade');

            $table->foreignId('id_maturasi')
                  ->constrained('maturasi', 'id_maturasi')
                  ->onDelete('cascade');
            
            $table->string('jenis');
            $table->decimal('netto_basah', 15, 2);
            $table->decimal('k3', 8, 2);
            $table->decimal('netto_kering', 15, 2);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_bokar_diolah');
    }
};