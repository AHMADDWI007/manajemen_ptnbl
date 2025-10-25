<?php

namespace App\Models; // Pastikan namespace sesuai

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiBokarOlah extends Model
{
    use HasFactory;

    /**
     * Nama tabel database.
     * @var string
     */
    protected $table = 'hasil_uji_bokar_olah'; // Nama tabel tanpa 's'

    /**
     * Atribut yang boleh diisi massal.
     * PERBAIKAN: Hapus 'berat_truck' dan 'berat_timbang'.
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'bak_maturasi',
        'jenis',
        // 'berat_truck',   // <-- DIHAPUS
        // 'berat_timbang', // <-- DIHAPUS
        'netto_basah',
        'k3',
        'netto_kering',
    ];

    /**
     * Casts tipe data.
     * PERBAIKAN: Hapus 'berat_truck' dan 'berat_timbang'.
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal'       => 'date',
        // 'berat_truck'   => 'decimal:2', // <-- DIHAPUS
        // 'berat_timbang' => 'decimal:2', // <-- DIHAPUS
        'netto_basah'   => 'decimal:2',
        'k3'            => 'decimal:2',
        'netto_kering'  => 'decimal:2',
    ];

    // public $timestamps = false; // Hapus komentar jika tidak pakai timestamps
}