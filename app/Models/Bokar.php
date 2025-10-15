<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bokar extends Model
{
    use HasFactory;

    protected $table = 'bokar'; // nama tabel

    protected $fillable = [
        'berat_penuh',
        'berat_truk',
        'berat_muatan',
    ];
}
