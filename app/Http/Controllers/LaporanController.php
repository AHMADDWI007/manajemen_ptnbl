<?php

namespace App\Http\Controllers;

use App\Exports\LaporanBulananExport;
use App\Exports\LaporanHarianExport;
use App\Http\Controllers\Controller;
use App\Models\BahanProses;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\Lokasi;
use App\Models\Maturasi;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\Pengaturan;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\PenjualanSir20;
use App\Models\ProduksiSir20; 
use App\Models\RektifikasiStok;
use App\Models\TransaksiApiBokar;
use App\Traits\MaturasiSyncTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{

    use MaturasiSyncTrait;

    // =========================================================================
    // 1. TAMPILAN DASHBOARD (INDEX)
    // =========================================================================
    public function index(Request $request)
    {
        $data = $this->getDataLaporan($request->input('tanggal'));
        return view('Laporan.laporan', $data);
    }

    // =========================================================================
    // 2. PREVIEW CETAK PDF (HTML)
    // =========================================================================
    public function previewCetak(Request $request)
    {
        // 1. Ambil data (Logika sama persis)
        $data = $this->getDataLaporan($request->input('tanggal'));

        // 2. Return View Cetak
        return view('Cetak.cetak-laporan', $data);
    }

    // =========================================================================
    // 3. EXPORT EXCEL (TERBARU) 🚀
    // =========================================================================
    public function exportExcel(Request $request)
    {
        // 1. Ambil Data
        $data = $this->getDataLaporan($request->input('tanggal'));
        
        // 2. Format Nama File
        $tglFile = $data['tanggal']->format('d-m-Y');
        $namaFile = "Laporan_Harian_Produksi_{$tglFile}.xlsx";

        // 🔥 MEMBERSIHKAN BUFFER (HARD RESET) 🔥
        // Hapus semua buffer yang ada supaya file excel murni
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Jaga-jaga kalau ada multiple buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 3. Download
        return Excel::download(new LaporanHarianExport($data), $namaFile);
    }

    // =========================================================================
    // 🔥 CORE LOGIC: PENGAMBILAN DATA (PUSAT DATA)
    // =========================================================================
    // =========================================================================
    // 🔥 CORE LOGIC: PENGAMBILAN DATA (PUSAT DATA)
    // =========================================================================
    public function getDataLaporan($tglInput)
    {
        $tanggal = $tglInput ? Carbon::parse($tglInput) : Carbon::today();
        
        // Panggil fungsi-fungsi kecil untuk setiap bagian laporan
        $rekapBokar   = $this->getRekapBokar($tanggal);
        $dataMaturasi = $this->getDataMaturasi($tanggal);
        $dataWip      = $this->getDataWip($tanggal);
        $gudangMutu   = $this->getDataGudangMutu($tanggal); // Return array [gudang, mutu]
        $dataPenjualan = $this->getDataPenjualan($tanggal);

        // =====================================================================
        // 🔥 TAMBAHAN: HITUNG TOTAL I s/d IV (TOTAL SALDO AKHIR)
        // =====================================================================
        // Total I: Bokar (Stok Awal + Masuk - Kering + Rektifikasi)
        $totalBokar = 0;
        foreach ($rekapBokar as $jenis => $val) {
            $totalBokar += ($val['stok_awal'] + $val['masuk_hi'] - $val['kering_hi'] + $val['rektif']);
        }
        
        // Total II: Maturasi
        $totalMaturasi = $dataMaturasi->sum('stok_akhir');
        
        // Total III: WIP
        $totalWip = collect($dataWip)->sum('stok_akhir');
        // 🔥 TAMBAHAN BARU UNTUK KETERANGAN WIP
        $grandTotalKeterangan = $totalWip + $totalMaturasi;
        
        // Total IV: Gudang
        $totalGudang = collect($gudangMutu['gudang'])->sum('stok_akhir');
        
        // Grand Total
        $total_1_sd_4 = $totalBokar + $totalMaturasi + $totalWip + $totalGudang;
        // =====================================================================

        // Ambil data Tanda Tangan dari tabel pengaturan
        $ttd = Pengaturan::whereIn('kunci', [
            'ttd_kiri_nama', 
            'ttd_kiri_jabatan', 
            'ttd_kanan_nama', 
            'ttd_kanan_jabatan'
        ])->get()->keyBy('kunci');

        // Satukan hasilnya dalam satu array
        return [
            'tanggal'       => $tanggal,
            'rekapBokar'    => $rekapBokar,
            'dataMaturasi'  => $dataMaturasi,
            'dataWip'       => $dataWip,
            'dataGudang'    => $gudangMutu['gudang'],
            'dataMutu'      => $gudangMutu['mutu'],
            'dataPenjualan' => $dataPenjualan,
            'total_1_sd_4'  => $total_1_sd_4, // 🔥 KIRIM KE BLADE
            // 🔥 TAMBAHKAN DUA VARIABEL INI KE ARRAY RETURN
            'stokAkhirMaturasi'    => $totalMaturasi, 
            'grandTotalKeterangan' => $grandTotalKeterangan,
            'ttd_kiri_nama'     => $ttd->get('ttd_kiri_nama')->nilai ?? 'Sri Winarno',
            'ttd_kiri_jabatan'  => $ttd->get('ttd_kiri_jabatan')->nilai ?? 'Kadiv Pengolahan',
            'ttd_kanan_nama'    => $ttd->get('ttd_kanan_nama')->nilai ?? 'Sri Winarno',
            'ttd_kanan_jabatan' => $ttd->get('ttd_kanan_jabatan')->nilai ?? 'Manager',
        ];
    }

    // =========================================================================
    // A. LOGIKA BOKAR (TABEL I)
    // =========================================================================
    private function getRekapBokar($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $startOfMonth = $tanggal->copy()->startOfMonth();
        $yesterday = $tanggal->copy()->subDay();

        $rekapBokar = [];
        $mapBokar = ['PT' => 'ptpn', 'DS' => 'petani', 'INHUT' => 'inhut'];
        $rektifRecords = RektifikasiStok::where('tanggal', $tglStr)->get()->keyBy('jenis');

        foreach ($mapBokar as $jenis => $kodeApi) {
            // Hitung Stok Awal
            $masuk_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', '<=', $yesterday)->sum('masuk_hi');
            $olah_kemarin  = PengolahanBasah::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('netto_kering');
            $rektif_kemarin = RektifikasiStok::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('berat');
            $stok_awal = max(0, round($masuk_kemarin - $olah_kemarin + $rektif_kemarin, 2));

            // Data Hari Ini
            $masuk_hi = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', $tglStr)->value('masuk_hi') ?? 0;
            $data_olah = PengolahanBasah::where('jenis', $jenis)->whereDate('tanggal', $tglStr)
                ->selectRaw('SUM(netto_basah) as basah, SUM(netto_kering) as kering')->first();
            
            // Data S/D Hari Ini
            $masuk_sd_kemarin = 0;
            if ($yesterday->gte($startOfMonth)) {
                $masuk_sd_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)
                    ->whereBetween('tanggal', [$startOfMonth, $yesterday])->sum('masuk_hi');
            }
            $olah_sdhi = PengolahanBasah::where('jenis', $jenis)->whereBetween('tanggal', [$startOfMonth, $tglStr])->sum('netto_kering');
            $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->berat : 0;

            $rekapBokar[$jenis] = [
                'stok_awal'   => $stok_awal,
                'basah_hi'    => $data_olah->basah ?? 0,
                'kering_hi'   => $data_olah->kering ?? 0,
                'basah_sdhi'  => $masuk_sd_kemarin, 
                'masuk_hi'    => $masuk_hi,
                'masuk_sdhi'  => $masuk_sd_kemarin + $masuk_hi,
                'kering_sdhi' => $olah_sdhi,
                'rektif'      => $rektif_today
            ];
        }
        return $rekapBokar;
    }

    // =========================================================================
    // 🔥 HELPER: PENCARIAN ASAL BOKAR (CMP TRACING) KHUSUS LAPORAN
    // =========================================================================
    private function getDetailedAsalBokarStringLaporan($maturasi, float $stokAkhir, float $stokAwal, Carbon $filterDate)
    {
        if ($stokAkhir <= 0.01 && $stokAwal <= 0.01) return '-';

        try {

        // 🔥 PERBAIKAN 3: Cek metadata "Jenis" di keterangan log (Logika Mutasi)
            $logMutasi = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->where('keterangan', 'LIKE', '%Jenis: %')
                ->orderBy('tgl_laporan', 'desc')->first();

            if ($logMutasi && preg_match('/Jenis: ([^)]+)/', $logMutasi->keterangan, $matches)) {
                return trim($matches[1]);
            }
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = ($stokAkhir > 0.01) ? $stokAkhir : ($stokAwal + 0.1);
            $akumulasiKeluar = 0; 

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $akumulasiKeluar += $keluar; 
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // LOGIKA RESET: Jika ada masuk baru dan sudah ada produksi, abaikan masa lalu
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
    // B. LOGIKA MATURASI (TABEL II)
    // =========================================================================
    private function getDataMaturasi($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        $dataMaturasi = new Collection();

        foreach ($data_maturasi_db as $bak) {
            
            // 1. Cek apakah pernah ada log s/d hari ini
            $hasLog = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $tglStr)->exists();
            
            if (!$hasLog) {
                // Jika belum pernah ada transaksi sama sekali
                $row = $this->createMaturasiObj($bak, 0, 0, 0, 0, 0, null, 0, 'KOSONG');
                $row->k3_olah = 0; $row->po = '-'; $row->pri = '-'; $row->asal_bokar = '-';
            } else {
                // 2. Logic Snapshot Stok Awal
                $sumsAwal = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                $stok_awal = $sumsAwal ? ($sumsAwal->m - $sumsAwal->d - $sumsAwal->u) : 0;

                // 3. Logic Transaksi Hari Ini
                $s = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                
                $trans_masuk = (float)($s->m ?? 0);
                $trans_diolah = (float)($s->d ?? 0);
                $trans_mutasi = (float)($s->u ?? 0);

                // Cek Masuk dari Uji Bokar (Backup)
                $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', $tglStr)->sum('netto_kering');
                $masuk_hi_today = max($trans_masuk, $masuk_from_uji);
                
                // 4. Hitung Stok Akhir
                $stok_akhir = round($stok_awal + $masuk_hi_today - $trans_diolah - $trans_mutasi, 2);

                // 5. Logika Umur
                $tgl_basis = null;
                $logsBeforeToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', '<', $tglStr)
                    ->orderBy('tgl_laporan', 'asc')->get();

                $running_stock = 0;
                foreach($logsBeforeToday as $log) {
                    $logDate = Carbon::parse($log->tgl_laporan);
                    $in_fresh = $log->masuk_hi;
                    $mutasi_in = $log->mutasi < -0.01 ? abs($log->mutasi) : 0;
                    $out = $log->diolah + ($log->mutasi > 0.01 ? $log->mutasi : 0);

                    // 🔥 PRIORITAS 1: Jika ada masuk Fresh (dari Timbang/Lab)
                    if ($in_fresh > 0.01) {
                        $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                            ->whereDate('tanggal', '<=', $logDate->toDateString())->orderBy('tanggal', 'desc')->first();
                        $tgl_basis = $lab ? Carbon::parse($lab->tanggal) : $logDate;
                    } 
                    // 🔥 PRIORITAS 2: Baca teks Asal TANPA mempedulikan angka netto mutasi (Anti-Bug)
                    elseif (preg_match('/Asal: (\d{4}-\d{2}-\d{2})/', $log->keterangan, $matches)) {
                        $tgl_basis = Carbon::parse($matches[1]);
                    } 
                    // 🔥 PRIORITAS 3: Fallback jika mutasi masuk tapi gak ada keterangan asal
                    elseif ($mutasi_in > 0.01 && !$tgl_basis) {
                        $tgl_basis = $logDate;
                    }

                    $running_stock = $running_stock + $in_fresh + $mutasi_in - $out;
                    if ($running_stock <= 0.01) $tgl_basis = null;
                }

                // 🔥 PERBAIKAN: Cek Log Hari Ini (Pisahkan Identitas Pagi vs Status Akhir)
                $logToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', $tglStr)->first();
                    
                $adaPenerimaanMutasi = false;
                $tgl_mutasi_hari_ini = null;

                if ($logToday && preg_match('/Asal: (\d{4}-\d{2}-\d{2})/', $logToday->keterangan, $matches)) {
                    $adaPenerimaanMutasi = true;
                    $tgl_mutasi_hari_ini = Carbon::parse($matches[1]);
                    
                    // TAHAN UMUR AWAL: Jangan timpa identitas utama (tgl_basis) jika pagi harinya 
                    // bak sudah punya stok (stok_awal > 0). 
                    if ($stok_awal <= 0.01) {
                        $tgl_basis = $tgl_mutasi_hari_ini;
                    }
                }

                // Fallback ke Master jika tgl_basis masih kosong
                if (!$tgl_basis && $stok_awal > 0.01) {
                    $tgl_basis = !empty($bak->tgl_masuk) ? Carbon::parse($bak->tgl_masuk) : Carbon::parse($bak->created_at);
                }

                // VISUALISASI STOK AWAL
                $tgl_masuk_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->toDateString() : null;
                $umur_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->diffInDays($tanggal) : 0;

                // 6. Logika Keterangan Akhir
                $keterangan_visual = 'KOSONG';
                if ($stok_akhir > 0.01) {
                    if ($masuk_hi_today > 0.01) {
                        $labToday = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', $tglStr)->first();
                        $keterangan_date = $labToday ? Carbon::parse($labToday->tanggal) : $tanggal;
                        $keterangan_visual = strtoupper($keterangan_date->format('d M Y'));
                    } elseif ($adaPenerimaanMutasi) { // 🔥 Ambil dari Tgl Mutasi Baru
                        $keterangan_visual = strtoupper($tgl_mutasi_hari_ini->format('d M Y'));
                    } else {
                        $keterangan_visual = $tgl_basis ? strtoupper($tgl_basis->format('d M Y')) : '-';
                    }
                }

                // Masukkan ke Objek
                $row = $this->createMaturasiObj($bak, $stok_awal, $trans_diolah, $trans_mutasi, $masuk_hi_today, $stok_akhir, $tgl_masuk_visual, $umur_visual, $keterangan_visual);
                
                // 7. Logika Asal Bokar CMP
                $row->asal_bokar = $this->getDetailedAsalBokarStringLaporan($bak, $stok_akhir, $stok_awal, $tanggal);
                if ($stok_akhir <= 0.01) {
                    $row->asal_bokar = '-';
                    $row->keterangan = 'KOSONG';
                }

                // 8. Quality Lab Maturasi
                $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', '<=', $tglStr)->orderBy('tanggal', 'desc')->first();
                $row->k3_olah = $ujiLab->k3 ?? 0;
                $row->po      = $ujiLab->po ?? '-';
                $row->pri     = $ujiLab->pri ?? '-';
            }
            
            $dataMaturasi->push($row);
        }
        return $dataMaturasi;
    }

    // =========================================================================
    // C. LOGIKA WIP (TABEL III)
    // =========================================================================
    private function getDataWip($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $masterUraianWip = [
            'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
            'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
            'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
        ];

        // Ambil Data Pendukung
        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $tglStr)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
        $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $tglStr)->sum('kg_yang_dipress');

        $dataWip = [];
        $prevWipKeluar = 0;

        foreach ($masterUraianWip as $index => $uraian) {
            
            // 1. Ambil Data Existing
            $existingRow = BahanProses::whereDate('tanggal', $tglStr)->where('uraian', $uraian)->first();
            $rektifUser = $existingRow ? $existingRow->rekfif : 0;
            $ketUser = $existingRow ? $existingRow->keterangan : '-';

            // 2. Ambil Saldo Awal
            $lastData = BahanProses::where('uraian', $uraian)->whereDate('tanggal', '<', $tglStr)->orderBy('tanggal', 'desc')->first();
            
            // 🔥 PERBAIKAN LOGIKA SALDO AWAL (Sinkron dengan Fitur Setup Manual) 🔥
            if ($lastData) {
                $saldoAwal = $lastData->saldo_akhir;
            } else {
                $saldoAwal = $existingRow ? $existingRow->saldo_awal : 0;
            }

            // 3. Tentukan Masuk (Estafet)
            $wipMasuk = ($index === 0) ? $inputDariMaturasi : $prevWipKeluar;
            
            $produksi = 0; 
            $wipKeluar = 0; 
            $saldoAkhir = 0;

            // 4. Logika Hitung (Sama seperti BahanProsesController)
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $produksi = $realProduction;
                $wipKeluar = $produksi; // Keluar dianggap produksi
                $saldoAkhir = $saldoAwal + $wipMasuk - $produksi + $rektifUser;
            } else {
                $targetSaldoAkhir = $saldoAwal + $rektifUser;
                if ($existingRow) {
                    $saldoAkhir = $existingRow->saldo_akhir;
                    $wipKeluar = $existingRow->wip_keluar;
                } else {
                    $saldoAkhir = max(0, $targetSaldoAkhir);
                    $wipKeluar = max(0, ($saldoAwal + $wipMasuk) - $saldoAkhir);
                }
            }

            // 5. Masukkan ke Array Hasil
            $dataWip[] = (object)[
                'uraian' => $uraian, 
                'stok_awal' => $saldoAwal, 
                'masuk' => $wipMasuk, 
                'keluar' => $wipKeluar,
                'produksi_sir20' => $produksi, 
                'rektif' => $rektifUser, 
                'stok_akhir' => $saldoAkhir, 
                'keterangan' => $ketUser
            ];

            // 6. LOGIKA CUT-OFF
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $prevWipKeluar = 0; 
            } else {
                $prevWipKeluar = $wipKeluar;
            }

        } // <--- Akhir Loop Foreach

        return $dataWip;
    }   

    // =========================================================================
    // D & F. LOGIKA GUDANG & MUTU (TABEL IV & VI)
    // =========================================================================
    // =========================================================================
    // D & F. LOGIKA GUDANG & MUTU (TABEL IV & VI)
    // =========================================================================
    private function getDataGudangMutu($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $startOfMonth = $tanggal->copy()->startOfMonth()->format('Y-m-d');
        $dateAkhirBulanLalu = $tanggal->copy()->startOfMonth()->subDay()->format('Y-m-d');

        $lokasiList = Lokasi::all();
        $mutuList   = Mutu::all();

        // 1. Ambil Pallet Aktif (Belum terjual atau terjual SETELAH tanggal laporan)
        $activePallets = Pallet::where(function($q) use ($tglStr) {
                $q->whereNull('tanggal_penjualan')
                  ->orWhereDate('tanggal_penjualan', '>', $tglStr); // PERBAIKAN: Ubah >= menjadi >
            })->get();

        // 2. SALDO AWAL (H-1)
        $subQueryKemarin = DB::table('lokasi_pallet')
            ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
            ->whereDate('tanggal', '<', $tglStr)
            ->groupBy('id_pallet');

        $saldoAwalList = DB::table('lokasi_pallet as lp')
            ->joinSub($subQueryKemarin, 'latest', function ($join) {
                $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
            })
            ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
            ->where(function($q) use ($tglStr) {
                $q->whereNull('p.tanggal_penjualan')
                  ->orWhereDate('p.tanggal_penjualan', '>=', $tglStr);
            })
            ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
            ->groupBy('lp.id_lokasi')
            ->pluck('total_berat', 'id_lokasi');

        // 3. SALDO AWAL BULAN
        $subQueryAwalBulan = DB::table('lokasi_pallet')
            ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
            ->whereDate('tanggal', '<=', $dateAkhirBulanLalu)
            ->groupBy('id_pallet');

        $saldoAwalBulanList = DB::table('lokasi_pallet as lp')
            ->joinSub($subQueryAwalBulan, 'latest', function ($join) {
                $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
            })
            ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
            ->where(function($q) use ($startOfMonth) {
                $q->whereNull('p.tanggal_penjualan')
                  ->orWhereDate('p.tanggal_penjualan', '>=', $startOfMonth);
            })
            ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
            ->groupBy('lp.id_lokasi')
            ->pluck('total_berat', 'id_lokasi');

        // 🔥 PERBAIKAN PENTING: AMBIL TOTAL PENJUALAN LANGSUNG DARI TABEL PENJUALAN
        // 🔥 PERBAIKAN PENTING: AMBIL TOTAL PENJUALAN DAN NOMOR KONTRAK
        // Ambil Data Transaksi Hari Ini
        $trxPenjualanHariIni = PenjualanSir20::whereDate('tanggal', $tglStr)->where('is_summary', 0)->get();
        $totalPenjualanHariIni = $trxPenjualanHariIni->sum('hari_ini');
        
        // Ekstrak Nomor Kontrak unik dari transaksi hari ini untuk keterangan
        $listKontrakHariIni = $trxPenjualanHariIni->pluck('no_kontrak')
                                ->filter() // buang yang null/kosong
                                ->unique() // pastikan tidak duplikat
                                ->implode(', '); // gabungkan dengan koma

        $totalPenjualanSdKemarin = PenjualanSir20::where('is_summary', 0)
                                    ->whereBetween('tanggal', [$startOfMonth, $tanggal->copy()->subDay()->format('Y-m-d')])
                                    ->sum('hari_ini');

        $dataGudang = [];
        $lainnya_saldo_awal = 0;
        $lainnya_masuk = 0;
        $lainnya_sd_hi = 0;
        $lainnya_yg_lalu = 0;
        $lainnya_keluar = 0;
        $lainnya_saldo_akhir = 0;

        foreach ($lokasiList as $lokasi) {
            $saldo_awal_kg = $saldoAwalList->get($lokasi->id_lokasi, 0);
            $saldo_awal_bulan_kg = $saldoAwalBulanList->get($lokasi->id_lokasi, 0);

            $saldo_akhir_kg = 0;
            foreach($activePallets as $p) {
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $saldo_akhir_kg += $p->berat;
                }
            }

            // PENGIRIMAN: Hanya Di Gudang SIR yang punya pengiriman ke luar (Dijual)
            $keluar_kg = ($lokasi->nama == 'Di Gudang SIR') ? $totalPenjualanHariIni : 0;
            $pengiriman_sd_kemarin_kg = ($lokasi->nama == 'Di Gudang SIR') ? $totalPenjualanSdKemarin : 0;
            // 🔥 TAMBAHAN: Simpan Keterangan Kontrak Jika Ada Pengiriman
            $keterangan_gudang = ($lokasi->nama == 'Di Gudang SIR' && $keluar_kg > 0) ? "KONTRAK " . $listKontrakHariIni : '-';

            // RUMUS NET MATEMATIKA
            $net_masuk_kg = $saldo_akhir_kg - $saldo_awal_kg + $keluar_kg;
            $masuk_kg = $net_masuk_kg > 0 ? $net_masuk_kg : 0;

            $net_yg_lalu_kg = $saldo_awal_kg - $saldo_awal_bulan_kg + $pengiriman_sd_kemarin_kg;
            $yg_lalu_kg = $net_yg_lalu_kg > 0 ? $net_yg_lalu_kg : 0;

            $sd_hi_kg = $yg_lalu_kg + $masuk_kg;

            if (in_array($lokasi->id_lokasi, [1, 2])) {
                $dataGudang[] = (object)[
                    'uraian'        => $lokasi->nama,
                    'stok_awal'     => $saldo_awal_kg,
                    'prod_hi'       => $masuk_kg,
                    'prod_sdhi'     => $sd_hi_kg,
                    'prod_bln_lalu' => $yg_lalu_kg,
                    'pengiriman'    => $keluar_kg,
                    'stok_akhir'    => $saldo_akhir_kg,
                    'keterangan'    => $keterangan_gudang // 🔥 TAMBAHKAN INI
                ];
            } else {
                $lainnya_saldo_awal += $saldo_awal_kg;
                $lainnya_masuk += $masuk_kg;
                $lainnya_yg_lalu += $yg_lalu_kg;
                $lainnya_sd_hi += $sd_hi_kg;
                $lainnya_keluar += $keluar_kg;
                $lainnya_saldo_akhir += $saldo_akhir_kg;
            }
        }

        if (count($lokasiList) > 2) {
            $dataGudang[] = (object)[
                'uraian'        => 'Lainnya',
                'stok_awal'     => $lainnya_saldo_awal,
                'prod_hi'       => $lainnya_masuk,
                'prod_sdhi'     => $lainnya_sd_hi,
                'prod_bln_lalu' => $lainnya_yg_lalu,
                'pengiriman'    => $lainnya_keluar,
                'stok_akhir'    => $lainnya_saldo_akhir
            ];
        }

        // =====================================================================
        // BAGIAN MUTU (Tetap Sama)
        // =====================================================================
        $dataMutu = [];
        foreach ($mutuList as $m) {
            $kg = 0; $palletCount = 0;
            foreach($activePallets as $p) {
                $lastMutu = \App\Models\KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)->orderBy('id_kondisi_pallet', 'desc')->first();
                if ($lastMutu && $lastMutu->id_mutu == $m->id_mutu) {
                    $kg += $p->berat; $palletCount++;
                }
            }
            $dataMutu[] = [$m->uraian, $kg, $palletCount];
        }

        return ['gudang' => $dataGudang, 'mutu' => $dataMutu];
    }

    // =========================================================================
    // E. LOGIKA PENJUALAN (TABEL V) - 🔥 SINKRON DENGAN CONTROLLER PENJUALAN
    // =========================================================================
    // =========================================================================
    // E. LOGIKA PENJUALAN (TABEL V) - 🔥 SINKRON DENGAN CONTROLLER PENJUALAN
    // =========================================================================
    private function getDataPenjualan($tanggal)
    {
        // 1. Inisialisasi Tanggal
        $selectedDate = $tanggal->copy()->startOfDay();
        
        $startOfYear = $selectedDate->copy()->startOfYear(); // 1 Januari tahun berjalan
        $startOfMonth = $selectedDate->copy()->startOfMonth(); // Tanggal 1 bulan berjalan

        // 2. Ambil Keterangan Hari Ini (is_summary = 1 hanya untuk narik "keterangan")
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)
            ->where('is_summary', 1)
            ->get()
            ->keyBy('uraian');

        // 3. Define Master Uraian
        $masterUraian = [
            '5.1' => 'SIR20 PTNBL', 
            '5.2' => 'SIR20 PTPN4'
        ];
        
        $tabelSummary = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB->get($uraian);

            // A. Logika: s/d Bulan Lalu 
            // Jika Januari, otomatis 0. Jika bukan, sum dari 1 Januari s/d akhir bulan lalu.
            if ($selectedDate->month == 1) {
                $sd_bln_lalu = 0;
            } else {
                $sd_bln_lalu = PenjualanSir20::where('uraian', $uraian)
                    ->where('is_summary', 0)
                    ->whereBetween('tanggal', [
                        $startOfYear->format('Y-m-d'), 
                        $startOfMonth->copy()->subDay()->format('Y-m-d')
                    ])
                    ->sum('hari_ini');
            }

            // B. Logika: Bulan Ini Yg Lalu = SUM dari tgl 1 bulan ini sampai H-1
            $bln_ini_lalu = PenjualanSir20::where('uraian', $uraian)
                ->where('is_summary', 0)
                ->whereBetween('tanggal', [
                    $startOfMonth->format('Y-m-d'), 
                    $selectedDate->copy()->subDay()->format('Y-m-d')
                ])
                ->sum('hari_ini');

            // C. Penjualan Hari Ini
            $hari_ini = PenjualanSir20::where('uraian', $uraian)
                ->where('is_summary', 0)
                ->whereDate('tanggal', $selectedDate)
                ->sum('hari_ini');

            // D. Hitung Total
            $total_bln_ini     = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bln_lalu + $total_bln_ini;

            // E. Masukkan ke Collection
            $tabelSummary->push((object)[
                'no'                => $no,
                'uraian'            => $uraian,
                'sd_bulan_lalu'     => $sd_bln_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan'        => $itemToday->keterangan ?? '-',
            ]);
        }

        return $tabelSummary;
    }

    // =========================================================================
    // 4. EXPORT EXCEL BULANAN 📅
    // =========================================================================
    public function exportExcelBulanan(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        if (!$bulan || !$tahun) {
            return back()->with('error', 'Bulan dan Tahun harus dipilih.');
        }

        // Format nama file: Laporan_Harian_Produksi_January_2026.xlsx
        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->translatedFormat('F_Y');
        $namaFile = "Laporan_Harian_Produksi_{$namaBulan}.xlsx";

        // Bersihkan Buffer
        if (ob_get_length()) { ob_end_clean(); }
        while (ob_get_level() > 0) { ob_end_clean(); }

        // Pastikan Maswi sudah punya file LaporanBulananExport di folder Exports
        return Excel::download(new LaporanBulananExport($bulan, $tahun), $namaFile);
    }

    // Helper Object Creator untuk Maturasi
    private function createMaturasiObj($bak, $awal, $olah, $mutasi, $masuk, $akhir, $tgl, $umur, $ket) {
        return (object)[
            'no_bak' => $bak->no_bak ?? $bak->uraian, 'tgl_isi' => $tgl, 'jenis' => $bak->jenis ?? $bak->asal_bokar, 
            'umur' => $umur, 'kering' => $awal, 'diolah' => $olah, 'mutasi' => $mutasi, 'masuk_hi' => $masuk, 'stok_akhir' => $akhir, 'keterangan' => $ket
        ];
    }
}