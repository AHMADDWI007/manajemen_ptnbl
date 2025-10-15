<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHasilUjiMaturasiTable extends Migration
{
    public function up()
    {
        Schema::create('hasil_uji_maturasi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->time('jam');
            $table->string('no_kamar');
            $table->string('hasil_uji');
            $table->enum('status', ['Lulus', 'Gagal']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hasil_uji_maturasi');
    }
}
