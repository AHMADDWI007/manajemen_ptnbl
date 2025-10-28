<?php

namespace App\Http\Controllers;

use App\Models\PengolahanMaturasi; // Pastikan nama model benar
use App\Models\PengolahanBasah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PengolahanMaturasiController extends Controller
{
    /**
     * Menampilkan data maturasi untuk tanggal tertentu (default hari ini).
     */
    public function index(Request $request)
    {
        // 1. Tentukan Tanggal yang Akan Ditampilkan
        $selectedDate = $request->input('filter_tanggal') ? Carbon::parse($request->input('filter_tanggal')) : Carbon::today();
        $previousDate = $selectedDate->copy()->subDay();

        // 2. Ambil Data yang Relevan dari Database (Menggunakan created_at)
        $maturasiHariIni = PengolahanMaturasi::whereDate('created_at', $selectedDate)->get()->keyBy('uraian');
        $maturasiKemarin = PengolahanMaturasi::whereDate('created_at', $previousDate)->get()->keyBy('uraian');
        $pengolahanBasahHariIni = PengolahanBasah::whereDate('tanggal', $selectedDate)->get()->keyBy('bak_maturasi');

        // 3. Siapkan Array Data untuk 49 Bak
        $dataTampilan = new Collection();

        for ($i = 1; $i <= 49; $i++) {
            $uraian = "Di Bak Maturasi " . $i;
            $bakName = "Bak Maturasi " . $i;

            $dataBak = [
                'id' => null,
                'tanggal_input_view' => $selectedDate->format('d-m-Y'),
                'uraian' => $uraian,
                'stok_awal' => 0.00,
                'tgl_masuk' => null,
                'umur' => 0,
                'diolah' => 0.00,
                'mutasi' => 0.00,
                'masuk_hi' => 0.00,
                'stok_akhir' => 0.00,
                'keterangan' => $selectedDate->isoFormat('D MMMM'),
                'created_at_view' => null, // Untuk kolom Tanggal Input asli (jika data ada)
                'asal_bokar' => 'Petani'
            ];

            // Cek data HARI INI
            if (isset($maturasiHariIni[$uraian])) {
                $item = $maturasiHariIni[$uraian];
                $dataBak['id'] = $item->id;
                $dataBak['stok_awal'] = $item->stok_awal ?? 0.00;
                $dataBak['tgl_masuk'] = $item->tgl_masuk;
                $dataBak['umur'] = $item->umur ?? 0;
                $dataBak['diolah'] = $item->diolah ?? 0.00;
                $dataBak['mutasi'] = $item->mutasi ?? 0.00;
                $dataBak['masuk_hi'] = $item->masuk_hi ?? 0.00;
                $dataBak['stok_akhir'] = $item->stok_akhir ?? 0.00;
                $dataBak['keterangan'] = $item->keterangan ?? $selectedDate->isoFormat('D MMMM');
                $dataBak['created_at_view'] = $item->created_at; // Ambil created_at asli
                $dataBak['asal_bokar'] = $item->asal_bokar ?? 'Petani';
            }
            // Jika data HARI INI belum ada, cek data KEMARIN
            elseif (isset($maturasiKemarin[$uraian])) {
                $itemKemarin = $maturasiKemarin[$uraian];
                $dataBak['stok_awal'] = $itemKemarin->stok_akhir ?? 0.00;
                $dataBak['umur'] = ($itemKemarin->umur ?? -1) + 1;
                $dataBak['tgl_masuk'] = $itemKemarin->tgl_masuk;

                // Cek Pengolahan Basah HARI INI untuk MASUK HI
                if (isset($pengolahanBasahHariIni[$bakName])) {
                     $itemBasah = $pengolahanBasahHariIni[$bakName];
                     $dataBak['masuk_hi'] = $itemBasah->netto_kering ?? 0.00;
                }
                $dataBak['stok_akhir'] = $dataBak['stok_awal'] + $dataBak['masuk_hi']; // Hitung stok akhir default
            }
            // Jika data kemarin juga tidak ada
            else {
                 // Cek Pengolahan Basah HARI INI untuk MASUK HI
                if (isset($pengolahanBasahHariIni[$bakName])) {
                     $itemBasah = $pengolahanBasahHariIni[$bakName];
                     $dataBak['masuk_hi'] = $itemBasah->netto_kering ?? 0.00;
                     $dataBak['stok_akhir'] = $dataBak['masuk_hi'];
                     // $dataBak['tgl_masuk'] = $selectedDate; // Opsional
                }
            }

            $dataTampilan->push($dataBak);
        }

        return view('Pengolahan.data_maturasi', [ // Pastikan nama view benar
            'data_maturasi' => $dataTampilan,
            'selected_date' => $selectedDate->format('Y-m-d')
        ]);
    }

    /**
     * Menyimpan data baru maturasi. Tanggal input diambil dari created_at.
     */
    public function store(Request $request)
    {
        // Hapus 'tanggal_input' dari validasi
        $validator = Validator::make($request->all(), [
            'uraian' => 'required|string',
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $stok_akhir = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);

        // Ambil tgl_masuk dari data hari sebelumnya
        // Kita butuh tanggal input HARIAN dari form untuk mencari data kemarin
        // Karena 'tanggal_input' tidak lagi disimpan, kita ambil dari created_at atau request (jika ada)
        // Solusi: Ambil tanggal dari request (hidden input jika perlu, atau gunakan created_at jika baru disimpan)
        // Untuk simple, kita query lagi berdasarkan uraian saja (asumsi data kemarin unik per uraian)
        $tanggalInputHariIni = Carbon::today(); // Asumsi input selalu untuk hari ini jika tidak ada info lain
        $tanggalKemarin = $tanggalInputHariIni->copy()->subDay();
         $previousMaturasi = PengolahanMaturasi::where('uraian', $data['uraian'])
                            ->whereDate('created_at', $tanggalKemarin) // Cari berdasarkan created_at kemarin
                            ->orderBy('created_at', 'desc')
                            ->first();
        $tgl_masuk_stok = $previousMaturasi ? $previousMaturasi->tgl_masuk : null;

        if (($data['masuk_hi'] ?? 0) > 0 && is_null($tgl_masuk_stok) && ($data['stok_awal'] ?? 0) == 0) {
             $tgl_masuk_stok = $tanggalInputHariIni; // Set tgl_masuk hari ini
        }

        // Hapus 'tanggal_input' dari data yang disimpan
        PengolahanMaturasi::create(array_merge($data, [
            'stok_akhir' => $stok_akhir,
            'tgl_masuk' => $tgl_masuk_stok
            // created_at akan otomatis terisi
        ]));

        // Redirect kembali ke tanggal hari ini (atau tanggal dari form jika ada)
        return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalInputHariIni->format('Y-m-d')])
                         ->with('success', 'Data maturasi berhasil ditambahkan.');
    }

    /**
     * Ambil data sebelumnya untuk modal tambah.
     */
    public function getPreviousData(Request $request)
    {
        // Ganti nama parameter request
        $validator = Validator::make($request->all(),[
            'uraian' => 'required|string',
            'tanggal_filter' => 'required|date_format:Y-m-d', // Parameter dari JS
        ]);
         if ($validator->fails()) {
            return response()->json(['error' => 'Input tidak valid'], 400);
        }

        $uraian = $request->input('uraian');
        $tanggalFilter = Carbon::parse($request->input('tanggal_filter'));
        $tanggalKemarin = $tanggalFilter->copy()->subDay();

        // Cari data kemarin berdasarkan created_at
        $previousMaturasi = PengolahanMaturasi::where('uraian', $uraian)
                            ->whereDate('created_at', $tanggalKemarin)
                            ->orderBy('created_at', 'desc')->first();

        $stok_awal = $previousMaturasi ? $previousMaturasi->stok_akhir : 0;
        $umur = $previousMaturasi ? ($previousMaturasi->umur + 1) : 0;

        // Ambil Netto Kering dari Pengolahan Basah Hari Ini (tanggal filter)
        $netto_kering_hari_ini = 0;
        if (preg_match('/Bak Maturasi (\d+)/', $uraian, $matches)) {
            $bakMaturasiName = "Bak Maturasi " . $matches[1];
            $pengolahanBasahHariIni = PengolahanBasah::where('bak_maturasi', $bakMaturasiName)
                                    ->whereDate('tanggal', $tanggalFilter) // Gunakan tanggal filter
                                    ->orderBy('created_at', 'desc')->first();
            if ($pengolahanBasahHariIni) {
                $netto_kering_hari_ini = $pengolahanBasahHariIni->netto_kering ?? 0;
            }
        }

        return response()->json([
            'stok_awal' => $stok_awal,
            'umur' => $umur,
            'netto_kering_hi' => $netto_kering_hari_ini
        ]);
    }


    public function show($id)
    {
        $data = PengolahanMaturasi::findOrFail($id);
        return response()->json($data);
    }

    public function edit($id)
    {
        $data = PengolahanMaturasi::findOrFail($id);
        // Tidak perlu $data->tanggal_input_edit lagi
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $maturasi = PengolahanMaturasi::findOrFail($id);

        // Hapus 'tanggal_input' dari validasi
        $validator = Validator::make($request->all(), [
            'stok_awal' => 'required|numeric|min:0',
            'umur' => 'required|integer|min:0',
            'tgl_masuk' => 'nullable|date',
            'diolah' => 'nullable|numeric|min:0',
            'mutasi' => 'nullable|numeric|min:0',
            'masuk_hi' => 'nullable|numeric|min:0',
            'asal_bokar' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:255',
            // Uraian tidak divalidasi karena readonly
        ]);

         if ($validator->fails()) {
            // Ambil tanggal dari data yang ada untuk redirect
            $tanggalRedirect = Carbon::parse($maturasi->created_at)->format('Y-m-d');
            return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalRedirect])
                             ->withErrors($validator)
                             ->withInput()
                             ->with(['edit_error' => true, 'edit_id' => $id]);
        }

        $data = $validator->validated();
        $stok_akhir = ($data['stok_awal'] ?? 0) - ($data['diolah'] ?? 0) - ($data['mutasi'] ?? 0) + ($data['masuk_hi'] ?? 0);

        // Hapus 'tanggal_input' dari data update
        // Pastikan 'uraian' tidak ikut terupdate (jika ada di $data)
        unset($data['uraian']); // Hapus uraian dari array data update

        $maturasi->update(array_merge($data, ['stok_akhir' => $stok_akhir]));

        $tanggalRedirect = Carbon::parse($maturasi->created_at)->format('Y-m-d');
        return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggalRedirect])
                         ->with('success', 'Data maturasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $maturasi = PengolahanMaturasi::findOrFail($id);
        $tanggal_input = Carbon::parse($maturasi->created_at)->format('Y-m-d');
        $maturasi->delete();
        return redirect()->route('maturasi.index', ['filter_tanggal' => $tanggal_input])
                         ->with('success', 'Data berhasil dihapus.');
    }
}