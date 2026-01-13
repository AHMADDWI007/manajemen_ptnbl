<?php

    namespace App\Http\Controllers;

    use App\Models\ProduksiSir;
    use App\Models\BahanProses; // Pastikan Model BahanProses di-import
    use Illuminate\Http\Request;
    use Carbon\Carbon;
    use Illuminate\Support\Collection;

    class DataSirController extends Controller
    {
        public function index(Request $request)
        {
            $selectedDate = $request->input('filter_tanggal') 
                ? Carbon::parse($request->input('filter_tanggal')) 
                : Carbon::today();

            // 1. Ambil data HARI INI
            $dataDB = ProduksiSir::whereDate('created_at', $selectedDate)->get()->keyBy('uraian');

            // 2. Ambil data TERAKHIR (sebelum hari ini) untuk cari saldo akhir kemarin
            $prevDataDB = ProduksiSir::whereDate('created_at', '<', $selectedDate)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->unique('uraian')
                            ->keyBy('uraian');

            // =========================================================================
            // A. HITUNG TOTAL SALDO BAHAN PROSES (I - III)
            // =========================================================================
            $totalBahanProses = BahanProses::whereDate('tanggal', $selectedDate)->sum('saldo_akhir');

            // FALLBACK: Jika data Bahan Proses hari ini kosong, ambil data terakhir
            if ($totalBahanProses == 0 && BahanProses::whereDate('tanggal', $selectedDate)->count() == 0) {
                $lastBPDate = BahanProses::whereDate('tanggal', '<', $selectedDate)->max('tanggal');
                if ($lastBPDate) {
                    $totalBahanProses = BahanProses::whereDate('tanggal', $lastBPDate)->sum('saldo_akhir');
                }
            }

            // =========================================================================
            // B. SIAPKAN DATA TABEL IV (GUDANG)
            // =========================================================================
            $masterGudang = [
                '4.1' => 'Di Gudang SIR',
                '4.2' => 'Di Areal Press Bale',
                '4.3' => 'Di Gudang TOH 1',
                '4.4' => 'Di Gudang TOH 2',
            ];

            $tabelIV = new Collection();
            $totalSaldoGudang = 0;

            foreach ($masterGudang as $no => $namaGudang) {
                $item = $dataDB[$namaGudang] ?? null;
                $prevItem = $prevDataDB[$namaGudang] ?? null;

                // 1. TENTUKAN SALDO AWAL
                if ($item) {
                    $saldo_awal = $item->saldo_awal;
                } else {
                    $saldo_awal = $prevItem ? $prevItem->saldo_akhir : 0;
                }

                // 2. TENTUKAN "YG LALU"
                if ($item) {
                    $prod_bln_lalu = $item->prod_bln_lalu; 
                } else {
                    $prod_bln_lalu = $prevItem ? $prevItem->prod_sd_hi : 0;
                }

                // 3. AMBIL INPUTAN
                $masuk = $item->masuk ?? 0;
                $pengiriman = $item->pengiriman ?? 0;

                // 4. HITUNG ULANG TAMPILAN
                $total = $saldo_awal + $masuk;
                $prod_sd_hi = $prod_bln_lalu + $masuk; 
                $saldo_akhir = $total - $pengiriman;

                // Akumulasi Total Saldo Gudang (Untuk rumus Grand Total nanti)
                $totalSaldoGudang += $saldo_akhir;

                $tabelIV->push((object)[
                    'id' => $item->id ?? null,
                    'no' => $no,
                    'uraian' => $namaGudang,
                    'saldo_awal' => $saldo_awal,
                    'masuk' => $masuk,
                    'total' => $total,
                    'prod_bln_lalu' => $prod_bln_lalu,
                    'prod_sd_hi' => $prod_sd_hi,
                    'pengiriman' => $pengiriman,
                    'saldo_akhir' => $saldo_akhir,
                    'ptnb' => $saldo_akhir, 
                    // Kita set 0 atau null saja karena di baris akan ditampilkan Strip (-)
                    'total_i_sd_iv' => 0, 
                    'keterangan' => $item->keterangan ?? '-',
                ]);
            }

            // =========================================================================
            // C. HITUNG GRAND TOTAL (I s/d IV) UNTUK FOOTER
            // =========================================================================
            // Rumus: Total Bahan Proses + Total Gudang SIR
            $grandTotal = $totalBahanProses + $totalSaldoGudang;

            // --- TABEL VI (MUTU) ---
            $masterMutu = [
                '6.1' => 'Mutu Prima (siap jual)',
                '6.2' => 'PO / PRI Low',
                '6.3' => 'WhiteSpot (WS)',
                '6.4' => 'Kontaminasi',
                '6.5' => 'Repacking On Hold',
            ];
            
            $tabelVI = new Collection();
            foreach ($masterMutu as $no => $uraian) {
                $item = $dataDB[$uraian] ?? null;
                $tabelVI->push((object)[
                    'id' => $item->id ?? null,
                    'no' => $no,
                    'uraian' => $uraian,
                    'kg' => $item->kg ?? 0,
                    'pallet' => $item->pallet ?? 0,
                    'keterangan' => $item->keterangan ?? '-',
                ]);
            }

            return view('Pengolahan.data_sir', [
                'tabelIV' => $tabelIV,
                'tabelVI' => $tabelVI,
                'selected_date' => $selectedDate->format('Y-m-d'),
                'grandTotal' => $grandTotal // <--- Variabel ini dikirim khusus untuk footer
            ]);
        }

        public function store(Request $request)
        {
            $request->validate([
                'tanggal' => 'required|date',
                'uraian' => 'required|string',
            ]);

            $tgl = Carbon::parse($request->tanggal);
            
            $prevData = ProduksiSir::where('uraian', $request->uraian)
                        ->whereDate('created_at', '<', $tgl)
                        ->orderBy('created_at', 'desc')
                        ->first();

            $saldo_awal = $prevData ? $prevData->saldo_akhir : 0;
            $prod_bln_lalu = $prevData ? $prevData->prod_sd_hi : 0;

            $isMutu = in_array($request->uraian, [
                'Mutu Prima (siap jual)', 'PO / PRI Low', 'WhiteSpot (WS)', 
                'Kontaminasi', 'Repacking On Hold'
            ]);

            if ($isMutu) {
                $dataToUpdate = [
                    'kg' => $request->kg,
                    'pallet' => $request->pallet,
                    'keterangan' => $request->keterangan
                ];
            } else {
                $masuk = $request->masuk ?? 0;
                $pengiriman = $request->pengiriman ?? 0;

                $total = $saldo_awal + $masuk;
                $prod_sd_hi = $prod_bln_lalu + $masuk; 
                $saldo_akhir = $total - $pengiriman;

                $dataToUpdate = [
                    'saldo_awal' => $saldo_awal,
                    'masuk' => $masuk,
                    'total' => $total,
                    'prod_bln_lalu' => $prod_bln_lalu,
                    'prod_sd_hi' => $prod_sd_hi,
                    'pengiriman' => $pengiriman,
                    'saldo_akhir' => $saldo_akhir,
                    'keterangan' => $request->keterangan
                ];
            }

            ProduksiSir::updateOrCreate(
                [
                    'uraian' => $request->uraian,
                    'created_at' => $tgl->format('Y-m-d H:i:s')
                ],
                $dataToUpdate + ['updated_at' => Carbon::now()]
            );

            return redirect()->route('data-sir.index', ['filter_tanggal' => $tgl->format('Y-m-d')])
                            ->with('success', 'Data berhasil disimpan!');
        }

        public function getJson($id)
        {
            $data = ProduksiSir::find($id);
            if (!$data) return response()->json(['message' => 'Data tidak ditemukan'], 404);
            return response()->json($data);
        }

        public function destroy($id)
        {
            $data = ProduksiSir::find($id);
            if ($data) {
                $data->delete();
                return back()->with('success', 'Data berhasil di-reset / dihapus.');
            }
            return back()->with('error', 'Data tidak ditemukan.');
        }
    }