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
    Schema::create('maturasis', function (Blueprint $table) {
        $table->id();
        $table->string('uraian')->unique(); // "Di Bak Maturasi 1", dst.
        $table->decimal('stok_awal', 15, 2)->nullable()->default(0);
        $table->date('tgl_masuk')->nullable(); // Tanggal bokar asli masuk
        $table->string('umur')->nullable()->default(0);
        $table->decimal('diolah', 15, 2)->nullable()->default(0);
        $table->decimal('mutasi', 15, 2)->nullable()->default(0);
        $table->decimal('masuk_hi', 15, 2)->nullable()->default(0);
        $table->decimal('stok_akhir', 15, 2)->nullable()->default(0);
        $table->string('asal_bokar')->nullable();
        $table->text('keterangan')->nullable();
        $table->timestamps(); // updated_at akan jadi tgl update terakhir
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maturasis');
    }
};
