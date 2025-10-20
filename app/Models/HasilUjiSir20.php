<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiSir20 extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_sir_20s';

    protected $fillable = [
        'no_palet',
        'po',
        'pa',
        'pri',
        'dirt',
        'ash',
        'vm',
        'money',
        'nitrogen',
    ];
}
