<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Pallet;
use App\Models\Maturasi;
use App\Models\RemahanSir20;
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\BahanBakarSir20;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AktualTemperatureSir20;

class ProduksiSir20Controller extends Controller
{
    public function index()
    {
        // 1. Ambil History Produksi
        $history = ProduksiSir20::with(['remahan'])->orderBy('tanggal_produksi', 'desc')->get();
        
        // 2. Ambil Bak Maturasi Aktif
        $bak_aktif = Maturasi::where('stok_akhir', '>', 0)
                            ->orderBy('uraian', 'asc')
                            ->get();

        $hari_ini = Carbon::today()->startOfDay();

        // 3. Logika Hitung Umur Maturasi
        foreach ($bak_aktif as $bak) {
            $lastLog = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->where('masuk_hi', '>', 0)
                ->whereDate('tgl_laporan', '<=', $hari_ini)
                ->orderBy('tgl_laporan', 'desc')
                ->first();

            $tgl_acuan = null;

            // Tentukan tanggal dasar perhitungan (dari log terakhir atau tanggal masuk awal)
            if ($lastLog) {
                $tgl_acuan = Carbon::parse($lastLog->tgl_laporan)->startOfDay();
            } elseif (!empty($bak->tgl_masuk)) {
                $tgl_acuan = Carbon::parse($bak->tgl_masuk)->startOfDay();
            }

            // 🔥 PERBAIKAN DISINI 🔥
            if ($tgl_acuan) {
                // A. Hitung Umur Real (Untuk Tampilan Teks di Dropdown PHP)
                // Ini yang tadi KETINGGALAN, makanya umur awalnya ga muncul/0
                $bak->umur_real = abs($tgl_acuan->diffInDays($hari_ini)); 
                
                // B. Simpan Tanggal Acuan (Untuk Logika JavaScript Dinamis)
                $bak->tgl_dasar_hitung = $tgl_acuan->format('Y-m-d'); 
            } else {
                $bak->umur_real = 0;
                $bak->tgl_dasar_hitung = null;
            }
        }

        // 4. Ambil Nomor Terakhir & User
        $lastProduction = ProduksiSir20::orderBy('id_produksi_sir20', 'desc')->first();
        $users = User::orderBy('fullname', 'asc')->get();
        $lastNomorAkhir = $lastProduction ? $lastProduction->total_nomor_akhir : 0;
        
        // 5. Kirim ke View
        return view('DataProduksi.produksi-sir20', compact('history', 'bak_aktif', 'lastNomorAkhir', 'users'));
    }

    public function show($id)
    {
        // 🔥 [PERBAIKAN] find($id) otomatis cari di PK 'id_produksi_sir20'
        $data = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])
                ->findOrFail($id);

        return response()->json($data);
    }

    // =========================================================================
    // 🔥 STORE (SIMPAN BARU + TRIGGER STOK MATURASI)
    // =========================================================================
    // =========================================================================
    // 🔥 STORE FINAL (SIMPAN DATA PABRIK + GENERATE PALLET GUDANG)
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        DB::beginTransaction(); 

        try {
            // 1. Simpan Header Produksi (Laporan Pabrik)
            $produksi = ProduksiSir20::create([
                'tanggal_produksi'      => $request->tanggal_produksi,
                'shift_kerja'           => $request->shift_kerja,
                
                'jam_start_dryer'       => $request->jam_start_dryer,
                'jumlah_trolly_masuk'   => $this->cleanNumber($request->trolly_masuk),
                'jumlah_trolly_keluar'  => $this->cleanNumber($request->trolly_keluar),
                'jam_stop_dryer'        => $request->jam_stop_dryer,
                'jumlah_jam_dryer'      => $this->cleanNumber($request->jam_jalan_dryer),

                'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales),
                'kg_yang_dipress'       => $this->cleanNumber($request->kg_press),
                'capacity_per_jam'      => $this->cleanNumber($request->input('capacity_per_jam', 0)),
                'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
                'produktivitas'         => $this->cleanNumber($request->input('produktivitas', 0)),

                'kg_cake'               => $this->cleanNumber($request->kg_sir20),
                'bales_terkontaminasi'  => $this->cleanNumber($request->bales_kontamin),
                'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
                'jam_operasional_genset'=> $this->cleanNumber($request->jam_genset),
                'pemakaian_listrik_pln' => $this->cleanNumber($request->pln_kwh),

                'jumlah_pallet'         => $this->cleanNumber($request->jml_pallet),
                'total_nomor'           => $this->cleanNumber($request->total_nomor),
                'mc_val'                => $this->cleanNumber($request->mc_val),
                'nomor_start'           => $request->nomor_start,
                'nomor_end'             => $request->nomor_end,
                'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
                'petugas'               => $request->petugas,
            ]);

            // 2. Simpan Remahan & Trigger Maturasi
            if ($request->has('maturasi')) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) || !empty($item['berat'])) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $produksi->id_produksi_sir20,
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur']),
                        ]);

                        // Update Stok Maturasi
                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 3. Simpan Temperature
            $tempData = [
                ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
                ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
                ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
            ];

            foreach ($tempData as $temp) {
                if ($temp['start'] || $temp['end']) {
                    AktualTemperatureSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20,
                        'jenis'             => $temp['jenis'],
                        'nilai_start'       => $this->cleanNumber($temp['start']),
                        'nilai_end'         => $this->cleanNumber($temp['end']),
                    ]);
                }
            }

            // 4. Simpan Bahan Bakar
            $bbData = [
                ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
                ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
                ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
            ];

            foreach ($bbData as $bb) {
                $cleanJumlah = $this->cleanNumber($bb['jumlah']);
                if ($cleanJumlah > 0) {
                    BahanBakarSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20,
                        'bahan_bakar'       => $bb['nama'],
                        'digunakan'         => $cleanJumlah,
                    ]);
                }
            }

            // -----------------------------------------------------------------
            // 🔥 PANGGIL HELPER GENERATE PALLET 🔥
            // Bagian ini yang membuat data otomatis masuk ke Gudang SIR & Mutu Prima
            // -----------------------------------------------------------------
            $this->generatePalletsOtomatis(
                $request->tanggal_produksi, 
                $this->cleanNumber($request->kg_press), 
                $this->cleanNumber($request->jml_pallet)
            );

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi Berhasil Disimpan & Pallet Masuk ke Gudang!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 HELPER BARU: GENERATE PALLET OTOMATIS KE GUDANG 🔥
    // =========================================================================
    private function generatePalletsOtomatis($tanggal, $totalKg, $totalPallet)
    {
        // Validasi sederhana agar tidak error bagi 0
        if ($totalPallet <= 0) return;

        // 1. Buat atau Update Header Laporan Harian (Table: produksi_sir)
        // Ini menggabungkan semua shift pada tanggal tersebut menjadi satu rekap harian
        $header = \App\Models\ProduksiSir::firstOrCreate(
            ['tanggal_produksi' => $tanggal],
            ['kg' => 0, 'pallet' => 0, 'keterangan' => 'Generate Otomatis dari Laporan Pabrik']
        );

        // Update akumulasi header (Menambah nilai yang baru diinput)
        $header->increment('kg', $totalKg);
        $header->increment('pallet', $totalPallet);

        // 2. Ambil Default Lokasi & Mutu
        // Pastikan nama ini SAMA PERSIS dengan di database (tabel lokasi & mutu)
        $lokasiAwal = \App\Models\Lokasi::where('nama', 'Di Gudang SIR')->first(); 
        
        // Fallback jika belum di-seed (safety)
        if (!$lokasiAwal) {
            $lokasiAwal = \App\Models\Lokasi::create(['nama' => 'Di Gudang SIR']);
        }

        $mutuPrima = \App\Models\Mutu::where('uraian', 'LIKE', '%Prima%')->first();
        if (!$mutuPrima) {
            $mutuPrima = \App\Models\Mutu::create(['uraian' => 'Mutu Prima (siap jual)']);
        }

        // 3. Hitung Berat Rata-rata per Pallet (Untuk data awal)
        $kgPerPallet = $totalKg / $totalPallet;

        // 4. Generate Nomor Pallet Unik
        // Format: YYYYMMDD-XXXX (Misal: 20260205-0001)
        // Cek dulu sudah ada berapa pallet hari ini di DB Tracking untuk melanjutkan urutan
        $existingCount = Pallet::whereDate('tanggal_produksi', $tanggal)->count();

        for ($i = 1; $i <= $totalPallet; $i++) {
            $sequence = $existingCount + $i;
            $noPallet = Carbon::parse($tanggal)->format('Ymd') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // A. Create Data Pallet (Identitas Fisik)
            $pallet = Pallet::create([
                'id_produksi_sir' => $header->id_produksi_sir,
                'no_pallet'       => $noPallet,
                'berat'           => $kgPerPallet,
                'tanggal_produksi'=> $tanggal,
            ]);

            // B. Set Lokasi Awal (Otomatis Masuk Gudang SIR)
            \App\Models\LokasiPallet::create([
                'id_lokasi' => $lokasiAwal->id_lokasi,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggal
            ]);

            // C. Set Mutu Awal (Otomatis Mutu Prima)
            \App\Models\KondisiPallet::create([
                'id_mutu'   => $mutuPrima->id_mutu,
                'id_pallet' => $pallet->id_pallet,
                'tanggal'   => $tanggal
            ]);
        }
    }

    // =========================================================================
    // 🔥 UPDATE (EDIT DATA + SINKRONISASI STOK MATURASI)
    // =========================================================================
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        DB::beginTransaction();

        try {
            $produksi = ProduksiSir20::findOrFail($id);
            $oldDate = $produksi->tanggal_produksi; // Simpan tanggal lama utk revert

            // 1. Update Header
            $produksi->update([
                'tanggal_produksi'      => $request->tanggal_produksi,
                'shift_kerja'           => $request->shift_kerja,
                'jam_start_dryer'       => $request->jam_start_dryer,
                'jumlah_trolly_masuk'   => $this->cleanNumber($request->trolly_masuk),
                'jumlah_trolly_keluar'  => $this->cleanNumber($request->trolly_keluar),
                'jam_stop_dryer'        => $request->jam_stop_dryer,
                'jumlah_jam_dryer'      => $this->cleanNumber($request->jam_jalan_dryer),
                'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales),
                'kg_yang_dipress'       => $this->cleanNumber($request->kg_press),
                'capacity_per_jam'      => $this->cleanNumber($request->input('capacity_per_jam', 0)),
                'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
                'produktivitas'         => $this->cleanNumber($request->input('produktivitas', 0)),
                'kg_cake'               => $this->cleanNumber($request->kg_sir20),
                'bales_terkontaminasi'  => $this->cleanNumber($request->bales_kontamin),
                'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
                'jam_operasional_genset'=> $this->cleanNumber($request->jam_genset),
                'pemakaian_listrik_pln' => $this->cleanNumber($request->pln_kwh),
                'jumlah_pallet'         => $this->cleanNumber($request->jml_pallet),
                'total_nomor'           => $this->cleanNumber($request->total_nomor),
                'mc_val'                => $this->cleanNumber($request->mc_val),
                'nomor_start'           => $request->nomor_start,
                'nomor_end'             => $request->nomor_end,
                'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
                // 🔥 TAMBAHAN: Simpan Petugas 🔥
                'petugas'               => $request->petugas,
            ]);

            // 2. Update Remahan & 🔥 TRIGGER MATURASI (Revert & Add)
            
            // A. REVERT STOK LAMA (Kembalikan stok seolah produksi lama batal)
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                // 'kurang' = Kurangi nilai 'Diolah' di log Maturasi -> Stok Maturasi bertambah kembali
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }
            RemahanSir20::where('id_produksi_sir20', $id)->delete();

            // B. INSERT DATA BARU & POTONG STOK
            if ($request->has('maturasi')) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) || !empty($item['berat'])) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $id, 
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur']),
                        ]);

                        // 'tambah' = Tambah nilai 'Diolah' -> Stok Maturasi berkurang
                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 3. Update Lainnya (Temp & BB) - Hapus & Insert Ulang
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            $tempData = [
                ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
                ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
                ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
            ];
            foreach ($tempData as $temp) {
                if ($temp['start'] || $temp['end']) {
                    AktualTemperatureSir20::create([
                        'id_produksi_sir20' => $id,
                        'jenis'             => $temp['jenis'],
                        'nilai_start'       => $this->cleanNumber($temp['start']),
                        'nilai_end'         => $this->cleanNumber($temp['end']),
                    ]);
                }
            }

            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $bbData = [
                ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
                ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
                ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
            ];
            foreach ($bbData as $bb) {
                $cleanJumlah = $this->cleanNumber($bb['jumlah']);
                if ($cleanJumlah > 0) {
                    BahanBakarSir20::create([
                        'id_produksi_sir20' => $id,
                        'bahan_bakar'       => $bb['nama'],
                        'digunakan'         => $cleanJumlah,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi Diperbarui & Stok Maturasi Disesuaikan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Update: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 🔥 DELETE (HAPUS DATA + KEMBALIKAN STOK MATURASI)
    // =========================================================================
    // public function destroy($id)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $produksi = ProduksiSir20::findOrFail($id);
    //         $oldDate = $produksi->tanggal_produksi;

    //         // Kembalikan Stok Maturasi (Revert)
    //         $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
    //         foreach($oldRemahan as $old) {
    //             $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
    //         }

    //         // Hapus Data (Cascade delete di DB biasanya handle child, tapi manual lebih aman)
    //         RemahanSir20::where('id_produksi_sir20', $id)->delete();
    //         AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
    //         BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
    //         $produksi->delete();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Data Dihapus & Stok Maturasi Dikembalikan!');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal Hapus: ' . $e->getMessage());
    //     }
    // }

    // =========================================================================
    // HELPER FUNCTIONS
    // =========================================================================
    
    private function cleanNumber($value)
    {
        if (empty($value)) return 0;

        // 🔥 CEK 1: Apakah nilainya sudah berupa angka standar? (Contoh: "2250.00" atau 2250)
        // Input dari <input type="number"> selalu mengirim format ini.
        // Jika iya, langsung kembalikan sebagai float, JANGAN hapus titiknya.
        if (is_numeric($value)) {
            return (float) $value;
        }

        // 🔥 CEK 2: Jika format Indonesia (ada koma sebagai desimal, misal "2.250,50")
        // Ini biasanya dari input text manual atau plugin masking
        $string = (string) $value;
        $string = str_replace('.', '', $string); // Hapus titik ribuan
        $string = str_replace(',', '.', $string); // Ganti koma jadi titik desimal
        return (float) $string;
    }

    // 🔥 LOGIKA SINKRONISASI KE TABEL MATURASI
    private function triggerUpdateMaturasi($namaRuang, $tanggal, $berat, $aksi)
    {
        if ($berat <= 0) return;

        // 1. Cari ID Maturasi berdasarkan Nama Ruang
        $maturasi = Maturasi::where('uraian', $namaRuang)->first();
        
        if ($maturasi) {
            // 2. Cari Log Harian / Buat Baru (PengolahanMaturasi)
            $log = PengolahanMaturasi::firstOrCreate(
                ['id_maturasi' => $maturasi->id_maturasi, 'tgl_laporan' => $tanggal],
                ['masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0, 'keterangan' => 'Auto Produksi']
            );

            // 3. Update Kolom 'Diolah' pada Log & 'Stok Akhir' pada Master
            if ($aksi === 'tambah') {
                // Produksi Baru: Diolah bertambah, Stok Master berkurang
                $log->increment('diolah', $berat);
                $maturasi->decrement('stok_akhir', $berat); 
            } else {
                // Edit/Hapus (Revert): Diolah berkurang (dikembalikan), Stok Master bertambah
                if ($log->diolah >= $berat) {
                    $log->decrement('diolah', $berat);
                } else {
                    $log->update(['diolah' => 0]); // Cegah minus di log
                }
                $maturasi->increment('stok_akhir', $berat); 
            }
            
            // 4. Update status master jika stok habis/ada
            if ($maturasi->stok_akhir <= 0.01) {
                $maturasi->update(['stok_akhir' => 0, 'keterangan' => 'KOSONG', 'asal_bokar' => null]);
            } else {
                $maturasi->touch(); // Update timestamp updated_at
            }
        }
    }
}