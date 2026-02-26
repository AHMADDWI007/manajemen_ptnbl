<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_pallet', function (Blueprint $table) {
            $table->id();
            // Kolom tanggal untuk mencocokkan inputan di Modal Web
            $table->date('tanggal')->unique(); 
            // Kolom teks panjang untuk menyimpan daftar nomor pallet (misal: "PLT-01,PLT-02")
            $table->text('no_palet_list'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pallet');
    }
};