<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; padding: 6px 12px; }
        .header-white th { 
            text-align: center !important; 
            vertical-align: middle !important; /* 🔥 TAMBAHAN RATA TENGAH */
            font-weight: bold; 
            background-color: #f8f9fa; /* Ubah ke abu-abu terang */
            color: #343a40; 
        }
        .header-green th { text-align: center; font-weight: bold; background-color: #28a745; color: white; }
        .bg-highlight { background-color: #d4edda; color: #155724; } 
        .card-header { font-weight: bold; }
        .judul-tabel { text-transform: uppercase; }
        .row-jumlah { font-weight: bold; background-color: #f8f9fa; }
        .stok-info { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; border-radius: 5px; }

        /* Style untuk Grid Nomor Palet */
        .pallet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
            gap: 8px;
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            padding: 10px;
            border-radius: 5px;
            background-color: #fdfdfd;
        }
        .pallet-item {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 13px;
            margin-bottom: 0;
        }
        .pallet-item:hover { border-color: #28a745; background: #f0f0f0; }
        .pallet-item input { margin-right: 6px; cursor: pointer; }
        .pallet-item.selected { background: #d4edda !important; border-color: #28a745 !important; color: #155724 !important; font-weight: bold; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Penjualan SIR 20</h3>
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" id="btnInputBaru">
                    <i class="fas fa-plus-circle"></i> Input Penjualan
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                @if(session('success'))
                    <script>Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 2000, showConfirmButton: false });</script>
                @endif

                {{-- 1. TABEL RINGKASAN (TABEL V) --}}
                <div class="card shadow-sm mb-4">
                    {{-- HEADER KARTU (Terpisah) --}}
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong class="my-auto">V. TELAH DIJUAL (KG SIR-20) - RINGKASAN</strong>
                        <form action="{{ route('penjualan-sir20.index') }}" method="GET" class="form-inline ml-auto mb-0">
                            <label for="filter_tanggal" class="mr-2 text-white font-weight-normal mb-0">Tanggal:</label>
                            <input type="date" name="filter_tanggal" id="filter_tanggal" 
                                   class="form-control form-control-sm" 
                                   value="{{ $selected_date }}" 
                                   onchange="this.form.submit()" 
                                   style="max-width: 160px;">
                        </form>
                    </div>

                    {{-- CARD BODY (Tanpa class p-0 agar ada padding) --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover mb-0 text-center">
                                <thead class="header-white">
                                    <tr>
                                        {{-- 🔥 KOLOM ROMAWI 'V.' DIHAPUS AGAR SINKRON DENGAN YANG LAIN --}}
                                        <th rowspan="2" width="5%">No</th>
                                        <th rowspan="2">Telah Dijual (KG SIR-20)</th>
                                        <th rowspan="2" width="12%">s/d<br>{{ $headerBulanLalu }}</th>
                                        <th colspan="2">Penjualan Bulan Ini</th>
                                        <th rowspan="2">Total Bulan Ini</th>
                                        <th rowspan="2">Total Penjualan<br>s/d Hari ini</th>
                                        <th rowspan="2">Keterangan</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-highlight">Yg lalu</th>
                                        <th>Hari Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tabelSummary as $item)
                                        <tr>
                                            <td class="font-weight-bold">{{ $item->no }}</td>
                                            <td class="text-left font-weight-bold">{{ $item->uraian }}</td>
                                            {{-- 🔥 HAPUS KOMA DESIMAL (Ubah ke format 0) --}}
                                            <td>{{ number_format($item->sd_bulan_lalu, 0, ',', '.') }}</td>
                                            <td class="bg-highlight">{{ number_format($item->bln_ini_lalu, 0, ',', '.') }}</td>
                                            <td class="font-weight-bold text-success">{{ number_format($item->hari_ini, 0, ',', '.') }}</td>
                                            <td class="font-weight-bold">{{ number_format($item->total_bln_ini, 0, ',', '.') }}</td>
                                            <td class="font-weight-bold">{{ number_format($item->total_sd_hari_ini, 0, ',', '.') }}</td>
                                            <td>{{ $item->keterangan }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="row-jumlah">
                                        <td colspan="2" class="text-uppercase text-center">Total Ringkasan</td>
                                        <td>{{ number_format($tabelSummary->sum('sd_bulan_lalu'), 0, ',', '.') }}</td>
                                        <td class="bg-highlight">{{ number_format($tabelSummary->sum('bln_ini_lalu'), 0, ',', '.') }}</td>
                                        <td class="text-success">{{ number_format($tabelSummary->sum('hari_ini'), 0, ',', '.') }}</td>
                                        <td>{{ number_format($tabelSummary->sum('total_bln_ini'), 0, ',', '.') }}</td>
                                        <td>{{ number_format($tabelSummary->sum('total_sd_hari_ini'), 0, ',', '.') }}</td>
                                        <td>-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 2. TABEL RIWAYAT PENJUALAN --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong class="my-auto">RIWAYAT PENJUALAN PER KONTRAK</strong>
                    </div>
                    
                    {{-- CARD BODY --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover mb-0 text-center">
                                <thead class="header-white">
                                    <tr>
                                        <th width="5%">No.</th>
                                        <th width="15%">Tgl Penjualan</th>
                                        <th>No. Kontrak</th>
                                        <th>Jumlah Penjualan (Kg)</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($riwayatKontrak as $index => $kontrak)
                                    <tr>
                                        <td class="font-weight-bold">{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($kontrak->tanggal)->format('d-m-Y') }}</td>
                                        <td class="text-primary font-weight-bold">{{ $kontrak->no_kontrak }}</td>
                                        {{-- 🔥 HAPUS KOMA DESIMAL --}}
                                        <td class="font-weight-bold text-success">{{ number_format($kontrak->hari_ini, 0, ',', '.') }} Kg</td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center" style="gap: 5px;">
    
                                                {{-- 1. TOMBOL DETAIL --}}
                                                <button type="button" class="btn btn-info btn-xs btn-detail" title="Lihat Detail"
                                                    data-no_kontrak="{{ $kontrak->no_kontrak }}" 
                                                    data-no_invoice="{{ $kontrak->no_invoice }}"
                                                    data-tanggal="{{ \Carbon\Carbon::parse($kontrak->tanggal)->format('d-m-Y') }}" 
                                                    data-uraian="{{ $kontrak->uraian }}"
                                                    data-pallet="{{ $kontrak->pallet }}" 
                                                    data-hari_ini="{{ number_format($kontrak->hari_ini, 0, ',', '.') }}"
                                                    data-no_palet_list="{{ $kontrak->no_palet_list }}"
                                                    data-harga="{{ number_format($kontrak->harga, 0, ',', '.') }}" 
                                                    data-keterangan="{{ $kontrak->keterangan }}">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>

                                                {{-- 2. TOMBOL EDIT --}}
                                                <button type="button" class="btn btn-warning btn-xs btn-edit text-white" title="Edit Administrasi"
                                                    data-id_penjualan="{{ $kontrak->id_penjualan_sir20 }}"
                                                    data-no_kontrak="{{ $kontrak->no_kontrak }}" 
                                                    data-no_invoice="{{ $kontrak->no_invoice }}"
                                                    data-harga_raw="{{ $kontrak->harga }}"
                                                    data-uraian="{{ $kontrak->uraian }}"
                                                    data-tanggal="{{ \Carbon\Carbon::parse($kontrak->tanggal)->format('d-m-Y') }}"
                                                    data-pallet="{{ $kontrak->pallet }}"
                                                    data-hari_ini="{{ number_format($kontrak->hari_ini, 0, ',', '.') }}"
                                                    data-no_palet_list="{{ $kontrak->no_palet_list }}">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>

                                                {{-- 3. TOMBOL HAPUS --}}
                                                <form action="{{ route('penjualan-sir20.destroy', $kontrak->id_penjualan_sir20) }}" method="POST" class="m-0 p-0 form-hapus">
                                                    @csrf 
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-xs btn-hapus" title="Hapus Data">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data kontrak tersimpan.</td></tr>
                                    @endforelse
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
{{-- 1. MODAL INPUT PENJUALAN --}}
<div class="modal fade" id="modalInput" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-success">
            <form action="{{ route('penjualan-sir20.store') }}" method="POST" id="formPenjualan">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold">Input Data Penjualan Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    
                    {{-- INFO STOK GUDANG (DIAMBIL DARI PROD_SIR) --}}
                    <div class="stok-info mb-3">
                        <small class="font-weight-bold"><i class="fas fa-warehouse"></i> STOK MUTU PRIMA TERSEDIA (GUDANG):</small>
                        <h5 class="mb-0 font-weight-bold text-success" id="txtStokTersedia">0 <span style="font-size: 14px;">Pallet</span></h5>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Jenis SIR</label>
                        <select name="uraian" id="inputUraian" class="form-control" required>
                            <option value="SIR20 PTNBL">SIR20 PTNBL</option>
                            <option value="SIR20 PTPN4">SIR20 PTPN4</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6"><label>No. Kontrak</label><input type="text" name="no_kontrak" class="form-control" required></div>
                        <div class="col-6"><label>No. Invoice</label><input type="text" name="no_invoice" class="form-control" required></div>
                    </div>

                    <div class="form-group mt-3 mb-3">
                        <label>Tanggal Penjualan</label>
                        <input type="date" name="tanggal" id="inputTanggal" class="form-control" value="{{ $selected_date }}" required>
                    </div>

                    <hr>

                    {{-- FITUR PILIH PALET BERDASARKAN HASIL LAB --}}
                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold mb-0 text-primary"><i class="fas fa-microscope"></i> Pilih Nomor Palet (Mutu Prima di Lab)</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkAllPallet">
                                <label class="custom-control-label small" for="checkAllPallet" style="cursor:pointer;">Pilih Semua</label>
                            </div>
                        </div>
                        <div id="palletGrid" class="pallet-grid">
                            <span class="text-muted small">Memuat daftar palet dari lab...</span>
                        </div>
                        <small class="text-muted">*Hanya menampilkan palet yang sudah diuji Lab dengan PRI &ge; 40.</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label>Jumlah Pallet</label>
                            <input type="number" name="pallet" id="inputPallet" class="form-control bg-light font-weight-bold" readonly required value="0">
                        </div>
                        <div class="col-6">
                            <label>Total Berat (Kg)</label>
                            <input type="number" step="0.01" name="hari_ini" id="inputHariIni" class="form-control bg-light font-weight-bold text-success" readonly required value="0">
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Harga Penjualan (Rp/Kg)</label>
                        <input type="number" name="harga" class="form-control" required placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success shadow-sm" id="btnSimpan" disabled>Simpan Penjualan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 2. MODAL DETAIL PENJUALAN (TAMBAHAN BARU) --}}
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-info">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-info-circle mr-2"></i> Rincian Penjualan Kontrak</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <th width="45%" class="pl-3">No. Kontrak</th>
                        <td id="detNoKontrak"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">No. Invoice</th>
                        <td id="detNoInvoice"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Tanggal Penjualan</th>
                        <td id="detTanggal"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Jenis SIR</th>
                        <td id="detUraian"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Jumlah Pallet</th>
                        <td id="detPallet" class="font-weight-bold"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Nomor Pallet</th>
                        <td id="detNoPaletList" class="text-primary font-weight-bold" style="word-break: break-all;"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Total Berat Bersih</th>
                        <td id="detKg" class="font-weight-bold text-success"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Harga Penjualan</th>
                        <td id="detHarga"></td>
                    </tr>
                    <tr>
                        <th class="pl-3">Keterangan</th>
                        <td id="detKeterangan"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- 3. MODAL EDIT PENJUALAN ADMINISTRATIF --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-warning">
            <form action="#" method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold">Edit Data Penjualan</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    
                    <div class="alert alert-info small pb-2 pt-2 mb-3">
                        <i class="fas fa-info-circle"></i> Info: Hanya nomor kontrak, invoice, dan harga yang dapat diubah. Jika salah pilih pallet, silakan hapus data ini.
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Jenis SIR</label>
                        <select name="uraian" id="edit_uraian" class="form-control" readonly style="pointer-events: none; background-color: #e9ecef;">
                            <option value="SIR20 PTNBL">SIR20 PTNBL</option>
                            <option value="SIR20 PTPN4">SIR20 PTPN4</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6"><label>No. Kontrak</label><input type="text" name="no_kontrak" id="edit_no_kontrak" class="form-control" required></div>
                        <div class="col-6"><label>No. Invoice</label><input type="text" name="no_invoice" id="edit_no_invoice" class="form-control" required></div>
                    </div>

                    <div class="form-group mt-3 mb-3">
                        <label>Tanggal Penjualan</label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control" readonly style="pointer-events: none; background-color: #e9ecef;">
                    </div>

                    <hr>

                    {{-- FITUR PILIH PALET (READ ONLY MODE) --}}
                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold mb-0 text-secondary"><i class="fas fa-box"></i> Pallet yang Terjual</label>
                        </div>
                        <div id="editPalletGrid" class="pallet-grid bg-light" style="pointer-events: none; opacity: 0.8;">
                            </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label>Jumlah Pallet</label>
                            <input type="number" name="pallet" id="edit_pallet_count" class="form-control bg-light font-weight-bold text-secondary" readonly>
                        </div>
                        <div class="col-6">
                            <label>Total Berat (Kg)</label>
                            <input type="text" name="hari_ini" id="edit_total_kg" class="form-control bg-light font-weight-bold text-secondary" readonly>
                        </div>
                    </div>

                    <div class="form-group mt-3 mb-0">
                        <label>Harga Penjualan (Rp/Kg)</label>
                        <input type="number" name="harga" id="edit_harga" class="form-control border-warning" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold shadow-sm">Update Penjualan</button>
                </div>
            </form>
        </div>
    </div>
</div>

    @include('template.script')

  <script>
    $(document).ready(function () {
        // Setup CSRF untuk AJAX
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        // ==========================================
        // 1. LOGIKA PILIHAN PALET (DARI LAB)
        // ==========================================
        function fetchPalletFromLab() {
            var date = $('#inputTanggal').val();
            var grid = $('#palletGrid');
            if(!date) return;

            grid.html('<span class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Mengecek Lab...</span>');
            $('#checkAllPallet').prop('checked', false);

            $.ajax({
                url: "{{ route('penjualan-sir20.getAvailableStock') }}",
                type: "GET",
                data: { date: date },
                success: function(response) {
                    grid.empty();
                    
                    // 🔥 DEFINISIKAN variabel bookedArr dari response server
                    var bookedArr = response.booked_pallets ? response.booked_pallets : [];

                    if (response.list_pallet && response.list_pallet.length > 0) {
                        $.each(response.list_pallet, function(i, val) {
                            
                            // 🔥 CEK APAKAH PALLET INI ADA DI LIST BOOKING
                            // Kita pakai toString() agar perbandingan datanya akurat
                            var isBooked = bookedArr.includes(val.toString());
                            var checkedAttr = isBooked ? 'checked' : '';
                            var selectedClass = isBooked ? 'selected' : '';

                            grid.append(`
                                <label class="pallet-item ${selectedClass}">
                                    <input type="checkbox" name="selected_pallets[]" 
                                        class="pallet-check" value="${val}" ${checkedAttr}>
                                    <span>#${val}</span>
                                </label>
                            `);
                        });
                        
                        $('#txtStokTersedia').html(response.count + " <span style='font-size: 14px;'>Pallet Siap Jual</span>");
                    } else {
                        grid.html('<span class="text-danger small">Belum ada palet yang tersedia.</span>');
                        $('#txtStokTersedia').html("0 <span style='font-size: 14px;'>Pallet</span>");
                    }
                    
                    // 🔥 Jalankan kalkulasi setelah grid terisi agar angka total Kg langsung muncul
                    updateCalculation();
                },
                error: function(xhr) {
                    grid.html('<span class="text-danger small">Gagal memuat data palet.</span>');
                }
            });
        }

        function updateCalculation() {
            var selected = $('.pallet-check:checked');
            var count = selected.length;
            var totalKg = count * 1260; 

            $('#inputPallet').val(count);
            $('#inputHariIni').val(totalKg);

            $('.pallet-item').removeClass('selected');
            selected.closest('.pallet-item').addClass('selected');

            $('#btnSimpan').prop('disabled', count === 0);
        }

        // Event Handlers untuk Input
        $(document).on('change', '.pallet-check', updateCalculation);
        $('#checkAllPallet').on('change', function() {
            $('.pallet-check').prop('checked', $(this).prop('checked'));
            updateCalculation();
        });
        $('#inputTanggal').change(fetchPalletFromLab);

        $('#btnInputBaru').click(function() {
            $('#formPenjualan')[0].reset();
            $('#inputTanggal').val('{{ $selected_date }}');
            $('#modalInput').modal('show');
            setTimeout(fetchPalletFromLab, 300);
        });

        // ==========================================
        // 2. LOGIKA TOMBOL DETAIL (PERBAIKAN ANDA)
        // ==========================================
        $('.btn-detail').click(function() {
            // Mengambil data dari atribut data- di tombol
            var no_kontrak = $(this).data('no_kontrak');
            var no_invoice = $(this).data('no_invoice');
            var tanggal    = $(this).data('tanggal');
            var uraian     = $(this).data('uraian');
            var pallet     = $(this).data('pallet');
            var no_palet_list = $(this).data('no_palet_list');
            var hari_ini   = $(this).data('hari_ini');
            var harga      = $(this).data('harga');
            var keterangan = $(this).data('keterangan');

            // Mengisi konten modal detail
            $('#detNoKontrak').text(no_kontrak);
            $('#detNoInvoice').text(no_invoice);
            $('#detTanggal').text(tanggal);
            $('#detUraian').text(uraian);
            $('#detPallet').text(pallet + " Pallet");
            $('#detNoPaletList').text(no_palet_list ? no_palet_list : '-');
            $('#detKg').text(hari_ini + " Kg");
            $('#detHarga').text("Rp " + harga);
            $('#detKeterangan').text(keterangan || '-');

            // Menampilkan Modal Detail
            $('#modalDetail').modal('show');
        });

        // ==========================================
        // 3. LOGIKA TOMBOL EDIT (SAMAKAN UI DGN TAMBAH)
        // ==========================================
        $('.btn-edit').click(function() {
            var id = $(this).data('id_penjualan'); 
            
            var no_kontrak = $(this).data('no_kontrak');
            var no_invoice = $(this).data('no_invoice');
            var harga      = $(this).data('harga_raw'); 
            var uraian     = $(this).data('uraian');
            
            // Format YYYY-MM-DD untuk input date
            var rawTgl     = $(this).data('tanggal'); // Format asalnya d-m-Y
            var parts      = rawTgl.split('-');
            var tglFix     = parts[2] + '-' + parts[1] + '-' + parts[0];

            var countPallet = $(this).data('pallet');
            var totalKg     = $(this).data('hari_ini');
            var listPallet  = $(this).data('no_palet_list');

            // Set Action URL
            var urlUpdate = "{{ url('penjualan-sir20') }}/" + id;
            $('#formEdit').attr('action', urlUpdate);

            // Isi Form Dasar
            $('#edit_no_kontrak').val(no_kontrak);
            $('#edit_no_invoice').val(no_invoice);
            $('#edit_harga').val(harga);
            $('#edit_uraian').val(uraian);
            $('#edit_tanggal').val(tglFix);

            $('#edit_pallet_count').val(countPallet);
            $('#edit_total_kg').val(totalKg);

            // Render Grid Pallet Terpilih
            var grid = $('#editPalletGrid');
            grid.empty();
            
            if(listPallet) {
                var arrPallets = listPallet.toString().split(',');
                $.each(arrPallets, function(i, val) {
                    grid.append(`
                        <label class="pallet-item selected" style="cursor: not-allowed;">
                            <input type="checkbox" checked disabled>
                            <span>#${val.trim()}</span>
                        </label>
                    `);
                });
            } else {
                grid.html('<span class="text-muted small">Tidak ada data pallet tersimpan.</span>');
            }

            // Tampilkan Modal Edit
            $('#modalEdit').modal('show');
        });

        // ==========================================
        // 4. LOGIKA TOMBOL HAPUS (SWEETALERT)
        // ==========================================
        $('.btn-hapus').click(function() {
            var form = $(this).closest('.form-hapus');
            Swal.fire({
                title: 'Batalkan Penjualan?',
                text: "Data penjualan akan dihapus dan stok pallet akan dikembalikan ke Gudang!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Notifikasi SweetAlert
        @if(session('success')) Swal.fire({ icon: 'success', title: 'BERHASIL!', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000 }); @endif
        @if(session('error')) Swal.fire({ icon: 'error', title: 'GAGAL!', text: '{{ session('error') }}' }); @endif
    });
</script>
</body>
</html>