<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Produksi SIR 20</title>

    {{-- CSS DataTables Bootstrap 4 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* --- STYLE KHUSUS MODAL LAPORAN --- */

        /* 1. Form Manual (Putih Bersih) */
        .form-control-sm {
            border-radius: 3px; /* Sedikit rounded */
        }
        
        /* 2. Form Otomatis (Kuning Lembut) */
        .auto-input {
            background-color: #fff9c4 !important; /* Kuning pastel */
            color: #333;
            font-weight: bold;
            border: 1px solid #e0e0e0;
            cursor: default; /* Kursor standar, bukan text */
        }
        .auto-input:focus {
            background-color: #fff9c4;
            box-shadow: none;
        }

        /* 3. Label & Satuan */
        label {
            font-size: 0.85rem; /* Font label agak kecil */
            font-weight: 600;
            margin-bottom: 2px;
            color: #495057;
        }
        .unit-label {
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 2; /* Agar sejajar vertikal dengan input sm */
            padding-left: 5px;
        }

        /* 4. Kotak Total Besar (Summary) */
        .summary-box {
            background: #e8f5e9; /* Hijau sangat muda */
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
        }
        .summary-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2e7d32;
            display: block;
        }
        .summary-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
        }

        /* 5. Tabel Maturasi Compact */
        .table-maturasi th { font-size: 0.85rem; padding: 8px; }
        .table-maturasi td { padding: 4px; }
        
        /* Modal Lebar */
        .modal-xl { max-width: 95%; }
        
        /* Garis Pemisah Halus */
        .divider { border-top: 1px solid #eee; margin: 15px 0; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        {{-- HEADER --}}
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Data Produksi SIR 20</h1>
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-toggle="modal" data-target="#modalInputLaporan">
                    <i class="fas fa-plus-circle"></i> Input Laporan Baru
                </button>
            </div>
        </div>

        {{-- KONTEN UTAMA --}}
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        <i class="fas fa-list mr-1"></i> Riwayat Produksi
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="dataTable" style="width:100%">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Total Remahan</th>
                                        <th>Total Produksi</th>
                                        <th>Jam Kerja</th>
                                        <th style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal_produksi)->format('d-m-Y') }}</td>
                                        <td class="text-center">{{ $item->shift_kerja }}</td>
                                        <td class="text-right">{{ number_format($item->remahan->sum('berat'), 0) }} Kg</td>
                                        <td class="text-right fw-bold">{{ number_format($item->kg_yang_dipress, 0) }} Kg</td>
                                        <td class="text-center">{{ $item->jam_kerja }} Jam</td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_produksi_sir20 }}" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

{{-- MODAL INPUT LAPORAN --}}
<div class="modal fade" id="modalInputLaporan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <form action="{{ route('produksi-sir20.store') }}" method="POST" id="formProduksi">
                @csrf
                <div class="modal-header bg-success text-white py-2"> 
                    <h5 class="modal-title fw-bold" style="font-size: 1.1rem;">Input Laporan Produksi SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body pt-2">
                    
                    {{-- HEADER FORM: TANGGAL --}}
                    <div class="row mb-3 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label>Tanggal Produksi</label>
                            <input type="date" name="tanggal_produksi" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label>Shift Kerja</label>
                            <select name="shift_kerja" class="form-control form-control-sm">
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                            </select>
                        </div>
                    </div>

                    {{-- TABEL MATURASI --}}
                    <h6 class="font-weight-bold text-success border-bottom pb-1">1. DATA MATURASI</h6>
                    <div class="row mb-3">
                        <div class="col-12">
                            <table class="table table-sm table-borderless table-maturasi mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="30%">Ruang Maturasi</th>
                                        <th width="30%">Berat Remahan (Kg)</th>
                                        <th width="30%">Umur (Hari)</th>
                                        <th>
                                            <button type="button" class="btn btn-success btn-xs btn-block" id="btnAddMaturasi">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="maturasiContainer">
                                    {{-- Baris Pertama (Default) --}}
                                    <tr>
                                        <td>
                                            {{-- Tambah class 'select-ruang' --}}
                                            <select name="maturasi[0][ruang]" class="form-control form-control-sm select-ruang" required>
                                                <option value="">- Pilih Ruang -</option>
                                                {{-- Loop Data dari Controller --}}
                                                @foreach($bak_aktif as $bak)
                                                    {{-- Tambah data attributes --}}
                                                    <option value="{{ $bak->uraian }}" 
                                                            data-berat="{{ $bak->stok_akhir }}" 
                                                            data-umur="{{ $bak->umur_real }}">
                                                        {{ $bak->uraian }} (Stok: {{ number_format($bak->stok_akhir, 0) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="maturasi[0][berat]" class="form-control form-control-sm input-berat" step="0.01" placeholder="0"></td>
                                        {{-- Tambah class 'input-umur' dan set readonly (opsional) --}}
                                        <td><input type="number" name="maturasi[0][umur]" class="form-control form-control-sm input-umur" placeholder="0" readonly></td>
                                        <td></td> 
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- GRID UTAMA --}}
                    <div class="row">
                        {{-- KOLOM KIRI --}}
                        <div class="col-lg-6 pr-lg-4" style="border-right: 1px solid #eee;">
                            
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">2. OPERASIONAL MESIN</h6>
                            
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Start Dryer</label>
                                <div class="col-4"><input type="time" name="jam_start_dryer" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Trolly Masuk</label>
                                <div class="col-4"><input type="number" name="trolly_masuk" class="form-control form-control-sm"></div>
                            </div>

                            <label class="text-muted small font-weight-bold">AKTUAL TEMPERATURE</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 1</label>
                                <div class="col-3"><input type="number" name="temp_b1_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b1_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Brunner 2</label>
                                <div class="col-3"><input type="number" name="temp_b2_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="temp_b2_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">°C</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cycle Time</label>
                                <div class="col-3"><input type="number" name="cycle_start" class="form-control form-control-sm" placeholder="Awal"></div>
                                <div class="col-1 text-center small">s/d</div>
                                <div class="col-3"><input type="number" name="cycle_end" class="form-control form-control-sm" placeholder="Akhir"></div>
                                <div class="col-1 unit-label">Min</div>
                            </div>

                            <label class="text-muted small font-weight-bold">BAHAN BAKAR</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="number" name="bb_solar" id="inputSolar" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="number" name="bb_batubara" id="inputBatubara" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="number" name="bb_cangkang" id="inputCangkang" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>

                            <div class="divider"></div>

                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jml Trolly Keluar</label>
                                <div class="col-5"><input type="number" name="trolly_keluar" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Unit</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Stop Dryer</label>
                                <div class="col-5"><input type="time" name="jam_stop_dryer" class="form-control form-control-sm"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Jalan Dryer</label>
                                <div class="col-5"><input type="number" name="jam_jalan_dryer" id="inputJamJalan" class="form-control form-control-sm calc-trigger" step="0.1"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            
                            {{-- HASIL PRODUKSI --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jumlah Bales</label>
                                <div class="col-5"><input type="number" name="jumlah_bales" id="inputBales" class="form-control form-control-sm calc-trigger"></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Kg Dipress</label>
                                <div class="col-5"><input type="text" name="kg_press" id="outKgPress" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            {{-- 👇 GANTI BAGIAN INI (CAPACITY) 👇 --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Capacity/Jam</label>
                                <div class="col-5">
                                    {{-- 🔥 Tambahkan name="capacity_per_jam" --}}
                                    <input type="text" name="capacity_per_jam" id="outCapacity" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Jam Kerja</label>
                                <div class="col-5"><input type="number" name="jam_kerja" id="inputJamKerja" class="form-control form-control-sm calc-trigger" step="0.1"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>
                            {{-- 👇 GANTI BAGIAN INI (PRODUKTIVITAS) 👇 --}}
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 text-primary">Produktivitas</label>
                                <div class="col-5">
                                    {{-- 🔥 Tambahkan name="produktivitas" --}}
                                    <input type="text" name="produktivitas" id="outProductivity" class="form-control form-control-sm auto-input" readonly>
                                </div>
                                <div class="col-3 unit-label">Kg/H</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4">Kg Sir20/Cake</label>
                                <div class="col-5"><input type="number" name="kg_sir20" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4">Bales Kontamin</label>
                                <div class="col-5"><input type="number" name="bales_kontamin" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Bales</div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <label class="col-4">Jam Ops Genset</label>
                                <div class="col-5"><input type="number" name="jam_genset" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">Jam</div>
                            </div>

                            <label class="text-muted small font-weight-bold">RATA-RATA BAHAN BAKAR (AUTO)</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Solar</label>
                                <div class="col-5"><input type="text" id="avgSolar" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Liter</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Batu Bara</label>
                                <div class="col-5"><input type="text" id="avgBatubara" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Cangkang</label>
                                <div class="col-5"><input type="text" id="avgCangkang" class="form-control form-control-sm auto-input" readonly></div>
                                <div class="col-3 unit-label">Kg</div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN --}}
                        <div class="col-lg-6 pl-lg-4">
                            
                            <h6 class="font-weight-bold text-success border-bottom pb-1 mb-3">3. LAIN-LAIN & PACKING</h6>

                            <div class="row mb-4 align-items-center">
                                <label class="col-4">Listrik PLN</label>
                                <div class="col-5"><input type="number" name="pln_kwh" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">KWH</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Total Remahan</span>
                                        <span class="summary-value" id="bigTotalRemahan">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="summary-box">
                                        <span class="summary-title">Produksi SIR 20</span>
                                        <span class="summary-value" id="bigTotalProduksi">0</span>
                                        <small class="text-muted">Kilogram</small>
                                    </div>
                                </div>
                            </div>

                            <label class="text-muted small font-weight-bold">DETAIL PACKING</label>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Jml Pallet Diisi</label>
                                <div class="col-5"><input type="number" name="jml_pallet" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">SW</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Nomor</label>
                                <div class="col-5"><input type="number" name="total_nomor" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>
                             <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">MC</label>
                                <div class="col-5"><input type="number" name="mc_val" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">MC</div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Nomor</label>
                                <div class="col-2 pr-0"><input type="text" name="nomor_start" class="form-control form-control-sm"></div>
                                <div class="col-1 text-center small px-0">s/d</div>
                                <div class="col-2 pl-0"><input type="text" name="nomor_end" class="form-control form-control-sm"></div>
                                <div class="col-3"></div>
                            </div>
                            <div class="row mb-1 align-items-center">
                                <label class="col-4 font-weight-normal">Total Akhir</label>
                                <div class="col-5"><input type="number" name="total_nomor_akhir" class="form-control form-control-sm"></div>
                                <div class="col-3 unit-label">WP</div>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4">Simpan Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL DATA --}}
<div class="modal fade" id="modalDetailData" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle"></i> Detail Produksi SIR 20</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                
                {{-- INFO UTAMA --}}
                <div class="card card-body shadow-sm mb-3 pt-2 pb-2">
                    <div class="row">
                        <div class="col-md-3"><strong>Tanggal:</strong> <span id="detTanggal"></span></div>
                        <div class="col-md-3"><strong>Shift:</strong> <span id="detShift"></span></div>
                        <div class="col-md-3"><strong>Total Prod:</strong> <span id="detTotalProd" class="text-success font-weight-bold"></span></div>
                        <div class="col-md-3"><strong>Jam Kerja:</strong> <span id="detJamKerja"></span></div>
                    </div>
                </div>

                <div class="row">
                    {{-- KOLOM KIRI: DETAIL SUMBER --}}
                    <div class="col-md-6">
                        <h6 class="font-weight-bold border-bottom border-info pb-1">1. DATA MATURASI (Sumber)</h6>
                        <table class="table table-sm table-bordered bg-white">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ruang</th>
                                    <th class="text-right">Berat (Kg)</th>
                                    <th class="text-center">Umur</th>
                                </tr>
                            </thead>
                            <tbody id="detTabelMaturasi">
                                {{-- Isi via JS --}}
                            </tbody>
                        </table>

                        <h6 class="font-weight-bold border-bottom border-info pb-1 mt-4">2. OPERASIONAL & SUHU</h6>
                        <dl class="row mb-0 small text-dark">
                            <dt class="col-5">Dryer (Start - Stop)</dt>
                            <dd class="col-7">: <span id="detJamDryer"></span> (<span id="detDurasiDryer"></span> Jam)</dd>
                            
                            <dt class="col-5">Trolly (Masuk/Keluar)</dt>
                            <dd class="col-7">: <span id="detTrolly"></span> Unit</dd>

                            <dt class="col-5">Cycle Time</dt>
                            <dd class="col-7">: <span id="detCycle"></span> Menit</dd>
                        </dl>
                        
                        <div class="mt-2 bg-white p-2 border rounded">
                            <small class="font-weight-bold d-block mb-1">SUHU BURNER:</small>
                            <ul class="mb-0 pl-3 small" id="detListSuhu"></ul>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: HASIL & PACKING --}}
                    <div class="col-md-6">
                        <h6 class="font-weight-bold border-bottom border-info pb-1">3. HASIL PRODUKSI & UTILITAS</h6>
                        <dl class="row mb-0 small text-dark">
                            <dt class="col-6">Jumlah Bales</dt> <dd class="col-6">: <span id="detBales"></span></dd>
                            <dt class="col-6">Kg Dipress</dt> <dd class="col-6">: <span id="detKgPress"></span> Kg</dd>
                            <dt class="col-6">Capacity / Jam</dt> <dd class="col-6">: <span id="detCapacity"></span> Kg/H</dd>
                            <dt class="col-6">Produktivitas</dt> <dd class="col-6">: <span id="detProd"></span> Kg/H</dd>
                            <dt class="col-6">Listrik PLN</dt> <dd class="col-6">: <span id="detListrik"></span> KWH</dd>
                            <dt class="col-6">Genset</dt> <dd class="col-6">: <span id="detGenset"></span> Jam</dd>
                        </dl>

                        <h6 class="font-weight-bold border-bottom border-info pb-1 mt-3">4. BAHAN BAKAR</h6>
                        <table class="table table-sm table-bordered bg-white mb-3">
                            <thead class="thead-light">
                                <tr><th>Jenis</th><th class="text-right">Jumlah</th></tr>
                            </thead>
                            <tbody id="detTabelBB"></tbody>
                        </table>

                        <h6 class="font-weight-bold border-bottom border-info pb-1">5. PACKING</h6>
                        <div class="bg-white p-2 border rounded small">
                            <div><strong>Pallet:</strong> <span id="detPallet"></span> SW</div>
                            <div><strong>Nomor:</strong> <span id="detNomor"></span></div>
                            <div><strong>Total Akhir:</strong> <span id="detTotalAkhir"></span> WP</div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('template.script')

{{-- SCRIPTS --}}
{{-- 2. TAMBAHKAN Bootstrap JS (Wajib agar modal berfungsi) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

{{-- 3. Baru load DataTables --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        
        // --- Notifikasi Sukses/Error (Bawaan) ---
        @if (session('success')) Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 }); @endif
        @if (session('error')) Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", confirmButtonText: 'Cek Kembali' }); @endif

        // 1. Inisialisasi DataTable
        $('#dataTable').DataTable({ "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" } });

        // --- A. PERSIAPAN DATA DROPDOWN ---
        var optionsMaturasi = '<option value="">- Pilih Ruang -</option>';
        @foreach($bak_aktif as $bak)
            optionsMaturasi += `<option value="{{ $bak->uraian }}" data-berat="{{ $bak->stok_akhir }}" data-umur="{{ $bak->umur_real }}">{{ $bak->uraian }} (Stok: {{ number_format($bak->stok_akhir, 0, ",", ".") }})</option>`;
        @endforeach

        // --- B. LOGIKA ISI BERAT & UMUR OTOMATIS ---
        $(document).on('change', '.select-ruang', function() {
            var selectedOption = $(this).find(':selected');
            var row = $(this).closest('tr');
            if(selectedOption.data('berat') !== undefined) {
                row.find('.input-berat').val(selectedOption.data('berat'));
                row.find('.input-umur').val(selectedOption.data('umur'));
                calculateTotals();
            } else {
                row.find('.input-berat').val(''); row.find('.input-umur').val(''); calculateTotals();
            }
        });

        // --- C. LOGIKA TAMBAH BARIS ---
        var maturasiIndex = 1;
        $('#btnAddMaturasi').click(function() {
            var html = `<tr>
                <td><select name="maturasi[${maturasiIndex}][ruang]" class="form-control form-control-sm select-ruang" required>${optionsMaturasi}</select></td>
                <td><input type="number" name="maturasi[${maturasiIndex}][berat]" class="form-control form-control-sm input-berat" step="0.01"></td>
                <td><input type="number" name="maturasi[${maturasiIndex}][umur]" class="form-control form-control-sm input-umur" readonly></td>
                <td><button type="button" class="btn btn-danger btn-xs btn-remove-maturasi"><i class="fas fa-trash"></i></button></td>
            </tr>`;
            $('#maturasiContainer').append(html);
            maturasiIndex++;
        });

        $(document).on('click', '.btn-remove-maturasi', function() { $(this).closest('tr').remove(); calculateTotals(); });

        // --- D. LOGIKA HITUNGAN ---
        function calculateTotals() {
            var totalRemahan = 0;
            $('.input-berat').each(function() { totalRemahan += parseFloat($(this).val()) || 0; });
            $('#bigTotalRemahan').text(totalRemahan.toLocaleString('id-ID'));

            var bales = parseFloat($('#inputBales').val()) || 0;
            var kgPress = bales * 35; // Standar 35 Kg
            $('#outKgPress').val(kgPress);
            $('#bigTotalProduksi').text(kgPress.toLocaleString('id-ID'));

            var jamJalan = parseFloat($('#inputJamJalan').val()) || 0;
            var jamKerja = parseFloat($('#inputJamKerja').val()) || 0;
            
            $('#outCapacity').val(jamJalan > 0 ? (kgPress / jamJalan).toFixed(2) : 0);
            $('#outProductivity').val(jamKerja > 0 ? (kgPress / jamKerja).toFixed(2) : 0);

            var solar = parseFloat($('#inputSolar').val()) || 0;
            var batubara = parseFloat($('#inputBatubara').val()) || 0;
            var cangkang = parseFloat($('#inputCangkang').val()) || 0;

            $('#avgSolar').val(kgPress > 0 ? (solar/kgPress).toFixed(4) : 0);
            $('#avgBatubara').val(kgPress > 0 ? (batubara/kgPress).toFixed(4) : 0);
            $('#avgCangkang').val(kgPress > 0 ? (cangkang/kgPress).toFixed(4) : 0);
        }
        $(document).on('input', '.calc-trigger, .input-berat', calculateTotals);

        // --- E. 🔥 LOGIKA TOMBOL DETAIL (VIEW) YANG DIPERBAIKI ---
        // --- E. LOGIKA TOMBOL DETAIL (VERSI DEBUG & ANTI-CRASH) ---
        $(document).on('click', '.btn-detail', function() {
            var id = $(this).data('id');
            var url = "{{ url('produksi-sir20') }}/" + id;

            // 1. Tampilkan Loading
            Swal.fire({
                title: 'Sedang Memuat...',
                text: 'Mengambil data dari server',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: url,
                type: "GET",
                dataType: "JSON",
                success: function(data) {
                    // Tutup Loading
                    Swal.close();

                    // Bungkus dengan Try-Catch agar kita tahu jika ada error JS
                    try {
                        console.log("Data Diterima:", data); // Cek di Console (F12)

                        // Helper Format Angka (Aman dari Null/Undefined)
                        const fmt = (num) => {
                            if (num === null || num === undefined) return '0';
                            return parseFloat(num).toLocaleString('id-ID');
                        };

                        // 1. Header
                        $('#detTanggal').text(data.tanggal_produksi || '-');
                        $('#detShift').text(data.shift_kerja || '-');
                        $('#detTotalProd').text(fmt(data.kg_yang_dipress) + ' Kg');
                        $('#detJamKerja').text((data.jam_kerja || 0) + ' Jam');

                        // 2. Tabel Maturasi
                        var htmlMaturasi = '';
                        if (data.remahan && data.remahan.length > 0) {
                            $.each(data.remahan, function(i, val) {
                                htmlMaturasi += `<tr>
                                    <td>${val.ruang_maturasi || '-'}</td>
                                    <td class="text-right">${fmt(val.berat)}</td>
                                    <td class="text-center">${val.umur || 0} Hari</td>
                                </tr>`;
                            });
                        } else {
                            htmlMaturasi = '<tr><td colspan="3" class="text-center text-muted"><i>- Tidak ada data maturasi -</i></td></tr>';
                        }
                        $('#detTabelMaturasi').html(htmlMaturasi);

                        // 3. Operasional
                        $('#detJamDryer').text(`${data.jam_start_dryer || '?'} s/d ${data.jam_stop_dryer || '?'}`);
                        $('#detDurasiDryer').text(data.jumlah_jam_dryer || 0);
                        $('#detTrolly').text(`${data.jumlah_trolly_masuk || 0} Masuk / ${data.jumlah_trolly_keluar || 0} Keluar`);

                        // 4. Suhu & Cycle (Handle nama relasi Snake Case vs Camel Case)
                        // Laravel kadang mengirim 'aktual_temperature' atau 'aktualTemperature'
                        var arrSuhu = data.aktual_temperature || data.aktualTemperature || [];
                        var htmlSuhu = '';
                        var cycle = '-';

                        if (Array.isArray(arrSuhu) && arrSuhu.length > 0) {
                            $.each(arrSuhu, function(i, val) {
                                if (val.jenis === 'Cycle Time') {
                                    cycle = `${val.nilai_start} - ${val.nilai_end}`;
                                } else {
                                    htmlSuhu += `<li>${val.jenis}: <strong>${val.nilai_start} - ${val.nilai_end} °C</strong></li>`;
                                }
                            });
                        }
                        $('#detCycle').text(cycle);
                        $('#detListSuhu').html(htmlSuhu);

                        // 5. Hasil & Utilitas
                        $('#detBales').text(data.jumlah_bales_dipress || 0);
                        $('#detKgPress').text(fmt(data.kg_yang_dipress));
                        $('#detCapacity').text(fmt(data.capacity_per_jam));
                        $('#detProd').text(fmt(data.produktivitas));
                        $('#detListrik').text(fmt(data.pemakaian_listrik_pln));
                        $('#detGenset').text(data.jam_operasional_genset || 0);

                        // 6. Bahan Bakar (Handle relasi)
                        var arrBB = data.bahan_bakar || data.bahanBakar || [];
                        var htmlBB = '';
                        if (Array.isArray(arrBB) && arrBB.length > 0) {
                            $.each(arrBB, function(i, val) {
                                htmlBB += `<tr><td>${val.bahan_bakar}</td><td class="text-right">${fmt(val.digunakan)}</td></tr>`;
                            });
                        } else {
                            htmlBB = '<tr><td colspan="2" class="text-center">-</td></tr>';
                        }
                        $('#detTabelBB').html(htmlBB);

                        // 7. Packing
                        $('#detPallet').text(data.jumlah_pallet || 0);
                        $('#detNomor').text(`${data.nomor_start || ''} s/d ${data.nomor_end || ''}`);
                        $('#detTotalAkhir').text(data.total_nomor_akhir || 0);

                        // 🔥 AKHIRNYA: TAMPILKAN MODAL
                        console.log("Mencoba membuka modal...");
                        $('#modalDetailData').modal('show');

                    } catch (err) {
                        console.error(err);
                        Swal.fire('Error JS', 'Terjadi error saat menampilkan data: ' + err.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error("AJAX Error:", xhr.responseText);
                    var msg = "Gagal mengambil data.";
                    if (xhr.status == 404) msg = "Data tidak ditemukan (404).";
                    if (xhr.status == 500) msg = "Terjadi kesalahan server (500).";
                    Swal.fire('Gagal', msg, 'error');
                }
            });
        });

    });
</script>

</body>
</html>