<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_sir20', function (Blueprint $blueprint) {
            // Menambahkan kolom-kolom baru yang dibutuhkan
            $blueprint->string('no_kontrak')->nullable()->after('uraian');
            $blueprint->string('no_invoice')->nullable()->after('no_kontrak');
            $blueprint->integer('pallet')->default(0)->after('no_invoice');
            $blueprint->decimal('harga', 15, 2)->default(0)->after('hari_ini');
            $blueprint->boolean('is_summary')->default(0)->after('harga');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_sir20', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['no_kontrak', 'no_invoice', 'pallet', 'harga', 'is_summary']);
        });
    }
};