<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; padding: 6px 12px; }
        .header-white th { text-align: center; font-weight: bold; background-color: #ffffff; color: #343a40; }
        .header-green th { text-align: center; font-weight: bold; background-color: #28a745; color: white; }
        .row-jumlah { font-weight: bold; background-color: #f8f9fa; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Data SIR (Gudang & Mutu)</h3>
                <div>
                    {{-- TOMBOL INPUT DATA --}}
                    <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" id="btnInputData">
                        <i class="fas fa-plus-circle"></i> Input Data
                    </button>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- TABEL IV (GUDANG) --}}
                <div class="card shadow-sm mb-4">
                    
                    {{-- HEADER HIJAU DENGAN FILTER TANGGAL --}}
                    <div class="card-header bg-success d-flex align-items-center">
                        <h3 class="card-title font-weight-bold text-white">IV. PRODUKSI SIR 20</h3>
                        
                        {{-- FORM FILTER DI KANAN --}}
                        <form action="{{ route('data-sir.index') }}" method="GET" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white font-weight-normal">Tanggal:</label>
                            <input type="date" name="filter_tanggal" id="filter_tanggal" 
                                   class="form-control form-control-sm mr-2" 
                                   value="{{ $selected_date }}" 
                                   onchange="this.form.submit()" 
                                   style="max-width: 160px;">
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Stock Dalam Gudang SIR</th>
                                        <th rowspan="2">Saldo Awal</th>
                                        <th rowspan="2">Masuk</th>
                                        <th rowspan="2">Total</th>
                                        <th colspan="2">Produksi Bulan Ini</th>
                                        <th rowspan="2">Pengiriman</th>
                                        <th rowspan="2">Saldo Akhir</th>
                                        <th rowspan="2">TOTAL I SD IV</th>
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr><th>Yg lalu</th><th>s/d HI</th></tr>
                                </thead>
                                <tbody>
                                @foreach ($tabelIV as $item)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                        <td>{{ $item->uraian }}</td>
                                        <td class="text-center">{{ number_format($item->saldo_awal, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold text-primary">{{ number_format($item->masuk, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->total, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_bln_lalu, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_sd_hi, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold text-danger">{{ number_format($item->pengiriman, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($item->saldo_akhir, 2, ',', '.') }}</td>
                                        <td class="text-center">-</td>
                                        <td class="text-center">
                                        {{-- Jika ada stok, tampilkan tombol Pindah --}}
                                        @if($item->saldo_akhir > 0)
                                            <button class="btn btn-sm btn-warning font-weight-bold btn-mutasi" 
                                                    data-id="{{ $item->id_lokasi }}" 
                                                    data-nama="{{ $item->uraian }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        @else
                                            <span class="badge badge-secondary">Kosong</span>
                                        @endif
                                    </td>
                                    </tr>
                                @endforeach
                                <tr class="row-jumlah">
                                    <td colspan="2" class="text-center">Jumlah 4.1 - 4.4</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_awal'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('masuk'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('total'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_bln_lalu'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_sd_hi'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('pengiriman'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_akhir'), 2, ',', '.') }}</td>
                                    <td class="text-center font-weight-bold">{{ number_format($grandTotal ?? 0, 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TABEL VI (MUTU) --}}
                <div class="card shadow-sm">
                     <div class="card-header bg-success">
                         <h3 class="card-title font-weight-bold text-white">VI. Rincian Mutu</h3>
                     </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th>No</th>
                                        <th>Uraian</th>
                                        <th>Kg</th>
                                        <th>Pallet</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($tabelVI as $item)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                        <td>{{ $item->uraian }}</td>
                                        <td class="text-center">{{ number_format($item->kg, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->pallet, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->keterangan }}</td>
                                    </tr>
                                @endforeach
                                <tr class="row-jumlah">
                                    <td colspan="2" class="text-center">Total</td>
                                    <td class="text-center">{{ number_format($tabelVI->sum('kg'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelVI->sum('pallet'), 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <footer class="main-footer">@include('template.footer')</footer>
</div>

{{-- MODAL INPUT DATA --}}
<div class="modal fade" id="modalInputData" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('data-sir.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                {{-- Input hidden ini penting untuk logic controller baru --}}
                <input type="hidden" name="id" id="editId">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalTitle">Generate Pallet Harian</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    
                    {{-- 1. DATA TOTAL DARI PRODUKSI --}}
                    <div class="alert alert-light border">
                        <div class="row text-center">
                            <div class="col-6 border-right">
                                <label class="mb-0 text-muted">Total Pallet (Stok)</label>
                                <h4 class="font-weight-bold mb-0 text-dark" id="lblTotalPallet">0</h4>
                            </div>
                            <div class="col-6">
                                <label class="mb-0 text-muted">Total Masuk (Kg)</label>
                                <h4 class="font-weight-bold mb-0 text-dark" id="lblTotalKg">0</h4>
                            </div>
                        </div>
                    </div>

                    {{-- 2. PILIH GUDANG TUJUAN AWAL --}}
                    <div class="form-group mb-3" id="groupGudang">
                        <label>Simpan ke Lokasi Awal:</label>
                        <select name="uraian" id="inputUraian" class="form-control font-weight-bold" required>
                            <option value="Di Gudang SIR">Di Gudang SIR</option>
                            <option value="Di Areal Press Bale">Di Areal Press Bale</option>
                            <option value="Di Gudang TOH 1">Di Gudang TOH 1</option>
                            <option value="Di Gudang TOH 2">Di Gudang TOH 2</option>
                        </select>
                        <small class="text-muted">*Semua pallet hari ini akan masuk ke lokasi ini dulu.</small>
                    </div>

                    <hr>
                    {{-- 3. RINCIAN MUTU (Input Jumlah Pallet Cacat jika ada) --}}
                    <label class="text-success font-weight-bold mb-3">Input Mutu Cacat (Jika Ada):</label>
                    <p class="text-muted small">Masukkan jumlah pallet yang statusnya <b>BUKAN</b> Prima. Sisanya otomatis dianggap Prima.</p>
                    
                    {{-- Mutu Prima (Readonly / Sisa) --}}
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label font-weight-bold text-success">Mutu Prima (Sisa)</label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_mutu_prima" id="pal_mutu_prima" class="form-control text-center font-weight-bold bg-light" readonly placeholder="Auto">
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="mutu_prima" id="kg_mutu_prima" class="form-control text-right font-weight-bold bg-light" readonly>
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- PO / PRI Low (Readonly from Lab) --}}
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label"><small>PO / PRI Low (Lab)</small></label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_po_pri" id="pal_po_pri" class="form-control text-center bg-light" readonly>
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="po_pri" id="kg_po_pri" class="form-control text-right bg-light" readonly>
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Inputan Cacat Lainnya --}}
                    @php
                        $fields = [
                            ['label' => 'WhiteSpot (WS)', 'id' => 'ws'],
                            ['label' => 'Kontaminasi', 'id' => 'kontaminasi'],
                            ['label' => 'Repacking', 'id' => 'repacking']
                        ];
                    @endphp

                    @foreach($fields as $f)
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label"><small>{{ $f['label'] }}</small></label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_{{ $f['id'] }}" id="pal_{{ $f['id'] }}" class="form-control text-center input-pallet" placeholder="Pallet">
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="{{ $f['id'] }}" id="kg_{{ $f['id'] }}" class="form-control text-right bg-white" readonly>
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                </div>
                <div class="modal-footer"> <button type="submit" class="btn btn-primary">Generate & Simpan</button> </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMutasi" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- Form ID diberi nama agar bisa dimanipulasi JS --}}
            <form method="POST" id="formActionPallet">
                @csrf
                
                {{-- Container Input Hidden (ID Pallet & Mutu Baru akan masuk sini via JS) --}}
                <div id="hiddenInputsContainer"></div>

                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold text-dark">
                        <i class="fas fa-edit mr-2"></i>
                        <span id="modalTitleText">Kelola Pallet (Mutu & Lokasi)</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    {{-- HEADER INFO --}}
                    <div class="alert alert-info d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                        <span class="font-weight-bold">
                            <i class="fas fa-warehouse mr-1"></i> Lokasi: <span id="lblSumber" class="text-uppercase font-weight-bold">...</span>
                        </span>
                        <div class="text-right">
                            <span class="badge badge-light p-2 mr-1 border text-dark">Pallet: <b id="lblStokPallet">0</b></span>
                            <span class="badge badge-light p-2 border text-dark">Berat: <b id="lblStokBerat">0</b> Kg</span>
                        </div>
                    </div>

                    {{-- ======================== STEP 1: PILIH & EDIT MUTU ======================== --}}
                    <div id="step1-content">
                        <h6 class="font-weight-bold text-dark mb-2">
                            <i class="fas fa-list-ul mr-1 text-primary"></i> Pilih Pallet:
                        </h6>
                        
                        <div class="table-responsive border rounded mb-3" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-bordered table-hover mb-0">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th width="5%" class="text-center"><input type="checkbox" id="checkAll"></th>
                                        <th>No Pallet</th>
                                        <th width="45%">Kondisi Mutu (Bisa Diedit)</th>
                                        <th class="text-right">Berat (Kg)</th>
                                    </tr>
                                </thead>
                                <tbody id="listPalletMutasi">
                                    <tr><td colspan="4" class="text-center py-3 text-muted">Sedang memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mb-0"><i class="fas fa-info-circle"></i> Centang pallet, lalu ubah Mutu di dropdown jika perlu.</p>
                    </div>

                    {{-- ======================== STEP 2: PILIH TUJUAN (PINDAH) ======================== --}}
                    <div id="step2-content" style="display: none;">
                        <div class="alert alert-warning text-center mb-4 border-warning">
                            <h6 class="font-weight-bold mb-1 text-dark">Anda akan memindahkan:</h6>
                            <h3 class="mb-0 font-weight-bold text-dark"><span id="lblCountSelected">0</span> Pallet</h3>
                            <small class="text-dark">Total Berat: <span id="lblWeightSelected">0</span> Kg</small>
                        </div>

                        <div class="card bg-light border-0">
                            <div class="card-body py-3 px-3">
                                <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2">Tujuan Perpindahan</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold text-muted">Tanggal Pindah</label>
                                            <input type="date" name="tanggal_pindah" id="inputTanggalPindah" class="form-control font-weight-bold" value="{{ $selected_date }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold text-muted">Pindah Ke Gudang/Lokasi</label>
                                            <select name="id_lokasi_tujuan" id="inputLokasiTujuan" class="form-control font-weight-bold border-warning">
                                                <option value="">-- Pilih Lokasi Tujuan --</option>
                                                @foreach($lokasiList as $loc)
                                                    <option value="{{ $loc->id_lokasi }}">{{ $loc->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- FOOTER DENGAN 2 OPSI --}}
                <div class="modal-footer bg-light d-flex justify-content-between">
                    
                    {{-- [KIRI] Tombol Batal / Kembali --}}
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btnCloseModal">Tutup</button>
                        <button type="button" class="btn btn-secondary" id="btnPrevStep" style="display: none;">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </button>
                    </div>

                    {{-- [KANAN] Tombol Aksi --}}
                    <div id="actionButtonsStep1">
                        {{-- AKSI 1: SIMPAN MUTU SAJA --}}
                        <button type="button" class="btn btn-success font-weight-bold mr-2" id="btnSaveMutuOnly">
                            <i class="fas fa-save mr-1"></i> Simpan Mutu Saja
                        </button>
                        {{-- AKSI 2: LANJUT PINDAH --}}
                        <button type="button" class="btn btn-primary font-weight-bold" id="btnNextStep">
                            Pindah Lokasi <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>

                    <div id="actionButtonsStep2" style="display: none;">
                        {{-- AKSI 3: KONFIRMASI PINDAH --}}
                        <button type="submit" class="btn btn-warning font-weight-bold px-4" id="btnSubmitPindah">
                            <i class="fas fa-dolly-flatbed mr-1"></i> Konfirmasi Pindah
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')

<script>
    // SweetAlert
    @if(session('success')) Swal.fire({ icon: 'success', title: 'BERHASIL!', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000 }); @endif
    @if(session('error')) Swal.fire({ icon: 'error', title: 'GAGAL!', text: '{{ session('error') }}', showConfirmButton: true }); @endif

    var globalTotalPallet = 0;
    var globalTotalKg = 0;
    var kgPerPalletActual = 1260; // Default

    $(document).ready(function () {

        function hitungMutu() {
            var p_low = parseInt($('#pal_po_pri').val()) || 0;
            var p_ws = parseInt($('#pal_ws').val()) || 0;
            var p_kontam = parseInt($('#pal_kontaminasi').val()) || 0;
            var p_repack = parseInt($('#pal_repacking').val()) || 0;

            var totalCacat = p_low + p_ws + p_kontam + p_repack;
            
            // Rumus: Sisa Pallet (Prima) = Total Pallet Stok - Total Cacat
            var sisaPrima = globalTotalPallet - totalCacat;
            if (sisaPrima < 0) sisaPrima = 0;

            // Update UI
            $('#pal_mutu_prima').val(sisaPrima);
            
            // Hitung Kg (Pakai rata-rata dinamis)
            $('#kg_mutu_prima').val((sisaPrima * kgPerPalletActual).toFixed(2));
            $('#kg_po_pri').val((p_low * kgPerPalletActual).toFixed(2));
            $('#kg_ws').val((p_ws * kgPerPalletActual).toFixed(2));
            $('#kg_kontaminasi').val((p_kontam * kgPerPalletActual).toFixed(2));
            $('#kg_repacking').val((p_repack * kgPerPalletActual).toFixed(2));
        }

        $('.input-pallet').on('input keyup', hitungMutu);

        // =========================
        // TOMBOL INPUT DATA
        // =========================
        $('#btnInputData').on('click', function () {
            var tanggal = $('#filter_tanggal').val();
            if (!tanggal) {
                Swal.fire('Error', 'Tanggal belum dipilih', 'error');
                return;
            }

            // Update input hidden
            $('input[name="tanggal"]').val(tanggal);

            // Reset input form
            $('.input-pallet').val('');
            $('#pal_po_pri').val(0);

            // AJAX Get Data Produksi
            $.ajax({
                url: "{{ route('data-sir.getProductionToday') }}",
                type: "GET",
                data: { date: tanggal },
                success: function (res) {
                    console.log('RESPON SERVER:', res);

                    // 1. DATA HEADER MODAL
                    // Total Pallet = Total Stok (Akumulasi)
                    globalTotalPallet = parseInt(res.total_target_pallet) || 0;
                    
                    // Total Kg Stok (Akumulasi)
                    globalTotalKg = parseFloat(res.total_target_kg) || 0;

                    // Data Masuk Hari Ini
                    var produksiHariIni = parseFloat(res.masuk_kg) || 0;

                    // 2. TAMPILKAN DI HEADER
                    $('#lblTotalPallet').text(globalTotalPallet); // Muncul 105 (Stok)
                    
                    // Label Kanan: Menampilkan PRODUKSI HARI INI (Sesuai request terakhir)
                    $('#lblTotalKg').text(
                        new Intl.NumberFormat('id-ID').format(produksiHariIni) 
                    );

                    // 3. LOGIC PENDUKUNG
                    if (globalTotalPallet > 0) {
                        kgPerPalletActual = globalTotalKg / globalTotalPallet;
                    } else {
                        kgPerPalletActual = 1260;
                    }

                    // Isi data Low dari Lab
                    $('#pal_po_pri').val(res.low_pri || 0);

                    // Hitung sisa Prima
                    hitungMutu();

                    $('#modalInputData').modal('show');
                },
                error: function () {
                    Swal.fire('Error', 'Gagal mengambil data produksi', 'error');
                }
            });
        });

        // KLIK TOMBOL PINDAH (MUTASI)
        // KLIK TOMBOL PINDAH (MUTASI)
        // KLIK TOMBOL PINDAH (MUTASI)
        $('.btn-mutasi').on('click', function() {
            var idLokasi = $(this).data('id');
            var namaLokasi = $(this).data('nama');

            // Reset UI ke Step 1
            $('#step1-content').show();
            $('#step2-content').hide();
            $('#actionButtonsStep1').show();
            $('#actionButtonsStep2').hide();
            $('#btnPrevStep').hide();
            $('#btnCloseModal').show();
            $('#hiddenInputsContainer').empty(); 

            $('#lblSumber').text(namaLokasi);
            $('#listPalletMutasi').html('<tr><td colspan="4" class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
            $('#lblStokPallet').text('0');
            $('#lblStokBerat').text('0');
            
            $('#modalMutasi').modal('show');

            // Dropdown Option Mutu
            var optionsMutu = '';
            @foreach($mutuList as $m)
                optionsMutu += '<option value="{{ $m->id_mutu }}">{{ $m->uraian }}</option>';
            @endforeach

            // AJAX Load Data
            $.ajax({
                url: "{{ route('data-sir.getPalletsByLocation') }}", 
                type: "GET",
                data: { id_lokasi: idLokasi },
                success: function(res) {
                    var html = '';
                    var totalP = 0;
                    var totalK = 0;

                    if(res.length > 0) {
                        $.each(res, function(i, val) {
                            totalP++;
                            var beratFloat = parseFloat(val.berat.replace(/\./g, '').replace(',', '.')) || 0;
                            totalK += beratFloat;

                            html += `
                                <tr data-id="${val.id_pallet}" data-berat="${val.berat}">
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="chk-pallet" value="${val.id_pallet}">
                                    </td>
                                    <td class="align-middle font-weight-bold text-primary">${val.no_pallet}</td>
                                    <td class="p-1">
                                        <select class="form-control form-control-sm border-0 bg-light select-mutu-row">
                                            ${optionsMutu}
                                        </select>
                                    </td>
                                    <td class="text-right align-middle">${val.berat}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="4" class="text-center text-danger py-3">Tidak ada pallet aktif di lokasi ini.</td></tr>';
                    }
                    
                    $('#listPalletMutasi').html(html);
                    
                    // Set Value Mutu Default
                    if(res.length > 0) {
                        $.each(res, function(i, val) {
                            $('#listPalletMutasi tr').eq(i).find('.select-mutu-row').val(val.id_mutu_now);
                        });
                    }

                    $('#lblStokPallet').text(totalP);
                    $('#lblStokBerat').text(new Intl.NumberFormat('id-ID').format(totalK));
                }
            });
        });

        // Check All
        $('#checkAll').click(function() {
            $('.chk-pallet').prop('checked', this.checked);
        });

        // --- FUNGSI BANTUAN: COLLECT DATA KE HIDDEN INPUT ---
        function collectDataToHidden() {
            var selectedCount = 0;
            var selectedWeight = 0;
            var hiddenHTML = '';

            $('.chk-pallet:checked').each(function() {
                selectedCount++;
                var row = $(this).closest('tr');
                var idPallet = $(this).val();
                
                var beratStr = row.data('berat'); 
                var beratFloat = parseFloat(String(beratStr).replace(/\./g, '').replace(',', '.')) || 0;
                selectedWeight += beratFloat;

                var idMutu = row.find('.select-mutu-row').val();

                // Buat Input Hidden
                hiddenHTML += `<input type="hidden" name="selected_pallets[]" value="${idPallet}">`;
                hiddenHTML += `<input type="hidden" name="mutu_baru[${idPallet}]" value="${idMutu}">`;
            });

            return { count: selectedCount, weight: selectedWeight, html: hiddenHTML };
        }

        // --- AKSI 1: SIMPAN MUTU SAJA (LANGSUNG SUBMIT) ---
        $('#btnSaveMutuOnly').click(function() {
            var data = collectDataToHidden();
            if (data.count === 0) { Swal.fire('Peringatan', 'Pilih minimal satu pallet.', 'warning'); return; }

            // Masukkan data hidden
            $('#hiddenInputsContainer').html(data.html);

            // Ubah Action Form ke Route Update Mutu
            $('#formActionPallet').attr('action', "{{ route('data-sir.updateMutu') }}");
            
            // Matikan required di input step 2 agar tidak error validasi HTML5
            $('#inputTanggalPindah').removeAttr('required');
            $('#inputLokasiTujuan').removeAttr('required');

            // Submit
            $('#formActionPallet').submit();
        });

        // --- AKSI 2: LANJUT KE PINDAH (BUKA STEP 2) ---
        $('#btnNextStep').click(function() {
            var data = collectDataToHidden();
            if (data.count === 0) { Swal.fire('Peringatan', 'Pilih minimal satu pallet.', 'warning'); return; }

            // Masukkan data hidden
            $('#hiddenInputsContainer').html(data.html);

            // Update Info Ringkasan
            $('#lblCountSelected').text(data.count);
            $('#lblWeightSelected').text(new Intl.NumberFormat('id-ID').format(data.weight));

            // Ganti Tampilan
            $('#step1-content').slideUp(200);
            $('#step2-content').slideDown(200);
            $('#actionButtonsStep1').hide();
            $('#actionButtonsStep2').show();
            $('#btnCloseModal').hide();
            $('#btnPrevStep').show();
        });

        // --- AKSI 3: KONFIRMASI PINDAH (SUBMIT AKHIR) ---
        $('#btnSubmitPindah').click(function(e) {
            // Ubah Action Form ke Route Pindah Lokasi
            $('#formActionPallet').attr('action', "{{ route('data-sir.pindahLokasi') }}");
            
            // Nyalakan kembali required
            $('#inputTanggalPindah').attr('required', true);
            $('#inputLokasiTujuan').attr('required', true);

            // Form akan tersubmit secara normal
        });

        // --- TOMBOL KEMBALI KE STEP 1 ---
        $('#btnPrevStep').click(function() {
            $('#step2-content').slideUp(200);
            $('#step1-content').slideDown(200);
            $('#actionButtonsStep2').hide();
            $('#actionButtonsStep1').show();
            $('#btnPrevStep').hide();
            $('#btnCloseModal').show();
        });
    });
</script>
</body>
</html>