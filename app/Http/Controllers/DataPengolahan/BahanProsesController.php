<?php

namespace App\Http\Controllers\DataPengolahan;

use App\Http\Controllers\Controller;
use App\Models\BahanProses;
use App\Models\PengolahanMaturasi;
use App\Models\PengolahanBasah;
use App\Models\TransaksiApiBokar;
use App\Models\RektifikasiStok;
use App\Models\ProduksiSir20;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BahanProsesController extends Controller
{
    // Urutan Proses Pabrik yang Baku
    private $masterUraian = [
        'Lantai Umpan Kering', 
        'Di Blending Tank 4', 
        'Di Lump Breaker-2 (Di Blending Tank-4)',
        'Di Pre Breaker-2 (Di Blending Tank-5)', 
        'Di Hammer Mill-2 (Di Blending Tank-6)',
        'Di Blending Tank-7', 
        'Di Trolley', 
        'Di Dalam Dryer/Press Bale', 
        'Di Reproses Ex WS.'
    ];

    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();
        
        // 1. Ambil data dari database berdasarkan tanggal
        $rawData = BahanProses::whereDate('tanggal', $selectedDate)->get();

        // 2. Cek apakah data tersedia
        if ($rawData->isEmpty()) {
            // Jika kosong (hari libur), buat data bayangan (virtual)
            $dataProduksi = $this->generateVirtualData($selectedDate);
        } else {
            // 🔥 LOGIKA PENGURUTAN: Memaksa urutan 1-9 sesuai masterUraian
            $dataProduksi = $rawData->sortBy(function($item) {
                return array_search($item->uraian, $this->masterUraian);
            })->values();
        }

        // 3. Kalkulasi total untuk footer tabel
        $totals = [
            'saldo_awal'     => $dataProduksi->sum('saldo_awal'),
            'wip_masuk'      => $dataProduksi->sum('wip_masuk'),
            'wip_keluar'     => $dataProduksi->sum('wip_keluar'),
            'produksi_sir20' => $dataProduksi->sum('produksi_sir20'),
            'rekfif'         => $dataProduksi->sum('rekfif'),
            'saldo_akhir'    => $dataProduksi->sum('saldo_akhir'),
        ];

        $stokAkhirBokar = $this->getStokAkhirBokar($selectedDate);
        $stokAkhirMaturasi = $this->getStokAkhirMaturasi($selectedDate);
        
        $grandTotalSaldoAkhir = $stokAkhirBokar + $stokAkhirMaturasi + $totals['saldo_akhir'];
        $grandTotalKeterangan = $totals['saldo_akhir'] + $stokAkhirMaturasi;

        return view('DataPengolahan.bahan-proses', [
            'data_produksi'        => $dataProduksi,
            'selectedDate'         => $selectedDate,
            'totals'               => $totals,
            'grandTotalSaldoAkhir' => $grandTotalSaldoAkhir,
            'grandTotalKeterangan' => $grandTotalKeterangan,
            'detail_bokar'         => $stokAkhirBokar,
            'detail_maturasi'      => $stokAkhirMaturasi,
            'detail_wip'           => $totals['saldo_akhir'],
            'masterUraian'         => $this->masterUraian
        ]);
    }

    /**
     * 🔥 SINKRONISASI BERANTAI & PEMBERSIHAN DATA SAMPAH
     */
    /**
     * 🔥 SINKRONISASI BERANTAI: MENJAMIN 9 URUTAN PROSES SELALU TERISI
     */
    /**
     * 🔥 LOGIKA EFISIENSI: Hanya simpan jika ada aktivitas nyata
     */
    /**
     * 🔥 LOGIKA FINAL: Bangkitkan 9 Baris Otomatis jika ada Aktivitas
     */
    public function syncChainData($startDate)
    {
        // 1. Ambil tanggal awal dalam format string
        $tglAwalStr = Carbon::parse($startDate)->toDateString();

        // 2. Cari semua tanggal unik yang SUDAH ADA di DB (>= tanggal input)
        // Ini yang mencegah looping liar sampai hari ini jika hari esok kosong
        $existingDates = BahanProses::whereDate('tanggal', '>=', $tglAwalStr)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal')
            ->toArray();

        // Pastikan tanggal yang diinput saat ini masuk dalam daftar proses
        if (!in_array($tglAwalStr, $existingDates)) {
            array_unshift($existingDates, $tglAwalStr);
        }

        // 3. Looping hanya pada tanggal yang valid
        foreach ($existingDates as $tglStr) {
            
            // Ambil data pendukung aktivitas pabrik
            $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $tglStr)
                ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
            $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
            
            $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $tglStr)->sum('kg_yang_dipress');
            
            // 🔥 PENENTU UTAMA: Jika Produksi ditiadakan, saklar aktivitas mati
            $adaAktivitasPabrik = ($inputDariMaturasi > 0 || $realProduction > 0);

            $prevWipKeluar = 0;

            foreach ($this->masterUraian as $index => $uraian) {
                $row = BahanProses::whereDate('tanggal', $tglStr)->where('uraian', $uraian)->first();
                
                // Cari Saldo Awal dari transaksi terakhir sebelum tanggal ini
                $lastData = BahanProses::where('uraian', $uraian)
                    ->whereDate('tanggal', '<', $tglStr)
                    ->orderBy('tanggal', 'desc')->first();
                
                $saldoAwal = $lastData ? $lastData->saldo_akhir : ($row ? $row->saldo_awal : 0);
                
                // 🔥 LOGIKA PAKSA NOL (SAMA SEPERTI MOBILE):
                // Jika aktivitas pabrik 0 (Dihapus), paksa Masuk, Keluar, dan Rektif jadi 0
                $wipMasuk      = ($adaAktivitasPabrik) ? (($index === 0) ? $inputDariMaturasi : $prevWipKeluar) : 0;
                $userWipKeluar = ($adaAktivitasPabrik) ? ($row ? $row->wip_keluar : 0) : 0;
                $rektif        = ($adaAktivitasPabrik) ? ($row ? $row->rekfif : 0) : 0;

                if ($uraian == 'Di Dalam Dryer/Press Bale') {
                    $finalWipKeluar = ($realProduction > 0) ? $realProduction : $userWipKeluar;
                    $produksi = $realProduction;
                } else {
                    $finalWipKeluar = $userWipKeluar;
                    $produksi = 0;
                }

                $saldoAkhir = ($saldoAwal + $wipMasuk + $rektif) - $finalWipKeluar;

                // Update database: Record tetap ada tapi nilainya 0 jika dihapus
                BahanProses::updateOrCreate(
                    ['tanggal' => $tglStr, 'uraian' => $uraian],
                    [
                        'saldo_awal'     => round($saldoAwal),
                        'wip_masuk'      => round($wipMasuk),
                        'wip_keluar'     => round($finalWipKeluar),
                        'produksi_sir20' => round($produksi),
                        'rekfif'         => round($rektif),
                        'saldo_akhir'    => round($saldoAkhir),
                    ]
                );
                
                $prevWipKeluar = ($uraian == 'Di Dalam Dryer/Press Bale') ? 0 : $finalWipKeluar;
            }
        }
    }

    private function generateVirtualData($date) {
        $list = collect();
        foreach($this->masterUraian as $u) {
            $last = BahanProses::where('uraian', $u)->whereDate('tanggal', '<', $date)->orderBy('tanggal', 'desc')->first();
            $item = new BahanProses();
            $item->uraian = $u;
            $item->tanggal = $date->format('Y-m-d');
            $item->saldo_awal = $last ? $last->saldo_akhir : 0;
            $item->wip_masuk = 0;
            $item->wip_keluar = 0;
            $item->produksi_sir20 = 0;
            $item->rekfif = 0;
            $item->saldo_akhir = $item->saldo_awal;
            $list->push($item);
        }
        return $list;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_input' => 'required|date',
            'wip_keluar'    => 'required|array',
            'rekfif'        => 'nullable|array',
        ]);

        if ($validator->fails()) return redirect()->back()->withErrors($validator);

        foreach ($request->wip_keluar as $uraian => $nilaiWip) {
            if ($nilaiWip !== null || isset($request->rekfif[$uraian])) {
                BahanProses::updateOrCreate(
                    ['tanggal' => $request->tanggal_input, 'uraian' => $uraian],
                    [
                        'wip_keluar' => round($nilaiWip ?? 0),
                        'rekfif'     => round($request->rekfif[$uraian] ?? 0),
                    ]
                );
            }
        }

        // Jalankan sinkronisasi berantai & pembersihan
        $this->syncChainData($request->tanggal_input);

        return redirect()->back()->with('success', 'Data berhasil disimpan dan disinkronkan.');
    }

    public function update(Request $request, $id)
    {
        $data = BahanProses::where('id_bahan_proses', $id)->firstOrFail();
        
        $saldoAwalBaru = round($request->saldo_awal);
        $saldoAkhirTarget = round($request->saldo_akhir);

        // Sesuaikan Rektif agar Saldo Akhir pas
        $rektif_baru = $saldoAkhirTarget - $saldoAwalBaru - $data->wip_masuk + $data->wip_keluar;
        
        $data->update([
            'saldo_awal' => $saldoAwalBaru,
            'rekfif'     => $rektif_baru,
            'keterangan' => 'Setup/Opname Manual'
        ]);

        $this->syncChainData($data->tanggal);

        return redirect()->back()->with('success', 'Saldo berhasil disesuaikan.');
    }

    public function destroy($id)
    {
        $data = BahanProses::where('id_bahan_proses', $id)->firstOrFail();
        $tanggal = $data->tanggal;
        
        // Reset nilai
        $data->update(['wip_keluar' => 0, 'rekfif' => 0, 'keterangan' => null]);

        $this->syncChainData($tanggal);
        
        return redirect()->back()->with('success', 'Data berhasil di-reset.');
    }

    public function getStokAkhirBokar($date) {
        $total_masuk = TransaksiApiBokar::whereDate('tanggal', '<=', $date)->whereIn('kode_api', ['petani', 'ptpn', 'inhut'])->sum('masuk_hi');
        $total_diolah = PengolahanBasah::whereDate('tanggal', '<=', $date)->sum('netto_kering');
        $total_rektif = RektifikasiStok::whereDate('tanggal', '<=', $date)->sum('berat');
        return max(0, round($total_masuk - $total_diolah + $total_rektif, 2));
    }

    private function getStokAkhirMaturasi($date) {
        $sums = PengolahanMaturasi::whereDate('tgl_laporan', '<=', $date)
            ->selectRaw('SUM(masuk_hi) as in_total, SUM(diolah) as out_process, SUM(mutasi) as out_mutation')->first();
        return $sums ? max(0, round($sums->in_total - $sums->out_process - $sums->out_mutation, 2)) : 0;
    }
}