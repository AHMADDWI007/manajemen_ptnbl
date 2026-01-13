<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('pengolahan_maturasi', function (Blueprint $table) {
        $table->string('asal_bokar')->nullable()->after('masuk_hi');
    });
}

public function down()
{
    Schema::table('pengolahan_maturasi', function (Blueprint $table) {
        $table->dropColumn('asal_bokar');
    });
}

};
