<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // PERBAIKAN: Nama tabel tanpa 's'
        Schema::create('hasil_uji_bokar_olah', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('bak_maturasi');
            $table->string('jenis')->nullable();
            $table->decimal('berat_truck', 10, 2)->nullable();
            $table->decimal('berat_timbang', 10, 2)->nullable();
            $table->decimal('netto_basah', 10, 2)->nullable();
            $table->decimal('k3', 5, 2)->nullable();
            $table->decimal('netto_kering', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // PERBAIKAN: Nama tabel tanpa 's'
        Schema::dropIfExists('hasil_uji_bokar_olah');
    }
};