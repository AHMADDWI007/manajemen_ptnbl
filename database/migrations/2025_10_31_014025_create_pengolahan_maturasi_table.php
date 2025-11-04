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
        Schema::create('pengolahan_maturasi', function (Blueprint $table) {
            $table->id();
            // Foreign key ke tabel master
            $table->foreignId('maturasi_id')->constrained('maturasi')->onDelete('cascade');
            $table->date('tanggal_input'); // Tanggal transaksi
            $table->decimal('stok_awal', 15, 2);
            $table->decimal('diolah', 15, 2)->default(0);
            $table->decimal('mutasi', 15, 2)->default(0);
            $table->decimal('masuk_hi', 15, 2)->default(0);
            $table->decimal('stok_akhir', 15, 2); // Stok akhir SETELAH transaksi
            $table->string('keterangan')->nullable();
            $table->timestamps(); // created_at bisa jadi tgl input
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengolahan_maturasi');
    }
};
