<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun Admin
        User::updateOrCreate(
            ['username' => 'admin'], // Cek berdasarkan username
            [
                'fullname' => 'Administrator Sistem',
                'jabatan'  => 'Kepala Pabrik', // Contoh jabatan
                'role'     => 'admin',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Buat Akun Operator
        User::updateOrCreate(
            ['username' => 'operator'], // Cek berdasarkan username
            [
                'fullname' => 'Operator Produksi',
                'jabatan'  => 'Staff Produksi',
                'role'     => 'operator', // Nanti bisa dipakai untuk hak akses
                'password' => Hash::make('password123'),
            ]
        );
    }
}