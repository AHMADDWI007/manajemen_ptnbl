<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bokar extends Model
{
    use HasFactory;

    protected $table = 'bokar';

    protected $fillable = [
        'tanggal',
        'bak_maturasi',
        'jenis',
        'berat_truck',
        'berat_timbang',
        'netto_basah',
        'k3',
        'netto_kering',
        'total_ds',
        'total_pt',
        'jumlah',
    ];
}
