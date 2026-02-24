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

    // =========================================================================
    // 🔥 CORE LOGIC: SNAPSHOT (PERBAIKAN SESUAI EXCEL)
    // =========================================================================
    protected function hitungSnapshot($maturasi, Carbon $selectedDate): array
    {
        $stok_awal = $this->getNetBeforeDate($maturasi->id_maturasi, $selectedDate);
        $transaksi = $this->getSumsOnDate($maturasi->id_maturasi, $selectedDate);

        $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', $selectedDate)->sum('netto_kering');

        $masuk_hi_today = max($transaksi['masuk_hi'], $masuk_from_uji);
        $mutasi_masuk_today = $transaksi['mutasi'] < -0.01 ? abs($transaksi['mutasi']) : 0;
        $mutasi_keluar_today = $transaksi['mutasi'] > 0.01 ? $transaksi['mutasi'] : 0;
        
        $total_masuk_today = $masuk_hi_today + $mutasi_masuk_today;
        $total_keluar_today = $transaksi['diolah'] + $mutasi_keluar_today;

        $stok_akhir = $stok_awal + $total_masuk_today - $total_keluar_today;

        // 🔥 1. SIMULASI MAJU: Cari Tanggal Acuan (Tgl Stok Awal)
        $logsBeforeToday = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tgl_laporan', '<', $selectedDate->toDateString())
            ->orderBy('tgl_laporan', 'asc')->get();

        $tgl_basis = null;
        $running_stock = 0;

        foreach($logsBeforeToday as $log) {
            $logDate = Carbon::parse($log->tgl_laporan);
            $in_fresh = $log->masuk_hi;
            $mutasi_in = $log->mutasi < -0.01 ? abs($log->mutasi) : 0;
            $out = $log->diolah + ($log->mutasi > 0.01 ? $log->mutasi : 0);

            // 🔥 PERBAIKAN: Pokoknya setiap ada MASUK FRESH, umur untuk besok otomatis reset!
            if ($in_fresh > 0.01) {
                $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)->whereDate('tanggal', '<=', $logDate->toDateString())->orderBy('tanggal', 'desc')->first();
                $tgl_basis = $lab ? Carbon::parse($lab->tanggal) : $logDate;
            } 
            elseif ($running_stock <= 0.01 && $mutasi_in > 0.01) {
                $tgl_basis = $logDate;
            }

            $running_stock = $running_stock + $in_fresh + $mutasi_in - $out;
            if ($running_stock <= 0.01) $tgl_basis = null; // Reset jika stok sempat habis
        }

        // Fallback ke Master
        if (!$tgl_basis && $stok_awal > 0.01) {
            $tgl_basis = !empty($maturasi->tgl_masuk) ? Carbon::parse($maturasi->tgl_masuk) : Carbon::parse($maturasi->created_at);
        }

        $tgl_masuk_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->toDateString() : 'KOSONG';
        $umur_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->diffInDays($selectedDate) : 0;

        // 🔥 2. LOGIKA KETERANGAN: Berubah Langsung Hari Ini!
        $keterangan_visual = 'KOSONG';
        $tgl_master_tersembunyi = null;

        if ($stok_akhir > 0.01) {
            if ($masuk_hi_today > 0.01) {
                // Jika hari ini ada Masuk, Keterangan LANGSUNG jadi tanggal hari ini
                $labToday = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)->whereDate('tanggal', $selectedDate)->first();
                $keterangan_date = $labToday ? Carbon::parse($labToday->tanggal) : $selectedDate;
                $keterangan_visual = strtoupper($keterangan_date->format('d M Y'));
                $tgl_master_tersembunyi = $keterangan_date->toDateString();
            } elseif ($mutasi_masuk_today > 0.01) {
                $keterangan_visual = strtoupper($selectedDate->format('d M Y'));
                $tgl_master_tersembunyi = $selectedDate->toDateString();
            } else {
                // Jika tidak ada masuk hari ini, pakai tanggal basis lama
                $keterangan_visual = $tgl_basis ? strtoupper($tgl_basis->format('d M Y')) : '-';
                $tgl_master_tersembunyi = $tgl_basis ? $tgl_basis->toDateString() : null;
            }
        }

        return [
            'stok_awal'  => $stok_awal,
            'diolah'     => $transaksi['diolah'],
            'mutasi'     => $transaksi['mutasi'],
            'masuk_hi'   => $masuk_hi_today,
            'stok_akhir' => $stok_akhir,
            'tgl_masuk'  => $tgl_masuk_visual == 'KOSONG' ? null : $tgl_masuk_visual, 
            'tgl_master' => $tgl_master_tersembunyi,
            'umur'       => $umur_visual,
            'keterangan' => $keterangan_visual,
        ];
    }

    // =========================================================================
    // HELPER 2: LOGIKA "ASAL BOKAR" (CMP DETAILED) - 4 PARAMETER
    // =========================================================================
    private function getDetailedAsalBokarString($maturasi, float $stokAkhir, float $stokAwal, Carbon $filterDate)
    {
        if ($stokAkhir <= 0.01 && $stokAwal <= 0.01) return '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = ($stokAkhir > 0.01) ? $stokAkhir : ($stokAwal + 0.1);
            $akumulasiKeluar = 0; // 🔥 VARIABEL BARU UNTUK MENGINGAT PRODUKSI

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $akumulasiKeluar += $keluar; // Tambahkan total yang sudah digiling sejauh ini
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // 🔥 LOGIKA RESET ULTIMATE:
                // Jika hari ini ada Masuk HI, DAN terdeteksi sudah pernah ada Produksi (Diolah) 
                // sejak hari itu atau pada hari itu, maka MASA LALU DIABAIKAN TOTAL!
                if (($masuk + $mutasiMasuk) > 0.01 && $akumulasiKeluar > 0.01) {
                    break; 
                }

                if ($prevStock <= 0.01) break;
                $currentTracingStock = $prevStock;
            }

            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function($v) { return strtoupper(trim($v)); })
                ->unique()->filter()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1 ? 'CMP (' . implode(', ', $jenisList) . ')' : $jenisList[0];
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
        $isToday = $selectedDate->isToday();

        foreach ($data_maturasi_db as $bak) {
            $snap = $this->hitungSnapshot($bak, $selectedDate);
            $row = clone $bak;
            foreach ($snap as $k => $v) $row->$k = $v;

                // 🔥 PERBAIKAN: HANYA UPDATE ASAL BOKAR. Jangan update 'tgl_masuk' database agar tidak merusak history!
                if ($isToday) {
                    // Gunakan pemanggilan langsung ke Model agar VS Code tidak bingung
                    Maturasi::where('id_maturasi', $bak->id_maturasi)->update([
                        'asal_bokar' => $this->getDetailedAsalBokarString($bak, $snap['stok_akhir'], $snap['stok_awal'], $selectedDate)
                    ]);
                }
            
            $row->updated_at = $selectedDate;

            $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', '<=', $selectedDate)->orderBy('tanggal', 'desc')->first();
            $row->k3_masuk = $ujiMasuk ? $ujiMasuk->k3 : 0;
            $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', '<=', $selectedDate)->orderBy('tanggal', 'desc')->first();
            $row->k3_olah = $ujiLab->k3 ?? 0;
            $row->po      = $ujiLab->po ?? '-';
            $row->pri     = $ujiLab->pri ?? '-';
            $row->tgl_uji = $ujiLab->tanggal ?? null;

            $bakFresh = Maturasi::find($bak->id_maturasi);
            $row->asal_bokar = $this->getDetailedAsalBokarString($bakFresh, $row->stok_akhir, $row->stok_awal, $selectedDate);
            
            if ($row->stok_akhir <= 0.01) {
                $row->keterangan = 'KOSONG';
                $row->asal_bokar = '-';
            } else {
                $row->keterangan = $snap['keterangan'];
            }

            $dataTampilan->push($row);
            if ($row->stok_akhir > 0) $bak_aktif_list[] = $row->uraian; 
        }

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
        $request->merge(['mutasi' => $clean($request->input('mutasi'))]);

        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string|exists:maturasi,uraian',
            'tanggal_input_harian' => 'required|date',
            'mutasi' => 'nullable|numeric|min:0',
            'tujuan_mutasi' => 'nullable|exists:maturasi,id_maturasi',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        $tglInput  = Carbon::parse($request->input('tanggal_input_harian'));
        $mutasiVal = (float) $request->input('mutasi');
        $bakAsal   = Maturasi::where('uraian', $request->input('uraian'))->firstOrFail();

        DB::beginTransaction();
        try {
            // 1. Ambil Identitas Stok Awal
            $snapVisualAsal = $this->hitungSnapshot($bakAsal, $tglInput);
            $tglMasukYangDikirim = $snapVisualAsal['tgl_masuk']; 
            
            // 🔥 FIX: Tambahkan parameter ke-3 (Stok Awal)
            $asalBokarYangDikirim = $this->getDetailedAsalBokarString(
                $bakAsal, 
                $snapVisualAsal['stok_akhir'], 
                $snapVisualAsal['stok_awal'], // <-- Ini yang tadi kurang
                $tglInput
            );

            if (!$tglMasukYangDikirim) {
                $historyDate = $this->getHistoryDateFromLog($bakAsal->id_maturasi, $tglInput);
                $tglMasukYangDikirim = $historyDate ? $historyDate->toDateString() : null;
            }

            // Hitung Stok Basis untuk Logic Pindah Identitas
            $stokBasisMutasi = $snapVisualAsal['stok_awal'] + $snapVisualAsal['masuk_hi'] - $snapVisualAsal['diolah'];

            // 2. Simpan Log di Bak ASAL
            $existingAsal = PengolahanMaturasi::where('id_maturasi', $bakAsal->id_maturasi)
                ->whereDate('tgl_laporan', $tglInput)->first();

            $ketAsal = $request->keterangan ?? 'Mutasi Keluar';
            if ($existingAsal && str_contains($existingAsal->keterangan, 'Terima dari')) {
                $ketAsal = $existingAsal->keterangan . ' & Mutasi';
            }

            PengolahanMaturasi::updateOrCreate(
                ['id_maturasi' => $bakAsal->id_maturasi, 'tgl_laporan' => $tglInput],
                [
                    'diolah' => $existingAsal->diolah ?? 0, 
                    'mutasi' => ($existingAsal->mutasi ?? 0) + $mutasiVal, 
                    'keterangan' => $ketAsal
                ]
            );

            // 3. Simpan Log di Bak TUJUAN
            if ($request->filled('tujuan_mutasi') && $mutasiVal > 0) {
                $bakTujuan = Maturasi::findOrFail($request->tujuan_mutasi);
                $existingTujuan = PengolahanMaturasi::where('id_maturasi', $bakTujuan->id_maturasi)
                    ->whereDate('tgl_laporan', $tglInput)->first();

                PengolahanMaturasi::updateOrCreate(
                    ['id_maturasi' => $bakTujuan->id_maturasi, 'tgl_laporan' => $tglInput],
                    [
                        'mutasi'     => ($existingTujuan->mutasi ?? 0) - $mutasiVal, 
                        'keterangan' => 'Terima dari ' . $bakAsal->uraian
                    ]
                );

                // LOGIKA PINDAH IDENTITAS (Jika mutasi > 50% dari stok yang ada)
                if ($stokBasisMutasi > 0 && ($mutasiVal / $stokBasisMutasi) >= 0.5) {
                    // 🔥 FIX: Panggil string label dengan 4 parameter
                    $asalBokarFinal = $this->getDetailedAsalBokarString(
                        $bakAsal, 
                        $stokBasisMutasi, 
                        $stokBasisMutasi, // Anggap stok awal sama dg basis
                        $tglInput
                    );

                    $bakTujuan->update([
                        'asal_bokar' => $asalBokarFinal,
                        'tgl_masuk'  => $tglMasukYangDikirim,
                        'updated_at' => $tglInput
                    ]);
                }

                // Refresh stok akhir tujuan
                $snapTujuan = $this->hitungSnapshot($bakTujuan, $tglInput);
                $bakTujuan->update(['stok_akhir' => $snapTujuan['stok_akhir']]);
            }

            // 4. Update Master Bak Asal
            $snapAsalFinal = $this->hitungSnapshot($bakAsal, $tglInput);
            $bakAsal->update([
                'stok_akhir' => $snapAsalFinal['stok_akhir'],
                'updated_at' => $tglInput 
            ]);

            if ($snapAsalFinal['stok_akhir'] <= 0.01) {
                $bakAsal->update(['tgl_masuk' => null, 'asal_bokar' => null, 'keterangan' => 'KOSONG']);
            }

            DB::commit();
            return redirect()->route('maturasi.index', ['filter_tanggal' => $tglInput->format('Y-m-d')])
                ->with('success', 'Mutasi berhasil diproses.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
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
        
        $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)->latest('tanggal')->first();

        $ujiMasuk = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
            ->whereDate('tanggal', '<=', $tgl)->latest('tanggal')->first();

        // 🔥 FIX: Tambahkan parameter ke-3 (Stok Awal)
        $asalBokarDetailed = $this->getDetailedAsalBokarString($maturasi, $snap['stok_akhir'], $snap['stok_awal'], $tgl);

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
            // 🔥 FIX: Panggil dengan 4 Parameter (Stok Awal ditambahkan)
            $asalBokarDisp = $this->getDetailedAsalBokarString(
                $maturasi,
                $snap['stok_akhir'],
                $snap['stok_awal'], // <--- Parameter ke-3 (Penting!)
                $selectedDate       // <--- Parameter ke-4
            );
            $keteranganDisp = $snap['keterangan'];
            $umurDisp = $snap['umur'];
        }

        // Reset identitas tampilan jika stok kosong
        if ($snap['stok_akhir'] <= 0.01) { // Pakai toleransi 0.01
            $asalBokarDisp = '-';
            $keteranganDisp = 'KOSONG';
            $umurDisp = 0;
        }

        // Cari Tujuan Mutasi (Untuk form edit)
        $tujuanId = null;
        $logAsal = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', $selectedDate)
            ->first();
            
        if ($logAsal && $logAsal->mutasi > 0) {
             $namaBakAsal = trim($maturasi->uraian);
             $logTujuan = PengolahanMaturasi::whereDate('tgl_laporan', $selectedDate)
                ->where('keterangan', 'LIKE', '%' . $namaBakAsal . '%')
                ->where('mutasi', '<', 0)
                ->first();
             if($logTujuan) $tujuanId = $logTujuan->id_maturasi;
        }

        return response()->json([
            'id_maturasi' => $maturasi->id_maturasi,
            'uraian'      => $maturasi->uraian,
            'stok_awal'   => $snap['stok_awal'],
            'masuk_hi'    => $snap['masuk_hi'],
            'diolah'      => $snap['diolah'],
            'mutasi'      => $snap['mutasi'],
            'stok_akhir'  => $snap['stok_akhir'],
            'tgl_masuk'   => $snap['tgl_masuk'], 
            'umur'        => $umurDisp,
            'asal_bokar'  => $asalBokarDisp,
            'keterangan'  => $keteranganDisp,
            'tujuan_mutasi_id' => $tujuanId,
            'updated_at'  => $selectedDate->format('Y-m-d'),
        ]);
    }
    
    public function update(Request $request, $id_maturasi): RedirectResponse 
    {
        $clean = fn($v) => $v ? str_replace(',', '.', str_replace('.', '', $v)) : 0;
        $mutasiBaru = (float) $clean($request->input('mutasi'));
        $tglInput = Carbon::parse($request->input('tanggal_input_harian'));

        DB::beginTransaction();
        try {
            $bakSekarang = Maturasi::findOrFail($id_maturasi);
            $logSekarang = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
                ->whereDate('tgl_laporan', $tglInput)->firstOrFail();

            $logPasangan = null;
            $bakPasangan = null;

            // 1. CARI PASANGAN MUTASI
            if ($logSekarang->mutasi > 0) {
                $namaBakAsal = trim($bakSekarang->uraian);
                $logPasangan = PengolahanMaturasi::whereDate('tgl_laporan', $tglInput)
                    ->where('keterangan', 'LIKE', '%Terima dari ' . $namaBakAsal . '%')
                    ->where('mutasi', '<', 0)->first();
                if ($logPasangan) $bakPasangan = Maturasi::find($logPasangan->id_maturasi);

            } else if ($logSekarang->mutasi < 0) {
                if (preg_match('/Terima dari (.*)/', $logSekarang->keterangan, $matches)) {
                    $namaBakAsal = trim($matches[1]);
                    $bakPasangan = Maturasi::where('uraian', $namaBakAsal)->first();
                    if ($bakPasangan) {
                        $logPasangan = PengolahanMaturasi::where('id_maturasi', $bakPasangan->id_maturasi)
                            ->whereDate('tgl_laporan', $tglInput)
                            ->where('mutasi', '>', 0)->first();
                    }
                }
            }

            // 2. UPDATE LOG
            $nilaiSekarang = ($logSekarang->mutasi > 0) ? $mutasiBaru : -$mutasiBaru;
            $logSekarang->update(['mutasi' => $nilaiSekarang]);

            if ($logPasangan) {
                $nilaiPasangan = ($logSekarang->mutasi > 0) ? -$mutasiBaru : $mutasiBaru;
                $logPasangan->update(['mutasi' => $nilaiPasangan]);
            }

            // 3. SYNC IDENTITAS & STOK
            $isOriginSource = ($logSekarang->mutasi > 0); 
            $bakAsalObj     = $isOriginSource ? $bakSekarang : $bakPasangan;
            $bakTujuanObj   = $isOriginSource ? $bakPasangan : $bakSekarang;

            // Sync Bak Asal
            if ($bakAsalObj) {
                $snapA = $this->hitungSnapshot($bakAsalObj, $tglInput);
                $updateA = ['stok_akhir' => $snapA['stok_akhir'], 'updated_at' => $tglInput];
                
                if ($snapA['stok_akhir'] <= 0.01) {
                    $updateA += ['tgl_masuk' => null, 'asal_bokar' => null, 'keterangan' => 'KOSONG'];
                } else {
                     $histTgl = $this->getHistoryDateFromLog($bakAsalObj->id_maturasi, $tglInput);
                     if($histTgl) {
                        $updateA['tgl_masuk'] = $histTgl->toDateString();
                        $updateA['keterangan'] = strtoupper($histTgl->format('d M Y'));
                     }
                }
                $bakAsalObj->update($updateA);
            }

            // Sync Bak Tujuan
            if ($bakTujuanObj) {
                if ($bakAsalObj && $mutasiBaru > 0) {
                     $snapVisualAsal = $this->hitungSnapshot($bakAsalObj, $tglInput);
                     $tglMasukKirim  = $snapVisualAsal['tgl_masuk'];
                     
                     // 🔥 FIX: Tambahkan parameter ke-3 (Dummy stok awal 100)
                     $asalBokarKirim = $this->getDetailedAsalBokarString($bakAsalObj, 100, 100, $tglInput); 

                     if (!$tglMasukKirim) {
                        $histTgl = $this->getHistoryDateFromLog($bakAsalObj->id_maturasi, $tglInput);
                        $tglMasukKirim = $histTgl ? $histTgl->toDateString() : null;
                     }

                     $bakTujuanObj->update([
                        'tgl_masuk'  => $tglMasukKirim,
                        'asal_bokar' => $asalBokarKirim,
                        'keterangan' => $tglMasukKirim ? strtoupper(Carbon::parse($tglMasukKirim)->format('d M Y')) : '-',
                        'updated_at' => $tglInput
                     ]);
                }
                $snapT = $this->hitungSnapshot($bakTujuanObj, $tglInput);
                $bakTujuanObj->update(['stok_akhir' => $snapT['stok_akhir']]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Mutasi berhasil diperbarui dan disinkronkan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // HELPER TAMBAHAN: PENCARIAN TANGGAL SEJARAH DARI LOG
    // =========================================================================
    protected function getHistoryDateFromLog(int $id_maturasi, Carbon $reportDate)
    {
        // 1. Cari transaksi masuk terakhir (Fresh In ATAU Mutasi In) SEBELUM tanggal laporan
        $lastEntry = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $reportDate->toDateString())
            ->where(function($q) {
                $q->where('masuk_hi', '>', 0.01)
                  ->orWhere('mutasi', '<', -0.01); // Mengenali mutasi masuk (negatif = masuk)
            })
            ->orderBy('tgl_laporan', 'desc')
            ->first();

        if ($lastEntry) {
            // Jika transaksi terakhir adalah Masuk Fresh (Bukan mutasi), cek tanggal lab bokar
            if ($lastEntry->masuk_hi > 0.01) {
                $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
                    ->whereDate('tanggal', '<=', $lastEntry->tgl_laporan)
                    ->orderBy('tanggal', 'desc')
                    ->first();

                // Kembalikan tanggal lab jika ada, jika tidak pakai tanggal laporan
                return $lab ? Carbon::parse($lab->tanggal) : Carbon::parse($lastEntry->tgl_laporan);
            }
            
            // Jika Mutasi, gunakan tanggal laporan log tersebut
            return Carbon::parse($lastEntry->tgl_laporan);
        }

        // 2. Fallback: Cari langsung di LAB jika tidak ada log pengolahan
        $logLab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', '<', $reportDate->toDateString())
            ->orderBy('tanggal', 'desc')
            ->first();

        return $logLab ? Carbon::parse($logLab->tanggal) : null;
    }
}