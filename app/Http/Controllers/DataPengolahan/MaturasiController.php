<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah; 
use App\Models\HasilUjiLabMaturasi;    
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MaturasiController extends Controller
{
    // =========================================================================
    // HELPER 1: HITUNGAN STOK & SNAPSHOT
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
        return (float) ($sums->sum_masuk - $sums->sum_diolah - $sums->sum_mutasi);
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

    protected function hitungSnapshot($maturasi, Carbon $selectedDate): array
    {
        // PK: id_maturasi
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

        // 4. Logika Tanggal Masuk & Umur
        $tgl_masuk = null;
        $umur = 0;
        $keterangan = '-';

        if ($stok_akhir > 0) {
            
            // A. Cari dulu history tanggal masuk SEBELUM hari ini
            $last_before = $this->getLastMasukHiDateBefore($maturasi->id_maturasi, $selectedDate);

            if ($last_before) {
                // PRIORITAS 1: Jika ada history masa lalu (batch berjalan).
                // Gunakan tanggal tersebut agar umur TIDAK reset jadi 0 hari ini.
                // (Meskipun hari ini ada penambahan stok baru).
                $tgl_masuk = $last_before->toDateString();
                $umur = $last_before->diffInDays($selectedDate);
                $keterangan = strtoupper($last_before->format('d M Y'));

            } elseif ($masuk_hi_today > 0) {
                // PRIORITAS 2: Jika history kosong (bak baru dipakai), DAN hari ini ada isi.
                // Baru kita set umur jadi 0 (Batch Baru Start Hari Ini).
                $tgl_masuk = $selectedDate->toDateString();
                $umur = 0;
                $keterangan = strtoupper($selectedDate->format('d M Y'));

            } elseif ($maturasi->tgl_masuk) {
                // PRIORITAS 3: Fallback ke data master manual jika tidak ada log transaksi sama sekali
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

        return [
            'stok_awal'  => $stok_awal,
            'diolah'     => $transaksi['diolah'],
            'mutasi'     => $transaksi['mutasi'],
            'masuk_hi'   => $masuk_hi_today,
            'stok_akhir' => $stok_akhir,
            'tgl_masuk'  => $tgl_masuk,
            'umur'       => $umur,
            'keterangan' => $keterangan,
        ];
    }

    // =========================================================================
    // HELPER 2: LOGIKA "ASAL BOKAR" (CMP DETAILED)
    // =========================================================================

    private function getDetailedAsalBokarString($maturasi, float $stokAkhir, Carbon $filterDate)
    {
        if ($stokAkhir <= 0) return '-';

        /**
         * 🔥 PRIORITAS 1
         * Kalau bak ini sudah ditandai batch baru
         * (asal_bokar & tgl_masuk di-set manual)
         * → JANGAN tracing CMP
         */
        if (!empty($maturasi->asal_bokar) && !empty($maturasi->tgl_masuk)) {
            $tglMasuk = Carbon::parse($maturasi->tgl_masuk);

            if ($tglMasuk->lte($filterDate)) {
                return strtoupper($maturasi->asal_bokar);
            }
        }

        // ===============================
        // BARU tracing CMP (kode lama)
        // ===============================
        try {
            $realBatchStartDate = $filterDate->copy();

            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = $stokAkhir;

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);

                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + $log->mutasi;

                $prevStock = $currentTracingStock - $masuk + $keluar;

                if ($prevStock <= 0.01) break;

                $currentTracingStock = $prevStock;
            }

            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(fn($v) => strtoupper(trim($v)))
                ->unique()
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1
                    ? 'CMP (' . implode(', ', $jenisList) . ')'
                    : $jenisList[0];
            }

            return $maturasi->asal_bokar ?? '-';

        } catch (\Exception $e) {
            return $maturasi->asal_bokar ?? '-';
        }
    }

    // =========================================================================
    // FUNGSI UTAMA (GET DATA UNTUK VIEW)
    // =========================================================================
    
    private function getMaturasiData($selectedDate)
    {
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        
        $dataTampilan = new Collection();
        $bak_aktif_list = [];

        foreach ($data_maturasi_db as $bak) {
            // 1. Hitung Snapshot
            $snap = $this->hitungSnapshot($bak, $selectedDate);
            $row = clone $bak;
            foreach ($snap as $k => $v) $row->$k = $v;
            $row->updated_at = $selectedDate;

            // 2. Ambil K3 Masuk & Olah
            $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tanggal', '<=', $selectedDate)
                ->orderBy('tanggal', 'desc')->first();
            $row->k3_masuk = $ujiMasuk ? $ujiMasuk->k3 : 0;

            $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tanggal', '<=', $selectedDate)
                ->orderBy('tanggal', 'desc')->first();
            $row->k3_olah = $ujiLab->k3 ?? 0;
            $row->po      = $ujiLab->po ?? 0;
            $row->pri     = $ujiLab->pri ?? 0;
            $row->tgl_uji = $ujiLab->tanggal ?? null;

            // 3. Logika Asal Bokar
           $bakFresh = Maturasi::find($bak->id_maturasi);

            $row->asal_bokar = $this->getDetailedAsalBokarString(
                $bakFresh,
                $row->stok_akhir,
                $selectedDate
            );
            if ($row->stok_akhir <= 0) {
                $row->asal_bokar = '-';
            }

            $dataTampilan->push($row);

            if ($row->stok_akhir > 0) {
                $bak_aktif_list[] = $row->uraian; 
            }
        }

        // Footer Summary
        $footer_data = [
            'total_stok_awal'  => $dataTampilan->sum('stok_awal'),
            'total_diolah'     => $dataTampilan->sum('diolah'),
            'total_mutasi'     => $dataTampilan->sum('mutasi'),
            'total_masuk_hi'   => $dataTampilan->sum('masuk_hi'),
            'total_stok_akhir' => $dataTampilan->sum('stok_akhir'),
            'maturasi_diolah_sd_kemarin'  => PengolahanMaturasi::whereDate('tgl_laporan', '<', $selectedDate)->sum('diolah'),
            'maturasi_diolah_hari_ini'    => $dataTampilan->sum('diolah'),
            'maturasi_diolah_sd_hari_ini' => PengolahanMaturasi::whereDate('tgl_laporan', '<=', $selectedDate)->sum('diolah'),
        ];

        return [
            'data_maturasi'  => $dataTampilan,
            'bak_aktif_list' => $bak_aktif_list,
            'selected_date'  => $selectedDate->format('Y-m-d'),
            'footer_data'    => $footer_data
        ];
    }

    public function index(Request $request): View
    {
        $selectedDate = $request->has('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        $data = $this->getMaturasiData($selectedDate);

        return view('DataPengolahan.data-maturasi', $data);
    }

    public function cetakPdf(Request $request)
    {
        $selectedDate = $request->has('filter_tanggal')
            ? Carbon::parse($request->input('filter_tanggal'))
            : Carbon::today();

        $data = $this->getMaturasiData($selectedDate);

        $pdf = Pdf::loadView('Cetak.cetak-maturasi', $data);
        $pdf->setPaper('a4', 'landscape'); 

        return $pdf->stream('Laporan-Maturasi-' . $selectedDate->format('d-m-Y') . '.pdf');
    }

    // --- FUNGSI STORE ---
    // =========================================================================
    // 🔥 PERBAIKAN LOGIKA STORE (SIMPAN MANUAL MUTASI)
    // =========================================================================
    public function store(Request $request): RedirectResponse
    {
        $clean = fn($v) => $v ? str_replace(',', '.', str_replace('.', '', $v)) : 0;

        $request->merge([
            'mutasi' => $clean($request->input('mutasi')),
        ]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian',
            'tanggal_input_harian' => 'required|date',
            'mutasi' => 'nullable|numeric|min:0',
            'tujuan_mutasi' => 'nullable|exists:maturasi,id_maturasi',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $tglInput  = Carbon::parse($request->input('tanggal_input_harian'));
        $mutasiVal = (float) $request->input('mutasi');

        $bakAsal = Maturasi::where('uraian', $request->input('uraian'))->firstOrFail();

        DB::beginTransaction();
        try {

            // ===============================
            // 1️⃣ HITUNG SNAPSHOT BAK ASAL
            // ===============================
            $snapAsalSebelum = $this->hitungSnapshot($bakAsal, $tglInput);

            // STOK BASIS UNTUK MUTASI
            $stokBasisMutasi =
                $snapAsalSebelum['stok_awal']
                + $snapAsalSebelum['masuk_hi']
                - $snapAsalSebelum['diolah'];

            // ===============================
            // 2️⃣ SIMPAN MUTASI BAK ASAL
            // ===============================
            $existingAsal = PengolahanMaturasi::where('id_maturasi', $bakAsal->id_maturasi)
                ->whereDate('tgl_laporan', $tglInput)
                ->first();

            PengolahanMaturasi::updateOrCreate(
                [
                    'id_maturasi' => $bakAsal->id_maturasi,
                    'tgl_laporan' => $tglInput
                ],
                [
                    'diolah'     => $existingAsal->diolah ?? 0,
                    'mutasi'     => ($existingAsal->mutasi ?? 0) + $mutasiVal,
                    'keterangan' => $request->keterangan ?? 'Mutasi Keluar'
                ]
            );

            // ===============================
            // 3️⃣ PROSES BAK TUJUAN
            // ===============================
            if ($request->filled('tujuan_mutasi') && $mutasiVal > 0) {

                $bakTujuan = Maturasi::findOrFail($request->tujuan_mutasi);

                $existingTujuan = PengolahanMaturasi::where('id_maturasi', $bakTujuan->id_maturasi)
                    ->whereDate('tgl_laporan', $tglInput)
                    ->first();

                // Mutasi masuk dicatat sebagai nilai negatif agar menambah stok di rumus (Total - Mutasi)
                PengolahanMaturasi::updateOrCreate(
                    [
                        'id_maturasi' => $bakTujuan->id_maturasi,
                        'tgl_laporan' => $tglInput
                    ],
                    [
                        'mutasi'     => ($existingTujuan->mutasi ?? 0) - $mutasiVal,
                        'keterangan' => 'Terima Mutasi dari ' . $bakAsal->uraian
                    ]
                );

                // LOGIKA UPDATE IDENTITAS
                if ($stokBasisMutasi > 0 && ($mutasiVal / $stokBasisMutasi) >= 0.5) {

                    $asalBokarFinal = $this->getDetailedAsalBokarString(
                        $bakAsal,
                        $stokBasisMutasi,
                        $tglInput
                    );

                    // ✅ UPDATE IDENTITAS (ASAL BOKAR) TANPA MENGHAPUS HISTORY
                    $bakTujuan->update([
                        'asal_bokar' => $asalBokarFinal,
                        'tgl_masuk'  => $tglInput,
                        'umur'       => 0,
                        'keterangan' => strtoupper($tglInput->format('d M Y')),
                    ]);

                    // ❌ BARIS ->delete() DI SINI SUDAH DIHAPUS AGAR SALDO AWAL TIDAK HILANG
                }

                // Refresh stok akhir tujuan agar data di tabel master sinkron
                $snapTujuan = $this->hitungSnapshot($bakTujuan, $tglInput);
                $bakTujuan->update([
                    'stok_akhir' => $snapTujuan['stok_akhir']
                ]);
            }

            // ===============================
            // 4️⃣ REFRESH MASTER BAK ASAL
            // ===============================
            $snapAsal = $this->hitungSnapshot($bakAsal, $tglInput);
            $bakAsal->update([
                'stok_awal'  => $snapAsal['stok_awal'],
                'diolah'     => $snapAsal['diolah'],
                'mutasi'     => $snapAsal['mutasi'],
                'masuk_hi'   => $snapAsal['masuk_hi'],
                'stok_akhir' => $snapAsal['stok_akhir'],
                'updated_at' => $tglInput
            ]);

            DB::commit();

            return redirect()
                ->route('maturasi.index', ['filter_tanggal' => $tglInput->format('Y-m-d')])
                ->with('success', 'Mutasi berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memproses mutasi: ' . $e->getMessage());
        }
    }
    
    // --- FUNGSI RESET ---
    public function reset($id_maturasi): RedirectResponse
    {
        $maturasi = Maturasi::findOrFail($id_maturasi);
        
        $maturasi->riwayatPengolahan()->delete();
        $maturasi->update([
            'stok_awal' => 0, 'tgl_masuk' => null, 'umur' => 0,
            'diolah' => 0, 'mutasi' => 0, 'masuk_hi' => 0,
            'stok_akhir' => 0, 'asal_bokar' => null, 'keterangan' => 'KOSONG',
        ]);
        return redirect()->back()->with('success', 'Data Bak telah di-reset total.');
    }
    
    // --- FUNGSI AJAX EDIT / DETAIL ---
    public function getPreviousData(Request $request): JsonResponse
    {
        $uraian = $request->input('uraian');
        $tgl = $request->input('tanggal_filter') ? Carbon::parse($request->input('tanggal_filter')) : Carbon::today();

        $maturasi = Maturasi::where('uraian', $uraian)->first();
        if (!$maturasi) return response()->json(['error' => 'Bak tidak ditemukan'], 404);

        $snap = $this->hitungSnapshot($maturasi, $tgl);
        
        // Data K3
        $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)
            ->latest('tanggal')->first();

        $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)
            ->latest('tanggal')->first();

        $asalBokarDetailed = $this->getDetailedAsalBokarString($maturasi, $snap['stok_akhir'], $tgl);

        return response()->json([
            'stok_awal' => $snap['stok_awal'],
            'umur'      => $snap['umur'],
            'diolah'    => $snap['diolah'],
            'mutasi'    => $snap['mutasi'],
            'masuk_hi'  => $snap['masuk_hi'],
            'stok_akhir'=> $snap['stok_akhir'],
            'tgl_masuk' => $snap['tgl_masuk'],
            
            'k3_masuk'  => $ujiMasuk->k3 ?? 0,
            'k3_olah'   => $ujiLab->k3 ?? 0,
            'po'        => $ujiLab->po ?? 0,
            'pri'       => $ujiLab->pri ?? 0,
            'tgl_uji'   => $ujiLab->tanggal ?? null,
            
            'asal_bokar'=> $asalBokarDetailed,
            'keterangan'=> $snap['keterangan'],
            'updated_at'=> $maturasi->updated_at,
        ]);
    }

    public function edit(Request $request, $id_maturasi): JsonResponse
    {
        $maturasi = Maturasi::find($id_maturasi);
        if (!$maturasi) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        $selectedDate = $request->has('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // 1. Ambil hitungan dasar (Stok Awal, Masuk, Keluar, Stok Akhir)
        $snap = $this->hitungSnapshot($maturasi, $selectedDate);

        /**
         * =====================================================
         * 🔥 LOGIKA IDENTITAS (PRIORITAS BATCH BARU / MUTASI)
         * =====================================================
         * Jika bak memiliki identitas statis (hasil mutasi besar),
         * maka umur dan keterangan dihitung dari tgl_masuk master.
         */
        $asalBokarDisp = '';
        $keteranganDisp = '';
        $umurDisp = 0;

        if (!empty($maturasi->asal_bokar) && !empty($maturasi->tgl_masuk)) {
            $tglMasukMaster = Carbon::parse($maturasi->tgl_masuk);

            // Jika tanggal masuk master lebih kecil/sama dengan tanggal filter
            if ($tglMasukMaster->lte($selectedDate) && $snap['stok_akhir'] > 0) {
                $asalBokarDisp = strtoupper($maturasi->asal_bokar);
                $keteranganDisp = strtoupper($tglMasukMaster->format('d M Y'));
                $umurDisp = $tglMasukMaster->diffInDays($selectedDate);
            }
        }

        // Jika identitas statis tidak ditemukan, gunakan tracing dinamis (CMP)
        if (empty($asalBokarDisp)) {
            $asalBokarDisp = $this->getDetailedAsalBokarString(
                $maturasi,
                $snap['stok_akhir'],
                $selectedDate
            );
            $keteranganDisp = $snap['keterangan'];
            $umurDisp = $snap['umur'];
        }

        // Reset identitas tampilan jika stok kosong
        if ($snap['stok_akhir'] <= 0) {
            $asalBokarDisp = '-';
            $keteranganDisp = 'KOSONG';
            $umurDisp = 0;
        }

        return response()->json([
            'id_maturasi' => $maturasi->id_maturasi,
            'uraian'      => $maturasi->uraian,
            'stok_awal'   => $snap['stok_awal'],
            'masuk_hi'    => $snap['masuk_hi'],
            'diolah'      => $snap['diolah'],
            'mutasi'      => $snap['mutasi'],
            'stok_akhir'  => $snap['stok_akhir'],
            'tgl_masuk'   => $snap['tgl_masuk'], // Digunakan untuk input tgl di modal jika perlu
            'umur'        => $umurDisp,
            'asal_bokar'  => $asalBokarDisp,
            'keterangan'  => $keteranganDisp,
            'updated_at'  => $selectedDate->format('Y-m-d'),
        ]);
    }
    public function update(Request $request, $id_maturasi): RedirectResponse
    {
        $clean = fn($v) => $v ? str_replace(',', '.', str_replace('.', '', $v)) : 0;
        $mutasiBaru = (float) $clean($request->input('mutasi'));
        
        // Diambil dari hidden input modal edit
        $tglInput = Carbon::parse($request->input('tanggal_input_harian'));

        DB::beginTransaction();
        try {
            $bakAsal = Maturasi::findOrFail($id_maturasi);

            // --- 1. CARI LOG ASAL (PENGIRIM) ---
            $logAsal = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
                ->whereDate('tgl_laporan', $tglInput)
                ->firstOrFail();

            // --- 2. CARI LOG TUJUAN (PENERIMA) ---
            // ✅ Perbaikan: Gunakan trim() agar spasi di awal/akhir nama bak tidak mengganggu pencarian
            $namaBakAsal = trim($bakAsal->uraian);
            $keteranganTarget = 'Terima Mutasi dari ' . $namaBakAsal;
            
            $logTujuan = PengolahanMaturasi::whereDate('tgl_laporan', $tglInput)
                ->where('keterangan', 'LIKE', '%' . $namaBakAsal . '%') // ✅ Lebih aman menggunakan LIKE
                ->where('mutasi', '<', 0) // ✅ Mutasi masuk selalu bernilai negatif
                ->first();

            // --- 3. PROSES UPDATE BAK ASAL ---
            $logAsal->update(['mutasi' => $mutasiBaru]);

            // --- 4. PROSES UPDATE BAK TUJUAN (JIKA DITEMUKAN) ---
            if ($logTujuan) {
                // Update nilai mutasi penerima (negatif agar menambah stok)
                // Jika mutasiBaru adalah 0, maka mutasi penerima juga menjadi 0
                $logTujuan->update(['mutasi' => -$mutasiBaru]);

                // 🔥 REFRESH MASTER STOK BAK PENERIMA (Bak 12 / Bak 3)
                $bakTujuan = Maturasi::find($logTujuan->id_maturasi);
                if ($bakTujuan) {
                    $snapTujuan = $this->hitungSnapshot($bakTujuan, $tglInput);
                    $bakTujuan->update([
                        'stok_akhir' => $snapTujuan['stok_akhir']
                    ]);
                }
            }

            // --- 5. REFRESH MASTER STOK BAK PENGIRIM (Bak 7 / Bak 1) ---
            $snapAsal = $this->hitungSnapshot($bakAsal, $tglInput);
            $bakAsal->update([
                'stok_awal'  => $snapAsal['stok_awal'],
                'diolah'     => $snapAsal['diolah'],
                'mutasi'     => $snapAsal['mutasi'],
                'masuk_hi'   => $snapAsal['masuk_hi'],
                'stok_akhir' => $snapAsal['stok_akhir'],
                'updated_at' => Carbon::now() 
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Nilai mutasi berhasil diubah dan saldo kedua bak telah sinkron.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui mutasi: ' . $e->getMessage());
        }
    }
}