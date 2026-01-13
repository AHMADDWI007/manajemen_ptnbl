<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        /* Styling Header Card seperti Data SIR */
        .card-header { font-weight: bold; text-transform: uppercase; }
        .bg-success { background-color: #28a745 !important; }
        
        /* Styling Tabel Formulir */
        .table-form th, .table-form td {
            vertical-align: middle;
            padding: 4px 8px;
            border: 1px solid #000 !important; /* Garis hitam tegas */
            font-size: 14px;
        }
        .header-gray {
            background-color: #e9ecef; /* Abu-abu muda untuk header tabel */
            text-align: center;
            font-weight: bold;
            color: #000;
        }
        
        /* Input Transparan agar terlihat menyatu */
        .table-input {
            width: 100%;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 500;
            color: #000;
        }
        .table-input:focus {
            outline: none;
            background-color: #e8f0fe;
        }
        
        /* Layout Kolom */
        .col-label { font-weight: bold; width: 35%; }
        .col-shift { width: 18%; text-align: center; font-weight: bold; }
        .col-unit { width: 11%; text-align: left; font-style: italic; color: #444; border-left: none !important; }
        
        .indent-1 { padding-left: 20px !important; }
        .bg-section { background-color: #d1e7dd; font-weight: bold; } /* Hijau muda pemisah section */
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Laporan Harian Produksi SIR 20</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- FILTER TANGGAL & RESET --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                        <form method="GET" action="{{ route('produksi-sir-baru.index') }}" class="form-inline">
                            <label class="mr-2 font-weight-bold">Tanggal Laporan:</label>
                            <input type="date" name="filter_tanggal" class="form-control form-control-sm mr-2" 
                                   value="{{ $selected_date }}" onchange="this.form.submit()">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-search"></i> Cari</button>
                        </form>
                        
                        @if(isset($shifts[1]) || isset($shifts[2]) || isset($shifts[3]))
                            <form action="{{ route('produksi-sir-baru.destroy', $shifts[1]->id ?? ($shifts[2]->id ?? $shifts[3]->id)) }}" 
                                  method="POST" onsubmit="return confirm('Reset data tanggal ini? Semua shift akan dihapus.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm font-weight-bold">
                                    <i class="fas fa-trash mr-1"></i> Reset Data
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                {{-- FORM INPUT UTAMA --}}
                <form action="{{ route('produksi-sir-baru.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $selected_date }}">

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            INPUT DATA PER SHIFT
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-form mb-0">
                                    <thead>
                                        <tr class="header-gray">
                                            <th class="col-label text-left pl-3" style="border-right:1px solid #000;">URAIAN</th>
                                            <th class="col-shift">SHIFT 1</th>
                                            <th class="col-shift">SHIFT 2</th>
                                            <th class="col-shift">SHIFT 3</th>
                                            <th class="col-unit" style="border-left:1px solid #000;">SATUAN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        {{-- 1. REMAHAN YANG DIPROSES --}}
                                        <tr class="bg-section"><td colspan="5">1. REMAHAN YANG DIPROSES</td></tr>
                                        <tr>
                                            <td class="indent-1">1. Ruang Maturasi</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" name="data[{{$s}}][remah_ruang_maturasi]" value="{{ $shifts[$s]->remah_ruang_maturasi ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit"></td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">2. Berat Remahan (Kg)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][remah_berat]" value="{{ $shifts[$s]->remah_berat ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">3. Tanggal Masuk</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="date" class="table-input" name="data[{{$s}}][remah_tgl_masuk]" value="{{ $shifts[$s]->remah_tgl_masuk ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit"></td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">4. Umur (Hari)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" class="table-input" name="data[{{$s}}][remah_umur]" value="{{ $shifts[$s]->remah_umur ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Hari</td>
                                        </tr>

                                        {{-- 2. PENGERINGAN --}}
                                        <tr class="bg-section"><td colspan="5">2. PENGERINGAN</td></tr>
                                        <tr>
                                            <td class="indent-1">A. JAM START DRYER</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="time" class="table-input" name="data[{{$s}}][dryer_jam_start]" value="{{ isset($shifts[$s]->dryer_jam_start) ? \Carbon\Carbon::parse($shifts[$s]->dryer_jam_start)->format('H:i') : '' }}"></td>
                                            @endfor
                                            <td class="col-unit"></td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">B. JUMLAH TROLLY DIISI/MASUK</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" class="table-input" name="data[{{$s}}][dryer_trolly_masuk]" value="{{ $shifts[$s]->dryer_trolly_masuk ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Trolly</td>
                                        </tr>

                                        {{-- C. AKTUAL TEMPERATUR --}}
                                        <tr>
                                            <td class="indent-1 font-weight-bold" colspan="5" style="background-color: #f8f9fa;">C. AKTUAL TEMPERATUR</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1 pl-4">- BURNER 1</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" placeholder="Contoh: 126-128" name="data[{{$s}}][dryer_temp_burner_1]" value="{{ $shifts[$s]->dryer_temp_burner_1 ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">°C</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1 pl-4">- BURNER 2</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" placeholder="Contoh: 119-120" name="data[{{$s}}][dryer_temp_burner_2]" value="{{ $shifts[$s]->dryer_temp_burner_2 ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">°C</td>
                                        </tr>

                                        <tr>
                                            <td class="indent-1">D. WAKTU SETIAP CYCLE</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" placeholder="13.30-13.40" name="data[{{$s}}][dryer_cycle_time]" value="{{ $shifts[$s]->dryer_cycle_time ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Minute</td>
                                        </tr>

                                        {{-- E. BAHAN BAKAR --}}
                                        <tr>
                                            <td class="indent-1">E.(1) BAHAN BAKAR : SOLAR</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_solar]" value="{{ $shifts[$s]->bb_solar ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Liter</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">E.(2) BAHAN BAKAR : BATUBARA</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_batubara]" value="{{ $shifts[$s]->bb_batubara ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">E.(3) BAHAN BAKAR : CANGKANG</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_cangkang]" value="{{ $shifts[$s]->bb_cangkang ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>

                                        <tr>
                                            <td class="indent-1">F. JUMLAH TROLLY KELUAR</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" class="table-input" name="data[{{$s}}][dryer_trolly_keluar]" value="{{ $shifts[$s]->dryer_trolly_keluar ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Trolly</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">G. JAM STOP DRYER</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="time" class="table-input" name="data[{{$s}}][dryer_jam_stop]" value="{{ isset($shifts[$s]->dryer_jam_stop) ? \Carbon\Carbon::parse($shifts[$s]->dryer_jam_stop)->format('H:i') : '' }}"></td>
                                            @endfor
                                            <td class="col-unit"></td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">H. JUMLAH JAM JALAN DRYER (G-A)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input bg-light" readonly placeholder="Auto" name="data[{{$s}}][dryer_jam_jalan]" value="{{ $shifts[$s]->dryer_jam_jalan ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Jam</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">I. JUMLAH BALES YANG DI PRESS</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" class="table-input" name="data[{{$s}}][press_jml_bales]" value="{{ $shifts[$s]->press_jml_bales ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Bales</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">J. KG YANG DI PRESS (I x 35 Kg)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input bg-light" readonly placeholder="Auto" name="data[{{$s}}][press_kg]" value="{{ $shifts[$s]->press_kg ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">K. CAPACITY PER JAM (J : H)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input bg-light" readonly placeholder="Auto" name="data[{{$s}}][capacity_per_jam]" value="{{ $shifts[$s]->capacity_per_jam ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg/Jam</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">L. JAM KERJA</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][jam_kerja]" value="{{ $shifts[$s]->jam_kerja ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Jam</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">M. PRODUKTIVITAS (Kg : Jam Kerja)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input bg-light" readonly placeholder="Auto" name="data[{{$s}}][produktivitas]" value="{{ $shifts[$s]->produktivitas ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">N. KG SIR 20 / Cake</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][kg_sir_20_cake]" value="{{ $shifts[$s]->kg_sir_20_cake ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">O. HASIL BALES KONTAMINASI LOGAM</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" name="data[{{$s}}][kontaminasi_bales]" value="{{ $shifts[$s]->kontaminasi_bales ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Bales</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">P. BERAT KONTAMINAN</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.0001" placeholder="0.00" class="table-input" name="data[{{$s}}][kontaminasi_berat]" value="{{ $shifts[$s]->kontaminasi_berat ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Gram</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">Q. JAM OPERASIONAL GENSET</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][jam_ops_genset]" value="{{ $shifts[$s]->jam_ops_genset ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Jam</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">R.(1) BAHAN BAKAR : SOLAR / TON</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_solar_per_ton]" value="{{ $shifts[$s]->bb_solar_per_ton ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Liter</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">R.(2) BAHAN BAKAR : BATUBARA / TON</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_batubara_per_ton]" value="{{ $shifts[$s]->bb_batubara_per_ton ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">R.(3) BAHAN BAKAR : CANGKANG / TON</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][bb_cangkang_per_ton]" value="{{ $shifts[$s]->bb_cangkang_per_ton ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">Kg</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">S. PEMAKAIAN LISTRIK PLN</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" step="0.01" class="table-input" name="data[{{$s}}][listrik_pln]" value="{{ $shifts[$s]->listrik_pln ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">KWH</td>
                                        </tr>

                                        {{-- 3. PACKING ROOM --}}
                                        <tr class="bg-section"><td colspan="5">3. PACKING ROOM</td></tr>
                                        <tr>
                                            <td class="indent-1">A. JUMLAH PALLET DIISI</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="number" class="table-input" name="data[{{$s}}][packing_jml_pallet]" value="{{ $shifts[$s]->packing_jml_pallet ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit">SW</td>
                                        </tr>
                                        <tr>
                                            <td class="indent-1">B. NOMOR (SW: s/d)</td>
                                            @for($s=1; $s<=3; $s++)
                                                <td><input type="text" class="table-input" placeholder="3620 s/d 3634" name="data[{{$s}}][packing_nomor]" value="{{ $shifts[$s]->packing_nomor ?? '' }}"></td>
                                            @endfor
                                            <td class="col-unit"></td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-right bg-white">
                            <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                                <i class="fas fa-save mr-2"></i> SIMPAN SEMUA DATA
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <footer class="main-footer">@include('template.footer')</footer>
</div>

@include('template.script')
</body>
</html>