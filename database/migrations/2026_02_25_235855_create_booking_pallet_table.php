<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_pallet', function (Blueprint $table) {
            // Menggunakan nama ID yang spesifik
            $table->id('id_booking_pallet'); 
            
            // Relasi ke tabel pallet asli
            $table->unsignedBigInteger('id_pallet'); 
            
            // Tanggal booking (untuk filter di modal web)
            $table->date('tanggal'); 
            
            $table->timestamps();

            // Foreign Key: Jika pallet di hapus, booking ikut terhapus
            $table->foreign('id_pallet')
                  ->references('id_pallet')
                  ->on('pallet')
                  ->onDelete('cascade');
                  
            // Opsional: Agar satu pallet tidak bisa di-booking dua kali di hari yang sama
            $table->unique(['id_pallet', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pallet');
    }
};