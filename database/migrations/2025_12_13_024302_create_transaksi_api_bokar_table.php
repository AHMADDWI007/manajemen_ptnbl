<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_api_bokar', function (Blueprint $table) {
            $table->id('id_transaksi_api_bokar');
            $table->date('tanggal');
            $table->string('kode_api');
            $table->decimal('masuk_sd_kemarin', 20, 2)->default(0);
            $table->decimal('masuk_hi', 20, 2)->default(0);
            $table->timestamps();
            $table->unique(['tanggal', 'kode_api']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_api_bokar');
    }
};