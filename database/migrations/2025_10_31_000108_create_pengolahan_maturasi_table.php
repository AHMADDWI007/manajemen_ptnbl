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
        $table->foreignId('maturasi_id')->constrained('maturasis')->onDelete('cascade');
        $table->date('tgl_laporan'); // Tanggal data ini diinput
        $table->decimal('diolah', 15, 2)->nullable()->default(0);
        $table->decimal('mutasi', 15, 2)->nullable()->default(0);
        $table->decimal('masuk_hi', 15, 2)->nullable()->default(0);
        $table->text('keterangan')->nullable();
        $table->timestamps();
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
