<?php

namespace App\Http\Controllers;

use App\Models\Maturasi;             // <-- 1. Model MASTER/STATE
use App\Models\PengolahanMaturasi; // <-- 2. Model LOG/TRANSAKSI
use App\Models\PengolahanBasah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // <-- Penting untuk Transaksi

class PengolahanMaturasiController extends Controller
{
    /**
     * Menampilkan data LOG harian (sesuai request).
     * Data default (Stok Awal) diambil dari tabel MASTER.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        // 1. Ambil log yang SUDAH TERSIMPAN hari ini (berdasarkan tanggal_input)
        // Kita butuh relasi 'maturasi' untuk mendapatkan 'uraian'
        $maturasiHariIni = PengolahanMaturasi::whereDate('tanggal_input', $selectedDate)
                            ->with('maturasi') // Asumsi relasi 'maturasi' di model PengolahanMaturasi
                            ->get()
                            ->keyBy(function($item) {
                                // Key berdasarkan 'uraian' dari tabel master
                                return $item->maturasi->uraian ?? 'INVALID'; 
                            });

        // 2. Ambil SEMUA data master (sebagai sumber 'stok_akhir' terakhir)
        $dataMaster = Maturasi::all()->keyBy('uraian');

        // 3. Ambil data 'Masuk HI' dari Pengolahan Basah untuk tanggal terpilih
        $pengolahanBasahHariIni = PengolahanBasah::whereDate('tanggal', $selectedDate)
            ->select('bak_maturasi', DB::raw('SUM(netto_kering) as total_netto_kering'))
            ->groupBy('bak_maturasi')
            ->get()
            ->keyBy('bak_maturasi');
        
        // (Opsional: tambahkan logika cek 'created_at' jika 'tanggal' 0)

        $dataTampilan = new Collection();

        // Loop 49 bak untuk membangun view
        for ($i = 1; $i <= 49; $i++) {
            $uraian = "Di Bak Maturasi " . $i;
            $bakName = "Bak Maturasi " . $i; // Untuk pencocokan ke pengolahan_basah
            
            // Lewati jika bak ini tidak ada di master (seharusnya tidak terjadi jika seeder benar)
            if (!isset($dataMaster[$uraian])) continue; 

            $masterBak = $dataMaster[$uraian];

            if (isset($maturasiHariIni[$uraian])) {
                // --- A. DATA SUDAH ADA DI LOG (untuk tanggal $selectedDate) ---
                // Tampilkan data log yang tersimpan
                $itemLog = $maturasiHariIni[$uraian];
                
                $dataTampilan->push([
                    'id' => $itemLog->id, // Ada ID dari tabel log
                    'tanggal_input_view' => $itemLog->tanggal_input->format('d-m-Y'),
                    'uraian' => $uraian,
                    'stok_awal' => $itemLog->stok_awal,
                    'tgl_masuk' => $itemLog->tgl_masuk_log, // Asumsi Anda simpan tgl_masuk di log
                    'umur' => $itemLog->umur_log, // Asumsi Anda simpan umur di log
                    'diolah' => $itemLog->diolah,
                    'mutasi' => $itemLog->mutasi,
                    'masuk_hi' => $itemLog->masuk_hi,
                    'stok_akhir' => $itemLog->stok_akhir,
                    'keterangan' => $itemLog->keterangan,
                    // 'created_at_view' => $itemLog->created_at, // Tanggal asli input
                    'asal_bokar' => $masterBak->asal_bokar // Asal bokar tetap dari master
                ]);

            } else {
                // --- B. DATA BELUM ADA DI LOG (DEFAULT untuk $selectedDate) ---
                // Ambil 'Stok Awal' dari 'stok_akhir' tabel master
                $stok_awal = $masterBak->stok_akhir;
                
                // Ambil 'Masuk HI' dari Pengolahan Basah
                $masuk_hi = $pengolahanBasahHariIni[$bakName]->total_netto_kering ?? 0;
                
                // Hitung umur
                $tgl_masuk_preview = $masterBak->tgl_masuk; // Warisi dari master
                $umur_preview = 0;

                if ($masuk_hi > 0) {
                    // Jika ada Masuk HI hari ini, Tgl Masuk di-reset ke hari ini
                    $tgl_masuk_preview = $selectedDate;
                    $umur_preview = 0; // Umur di-reset
                } elseif ($stok_awal > 0 && $tgl_masuk_preview) {
                    // Jika tidak ada Masuk HI, hitung umur seperti biasa
                    $umur_preview = $tgl_masuk_preview->diffInDays($selectedDate);
                }

                // --- AWAL PERBAIKAN (Meniru Rumus Excel) ---
                $stok_akhir_preview = $stok_awal + $masuk_hi; // 1. Hitung stok akhir preview

                // 3. Terapkan logika IF dari Excel
                $keterangan_text = $tgl_masuk_preview ? $tgl_masuk_preview->isoFormat('D MMMM YYYY') : null;
                // --- AKHIR PERBAIKAN ---
                
                $dataTampilan->push([
                    'id' => null, // Tidak ada ID
                    'tanggal_input_view' => $selectedDate->format('d-m-Y'),
                    'uraian' => $uraian,
                    'stok_awal' => $stok_awal,
                    'tgl_masuk' => $tgl_masuk_preview, // Gunakan tgl_masuk_preview
                    'umur' => $umur_preview, // Gunakan umur_preview
                    'diolah' => 0,
                    'mutasi' => 0,
                    'masuk_hi' => $masuk_hi,
                    'stok_akhir' => $stok_akhir_preview, // 4. Gunakan hasil preview
                    'keterangan' => $keterangan_text,    // 5. Gunakan hasil logika IF
                    'created_at_view' => null, // Belum diinput
                    'asal_bokar' => $masterBak->asal_bokar
                ]);
            }
        }

        return view('Pengolahan.data_maturasi', [
            'data_maturasi' => $dataTampilan,
            'selected_date' => $selectedDate->format('Y-m-d')
        ]);
    }

    /**
     * Menyimpan data TRANSAKSI harian baru.
     * INSERT ke 'pengolahan_maturasi' (log)
     * UPDATE ke 'maturasi' (master)
     */
    public function store(Request $request)
    {
        $cleanNumber = function ($value) {
            if (empty($value)) return 0;
            return str_replace(',', '.', str_replace('.', '', $value));
        };
        $request->merge([
            'stok_awal' => $cleanNumber($request->input('stok_awal')),
            'masuk_hi'  => $cleanNumber($request->input('masuk_hi')),
            'diolah'    => $cleanNumber($request->input('diolah')),
            'mutasi'    => $cleanNumber($request->input('mutasi')),
        ]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian', // Validasi ke tabel MASTER
            'stok_awal' => 'required|numeric',
            'umur' => 'required|integer',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0', // Ini dari AJAX, akan divalidasi ulang
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            'tanggal_input_harian' => 'required|date',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $tanggalInput = Carbon::parse($data['tanggal_input_harian']);

        // Cek apakah data untuk tanggal ini sudah ada
        $cekLog = DB::table('pengolahan_maturasi as pl')
            ->join('maturasi as m', 'pl.maturasi_id', '=', 'm.id')
            ->where('m.uraian', $data['uraian'])
            ->whereDate('pl.tanggal_input', $tanggalInput)
            ->exists();
            
        if ($cekLog) {
            return redirect()->back()->withErrors(['error' => 'Data untuk bak ' . $data['uraian'] . ' pada tanggal ' . $tanggalInput->format('d-m-Y') . ' sudah ada. Gunakan tombol edit.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $masterBak = Maturasi::where('uraian', $data['uraian'])
                        ->lockForUpdate()
                        ->firstOrFail();

            // 2. Ambil Stok Awal (HARUS dari master)
            $stok_awal_transaksi = $masterBak->stok_akhir;

            // 3. Ambil Masuk HI (HARUS dari DB)
            $nomorBak = "Bak Maturasi " . preg_replace('/[^0-9]/', '', $data['uraian']);
            $masuk_hi_transaksi = PengolahanBasah::where('bak_maturasi', $nomorBak)
                                    ->whereDate('tanggal', $tanggalInput)
                                    ->sum('netto_kering');
            // (Tambahkan logika created_at jika perlu)

            // 4. Ambil data 'diolah' dan 'mutasi' dari form
            $diolah_transaksi = $data['diolah'] ?? 0;
            $mutasi_transaksi = $data['mutasi'] ?? 0;

            // 5. Hitung Stok Akhir BARU
            $stok_akhir_baru = $stok_awal_transaksi 
                             + $masuk_hi_transaksi 
                             - $diolah_transaksi 
                             - $mutasi_transaksi;

            // 6. Tentukan Tanggal Masuk (tgl_masuk) BARU untuk MASTER
            // --- [PERBAIKAN LOGIKA TGL MASUK & UMUR DIMULAI DI SINI] ---
            // Tentukan Tgl Masuk & Umur untuk LOG
            $tgl_masuk_log = $masterBak->tgl_masuk; // 1. Warisi dari master
            if ($masuk_hi_transaksi > 0) {
                $tgl_masuk_log = $tanggalInput; // 2. Reset jika ada Masuk HI
            }
            $umur_log = $tgl_masuk_log ? $tgl_masuk_log->diffInDays($tanggalInput) : 0;

            // Tentukan Tgl Masuk untuk MASTER
            $tgl_masuk_master_baru = $tgl_masuk_log; // 3. Tgl master = tgl log
            if ($stok_akhir_baru <= 0) {
                $tgl_masuk_master_baru = null; // 4. Reset jika stok habis
            }
            // --- [PERBAIKAN LOGIKA TGL MASUK & UMUR SELESAI] ---

            // --- AWAL LOGIKA KETERANGAN BARU (Sudah Benar) ---
            $keterangan_final = $tgl_masuk_log ? $tgl_masuk_log->isoFormat('D MMMM YYYY') : null;
            // --- AKHIR LOGIKA KETERANGAN BARU ---

            // 7. === UPDATE TABEL MASTER (maturasi) ===
            $masterBak->update([
                'stok_akhir' => $stok_akhir_baru,
                'tgl_masuk'  => $tgl_masuk_master_baru,
                'asal_bokar' => $data['asal_bokar'],
                'keterangan' => $keterangan_final,
            ]);

            // --- AWAL LOGIKA KETERANGAN BARU ---
            $keterangan_final = $tgl_masuk_log ? $tgl_masuk_log->isoFormat('D MMMM YYYY') : null;
            // --- AKHIR LOGIKA KETERANGAN BARU ---

            // 8. === INSERT TABEL LOG (pengolahan_maturasi) ===
            PengolahanMaturasi::create([
                'maturasi_id'   => $masterBak->id,
                'tanggal_input' => $tanggalInput,
                'stok_awal'     => $stok_awal_transaksi,
                'diolah'        => $diolah_transaksi,
                'mutasi'        => $mutasi_transaksi,
                'masuk_hi'      => $masuk_hi_transaksi,
                'stok_akhir'    => $stok_akhir_baru,
                'keterangan'    => $keterangan_final, // <-- Perbaikan
                'tgl_masuk_log' => $tgl_masuk_log, // Simpan state tgl_masuk saat itu
                'umur_log'      => $umur_log,      // Simpan state umur saat itu
                'created_at'    => $tanggalInput, // <-- GANTI JADI INI
                'updated_at'    => $tanggalInput  // <-- GANTI JADI INI
            ]);
            
            DB::commit();

            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalInput->format('Y-m-d')])
                             ->with('success', 'Transaksi harian berhasil disimpan. Stok master telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->withErrors(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()])
                             ->withInput();
        }
    }

    /**
     * Dipanggil AJAX untuk mengisi modal tambah.
     * Mengambil data dari MASTER 'maturasi' dan 'pengolahan_basah'.
     */
    public function getPreviousData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian',
            'tanggal_filter' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Input tidak valid: ' . $validator->errors()->first()], 400);
        }

        $uraian = $request->input('uraian');
        $tanggalFilter = Carbon::parse($request->input('tanggal_filter'));

        $masterBak = Maturasi::where('uraian', $uraian)->firstOrFail();

        $stok_awal = $masterBak->stok_akhir ?? 0;
        
        $umur = 0;
        if ($stok_awal > 0 && $masterBak->tgl_masuk) {
            $umur = $masterBak->tgl_masuk->diffInDays($tanggalFilter);
        }

        $nomorBak = "Bak Maturasi " . preg_replace('/[^0-9]/', '', $uraian);
        $netto_kering_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                                    ->whereDate('tanggal', $tanggalFilter)
                                    ->sum('netto_kering');
        // (Tambahkan logika created_at jika perlu)

        if($stok_awal == 0 && $netto_kering_hari_ini > 0) {
            $umur = 0;
        }

        // Terapkan Logika LIFO/Reset di modal juga
        if ($netto_kering_hari_ini > 0) {
             $umur = 0;
        }

        return response()->json([
            'stok_awal' => $stok_awal,
            'umur' => $umur,
            'netto_kering_hi' => $netto_kering_hari_ini,
            'stok_akhir' => $stok_awal + $netto_kering_hari_ini, 
        ]);
    }

    /**
     * Menampilkan detail LOG (data dari tabel 'pengolahan_maturasi').
     */
    public function show($id)
    {
        $log = PengolahanMaturasi::with('maturasi')->findOrFail($id);
        // Ubah data agar sesuai format modal detail Anda
        $data = $log->toArray();
        $data['uraian'] = $log->maturasi->uraian;
        $data['tgl_masuk'] = $log->tgl_masuk_log;
        $data['umur'] = $log->umur_log;
        $data['asal_bokar'] = $log->maturasi->asal_bokar;
        
        return response()->json($data);
    }

    /**
     * Mengambil data LOG untuk modal edit.
     */
    public function edit($id)
    {
        $log = PengolahanMaturasi::with('maturasi')->findOrFail($id);
        // Ubah data agar sesuai format modal edit Anda
        $data = $log->toArray();
        $data['uraian'] = $log->maturasi->uraian;
        $data['tgl_masuk'] = $log->tgl_masuk_log;
        $data['umur'] = $log->umur_log;
        $data['asal_bokar'] = $log->maturasi->asal_bokar;
        $data['tanggal_input'] = $log->tanggal_input->format('Y-m-d'); // Ganti nama field
        $data['created_at'] = $log->created_at->format('Y-m-d'); // Untuk JS Anda
        
        return response()->json($data);
    }

    /**
     * Update data LOG.
     * INI PALING PENTING: Harus update log & update master.
     */
    public function update(Request $request, $id)
    {
        // $id di sini adalah ID dari tabel LOG 'pengolahan_maturasi'
        $log = PengolahanMaturasi::with('maturasi')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'tgl_masuk' => 'nullable|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255', // Ini akan update master
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('maturasi.index', ['filter_tanggal' => $log->tanggal_input->format('Y-m-d')])
                             ->withErrors($validator)
                             ->withInput()
                             ->with(['edit_error' => true, 'edit_id' => $id]);
        }

        $data = $validator->validated();
        $tanggalInputLog = Carbon::parse($data['tanggal_input']);

        // Jika user mengedit log, kita harus mengunci master bak terkait
        DB::beginTransaction();
        try {
            $masterBak = $log->maturasi()->lockForUpdate()->first();
            
            // Hitung stok akhir baru untuk LOG INI
            $stok_akhir_log = ($data['stok_awal'] ?? 0)
                             - ($data['diolah'] ?? 0)
                             - ($data['mutasi'] ?? 0)
                             + ($data['masuk_hi'] ?? 0);

            // --- AWAL LOGIKA KETERANGAN BARU ---
            // --- [PERBAIKAN LOGIKA KETERANGAN] ---
            // Tgl Masuk Stok dan Keterangan harus dihitung ulang berdasarkan data yg diedit
            $tgl_masuk_log = $data['tgl_masuk'] ? Carbon::parse($data['tgl_masuk']) : null;
            $keterangan_final = $tgl_masuk_log ? $tgl_masuk_log->isoFormat('D MMMM YYYY') : null;
            // --- [AKHIR PERBAIKAN] ---

            // Update log-nya
            $log->update([
                'tanggal_input' => $tanggalInputLog,
                'stok_awal' => $data['stok_awal'],
                'diolah' => $data['diolah'],
                'mutasi' => $data['mutasi'],
                'masuk_hi' => $data['masuk_hi'],
                'stok_akhir' => $stok_akhir_log,
                'keterangan' => $keterangan_final, // <-- Ganti $data['keterangan']
                'tgl_masuk_log' => $data['tgl_masuk'],
                'umur_log' => $data['umur'],
                'updated_at' => now()
            ]);
            
            // Update data master
            $masterBak->update([
                'asal_bokar' => $data['asal_bokar'],
                'keterangan' => $keterangan_final,
            ]);

            // PENTING: Hitung ulang state master
            $this->_recalculateMasterState($masterBak);

            DB::commit();
            
            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalInputLog->format('Y-m-d')])
                             ->with('success', 'Data log berhasil diperbarui. Stok master telah dihitung ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->withErrors(['error' => 'Gagal update log: ' . $e->getMessage()])
                             ->withInput();
        }
    }

    /**
     * Hapus data LOG.
     * Ini juga harus memicu perhitungan ulang master.
     */
    public function destroy($id)
    {
        // $id di sini adalah ID dari tabel LOG 'pengolahan_maturasi'
        DB::beginTransaction();
        try {
            $log = PengolahanMaturasi::with('maturasi')->findOrFail($id);
            $masterBak = $log->maturasi()->lockForUpdate()->first();
            $tanggal_input = $log->tanggal_input->format('Y-m-d');

            // Hapus log
            $log->delete();
            
            // Hitung ulang state master
            $this->_recalculateMasterState($masterBak);

            DB::commit();

            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggal_input])
                             ->with('success', 'Data log berhasil dihapus. Stok master telah dihitung ulang.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->withErrors(['error' => 'Gagal menghapus log: ' . $e->getMessage()]);
        }
    }

    /**
     * Fungsi private untuk menghitung ulang state master berdasarkan log terakhir.
     */
    private function _recalculateMasterState(Maturasi $masterBak)
    {
        // Cari log TERAKHIR untuk bak ini
        $latestLog = PengolahanMaturasi::where('maturasi_id', $masterBak->id)
                        ->orderBy('tanggal_input', 'desc')
                        ->orderBy('id', 'desc') // Jika ada tanggal yg sama
                        ->first();

        if ($latestLog) {
            // Jika ada log, update master berdasarkan log terakhir
            
            // Tentukan tgl_masuk master
           // --- [PERBAIKAN LOGIKA TGL MASUK MASTER] ---
            // Tentukan tgl_masuk master
            $tgl_masuk_master_baru = $latestLog->tgl_masuk_log; // 1. Ambil dari log terakhir
            if ($latestLog->stok_akhir <= 0) {
                $tgl_masuk_master_baru = null; // 2. Reset jika stok habis
            }
            // --- [AKHIR PERBAIKAN LOGIKA TGL MASUK MASTER] ---

            // --- [PERBAIKAN 3]: Tambahkan logika Keterangan di sini ---
            $keterangan_recalc = $latestLog->tgl_masuk_log ? $latestLog->tgl_masuk_log->isoFormat('D MMMM YYYY') : null;
            // --- AKHIR PERBAIKAN 3 ---
            
            $masterBak->update([
                'stok_akhir' => $latestLog->stok_akhir,
                'tgl_masuk'  => $tgl_masuk_master_baru,
                'keterangan' => $keterangan_recalc // <-- TAMBAHKAN INI
            ]);
        } else {
            // Jika TIDAK ADA log sama sekali, reset master ke 0
            $masterBak->update([
                'stok_akhir' => 0,
                'tgl_masuk'  => null,
                'keterangan' => null // <-- TAMBAHKAN INI
            ]);
        }
    }
}