<?php
namespace App\Http\Controllers;

// ----- IMPORT SEMUA MODEL DAN CLASS YANG KITA BUTUHKAN -----
use App\Models\Maturasi;             // Model untuk tabel status 'maturasis'
use App\Models\PengolahanMaturasi;   // Model untuk tabel log 'pengolahan_maturasi'
use App\Models\PengolahanBasah;    // Model dari kode lama Anda (PENTING)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse; // PENTING: Untuk merespon AJAX
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection; // Kita butuh Collection
use Illuminate\Routing\Controller; // Pastikan ini di-import

class MaturasiController extends Controller
{
    /**
     * INI FUNGSI INDEX YANG BENAR
     * Dia akan menghitung status 49 Bak untuk TANGGAL MANAPUN
     * yang Anda pilih di filter header.
     */
    public function index(Request $request): View
    {
        // 1. Tentukan Tanggal yang Dipilih (DARI FILTER HEADER)
        $selectedDate = $request->input('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today(); // Default hari ini
        
        // 2. Ambil SEMUA 49 baris dari tabel status 'maturasis'.
        $data_maturasi_db = Maturasi::orderBy('id')->get();

        // 3. Ambil 'masuk_hi' PADA TANGGAL YANG DIPILIH
        $pengolahanBasahHariIni = PengolahanBasah::whereDate('tanggal', $selectedDate)
                                        ->select('bak_maturasi', DB::raw('SUM(netto_kering) as total_netto_kering'))
                                        ->groupBy('bak_maturasi')->get()->keyBy('bak_maturasi');
        // (Saya juga cek created_at untuk fallback)
        $pengolahanBasahHariIni_created = PengolahanBasah::whereDate('created_at', $selectedDate)
                                        ->select('bak_maturasi', DB::raw('SUM(netto_kering) as total_netto_kering'))
                                        ->groupBy('bak_maturasi')->get()->keyBy('bak_maturasi');
        $pengolahanBasahHariIni = $pengolahanBasahHariIni->union($pengolahanBasahHariIni_created);

        
        $dataTampilan = new Collection();
        $bak_aktif_list = [];

        // 4. Loop 49 Bak dan jalankan logika "Tutup Buku"
        foreach ($data_maturasi_db as $bak) {
            
            // Cek apakah data di DB adalah data PADA TANGGAL YANG DIPILIH
            if ($bak->updated_at->isSameDay($selectedDate)) {
                // LOGIKA 1: Data sudah di-input HARI ITU. Tampilkan apa adanya.
                $dataTampilan->push($bak);
                
            } else {
                // LOGIKA 2: Data di DB adalah data LAMA (sebelum tgl yang dipilih).
                // Kita harus hitung ulang secara virtual.
                
                $nomorBak = null;
                if (preg_match('/Di Bak Maturasi-(\d+)/', $bak->uraian, $matches)) { 
                    $nomorBak = "Bak Maturasi " . $matches[1];
                }
                
                $masuk_hi_hari_ini = 0;
                if ($nomorBak && isset($pengolahanBasahHariIni[$nomorBak])) {
                    $masuk_hi_hari_ini = $pengolahanBasahHariIni[$nomorBak]->total_netto_kering ?? 0;
                }

                // INI LOGIKA ANDA: "pindah di stock awal"
                $bak->stok_awal = $bak->stok_akhir; // Stok awal = Stok akhir terakhir
                
                $bak->diolah = 0;
                $bak->mutasi = 0;
                $bak->masuk_hi = $masuk_hi_hari_ini; // Set 'masuk_hi' baru
                $bak->stok_akhir = $bak->stok_awal - $bak->diolah - $bak->mutasi + $bak->masuk_hi;

                if ($bak->stok_akhir <= 0) {
                    $bak->umur = 0;
                    $bak->tgl_masuk = null;
                    $bak->keterangan = "KOSONG";
                } elseif ($masuk_hi_hari_ini > 0 && $bak->stok_awal <= 0) {
                    $bak->umur = 0;
                    $bak->tgl_masuk = $selectedDate;
                    $bak->keterangan = $selectedDate->isoFormat('D MMMM YYYY');
                } elseif ($bak->tgl_masuk) {
                    // PERBAIKAN BUG UMUR (tidak bisa -1)
                    $umur = Carbon::parse($bak->tgl_masuk)->diffInDays($selectedDate, false); // false = bisa negatif
                    $bak->umur = $umur < 0 ? 0 : $umur; // Jika negatif, jadikan 0
                    // $bak->keterangan (biarkan keterangan lama dari DB)
                }

                // Palsukan tgl update agar konsisten
                $bak->updated_at = $selectedDate; 
                
                $dataTampilan->push($bak);
            }
            
            // Cek bak mana yang "aktif"
            if ($bak->stok_akhir > 0) {
                 $bak_aktif_list[] = $bak->uraian;
            }
        }
        
        // 6. Kirim data yang sudah di-PROSES ke view
        return view('Pengolahan.data_maturasi', [
            'data_maturasi' => $dataTampilan,
            'bak_aktif_list' => $bak_aktif_list,
            'selected_date' => $selectedDate->format('Y-m-d') // Kirim TANGGAL YANG DIPILIH
        ]);
    }

    /**
     * FUNGSI UNTUK AJAX 'getPreviousData'
     */
    public function getPreviousData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'tanggal_filter' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) { return response()->json(['error' => 'Input tidak valid'], 400); }

        $uraian = $request->input('uraian');
        $tanggalFilter = Carbon::parse($request->input('tanggal_filter'));

        $maturasi = Maturasi::where('uraian', $uraian)->first();
        if (!$maturasi) { return response()->json(['error' => 'Data Bak tidak ditemukan'], 404); }

        $stok_awal = 0;
        $umur = 0;

        // LOGIKA BARU: Cek data di DB vs Tanggal Filter
        if ($maturasi->updated_at->isSameDay($tanggalFilter)) {
             $stok_awal = $maturasi->stok_awal;
        } else {
             $stok_awal = $maturasi->stok_akhir;
        }
        
        if ($maturasi->tgl_masuk && $stok_awal > 0) {
            $umur = Carbon::parse($maturasi->tgl_masuk)->diffInDays($tanggalFilter, false);
            $umur = $umur < 0 ? 0 : $umur; // tidak boleh negatif
        }

        // Logika Pengolahan Basah
        $nomorBak = null;
        if (preg_match('/Di Bak Maturasi-(\d+)/', $uraian, $matches)) { $nomorBak = "Bak Maturasi " . $matches[1]; }
        $netto_kering_hari_ini = 0;
        if ($nomorBak) {
            $netto_kering_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                ->whereDate('tanggal', $tanggalFilter)->sum('netto_kering');
            if ($netto_kering_hari_ini == 0) {
                $netto_kering_hari_ini = PengolahanBasah::where('bak_maturasi', $nomorBak)
                    ->whereDate('created_at', $tanggalFilter)->sum('netto_kering');
            }
        }
        
        if($stok_awal == 0 && $netto_kering_hari_ini > 0) { $umur = 0; }

        return response()->json([
            'stok_awal' => $stok_awal, 'umur' => $umur,
            'netto_kering_hi' => $netto_kering_hari_ini, 
            'stok_akhir' => $stok_awal + $netto_kering_hari_ini, 
        ]);
    }

    /**
     * FUNGSI STORE
     */
    public function store(Request $request): RedirectResponse
    {
        $cleanNumber = function ($value) { if (empty($value)) return 0; return str_replace(',', '.', str_replace('.', '', $value)); };
        $request->merge([ 'stok_awal' => $cleanNumber($request->input('stok_awal')), 'masuk_hi'  => $cleanNumber($request->input('masuk_hi')), 'diolah'    => $cleanNumber($request->input('diolah')), 'mutasi'    => $cleanNumber($request->input('mutasi')), ]);
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasis,uraian', 'stok_awal' => 'required|numeric|min:0', 'umur' => 'required|integer|min:0', 'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0', 'masuk_hi' => 'nullable|numeric|min:0', 'asal_bokar' => 'nullable|string|max:255', 'keterangan' => 'nullable|string|max:255', 'tanggal_input_harian' => 'required|date',
        ]);
        if ($validator->fails()) { return redirect()->back()->withErrors($validator)->withInput(); }
        $data = $validator->validated();
        $tanggalInput = Carbon::parse($data['tanggal_input_harian']);
        $maturasi = Maturasi::where('uraian', $data['uraian'])->firstOrFail();
        $tgl_masuk_stok = $maturasi->tgl_masuk;
        if ($data['stok_awal'] <= 0 && $data['masuk_hi'] > 0) { $tgl_masuk_stok = $tanggalInput; }
        $stok_akhir_baru = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);
        if ($stok_akhir_baru <= 0) { $tgl_masuk_stok = null; }
        PengolahanMaturasi::create([ 'maturasi_id' => $maturasi->id, 'tgl_laporan' => $tanggalInput, 'diolah' => $data['diolah'], 'mutasi' => $data['mutasi'], 'masuk_hi' => $data['masuk_hi'], 'keterangan' => $data['keterangan'], ]);
        $maturasi->update([ 'stok_awal'  => $data['stok_awal'], 'tgl_masuk'  => $tgl_masuk_stok, 'umur' => $data['umur'], 'diolah'     => $data['diolah'], 'mutasi'     => $data['mutasi'], 'masuk_hi'   => $data['masuk_hi'], 'stok_akhir' => $stok_akhir_baru, 'asal_bokar' => $data['asal_bokar'], 'keterangan' => $data['keterangan'], 'updated_at' => $tanggalInput ]);
        
        // --- PERBAIKAN BUG REDIRECT ---
        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tanggalInput->format('Y-m-d') // Redirect kembali ke tanggal yang di-input
        ])->with('success', 'Data ' . $maturasi->uraian . ' berhasil ditambahkan.');
    }
    
    public function show(Maturasi $maturasi): JsonResponse { return response()->json($maturasi); }
    public function edit(Maturasi $maturasi): JsonResponse { return response()->json($maturasi); }

    public function update(Request $request, Maturasi $maturasi): RedirectResponse
    {
        $cleanNumber = function ($value) { if (empty($value)) return 0; return str_replace(',', '.', str_replace('.', '', $value)); };
        $request->merge([ 'stok_awal' => $cleanNumber($request->input('stok_awal')), 'masuk_hi'  => $cleanNumber($request->input('masuk_hi')), 'diolah'    => $cleanNumber($request->input('diolah')), 'mutasi'    => $cleanNumber($request->input('mutasi')), ]);
        $validator = Validator::make($request->all(), [ 'stok_awal' => 'required|numeric|min:0', 'umur' => 'required|integer|min:0', 'tgl_masuk' => 'nullable|date', 'diolah' => 'nullable|numeric|min:0', 'mutasi' => 'nullable|numeric|min:0', 'masuk_hi' => 'nullable|numeric|min:0', 'asal_bokar' => 'nullable|string|max:255', 'keterangan' => 'nullable|string|max:255', 'tanggal_input' => 'required|date', ]);
        $tanggalInput = Carbon::parse($request->tanggal_input);
        if ($validator->fails()) { return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalInput->format('Y-m-d')])->withErrors($validator)->withInput()->with(['edit_error' => true, 'edit_id' => $maturasi->id]); }
        $data = $validator->validated();
        $stok_akhir_baru = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);
        $tgl_masuk_stok = $data['tgl_masuk'];
        if ($data['stok_awal'] <= 0 && $data['masuk_hi'] > 0) { $tgl_masuk_stok = $tanggalInput; }
        if ($stok_akhir_baru <= 0) { $tgl_masuk_stok = null; }
        PengolahanMaturasi::create([ 'maturasi_id' => $maturasi->id, 'tgl_laporan' => $tanggalInput, 'diolah' => $data['diolah'], 'mutasi' => $data['mutasi'], 'masuk_hi' => $data['masuk_hi'], 'keterangan' => $data['keterangan'], ]);
        $maturasi->update([ 'stok_awal'  => $data['stok_awal'], 'tgl_masuk'  => $tgl_masuk_stok, 'umur' => $data['umur'], 'diolah' => $data['diolah'], 'mutasi' => $data['mutasi'], 'masuk_hi'   => $data['masuk_hi'], 'stok_akhir' => $stok_akhir_baru, 'asal_bokar' => $data['asal_bokar'], 'keterangan' => $data['keterangan'], 'updated_at' => $tanggalInput ]);
        
        // --- PERBAIKAN BUG REDIRECT ---
        return redirect()->route('maturasi.index', [
            'filter_tanggal' => $tanggalInput->format('Y-m-d') // Redirect kembali ke tanggal yang di-edit
        ])->with('success', 'Data ' . $maturasi->uraian . ' berhasil diperbarui.');
    }
    
    public function reset(Maturasi $maturasi): RedirectResponse
    {
        $maturasi->riwayatPengolahan()->delete();
        $maturasi->update([ 'stok_awal'  => 0, 'tgl_masuk'  => null, 'umur' => 0, 'diolah' => 0, 'mutasi' => 0, 'masuk_hi'   => 0, 'stok_akhir' => 0, 'asal_bokar' => null, 'keterangan' => 'KOSONG', 'updated_at' => Carbon::now() ]);
        // Redirect kembali ke tanggal HARI INI setelah reset
        return redirect()->route('maturasi.index', ['filter_tanggal' => Carbon::today()->format('Y-m-d')])->with('success', 'Data ' . $maturasi->uraian . ' telah di-reset.');
    }
    
    public function destroy(Maturasi $maturasi): RedirectResponse { return redirect()->route('maturasi.index')->with('error', 'Fungsi hapus tidak diizinkan.'); }
}