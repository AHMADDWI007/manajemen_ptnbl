<?php

namespace App\Http\Controllers;

use App\Models\ProduksiSirBaru;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProduksiSirBaruController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->input('filter_tanggal') 
            ? Carbon::parse($request->input('filter_tanggal')) 
            : Carbon::today();

        // Ambil data untuk tanggal tersebut
        $data = ProduksiSirBaru::whereDate('tanggal', $selectedDate)->get();

        // Mapping data berdasarkan shift agar mudah dipanggil di view
        // $shifts[1] untuk Shift 1, $shifts[2] untuk Shift 2, dst.
        $shifts = [
            1 => $data->firstWhere('shift', 1),
            2 => $data->firstWhere('shift', 2),
            3 => $data->firstWhere('shift', 3),
        ];

        return view('Pengolahan.produksi_sir_baru', [
            'selected_date' => $selectedDate->format('Y-m-d'),
            'shifts' => $shifts
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'data' => 'required|array', // Data dikirim dalam bentuk array per shift
        ]);

        $tanggal = $request->tanggal;
        $inputData = $request->data; // Struktur: data[1][remah_berat], data[2][...], dst

        foreach ($inputData as $shiftIndex => $values) {
            // Bersihkan format angka (jika ada koma diganti titik)
            foreach ($values as $key => $val) {
                if (is_numeric($val) || $val == '') continue;
                // Cek jika kolom adalah kolom angka/decimal
                // Disini kita lakukan pembersihan sederhana jika user input pakai koma
                // (Optional, tergantung kebutuhan)
            }

            // Simpan atau Update data per shift
            ProduksiSirBaru::updateOrCreate(
                [
                    'tanggal' => $tanggal,
                    'shift' => $shiftIndex
                ],
                $values // Masukkan semua kolom inputan
            );
        }

        return redirect()->route('produksi-sir-baru.index', ['filter_tanggal' => $tanggal])
                         ->with('success', 'Laporan Harian Produksi SIR 20 berhasil disimpan!');
    }
    
    // Fitur Reset/Hapus per tanggal jika diperlukan
    public function destroy($id)
    {
        // Cari data berdasarkan ID shift tertentu
        $item = ProduksiSirBaru::find($id);
        if($item) {
            $tanggal = $item->tanggal;
            // Hapus semua shift di tanggal yang sama (opsional, atau hapus satu saja)
            ProduksiSirBaru::whereDate('tanggal', $tanggal)->delete();
            
            return redirect()->route('produksi-sir-baru.index', ['filter_tanggal' => $tanggal])
                             ->with('success', 'Data tanggal tersebut telah di-reset.');
        }
        return back();
    }
}