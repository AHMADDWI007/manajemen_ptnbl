<?php

namespace App\Http\Controllers; // Pastikan namespace sesuai struktur folder

use Illuminate\Http\Request;
use App\Models\HasilUjiBokarOlah; // Pastikan nama model benar
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class HasilUjiBokarOlahController extends Controller
{
    /**
     * Menampilkan daftar data hasil uji bokar olah.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $data_uji = HasilUjiBokarOlah::orderBy('tanggal', 'desc')->get();
        // Pastikan path view 'Pengolahan.hasil_uji_bokar_olah' benar
        return view('Pengolahan.hasil_uji_bokar_olah', compact('data_uji'));
    }

    /**
     * Menyimpan data baru.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // PERBAIKAN: Hapus validasi untuk 'berat_truck' dan 'berat_timbang'
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'bak_maturasi'  => 'required|string|max:255',
            'jenis'         => 'nullable|string|max:255',
            // 'berat_truck'   => 'nullable|numeric|min:0',   // <-- DIHAPUS
            // 'berat_timbang' => 'nullable|numeric|min:0', // <-- DIHAPUS
            'netto_basah'   => 'nullable|numeric|min:0',
            'k3'            => 'nullable|numeric|min:0|max:100',
            'netto_kering'  => 'nullable|numeric|min:0',
        ]);

        HasilUjiBokarOlah::create($validated);

        return redirect()->route('hasil_uji_bokar_olah.index')
                         ->with('success', 'Data hasil uji bokar olah berhasil disimpan.');
    }

    /**
     * Mengambil data untuk modal Detail.
     * @param  \App\Models\HasilUjiBokarOlah  $hasilUjiBokarOlah
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(HasilUjiBokarOlah $hasilUjiBokarOlah)
    {
        return response()->json($hasilUjiBokarOlah);
    }

    /**
     * Mengambil data untuk modal Edit.
     * @param  \App\Models\HasilUjiBokarOlah  $hasilUjiBokarOlah
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(HasilUjiBokarOlah $hasilUjiBokarOlah)
    {
        return response()->json($hasilUjiBokarOlah);
    }

    /**
     * Memperbarui data.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HasilUjiBokarOlah  $hasilUjiBokarOlah
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, HasilUjiBokarOlah $hasilUjiBokarOlah)
    {
        // PERBAIKAN: Hapus validasi untuk 'berat_truck' dan 'berat_timbang'
        $validated = $request->validate([
            'tanggal'       => 'required|date',
            'bak_maturasi'  => 'required|string|max:255',
            'jenis'         => 'nullable|string|max:255',
            // 'berat_truck'   => 'nullable|numeric|min:0',   // <-- DIHAPUS
            // 'berat_timbang' => 'nullable|numeric|min:0', // <-- DIHAPUS
            'netto_basah'   => 'nullable|numeric|min:0',
            'k3'            => 'nullable|numeric|min:0|max:100',
            'netto_kering'  => 'nullable|numeric|min:0',
            // Tambahkan validasi unique jika perlu
        ]);

        $hasilUjiBokarOlah->update($validated);

        return redirect()->route('hasil_uji_bokar_olah.index')
                         ->with('success', 'Data hasil uji bokar olah berhasil diperbarui.');
    }

    /**
     * Menghapus data.
     * @param  \App\Models\HasilUjiBokarOlah  $hasilUjiBokarOlah
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(HasilUjiBokarOlah $hasilUjiBokarOlah)
    {
        $hasilUjiBokarOlah->delete();
        return redirect()->route('hasil_uji_bokar_olah.index')
                         ->with('success', 'Data hasil uji bokar olah berhasil dihapus.');
    }
}