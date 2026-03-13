<?php

namespace App\Http\Controllers\DataLaboratorium;

use App\Exports\HasilUjiBokarExport;
use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokar; 
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class HasilUjiBokarController extends Controller
{
    public function index()
    {
        $data_lab = HasilUjiLabBokar::orderBy('tanggal', 'desc')->get();
        return view('DataLaboratorium.hasil-uji-bokar', compact('data_lab'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            // Cek unique pada kolom no_sampel di tabel hasil_uji_lab_bokar
            'no_sampel' => 'required|string|max:100|unique:hasil_uji_lab_bokar,no_sampel',
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0', // Sesuai migrasi: 'ask' (Ash Content)
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        // 🔥 PERBAIKAN: Hardcode nilai 0 untuk dirt dan ask sebelum disimpan
        $validated['dirt'] = 0;
        $validated['ask']  = 0;

        HasilUjiLabBokar::create($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil disimpan.');
    }

    public function show($id)
    {
        // 🔥 PERBAIKAN: Karena PK custom, find() mungkin tetap bekerja jika model dikonfigurasi benar.
        // Tapi untuk amannya, kita bisa pakai findOrFail atau where().
        $data = HasilUjiLabBokar::find($id);
        
        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = HasilUjiLabBokar::find($id);
        
        if (!$data) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        // 1. Cari Data
        $hasilUjiLabBokar = HasilUjiLabBokar::find($id);

        if (!$hasilUjiLabBokar) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // 2. Validasi
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'suplier'   => 'required|string|max:255',
            'no_sampel' => [
                'required',
                'string',
                'max:100',
                // 🔥 PERBAIKAN PENTING: Unique ignore harus mengacu pada KOLOM PRIMARY KEY yang baru
                // Format: Rule::unique('nama_tabel')->ignore($id_value, 'nama_kolom_pk')
                Rule::unique('hasil_uji_lab_bokar')->ignore($hasilUjiLabBokar->id_hasil_uji_lab_bokar, 'id_hasil_uji_lab_bokar'),
            ],
            'k3'        => 'nullable|numeric|min:0',
            'dirt'      => 'nullable|numeric|min:0',
            'ask'       => 'nullable|numeric|min:0',
            'po'        => 'nullable|numeric|min:0', 
            'pa'        => 'nullable|numeric|min:0', 
            'pri'       => 'nullable|numeric|min:0', 
        ]);

        // 🔥 PERBAIKAN: Hardcode nilai 0 untuk dirt dan ask sebelum di-update
        $validated['dirt'] = 0;
        $validated['ask']  = 0;

        // 3. Update
        $hasilUjiLabBokar->update($validated);

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('success', 'Data hasil uji lab berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $hasilUjiLabBokar = HasilUjiLabBokar::find($id);
        
        if ($hasilUjiLabBokar) {
            $hasilUjiLabBokar->delete();
            return redirect()->route('hasil-uji-bokar.index') 
                             ->with('success', 'Data hasil uji lab berhasil dihapus.');
        }

        return redirect()->route('hasil-uji-bokar.index') 
                         ->with('error', 'Data gagal dihapus / tidak ditemukan.');
    }

    // Tambahkan fungsi ini di dalam class
    public function exportExcel(Request $request)
    {
        if (ob_get_length()) { ob_end_clean(); }
        while (ob_get_level() > 0) { ob_end_clean(); }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $namaFile = "Laporan_Uji_Bokar_Diterima_" . ($startDate ? Carbon::parse($startDate)->format('d-m-Y') : 'Semua') . ".xlsx";

        return Excel::download(new HasilUjiBokarExport($startDate, $endDate), $namaFile);
    }
}