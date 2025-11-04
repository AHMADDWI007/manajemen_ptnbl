<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title>Pengolahan Maturasi</title>
    <style>
        /* (Style Anda tetap sama) */
        .form-control[readonly] { background-color: #e9ecef; opacity: 1; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; padding: 0.5rem; text-align: center; }
        label { margin-bottom: 0.2rem; font-weight: 500;}
        .form-group label { display: block; }
        .filter-search-row .col-md-3 { margin-bottom: 1rem; }
        @media (min-width: 768px) {
            .filter-search-row { display: flex; align-items: flex-end; }
            #search-input-container { margin-left: auto; }
        }
        .action-buttons .btn-group { display: flex; gap: 0.25rem; }
        .data-default td { color: #6c757d; font-style: italic; }
        .detail-list dt { display: flex; justify-content: space-between; padding-right: 0.5rem; }
        .detail-list dt::after { content: ":"; }
        .detail-list dd { text-align: left; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Pengolahan Maturasi</h3>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Input Diolah/Mutasi
                </button>
            </div>
        </div>
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong>Data Pengolahan Maturasi (Status per: {{ \Carbon\Carbon::parse($selected_date)->isoFormat('D MMMM YYYY') }})</strong>
                        
                        <form method="GET" action="{{ route('maturasi.index') }}" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white">Tampilkan Tanggal:</label>
                            <input type="date" id="filter_tanggal" name="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selected_date }}">
                            <button type="submit" class="btn btn-light btn-sm">Tampilkan</button>
                        </form>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                            </div>
                        @endif
                        @if(session('edit_error'))
                            <div class="alert alert-warning mb-3">
                                Gagal menyimpan perubahan. Silakan cek kembali input Anda.
                                <script> $(function() { $('#modalEdit[data-error-id="{{ session('edit_id') }}"]').modal('show'); }); </script>
                            </div>
                        @endif

                        <div class="row mb-3 filter-search-row">
                            <div class="col-md-3" id="search-input-container" style="margin-left: auto;">
                                <label for="searchInput">Cari (Uraian/Bak):</label>
                                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Ketik nomor bak...">
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                                <thead class="bg-light">
                                <tr>
                                    <th>Uraian</th>
                                    <th>Tgl Update Terakhir</th>
                                    <th>Stok Awal (Kg)</th>
                                    <th>Tgl Masuk Stok</th>
                                    <th>Umur</th>
                                    <th>Diolah (Kg)</th>
                                    <th>Mutasi (Kg)</th>
                                    <th>Masuk HI (Kg)</th>
                                    <th>Stok Akhir (Kg)</th>
                                    <th>Asal Bokar</th> {{-- <-- KOLOM DITAMBAHKAN --}}
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($data_maturasi as $item)
                                    <tr>
                                        <td>{{ $item->uraian }}</td>
                                        <td>{{ $item->updated_at->format('d-m-Y') }}</td> 
                                        <td>{{ number_format($item->stok_awal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($item->tgl_masuk)
                                                {{ $item->tgl_masuk->format('d-m-Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->umur ?? 0 }} hari</td>
                                        <td>{{ number_format($item->diolah, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->mutasi, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->masuk_hi, 0, ',', '.') }}</td>
                                        <td>{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                                        <td>{{ $item->asal_bokar ?? '-' }}</td> {{-- <-- KOLOM DITAMBAHKAN --}}
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center action-buttons">
                                            <div class="btn-group gap-1" role="group">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"> <i class="fas fa-edit"></i> </button>
                                                <form action="{{ route('maturasi.reset', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin ME-RESET data Bak ini kembali ke KOSONG?')" style="display:inline;">
                                                    @csrf 
                                                    <button type="submit" class="btn btn-secondary btn-sm" title="Reset Data"> 
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            </div>
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
    @include('template.footer')
</div>

{{-- ============================================= --}}
{{-- SEMUA MODAL DITEMPATKAN DI SINI --}}
{{-- ============================================= --}}

{{-- MODAL TAMBAH DATA --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('maturasi.store') }}" method="POST" id="formTambah">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Input Data Diolah / Mutasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Input Harian</label>
                            <input type="date" name="tanggal_input_harian" id="tanggal_input_tambah" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Uraian (Hanya Bak Aktif)</label>
                            <select name="uraian" id="uraian" class="form-control form-control-sm" required>
                                <option value="" disabled selected>-- Pilih Bak Maturasi --</option>
                                @forelse ($bak_aktif_list as $uraian_aktif)
                                    <option value="{{ $uraian_aktif }}">{{ $uraian_aktif }}</option>
                                @empty
                                    <option value="" disabled>Tidak ada Bak dengan stok untuk tanggal ini</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stok Awal (Kg)</label>
                            <input type="text" name="stok_awal" id="stok_awal" class="form-control form-control-sm" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari)</label>
                            <input type="number" name="umur" id="umur" class="form-control form-control-sm" readonly required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Diolah (Kg)</label>
                            <input type="number" name="diolah" id="diolah" class="form-control form-control-sm" value="0" step="0.01" min="0">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Mutasi (Kg)</label>
                            <input type="number" name="mutasi" id="mutasi" class="form-control form-control-sm" value="0" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Masuk Hari Ini (Kg)</label>
                            <input type="text" name="masuk_hi" id="masuk_hi" class="form-control form-control-sm" value="0,00" readonly>
                        </div>
                         
                         {{-- PERBAIKAN: Input Asal Bokar menjadi Dropdown --}}
                         <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <select name="asal_bokar" id="asal_bokar" class="form-control form-control-sm">
                                <option value="INHUT">INHUT</option>
                                <option value="PT">PT</option>
                                <option value="CMP">CMP</option>
                                <option value="Petani" selected>Petani</option> {{-- Default 'Petani' --}}
                            </select>
                        </div>
                        
                        <hr class="col-12 my-2">
                        <div class="col-md-6 mb-3">
                            <label>Perkiraan Stok Akhir (Kg)</label>
                            <input type="text" id="stok_akhir_display" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" id="keterangan" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
             <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Detail Data Maturasi</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 detail-list">
                    <dt class="col-sm-5">Uraian</dt><dd class="col-sm-7" id="detailUraian">-</dd>
                    <dt class="col-sm-5">Tgl Update Terakhir</dt><dd class="col-sm-7" id="detailTglInputAsli">-</dd>
                    <dt class="col-sm-5">Stok Awal (Kg)</dt><dd class="col-sm-7" id="detailStokAwal">-</dd>
                    <dt class="col-sm-5">Tgl Masuk Stok</dt><dd class="col-sm-7" id="detailTglMasuk">-</dd>
                    <dt class="col-sm-5">Umur</dt><dd class="col-sm-7" id="detailUmur">-</dd>
                    <dt class="col-sm-5">Diolah (Kg)</dt><dd class="col-sm-7" id="detailDiolah">-</dd>
                    <dt class="col-sm-5">Mutasi (Kg)</dt><dd class="col-sm-7" id="detailMutasi">-</dd>
                    <dt class="col-sm-5">Masuk HI (Kg)</dt><dd class="col-sm-7" id="detailMasukHi">-</dd>
                    <dt class="col-sm-5">Stok Akhir (Kg)</dt><dd class="col-sm-7" id="detailStokAkhir">-</dd>
                    <dt class="col-sm-5">Asal Bokar</dt><dd class="col-sm-7" id="detailAsalBokar">-</dd> {{-- <-- DITAMBAHKAN --}}
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detailKeterangan">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST" data-error-id="{{ session('edit_id') }}">
                @csrf
                @method('PUT')
                 <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Edit Data Maturasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Input Harian</label>
                            <input type="date" name="tanggal_input" id="editTanggalInput" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Uraian</label>
                            <input type="text" name="uraian" id="editUraian" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Stok Awal (Kg)</label>
                            <input type="number" name="stok_awal" id="editStokAwal" class="form-control form-control-sm" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Umur (Hari)</label>
                            <input type="number" name="umur" id="editUmur" class="form-control form-control-sm" step="1" required>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Tanggal Masuk Bokar</label>
                            <input type="date" name="tgl_masuk" id="editTglMasuk" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Diolah (Kg)</label>
                            <input type="number" name="diolah" id="editDiolah" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Mutasi (Kg)</label>
                            <input type="number" name="mutasi" id="editMutasi" class="form-control form-control-sm" step="0.01">
                        </div>
                         <div class="col-md-6 mb-3">
                            <label>Masuk HI (Kg)</label>
                            <input type="number" name="masuk_hi" id="editMasukHi" class="form-control form-control-sm" step="0.01">
                        </div>
                        
                        {{-- PERBAIKAN: Input Asal Bokar menjadi Dropdown --}}
                        <div class="col-md-6 mb-3">
                            <label>Asal Bokar</label>
                            <select name="asal_bokar" id="editAsalBokar" class="form-control form-control-sm">
                                <option value="" disabled>-- Pilih Asal --</option>
                                <option value="INHUT">INHUT</option>
                                <option value="PT">PT</option>
                                <option value="CMP">CMP</option>
                                <option value="Petani">Petani</option>
                            </select>
                        </div>
                         
                         <div class="col-md-6 mb-3">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" id="editKeterangan" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- Script Libraries --}}
@include('template.script')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {

    // ----- INISIALISASI DATATABLE DI AWAL -----
     var table = $('#dataTable').DataTable({
        "ordering": false,
        "paging": false,
        "info": false,
        "searching": true,
        "language": { "zeroRecords": "Tidak ada data yang cocok"},
        "dom": 'rt'
    });
    
    // ----- INISIALISASI FLATPICKR (DIHAPUS) -----
    
    // ----- FUNGSI PARSE TANGGAL (DIHAPUS) -----
    
    // ----- FUNGSI FILTER CLIENT-SIDE (DIHAPUS) -----
    
    // --- Tombol Reset Client-Side (DIHAPUS) ---
     
     // --- Listener search input custom (SATU-SATUNYA FILTER) ---
     $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
     });

    // ----- EVENT HANDLER LAINNYA -----
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ----- FUNGSI FORMATTING (Sama) -----
    function formatNumber(num, precision = 0) { if (num === null || typeof num === 'undefined' || num === '') return '0'; let parsedNum = parseFloat(String(num).replace(/[^0-9,.-]+/g,"").replace(',','.')); if (isNaN(parsedNum)) return '0'; return parsedNum.toLocaleString('id-ID', { minimumFractionDigits: precision, maximumFractionDigits: precision }); }
    function formatTanggalModal(dateStr) { if (!dateStr) return ''; try { if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr; let dateObj = new Date(dateStr); if (isNaN(dateObj.getTime())) return ''; let year = dateObj.getFullYear(); let month = ('0' + (dateObj.getMonth() + 1)).slice(-2); let day = ('0' + dateObj.getDate()).slice(-2); return `${year}-${month}-${day}`; } catch(e) { return ''; } }
    function formatTanggalDetailModal(dateStr) { if (!dateStr) return 'KOSONG'; try { let dateObj = new Date(dateStr + 'T00:00:00Z'); if (isNaN(dateObj.getTime())) return 'Invalid Date'; return dateObj.toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric', timeZone: 'UTC'}); } catch(e) { return 'Error'; } }

    // --- LOGIKA MODAL TAMBAH (Sama) ---
    function calculateStokAkhirDisplay() {
        let stok_awal_str = $('#stok_awal').val();
        let stok_awal = parseFloat(stok_awal_str.replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let diolah = parseFloat($('#diolah').val()) || 0;
        let mutasi = parseFloat($('#mutasi').val()) || 0;
        let masuk_hi_str = $('#masuk_hi').val();
        let masuk_hi = parseFloat(masuk_hi_str.replace(/[^0-9,.-]+/g,"").replace(',','.')) || 0;
        let stok_akhir = stok_awal - diolah - mutasi + masuk_hi;
        $('#stok_akhir_display').val(formatNumber(stok_akhir));
    }

    function updateKeterangan(prefix = '') {
        let tanggalInputId = (prefix === 'edit') ? '#editTanggalInput' : '#tanggal_input_tambah';
        let keteranganId = (prefix === 'edit') ? '#editKeterangan' : '#keterangan';
        let tanggalInput = $(tanggalInputId).val();
        if (tanggalInput) {
            try {
                let dateObj = new Date(tanggalInput + 'T00:00:00Z');
                if (isNaN(dateObj.getTime())) throw new Error("Invalid Date");
                let options = { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' };
                $(keteranganId).val(dateObj.toLocaleDateString('id-ID', options));
            } catch (e) {
                console.error("Error parsing date:", e);
                $(keteranganId).val('Tanggal Invalid');
            }
        } else {
            $(keteranganId).val('');
        }
    }

    function fetchPreviousData() {
        const selectedUraian = $('#uraian').val();
        const selectedDate = $('#tanggal_input_tambah').val();
        if (selectedUraian && selectedDate) {
            console.log("Fetching data for:", selectedUraian, selectedDate);
            $.ajax({
                url: "{{ route('maturasi.getPreviousData') }}", type: 'GET',
                data: { uraian: selectedUraian, tanggal_filter: selectedDate },
                dataType: 'json',
                success: function(data) {
                    console.log("Data received:", data);
                    $('#stok_awal').val(formatNumber(data.stok_awal));
                    $('#umur').val(data.umur !== null ? data.umur : 0);
                    $('#masuk_hi').val(formatNumber(data.netto_kering_hi));
                    calculateStokAkhirDisplay();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    var errorMsg = jqXHR.responseJSON && jqXHR.responseJSON.error ? jqXHR.responseJSON.error : 'Gagal mengambil data sebelumnya.';
                    alert('Gagal: ' + errorMsg);
                    $('#stok_awal').val('0,00'); $('#umur').val(0); $('#masuk_hi').val('0,00');
                    calculateStokAkhirDisplay();
                }
            });
        } else {
             $('#stok_awal').val('0,00'); $('#umur').val(0); $('#masuk_hi').val('0,00');
             calculateStokAkhirDisplay();
        }
    }

    $('#uraian').on('change', fetchPreviousData);
    $('#tanggal_input_tambah').on('change', function() { fetchPreviousData(); updateKeterangan(''); });
    $('#diolah, #mutasi, #masuk_hi, #stok_awal').on('input keyup', calculateStokAkhirDisplay);

    $('#modalTambah').on('show.bs.modal', function () {
         $('#formTambah')[0].reset();
         $('#asal_bokar').val('Petani'); // <-- PERBAIKAN: Set default dropdown
         $('#diolah, #mutasi').val('0');
         $('#masuk_hi').val('0,00');
         $('#stok_awal, #umur').val('');
         $('#stok_akhir_display, #keterangan').val('');
         let defaultDate = "{{ $selected_date }}"; 
         $('#tanggal_input_tambah').val(defaultDate).trigger('change');
    });

    $(document).on('change', '#editTanggalInput', function() { updateKeterangan('edit'); });


    // --- LOGIKA DETAIL & EDIT (Sama) ---
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        var url = "{{ url('maturasi') }}/" + id;
        $.get(url, function (data) {
            $('#detailUraian').text(data.uraian || '-');
            $('#detailTglInputAsli').text(data.updated_at ? formatTanggalDetailModal(data.updated_at.split('T')[0]) : '-');
            $('#detailStokAwal').text(formatNumber(data.stok_awal));
            $('#detailTglMasuk').text(data.tgl_masuk ? formatTanggalDetailModal(data.tgl_masuk.split('T')[0]) : 'KOSONG');
            $('#detailUmur').text((data.umur ?? 0) + ' hari');
            $('#detailDiolah').text(formatNumber(data.diolah));
            $('#detailMutasi').text(formatNumber(data.mutasi));
            $('#detailMasukHi').text(formatNumber(data.masuk_hi));
            $('#detailStokAkhir').text(formatNumber(data.stok_akhir));
            $('#detailAsalBokar').text(data.asal_bokar || '-'); // <-- DITAMBAHKAN
            $('#detailKeterangan').text(data.keterangan || '-');
            $('#modalDetail').modal('show');
         }).fail(function() { alert('Gagal memuat detail data.'); });
    });

    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var urlGet = "{{ url('maturasi') }}/" + id + "/edit";
        var urlPost = "{{ url('maturasi') }}/" + id;
        $.get(urlGet, function (data) {
             let tglModal = data.updated_at ? formatTanggalModal(data.updated_at.split('T')[0]) : '';
             $('#editTanggalInput').val(tglModal).trigger('change');

             $('#editUraian').val(data.uraian);
             $('#editStokAwal').val(data.stok_awal !== null ? parseFloat(data.stok_awal).toFixed(2) : '0');
             $('#editUmur').val(data.umur !== null ? data.umur : 0);
             $('#editTglMasuk').val(data.tgl_masuk ? formatTanggalModal(data.tgl_masuk) : '');
             $('#editDiolah').val(data.diolah !== null ? parseFloat(data.diolah).toFixed(2) : '0');
             $('#editMutasi').val(data.mutasi !== null ? parseFloat(data.mutasi).toFixed(2) : '0');
             $('#editMasukHi').val(data.masuk_hi !== null ? parseFloat(data.masuk_hi).toFixed(2) : '0');
             $('#editAsalBokar').val(data.asal_bokar); // <-- PERBAIKAN: Set value dropdown
             $('#formEdit').attr('action', urlPost);
             $('#formEdit').attr('data-error-id', id);
             $('#modalEdit').modal('show');
         }).fail(function() { alert('Gagal memuat data untuk diedit.'); });
    });


    // --- Notifikasi Sukses ---
    @if (session('success'))
        if (typeof Swal !== 'undefined') {
             Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
        } else { alert("{{ session('success') }}"); }
    @endif
    
    @if (session('error'))
        if (typeof Swal !== 'undefined') {
             Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}", showConfirmButton: false, timer: 2500 });
        } else { alert("{{ session('error') }}"); }
    @endif

});
</script>
</body>
</html>