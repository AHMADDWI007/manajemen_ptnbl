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
        $user = $request->user();

        // 4. PERIKSA ROLE (Sesuai permintaan Anda)
        // Ganti blok pengecekan role yang lama menjadi ini:
        $allowedRoles = ['admin', 'laboratorium', 'penimbangan', 'pengolahan', 'produksi', 'penjualan', 'user'];

        if (!in_array($user->role, $allowedRoles)) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Role Anda tidak terdaftar di sistem mobile.',
            ], 403);
        }

        // 5. Jika dia admin, buat token (Gunakan Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Kirim respons sukses
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'data'    => [
                'token' => $token,
                'user'  => $user
            ]
        ], 200);
    }

    /**
     * Mengambil semua daftar pengguna untuk dropdown Android
     */
    public function getUsers()
    {
        try {
            // Ambil data user. Bisa difilter misal: User::where('role', 'operator')->get() jika perlu
            $users = User::all(); 
            
            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user: ' . $e->getMessage()
            ], 500);
        }
    }
}