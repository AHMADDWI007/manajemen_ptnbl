<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            // ID disesuaikan dengan nama tabel (id_nama_tabel)
            $table->id('id_pengaturan'); 
            
            $table->string('kunci')->unique(); // Pengganti 'key'
            $table->text('nilai')->nullable(); // Pengganti 'value'
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        // Insert data awal (Seeder langsung di sini biar praktis)
        DB::table('pengaturan')->insert([
            'kunci' => 'url_api_bokar',
            'nilai' => 'https://test.ideclouds.com/api/get_bokar.php',
            'deskripsi' => 'Endpoint API untuk sinkronisasi data Bokar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('pengaturan');
    }
};