<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Menampilkan halaman daftar pengguna.
     * Method: GET
     * URL: /users
     */
    public function index()
    {
        // Mengambil semua data pengguna dari database
        $users = User::all();
        // Mengirim data pengguna ke view 'Datapengguana'
        return view('Pengguna.data_pengguna', compact('users'));
    }

    /**
     * Menyimpan pengguna baru ke database.
     * Method: POST
     * URL: /users
     */
    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users', // username harus unik
            'jabatan' => 'required|string|max:255',
            'role' => 'required|string|in:admin,user', // role harus 'admin' atau 'user'
            'password' => 'required|string|min:8', // password minimal 8 karakter
        ]);

        // Membuat user baru menggunakan data yang sudah divalidasi
        User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'jabatan' => $request->jabatan,
            'role' => $request->role,
            'password' => Hash::make($request->password), // Penting: Selalu hash password!
        ]);

        // Kembali ke halaman daftar pengguna dengan pesan sukses
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Memperbarui data pengguna yang ada.
     * Method: PUT/PATCH
     * URL: /users/{user} (contoh: /users/1)
     */
    public function update(Request $request, User $user)
    {
        // Validasi input
        $request->validate([
            'fullname' => 'required|string|max:255',
            // Username harus unik, tapi abaikan untuk user yang sedang diedit
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'jabatan' => 'required|string|max:255',
            'role' => 'required|string|in:admin,user',
            'password' => 'nullable|string|min:8', // Password boleh kosong (tidak diubah)
        ]);

        // Menyiapkan data untuk diupdate
        $dataToUpdate = $request->except('password');

        // Jika ada input password baru, hash dan tambahkan ke data update
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        // Update data pengguna
        $user->update($dataToUpdate);

        // Kembali ke halaman daftar pengguna dengan pesan sukses
        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna dari database.
     * Method: DELETE
     * URL: /users/{user} (contoh: /users/1)
     */
    public function destroy(User $user)
    {
        // Hapus pengguna
        $user->delete();

        // Kembali ke halaman daftar pengguna dengan pesan sukses
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}

