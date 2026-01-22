<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;

class MaturasiApiController extends Controller
{
    // =========================================================================
    // HELPER 1: HITUNGAN SNAPSHOT (COPY DARI WEB ADMIN)
    // =========================================================================

    protected function getNetBeforeDate(int $id_maturasi, Carbon $date): float
    {
        $sums = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->select(
                DB::raw('COALESCE(SUM(masuk_hi),0) as sum_masuk'),
                DB::raw('COALESCE(SUM(diolah),0) as sum_diolah'),
                DB::raw('COALESCE(SUM(mutasi),0) as sum_mutasi')
            )->first();

        if (!$sums) return 0;
        
        $hasil = $sums->sum_masuk - $sums->sum_diolah - $sums->sum_mutasi;
        
        // 🔥 PERBAIKAN 1: Bulatkan ke 2 desimal agar tidak ada sisa -0.00001
        return round((float) $hasil, 2);
    }

    protected function getSumsOnDate(int $id_maturasi, Carbon $date): array
    {
        $s = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', $date->toDateString())
            ->select(
                DB::raw('COALESCE(SUM(masuk_hi),0) as masuk_hi'),
                DB::raw('COALESCE(SUM(diolah),0) as diolah'),
                DB::raw('COALESCE(SUM(mutasi),0) as mutasi')
            )->first();

        return [
            'masuk_hi' => (float)($s->masuk_hi ?? 0),
            'diolah'   => (float)($s->diolah ?? 0),
            'mutasi'   => (float)($s->mutasi ?? 0),
        ];
    }

    protected function getLastMasukHiDateBefore(int $id_maturasi, Carbon $date)
    {
        $log = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $date->toDateString())
            ->where('masuk_hi', '>', 0)
            ->orderBy('tgl_laporan', 'desc')
            ->first();

        return $log ? Carbon::parse($log->tgl_laporan) : null;
    }

    protected function hasAnyLogUpToDate(int $id_maturasi, Carbon $date): bool
    {
        return PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<=', $date->toDateString())
            ->exists();
    }

    protected function hitungSnapshot(Maturasi $maturasi, Carbon $selectedDate): array
    {
        if (! $this->hasAnyLogUpToDate($maturasi->id_maturasi, $selectedDate)) {
            return [
                'stok_awal' => 0, 'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
                'stok_akhir' => 0, 'tgl_masuk' => null, 'umur' => 0, 'keterangan' => '-'
            ];
        }

        // 1. Stok Awal & Transaksi Hari Ini
        $stok_awal = $this->getNetBeforeDate($maturasi->id_maturasi, $selectedDate);
        $transaksi = $this->getSumsOnDate($maturasi->id_maturasi, $selectedDate);
        
        // 2. Cek Masuk dari Uji Bokar (Backup)
        $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', $selectedDate)
            ->sum('netto_kering');

        $masuk_hi_today = max($transaksi['masuk_hi'], $masuk_from_uji);

        // 3. Hitung Stok Akhir
        $stok_akhir = $stok_awal + $masuk_hi_today - $transaksi['diolah'] - $transaksi['mutasi'];

        // 🔥 PERBAIKAN 2: Bulatkan hasil akhir ke 2 desimal
        $stok_akhir = round($stok_akhir, 2);

        // 🔥 PERBAIKAN 3: Cegah Minus (Jika kebijakan perusahaan stok tidak boleh minus)
        // Web Admin biasanya melakukan ini di view atau logic controller.
        if ($stok_akhir < 0) {
            $stok_akhir = 0;
        }

        // 4. Logika Tanggal Masuk & Umur
        $tgl_masuk = null;
        $umur = 0;
        $keterangan = '-';

        if ($stok_akhir > 0) {
            if ($masuk_hi_today > 0) {
                $tgl_masuk = $selectedDate->toDateString();
                $umur = 0;
                $keterangan = strtoupper($selectedDate->format('d M Y'));
            } else {
                $last_before = $this->getLastMasukHiDateBefore($maturasi->id_maturasi, $selectedDate);
                
                if ($last_before) {
                    $tgl_masuk = $last_before->toDateString();
                    $umur = $last_before->diffInDays($selectedDate);
                    $keterangan = strtoupper($last_before->format('d M Y'));
                } elseif ($maturasi->tgl_masuk) {
                    try {
                        $candidate = Carbon::parse($maturasi->tgl_masuk);
                        if ($candidate->lte($selectedDate)) {
                            $tgl_masuk = $candidate->toDateString();
                            $umur = $candidate->diffInDays($selectedDate);
                            $keterangan = strtoupper($candidate->format('d M Y'));
                        }
                    } catch (\Exception $e) {}
                }
            }
        }

        return [
            'stok_awal'  => round($stok_awal, 2),  // Pastikan dibulatkan
            'diolah'     => round($transaksi['diolah'], 2),
            'mutasi'     => round($transaksi['mutasi'], 2),
            'masuk_hi'   => round($masuk_hi_today, 2),
            'stok_akhir' => $stok_akhir, // Sudah dibulatkan di atas
            'tgl_masuk'  => $tgl_masuk,
            'umur'       => $umur,
            'keterangan' => $keterangan,
        ];
    }

    private function getDetailedAsalBokarString(Maturasi $maturasi, float $stokAkhir, Carbon $filterDate)
    {
        if ($stokAkhir <= 0) return '-';

        $defaultAsal = $maturasi->asal_bokar ?? '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = $stokAkhir;

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk = $log->masuk_hi;
                $keluar = $log->diolah + $log->mutasi;
                
                $prevStock = $currentTracingStock - $masuk + $keluar;
                
                if ($prevStock <= 0.01) { 
                    break; 
                }
                $currentTracingStock = $prevStock;
            }

            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function ($item) { return strtoupper(trim($item)); })
                ->unique()
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            if (!empty($jenisList)) {
                if (count($jenisList) > 1) {
                    return 'CMP (' . implode(', ', $jenisList) . ')';
                } else {
                    return $jenisList[0];
                }
            }

            return $defaultAsal;

        } catch (\Exception $e) {
            return $defaultAsal;
        }
    }


    // =========================================================================
    // ENDPOINT API (SAMA SEPERTI ANDROID MINTA)
    // =========================================================================

    /**
     * [LIST TABLE] Mengambil status semua bak (Snapshot Harian)
     */
    public function index(Request $request)
    {
        try {
            // Default hari ini jika tidak ada filter
            $selectedDate = $request->has('date') ? Carbon::parse($request->query('date')) : Carbon::today();
            
            $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
            $dataTampilan = [];

            foreach ($data_maturasi_db as $bak) {
                // 1. Hitung Snapshot (LOGIKA WEB)
                $snap = $this->hitungSnapshot($bak, $selectedDate);
                
                // Masukkan hasil snapshot ke object
                $row = $bak->toArray();
                $row['stok_awal']  = $snap['stok_awal'];
                $row['diolah']     = $snap['diolah'];
                $row['mutasi']     = $snap['mutasi'];
                $row['masuk_hi']   = $snap['masuk_hi'];
                $row['stok_akhir'] = $snap['stok_akhir'];
                $row['tgl_masuk']  = $snap['tgl_masuk'];
                $row['umur']       = $snap['umur'];
                $row['keterangan'] = $snap['keterangan'];

                // 2. Ambil K3 Masuk & Olah
                $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tanggal', '<=', $selectedDate)
                    ->orderBy('tanggal', 'desc')->first();
                
                $row['k3_olah'] = $ujiLab ? $ujiLab->k3 : null;
                $row['po']      = $ujiLab ? $ujiLab->po : null;
                $row['pri']     = $ujiLab ? $ujiLab->pri : null;
                
                // ID Hasil Uji untuk keperluan Edit/Hapus di Android
                $row['id_hasil_uji_maturasi'] = $ujiLab ? $ujiLab->id : null; 

                // 3. Logika Asal Bokar (CMP)
                $row['asal_bokar'] = $this->getDetailedAsalBokarString($bak, $snap['stok_akhir'], $selectedDate);

                if ($snap['stok_akhir'] <= 0) {
                    $row['asal_bokar'] = '-';
                }

                $dataTampilan[] = $row;
            }

            return response()->json(['success' => true, 'data' => $dataTampilan]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [SPINNER] Mengambil daftar Bak yang Stoknya > 0 (Untuk Produksi & Olah)
     */
    public function getListAvailable()
    {
        // Kita gunakan logika snapshot hari ini untuk menentukan ketersediaan
        $today = Carbon::today();
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        $available = [];

        foreach ($data_maturasi_db as $bak) {
            $snap = $this->hitungSnapshot($bak, $today);
            if ($snap['stok_akhir'] > 0) {
                // Masukkan data snapshot ke objek agar Android dapat info stok terkini
                $bak->stok_akhir = $snap['stok_akhir'];
                $bak->tgl_masuk  = $snap['tgl_masuk'];
                $bak->umur       = $snap['umur'];
                $available[] = $bak;
            }
        }
        
        return response()->json(['success' => true, 'data' => $available]);
    }

    /**
     * [STORE] Simpan Input Harian (Mutasi / Diolah Manual)
     */
    public function store(Request $request)
    {
        $clean = fn($v) => $v ? str_replace(',', '.', str_replace('.', '', $v)) : 0;
        
        $request->merge([
            'mutasi' => $clean($request->input('mutasi')),
            // 'diolah' dari input Android juga dibersihkan
            'diolah' => $clean($request->input('diolah')),
        ]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian',
            'tanggal_input_harian' => 'required|date', // Sesuai Android: tanggal_input_harian
            'mutasi' => 'nullable|numeric|min:0',
            'diolah' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) return response()->json(['success'=>false, 'errors'=>$validator->errors()], 422);

        try {
            $tglInput = Carbon::parse($request->tanggal_input_harian);
            $maturasi = Maturasi::where('uraian', $request->uraian)->firstOrFail();

            // 1. Ambil Data Lama (Agar nilai yang tidak diinput tidak hilang)
            $existingLog = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                            ->whereDate('tgl_laporan', $tglInput)
                            ->first();
            
            // Jika Android mengirim 0, kita pakai nilai lama (kecuali user sengaja nol-kan? Asumsi: Android kirim apa adanya)
            // TAPI, logic di Web: 'diolah' itu readonly dari produksi.
            // Jika Android boleh edit 'diolah', pakai input Android. Jika tidak, pakai existing.
            
            // Skenario: Android form OlahMaturasi hanya untuk Mutasi (Diolah otomatis).
            // Maka kita prioritaskan existing 'diolah'.
            $diolahFinal = $existingLog ? $existingLog->diolah : 0;
            
            // Jika user mengisi 'diolah' di Android (bukan 0), kita bisa override (opsional)
            if($request->input('diolah') > 0) {
                 $diolahFinal = $request->input('diolah');
            }

            // 2. Simpan Data
            PengolahanMaturasi::updateOrCreate(
                [
                    'id_maturasi' => $maturasi->id_maturasi,
                    'tgl_laporan' => $tglInput,
                ],
                [
                    'diolah'     => $diolahFinal,
                    'mutasi'     => $request->input('mutasi'),
                    'keterangan' => $request->input('keterangan') ?? ($existingLog->keterangan ?? 'Input Android')
                ]
            );

            // 3. Update Master Stok (Snapshot)
            $snap = $this->hitungSnapshot($maturasi, $tglInput);
            
            $maturasi->update([
                'stok_awal'  => $snap['stok_awal'],
                'diolah'     => $snap['diolah'],
                'mutasi'     => $snap['mutasi'],
                'masuk_hi'   => $snap['masuk_hi'],
                'stok_akhir' => $snap['stok_akhir'],
                'tgl_masuk'  => $snap['tgl_masuk'],
                'umur'       => $snap['umur'],
                'updated_at' => $tglInput 
            ]);

            return response()->json(['success' => true, 'message' => 'Data Maturasi disimpan.']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [DROPDOWN ALL] Mengambil SEMUA daftar Bak (Untuk Timbang Bokar / Input Barang Masuk)
     * Tidak peduli stok ada atau nol.
     */
    public function getAll()
    {
        // Ambil id dan uraian saja biar ringan
        $data = Maturasi::select('id_maturasi', 'uraian')
            ->orderBy('id_maturasi') // atau orderBy('uraian')
            ->get();
            
        return response()->json(['success' => true, 'data' => $data]);
    }
}