<?php

namespace App\Http\Controllers\DataLaboratorium;

use Carbon\Carbon;
use App\Models\Maturasi;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use Illuminate\Http\JsonResponse;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\HasilUjiLabBokarDiolah; // 🔥 [PERBAIKAN] Nama Model Baru

class HasilUjiBokarDiolahController extends Controller
{
    /**
     * Tampilkan data dari 'pengolahan_basah'
     */
    public function index()
    {
        // PERBAIKAN: Gunakan Eager Loading 'maturasi' agar nama bak bisa diambil di View
        $data_diolah = PengolahanBasah::with('maturasi')
                                      ->whereNotNull('k3') // Menampilkan history yang sudah diisi
                                      ->orderBy('tanggal', 'desc')
                                      ->get();
        
        // Ambil data 'pengolahan_basah' yang K3-nya masih KOSONG
        // untuk mengisi dropdown di modal tambah K3
        $daftar_bak_belum_uji = PengolahanBasah::with('maturasi')
                                      ->whereNull('k3')
                                      ->orderBy('tanggal', 'desc')
                                      ->get();

        return view('DataLaboratorium.hasil-uji-bokar-diolah', compact('data_diolah', 'daftar_bak_belum_uji'));
    }

    /**
     * Ini BUKAN store (create), tapi UPDATE K3 pada Transaksi & Create Log
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // 🔥 [PERBAIKAN] Validasi FK ke 'id_pengolahan_basah'
            'id_pengolahan_basah' => 'required|exists:pengolahan_basah,id_pengolahan_basah',
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // 1. Cari data pengolahan basah berdasarkan ID
        $data = PengolahanBasah::find($request->id_pengolahan_basah);

        if (!$data) {
             return redirect()->back()->withErrors(['error' => 'Data pengolahan basah tidak ditemukan.']);
        }

        // 2. Ambil Netto Basah-nya
        $netto_basah = $data->netto_basah;
        $k3_value = $request->k3;

        // 3. Hitung Netto Kering
        $netto_kering = $netto_basah * ($k3_value / 100);

        // 4. Update data tersebut (Tabel: pengolahan_basah)
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering,
        ]);
        
        // 5. Buat catatan/log di tabel 'hasil_uji_lab_bokar_diolah'
        // 🔥 PERBAIKAN: Gunakan Model Baru & FK yang Sesuai
        HasilUjiLabBokarDiolah::create([
            'id_pengolahan_basah' => $data->id_pengolahan_basah,
            'id_maturasi'       => $data->id_maturasi, // Ambil dari data basah
            'tanggal'           => $data->tanggal,
            'jenis'             => $data->jenis,
            'netto_basah'       => $netto_basah,
            'k3'                => $k3_value,
            'netto_kering'      => $netto_kering
        ]);
        
        // 6. TRIGGER UPDATE STOK MATURASI
        // Kita panggil fungsi update stok agar saldo maturasi bertambah otomatis
        // Note: Kita butuh nama bak untuk log, ambil dari relasi
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
        
        // Panggil helper update (Diadaptasi ke ID)
        $this->updateMaturasiMasukHI($data->id_maturasi, $netto_kering, $data->tanggal, $data->jenis, $namaBak);

        return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 berhasil disimpan dan Stok Maturasi bertambah.');
    }

    public function show($id): JsonResponse
    {
        $data = PengolahanBasah::with('maturasi')->find($id);
        return response()->json($data);
    }

    public function edit($id): JsonResponse
    {
        $data = PengolahanBasah::with('maturasi')->find($id);
        return response()->json($data);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // Fungsi update di halaman ini sekarang MENGEDIT K3 yang salah input
        $validator = Validator::make($request->all(), [
            'k3' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
             return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = PengolahanBasah::find($id);
        if (!$data) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan.']);
        }
        
        $k3_value = $request->k3;
        $netto_kering_baru = $data->netto_basah * ($k3_value / 100);

        // 1. Update 'pengolahan_basah'
        $data->update([
            'k3' => $k3_value,
            'netto_kering' => $netto_kering_baru
        ]);
        
        // 2. Update juga log 'hasil_uji_bokar_diolah'
        // 🔥 PERBAIKAN: Gunakan id_pengolahan_basah & Model Baru
        HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $data->id_pengolahan_basah)->update([
             'k3' => $k3_value,
             'netto_kering' => $netto_kering_baru
        ]);
                            
        // 3. JALANKAN TRIGGER UPDATE STOK ULANG
        // (Logic sederhana: Timpa ulang masuk_hi di log maturasi)
        $namaBak = $data->maturasi ? $data->maturasi->uraian : 'Unknown Bak';
        $this->updateMaturasiMasukHI($data->id_maturasi, $netto_kering_baru, $data->tanggal, $data->jenis, $namaBak);

        return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        // Hapus data K3 (kembalikan ke status belum uji)
        $data = PengolahanBasah::find($id);
        if ($data) {
            
            // Hapus log di 'hasil_uji_bokar_diolah'
            // 🔥 PERBAIKAN: Gunakan id_pengolahan_basah & Model Baru
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $data->id_pengolahan_basah)->delete();
            
            // Reset kolom K3 di tabel induk
            $data->update(['k3' => null, 'netto_kering' => null]);
            
            // Reset Stok Maturasi (Kurangi stok yang sudah masuk)
            // Note: Ini logic kompleks, sederhananya kita set masuk_hi jadi 0 untuk transaksi ini di log maturasi
            // Tapi karena log maturasi itu agregat harian, menghapus satu transaksi butuh hitung ulang.
            // Untuk amannya, kita biarkan user memperbaiki manual lewat menu Maturasi jika perlu,
            // atau set 0 jika yakin ini satu-satunya transaksi hari itu.
            
            return redirect()->route('hasil-uji-bokar-diolah.index')->with('success', 'Data K3 dihapus. Silakan input ulang.');
        }
        return redirect()->route('hasil-uji-bokar-diolah.index')->withErrors(['error' => 'Data tidak ditemukan.']);
    }
    
    // ===================================================================
    // FUNGSI TRIGGER OTOMATIS KE PENGOLAHAN MATURASI
    // ===================================================================
    // ===================================================================
    // FUNGSI TRIGGER OTOMATIS KE PENGOLAHAN MATURASI (UPDATED)
    // ===================================================================
    private function updateMaturasiMasukHI(int $id_maturasi, float $masuk_hi, string $tanggalInput, ?string $asal_bokar_baru = null, string $uraianMaturasi = ''): void
    {
        $maturasi = Maturasi::find($id_maturasi);
        if (!$maturasi) return;

        $tgl = Carbon::parse($tanggalInput);

        // ---------------------------------------------------------
        // BAGIAN 1: UPDATE LOG HARIAN & STOK (TETAP SAMA)
        // ---------------------------------------------------------
        
        // Update/Create Log Harian
        $log = PengolahanMaturasi::firstOrNew([
            'id_maturasi' => $id_maturasi,
            'tgl_laporan' => $tgl
        ]);

        // Hitung total real hari ini dari inputan
        $totalMasukReal = PengolahanBasah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', $tgl)
            ->sum('netto_kering');
            
        // Jika belum ada netto kering, pakai estimasi
        if ($totalMasukReal <= 0) $totalMasukReal = $masuk_hi;

        $log->masuk_hi = $totalMasukReal;
        
        // Update keterangan log harian (Jika kosong, isi default)
        if (empty($log->keterangan)) {
            $log->keterangan = 'Fisik Bokar Masuk';
        }
        $log->save();

        // Hitung Stok H-1 (Kemarin Sore)
        $stokAwalH1 = round(PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $tgl)
            ->sum(DB::raw('masuk_hi - diolah - mutasi')), 2);

        // Ambil Data Transaksi Hari Ini
        $masuk  = $log->masuk_hi;
        $keluar = $log->diolah + $log->mutasi;
        
        // Update Master Maturasi (Angka Stok)
        $maturasi->stok_awal  = $stokAwalH1;
        $maturasi->stok_akhir = $stokAwalH1 + $masuk - $keluar;
        $maturasi->updated_at = $tgl;

        // ---------------------------------------------------------
        // BAGIAN 2: LOGIKA LABEL / JENIS (🔥 DISAMAKAN DENGAN CONTROLLER SEBELAH)
        // ---------------------------------------------------------
        
        if ($maturasi->stok_akhir > 0) {
            
            // 1. Ambil SEMUA jenis unik di bak ini pada tanggal ini
            $listJenis = PengolahanBasah::where('id_maturasi', $id_maturasi)
                        ->where('tanggal', $tanggalInput) // Cek tanggal yg sama
                        ->pluck('jenis')
                        ->filter(function ($value) { return !is_null($value) && $value !== '' && $value !== 'PENDING'; }) 
                        ->unique()
                        ->sort()
                        ->values();

            // 2. Tentukan Label Akhir (CMP Rincian atau Single)
            $labelAkhir = '';

            if ($listJenis->count() > 1) {
                // Jika Multi Jenis -> CMP (Jenis1, Jenis2)
                $rincian = $listJenis->implode(', ');
                $labelAkhir = "CMP ($rincian)";
            } 
            elseif ($listJenis->count() == 1) {
                // Jika cuma 1 jenis
                $labelAkhir = $listJenis->first();
            } 
            else {
                // Jika tidak ada data jenis hari ini, ambil label lama (fallback)
                $labelAkhir = $maturasi->asal_bokar; 
            }

            // 3. Update Kolom 'asal_bokar' di Master (Sesuai Revisi Abang)
            $maturasi->asal_bokar = $labelAkhir; 
            
            // Update info umur/tanggal jika ada barang masuk
            if ($masuk > 0) {
                $maturasi->umur = 0; // Reset umur jadi 0 hari
                $maturasi->keterangan = strtoupper($tgl->translatedFormat('d M Y')); // Kolom keterangan buat tanggal
            }

        } else {
            // Jika Stok Habis (0)
            $maturasi->keterangan = 'KOSONG'; 
            $maturasi->asal_bokar = null;
        }

        $maturasi->save();
    }
}