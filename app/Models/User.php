<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ✅ 1. TAMBAHKAN IMPORT INI

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Disesuaikan dengan kolom pada migrasi Anda.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullname',
        'username',
        'jabatan',
        'role',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atribut yang tipe datanya harus di-cast ke tipe lain.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Pastikan password di-hash secara otomatis saat disimpan
            'password' => 'hashed',
        ];
    }

    /**
     * Menentukan kolom yang digunakan untuk otentikasi.
     * Secara default, Laravel menggunakan 'email'. Kita ubah menjadi 'username'.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }
}

