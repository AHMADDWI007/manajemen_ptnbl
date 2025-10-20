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
            $table->id(); // Ini akan menjadi kolom "NO"
            $table->string('uraian');
            $table->decimal('stok_awal', 15, 2)->nullable()->default(0);
            $table->date('tgl_masuk')->nullable();
            $table->string('umur')->nullable();
            $table->decimal('diolah', 15, 2)->nullable()->default(0);
            $table->decimal('mutasi', 15, 2)->nullable()->default(0);
            $table->decimal('masuk_hi', 15, 2)->nullable()->default(0);
            $table->decimal('stok_akhir', 15, 2)->nullable()->default(0);
            $table->string('asal_bokar')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
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
