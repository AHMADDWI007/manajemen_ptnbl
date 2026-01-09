<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengolahan_maturasi', function (Blueprint $table) {
            $table->id('id_pengolahan_maturasi');
            
            $table->foreignId('id_maturasi')
                  ->constrained('maturasi', 'id_maturasi')
                  ->onDelete('cascade');
            
            $table->date('tgl_laporan');
            
            $table->decimal('diolah', 15, 2)->default(0);
            $table->decimal('mutasi', 15, 2)->default(0);
            $table->decimal('masuk_hi', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengolahan_maturasi');
    }
};