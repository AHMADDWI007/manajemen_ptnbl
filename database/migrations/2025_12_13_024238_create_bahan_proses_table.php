<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_proses', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('uraian');
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('wip_masuk', 15, 2)->nullable()->default(0);
            $table->decimal('wip_keluar', 15, 2)->nullable()->default(0);
            $table->decimal('produksi_sir20', 15, 2)->nullable()->default(0);
            $table->decimal('rekfif', 15, 2)->nullable()->default(0);
            $table->decimal('saldo_akhir', 15, 2)->nullable()->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_proses');
    }
};