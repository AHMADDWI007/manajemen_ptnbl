<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdHasilUjiMaturasiToMaturasisTable extends Migration
{
    public function up()
    {
        Schema::table('maturasis', function (Blueprint $table) {
            $table->unsignedBigInteger('id_hasil_uji_maturasi')->nullable()->after('keterangan');

            $table->foreign('id_hasil_uji_maturasi')
                ->references('id')
                ->on('hasil_uji_maturasi') // ✅ nama tabel benar
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('maturasis', function (Blueprint $table) {
            $table->dropForeign(['id_hasil_uji_maturasi']);
            $table->dropColumn('id_hasil_uji_maturasi');
        });
    }
}
