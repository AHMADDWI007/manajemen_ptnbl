<?php

namespace App\Http\Controllers;

use App\Models\Maturasi;
use Illuminate\Http\Request;
use Carbon\Carbon; // <-- Pastikan Carbon di-import
use Illuminate\Support\Facades\Validator;

class MaturasiController extends Controller
{
    /**
     * Menampilkan semua data maturasi.
     * Menggunakan created_at untuk pengurutan utama.
     */
    public function index()
    {
        // Urutkan berdasarkan created_at DESC, lalu Uraian ASC
        $data_maturasi = Maturasi::orderBy('created_at', 'desc')
                                 ->orderBy('uraian', 'asc')
                                 ->get();
        return view('Pengolahan.data_maturasi', compact('data_maturasi'));
    }

    /**
     * API BARU: Mengambil data hari sebelumnya untuk Uraian dan Tanggal Input tertentu.
     * Menggunakan created_at untuk perbandingan tanggal.
     */
    public function getPreviousDayData(Request $request)
    {
        $request->validate([
            'uraian' => 'required|string',
            'tanggal_input' => 'required|date_format:Y-m-d', // Tanggal dari form untuk perbandingan
        ]);

        $uraian = $request->input('uraian');
        // Tanggal yang dipilih di form untuk perbandingan
        $currentDate = Carbon::parse($request->input('tanggal_input'))->startOfDay();

        // Cari record terakhir SEBELUM tanggal yang dipilih di form untuk uraian yang sama
        // Menggunakan created_at untuk mencari
        $previousRecord = Maturasi::where('uraian', $uraian)
            ->whereDate('created_at', '<', $currentDate) // Mencari record SEBELUM tanggal input saat ini
            ->orderBy('created_at', 'desc')             // Urutkan descending untuk mendapatkan yang paling baru
            ->first();

        if ($previousRecord) {
            // Hitung umur baru: umur sebelumnya + 1 hari
            $newUmur = ($previousRecord->umur ?? 0) + 1;

            return response()->json([
                'stok_awal' => $previousRecord->stok_akhir, // Stok akhir kemarin jadi stok awal hari ini
                'umur' => $newUmur,
            ]);
        } else {
            // Jika tidak ada data sebelumnya untuk uraian ini
            return response()->json([
                'stok_awal' => 0,
                'umur' => 0, // Umur dimulai dari 0 jika ini input pertama
            ]);
        }
    }


    /**
     * Menyimpan data baru dengan logika kalkulasi otomatis yang disesuaikan.
     * Menggunakan created_at untuk perbandingan, tidak menyimpan tanggal_input.
     */
    public function store(Request $request)
    {
        // Validasi input - Hapus 'tanggal_input' dari validasi penyimpanan jika tidak disimpan
        $validated = $request->validate([
            'uraian' => 'required|string',
            // 'stok_awal' tidak divalidasi required karena diambil dari backend
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string',
        ]);

        // Tetap ambil tanggal dari form untuk perbandingan mencari data sebelumnya
        $tanggalInputForm = $request->input('tanggal_input'); // Ambil tanggal dari form
         if(!$tanggalInputForm) {
              // Jika tanggal tidak diisi di form, beri pesan error
             return redirect()->back()->withErrors(['tanggal_input' => 'Tanggal input harian wajib diisi.'])->withInput();
         }
        $currentDateForComparison = Carbon::parse($tanggalInputForm)->startOfDay();

        $uraian = $validated['uraian'];

        // 1. Ambil data hari sebelumnya (VALIDASI DI BACKEND menggunakan created_at)
        $previousRecord = Maturasi::where('uraian', $uraian)
                                    ->whereDate('created_at', '<', $currentDateForComparison) // Cari sebelum tanggal form
                                    ->orderBy('created_at', 'desc')             // Ambil yg terbaru
                                    ->first();

        // 2. Tentukan Stok Awal dan Umur baru
        $stokAwalHariIni = 0;
        $umurHariIni = 0;
        $tglMasukBokar = null; // Tanggal kapan bokar asli masuk

        if ($previousRecord) {
            $stokAwalHariIni = $previousRecord->stok_akhir;
            $umurHariIni = ($previousRecord->umur ?? 0) + 1;
            $tglMasukBokar = $previousRecord->tgl_masuk; // Ini sekarang harusnya sudah objek Carbon karena casting di model
        }

        // 3. Ambil nilai input hari ini
        $diolah = $validated['diolah'] ?? 0;
        $mutasi = $validated['mutasi'] ?? 0;
        $masukHi = $validated['masuk_hi'] ?? 0;

        // 4. Hitung Stok Akhir
        $stokAkhirHariIni = $stokAwalHariIni + $masukHi - $diolah - $mutasi;

        // 5. Logika Reset Umur dan Tanggal Masuk Bokar
        if ($masukHi > 0) {
            $umurHariIni = 0;
            // Gunakan Carbon::today() karena tidak menyimpan tanggal_input
            $tglMasukBokar = Carbon::today()->startOfDay();
        } elseif ($stokAkhirHariIni <= 0) {
            $umurHariIni = 0;
            $tglMasukBokar = null;
        }

        // 6. Tentukan Keterangan (berdasarkan tanggal input dari form, BUKAN created_at)
        $keterangan = $currentDateForComparison->isoFormat('D MMMM');

        // 7. Simpan data baru (TANPA 'tanggal_input')
        Maturasi::create([
            'uraian' => $uraian,
            'stok_awal' => $stokAwalHariIni,
            'tgl_masuk' => $tglMasukBokar, // Nilai Carbon atau null
            'umur' => $umurHariIni,
            'diolah' => $diolah,
            'mutasi' => $mutasi,
            'masuk_hi' => $masukHi,
            'stok_akhir' => $stokAkhirHariIni,
            'asal_bokar' => $validated['asal_bokar'] ?? null,
            'keterangan' => $keterangan,
            // 'created_at' dan 'updated_at' akan otomatis terisi
        ]);

        return redirect()->route('maturasi.index')->with('success', 'Data berhasil disimpan!');
    }

    // --- Method show, edit, update, destroy ---

    public function show($id)
    {
        $data = Maturasi::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = Maturasi::findOrFail($id);
         // Karena sudah ada casting, created_at juga objek Carbon
         $data->tanggal_input_edit = $data->created_at->format('Y-m-d');
        return response()->json($data);
    }

    /**
     * Update data maturasi yang sudah ada.
     */
    public function update(Request $request, $id)
    {
         $tanggalInputEdit = $request->input('tanggal_input'); // Harus ada input tanggal di form edit

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'stok_awal' => 'required|numeric',
            // 'tgl_masuk' => 'nullable|date', // Sebaiknya tidak diedit manual?
            'umur' => 'required|integer|min:0', // Pastikan umur dikirim dari form
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string',
        ]);

        if ($validator->fails() || !$tanggalInputEdit) {
             $errors = $validator->errors();
            if(!$tanggalInputEdit) {
                $errors->add('tanggal_input', 'Tanggal input harian wajib diisi saat edit.');
            }
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $validated = $validator->validated();
        $maturasi = Maturasi::findOrFail($id); // Ambil data asli (dengan tgl_masuk sudah dicasting)

        // Hitung ulang Stok Akhir
        $stok_akhir = ($validated['stok_awal'] ?? 0)
                      + ($validated['masuk_hi'] ?? 0)
                      - ($validated['diolah'] ?? 0)
                      - ($validated['mutasi'] ?? 0);
        // Jangan langsung masukkan stok akhir ke $validated dulu,
        // karena mungkin $validated['tgl_masuk'] akan di-unset nanti
        $calculated_stok_akhir = $stok_akhir;

        $currentEditDate = Carbon::parse($tanggalInputEdit)->startOfDay();

         // Variabel untuk menampung data update
        $updateData = $validated;

         if ($validated['masuk_hi'] > 0) {
            $updateData['umur'] = 0; // Reset umur jika ada input baru
            $updateData['tgl_masuk'] = $currentEditDate; // Jika ada input baru, tgl_masuk bokar jadi tanggal edit ini
        } elseif ($calculated_stok_akhir <= 0) {
             $updateData['umur'] = 0; // Reset umur
             $updateData['tgl_masuk'] = null; // Kosongkan tgl masuk bokar
        } else {
            // Jika tidak ada input baru & stok > 0
            // === PERBAIKAN DI SINI ===
            // Cek apakah $maturasi->tgl_masuk adalah instance dari Carbon (lebih aman)
             if($maturasi->tgl_masuk instanceof Carbon) { // <-- Pengecekan Instance
                // Hitung ulang umur berdasarkan tgl_masuk asli dan TANGGAL EDIT
                $updateData['umur'] = $maturasi->tgl_masuk->startOfDay()->diffInDays($currentEditDate);
                // Jaga tgl_masuk asli tidak berubah (tidak perlu di-set di $updateData)
                // Pastikan tgl_masuk tidak di-set jika tidak perlu diubah
                 if (isset($updateData['tgl_masuk'])) {
                     unset($updateData['tgl_masuk']);
                 }

             } else {
                 // Jika TIDAK ADA tgl masuk asli atau bukan objek Carbon, umur 0
                 $updateData['umur'] = 0;
                 $updateData['tgl_masuk'] = null; // Pastikan tgl_masuk diupdate menjadi null
             }
             // ==========================
        }

        // Set keterangan berdasarkan tanggal input editan
        $updateData['keterangan'] = $currentEditDate->isoFormat('D MMMM');
        // Masukkan stok akhir yang sudah dihitung
        $updateData['stok_akhir'] = $calculated_stok_akhir;

        // Update data (TANPA 'tanggal_input')
        $maturasi->update($updateData);

        return redirect()->route('maturasi.index')->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Hapus data maturasi.
     */
    public function destroy($id)
    {
        $maturasi = Maturasi::findOrFail($id);
        $maturasi->delete();
        return redirect()->route('maturasi.index')->with('success', 'Data berhasil dihapus!');
    }
}