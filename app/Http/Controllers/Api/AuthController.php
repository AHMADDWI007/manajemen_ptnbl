<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Pastikan model User di-import

class AuthController extends Controller
{
    /**
     * Menangani permintaan login dari API.
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Coba otentikasi
        if (!Auth::attempt($credentials)) {
            // Jika username/password salah
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password salah.',
            ], 401); // 401 Unauthorized
        }

        // 3. Otentikasi berhasil, dapatkan data user
        //   Auth::attempt() secara otomatis me-loginkan user jika berhasil,
        //   jadi $request->user() akan mengembalikan data user yang login.
        $user = $request->user();

        // ✅ PERBAIKAN: Hapus blok IF yang memeriksa role 'admin'
        /*
        // 4. PERIKSA ROLE (BAGIAN INI DIHAPUS/DIKOMENTARI)
        if ($user->role !== 'admin') {
            // Jika role bukan admin, tolak login
             Auth::logout(); // Logout user yang baru saja login
            return response()->json([
                'success' => false,
                'message' => 'Login gagal. Anda tidak memiliki hak akses Admin.',
            ], 403); // 403 Forbidden
        }
        */
        // ✅ AKHIR PERBAIKAN

        // 5. Buat token untuk user yang berhasil login (apapun rolenya)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Kirim respons sukses (berisi token dan data user termasuk rolenya)
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'data'    => [
                'token' => $token,
                'user'  => $user // Data user lengkap (termasuk role) dikirim ke mobile
            ]
        ], 200); // 200 OK
    }
}