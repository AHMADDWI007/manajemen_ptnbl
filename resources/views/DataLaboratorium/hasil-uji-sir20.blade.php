<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Sir 20</title>
    
    {{-- CSS DataTables Bootstrap 4 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; text-align: center; }
        #dataTable th, #dataTable td { text-align: center !important; vertical-align: middle !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        .page-item.active .page-link { background-color: #28a745; border-color: #28a745; }
        .page-link { color: #28a745; }
        .page-link:hover { color: #1e7e34; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Hasil Uji Sir 20</h1>
                <div>
                    <a href="javascript:void(0)" class="btn btn-warning btn-sm fw-bold mr-1" id="btnExportExcel">
                        <i class="fas fa-file-excel"></i> Cetak Excel
                    </a>
                    {{-- 🔥 SEMBUNYIKAN TOMBOL JIKA ROLE USER --}}
                    @if(auth()->user()->role != 'user')
                    <button class="btn btn-success btn-sm fw-bold" id="btnTambahData" data-toggle="modal" data-target="#modalTambahSir"> 
                        <i class="fas fa-plus-circle"></i> Tambah Data
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        <strong class="my-auto">Daftar Hasil Uji Sir 20</strong></div>
                    <div class="card-body">
                        @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul></div>
                        @endif
                            {{-- FILTER TANGGAL & MUTU --}}
                            <form method="GET" action="{{ route('hasil-uji-sir20.index') }}">
                                <div class="row mb-3 align-items-end">
                                    <div class="col-auto">
                            
                                <label for="min-date" class="form-label small fw-bold mb-1">Dari Tanggal:</label>
                                {{-- ✅ Ganti ke type="text", tambah bg-light dan readonly --}}
                                <input type="text" name="start_date" id="min-date" 
                                    class="form-control form-control-sm bg-light" 
                                    value="{{ $fromDate }}" readonly placeholder="dd/mm/yyyy" style="width: 140px;">
                            </div>
                            <div class="col-auto">
                                <label for="max-date" class="form-label small fw-bold mb-1">Sampai Tanggal:</label>
                                {{-- ✅ Lakukan hal yang sama untuk input ini --}}
                                <input type="text" name="end_date" id="max-date" 
                                    class="form-control form-control-sm bg-light" 
                                    value="{{ $toDate }}" readonly placeholder="dd/mm/yyyy" style="width: 140px;">
                            </div>
                                    <div class="col-auto">
                                        <label class="form-label small fw-bold mb-1">Status Mutu:</label>
                                        <select name="status_mutu" id="filter-mutu" class="form-control form-control-sm" style="width: 150px;">
                                            <option value="all" {{ $statusMutu == 'all' ? 'selected' : '' }}>Semua Data</option>
                                            <option value="low" {{ $statusMutu == 'low' ? 'selected' : '' }}>Hanya Low (PRI < 60)</option>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">
                                            <i class="fas fa-filter mr-1"></i> Filter
                                        </button>
                                        {{-- ✅ Tambahkan id="reset-filter" --}}
                                        <a href="{{ route('hasil-uji-sir20.index') }}" id="reset-filter" class="btn btn-secondary btn-sm fw-bold">
                                            <i class="fas fa-undo mr-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="dataTable" style="width:100%">
                                <thead class="text-center bg-light"> 
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Jenis Kemasan</th>
                                        <th>No. Palet</th>
                                        <th>Po</th>
                                        <th>Pa</th>
                                        <th>PRI</th>
                                        @if(auth()->user()->role != 'user')
                                        <th>Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data_sir_20 as $item) 
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $item->jenis_kemasan ?? '-' }}</td>
                                            <td>{{ $item->no_palet }}</td>
                                            <td>{{ $item->po }}</td>
                                            <td>{{ $item->pa }}</td>
                                            <td>{{ $item->pri }}</td>
                                            {{-- 🔥 SEMBUNYIKAN KOLOM AKSI JIKA ROLE USER --}}
                                            @if(auth()->user()->role != 'user')
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id_hasil_uji_lab_sir_20 }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                    
                                                    {{-- 🔥 TOMBOL HAPUS DENGAN CLASS BTN-HAPUS UNTUK SWEETALERT --}}
                                                    <button type="button" class="btn btn-danger btn-sm btn-hapus" 
                                                            data-id="{{ $item->id_hasil_uji_lab_sir_20 }}" 
                                                            data-nama="{{ $item->no_palet }}" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <form id="form-hapus-{{ $item->id_hasil_uji_lab_sir_20 }}" action="{{ route('hasil-uji-sir20.destroy', $item->id_hasil_uji_lab_sir_20) }}" method="POST" style="display:none;"> 
                                                        @csrf @method('DELETE')
                                                    </form>
                                                </div>
                                            </td>
                                            @endif
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
</div>

@include('template.script') 

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahSir" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <div class="modal-content">
            <form action="{{ route('hasil-uji-sir20.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-flask mr-2"></i>Tambah Hasil Uji SIR 20
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                            <label>Tanggal Uji</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ $today }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Jenis Kemasan</label>
                                <select name="jenis_kemasan" class="form-control" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="MB5">MB5</option>
                                    <option value="SW">SW</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">No. Palet <span class="text-muted small">(Sisa yang belum diuji)</span></label>
                        <select name="no_palet" id="tambah_no_palet" class="form-control" required disabled>
                            <option value="">-- Pilih Tanggal Dahulu --</option>
                        </select>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-success fw-bold mb-3"><i class="fas fa-vial mr-2"></i>Parameter Hasil Uji</h6>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Po</label>
                                <input type="number" name="po" class="form-control" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Pa</label>
                                <input type="number" name="pa" class="form-control" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                       <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">PRI</label>
                                {{-- bg-light memberikan warna abu muda, readonly mencegah pengetikan manual --}}
                                <input type="number" name="pri" class="form-control bg-light" 
                                    step="0.01" placeholder="Otomatis" readonly 
                                    style="background-color: #e9ecef; border: 1px solid #ced4da;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success shadow-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Data Hasil Uji
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-success text-white"> 
                    <h5 class="modal-title font-weight-bold">Edit Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- Baris 1: Tanggal --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Tanggal Produksi</label>
                        <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
                    </div>

                    {{-- Baris 2: Kemasan & Palet --}}
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Jenis Kemasan</label>
                                <select name="jenis_kemasan" id="editJenisKemasan" class="form-control" required>
                                    <option value="MB5">MB5</option>
                                    <option value="SW">SW</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">No. Palet</label>
                                <select name="no_palet" id="editNoPalet" class="form-control" required>
                                    {{-- Diisi secara otomatis via JavaScript --}}
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Baris 3: Po & Pa --}}
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Po</label>
                                <input type="number" name="po" id="editPo" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Pa</label>
                                <input type="number" name="pa" id="editPa" class="form-control" step="0.01">
                            </div>
                        </div>
                    </div>

                    {{-- Baris 4: PRI & Dirt --}}
                    <div class="row">
                       <div class="col-6">
                            <div class="form-group">
                                <label class="font-weight-bold">PRI</label>
                                <input type="number" name="pri" id="editPri" class="form-control bg-light" 
                                    step="0.01" readonly 
                                    style="background-color: #e9ecef; border: 1px solid #ced4da;">
                            </div>
                        </div>
                    </div>

                    {{-- Baris 5: Ash & VM --}}

                    {{-- Baris 6: Mooney & Nitrogen --}}
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning shadow-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function(){
    // 1. Notifikasi Sukses Global
    @if (session('success'))
        Swal.fire({ 
            icon: 'success', 
            title: 'Berhasil!', 
            text: "{{ session('success') }}", 
            showConfirmButton: false, 
            timer: 2000 
        });
    @endif

    // 2. Notifikasi Error Validasi Global
    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            html: '{!! implode("<br>", $errors->all()) !!}',
        });
    @endif

    // 🔥 LOGIKA EXCEL EXPORT (SIR 20)
    $('#btnExportExcel').on('click', function(e) {
        e.preventDefault();
        var minDate = $('#min-date').val();
        var maxDate = $('#max-date').val();
        var statusMutu = $('#filter-mutu').val(); // Mengambil filter status mutu
        
        var exportUrl = "{{ route('hasil-uji-sir20.export') }}";
        
        if (minDate && maxDate) {
            exportUrl += "?start_date=" + minDate + "&end_date=" + maxDate + "&status_mutu=" + statusMutu;
            window.location.href = exportUrl;
        } else {
            Swal.fire('Informasi', 'Silakan pilih rentang tanggal filter terlebih dahulu.', 'info');
        }
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- SETUP TANGGAL (Flatpickr) ---
    flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "{{ $fromDate }}" });
    flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "{{ $toDate }}" });

    // --- 3. LOGIKA HITUNG PRI OTOMATIS ---
    function hitungPRI(poSelector, paSelector, priSelector) {
        let po = parseFloat($(poSelector).val()) || 0;
        let pa = parseFloat($(paSelector).val()) || 0;
        let priField = $(priSelector);
        if (po > 0) {
            let hasil = (pa / po) * 100;
            priField.val(hasil.toFixed(2)); 
        } else { priField.val(''); }
    }

    $(document).on('input', '#modalTambahSir input[name="po"], #modalTambahSir input[name="pa"]', function() {
        hitungPRI('#modalTambahSir input[name="po"]', '#modalTambahSir input[name="pa"]', '#modalTambahSir input[name="pri"]');
    });

    $(document).on('input', '#editPo, #editPa', function() {
        hitungPRI('#editPo', '#editPa', '#editPri');
    });

    // --- 4. DATATABLES & FILTER ---
    function parseDMY(dateStr){
        var parts = dateStr.split('-');
        if(parts.length !== 3) return null;
        return new Date(parts[2], parts[1]-1, parts[0]);
    }

    var table = $('#dataTable').DataTable({
        "order": [[1,"desc"]],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" }
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var min = $('#min-date').val();
        var max = $('#max-date').val();
        var statusMutu = $('#filter-mutu').val();
        var tableDateStr = data[1] || '';      
        var priValue = parseFloat(data[6]) || 0; 

        var matchDate = true;
        if (tableDateStr) {
            var tableDate = parseDMY(tableDateStr);
            var minDate = min ? new Date(min + 'T00:00:00') : null;
            var maxDate = max ? new Date(max + 'T23:59:59') : null;
            if ((minDate && tableDate < minDate) || (maxDate && tableDate > maxDate)) { matchDate = false; }
        }
        var matchMutu = (statusMutu === 'low') ? (priValue < 60.00) : true;
        return matchDate && matchMutu;
    });

    table.draw();

    // --- 5. KONFIRMASI SIMPAN ---
    $('#modalTambahSir form').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Simpan Hasil Uji?',
            text: "Data akan dicatat sebagai hasil uji SIR 20 resmi.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => { if (result.isConfirmed) { form.submit(); } });
    });

    // --- 6. KONFIRMASI UPDATE ---
    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Update Data Uji?',
            text: "Perubahan parameter akan langsung diperbarui.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Update!',
            cancelButtonText: 'Batal'
        }).then((result) => { if (result.isConfirmed) { form.submit(); } });
    });

    // --- 7. KONFIRMASI HAPUS ---
    $(document).on('click', '.btn-hapus', function(e) {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        Swal.fire({
            title: 'Hapus Data Uji?',
            text: "Data uji palet nomor " + nama + " akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => { if (result.isConfirmed) { $('#form-hapus-' + id).submit(); } });
    });

    // --- 8. LOGIKA EDIT (AJAX) ---
    $(document).on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        $.get("{{ url('hasil-uji-sir20') }}/" + id + "/edit", function(data){
            $('#editTanggal').val(data.tanggal);
            $('#editJenisKemasan').val(data.jenis_kemasan);
            $('#editNoPalet').html(`<option value="${data.no_palet}" selected>Palet No. ${data.no_palet}</option>`).prop('disabled', false);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            $('#formEdit').attr('action', "{{ url('hasil-uji-sir20') }}/" + id);
            $('#modalEdit').modal('show');
        }).fail(function() { Swal.fire('Gagal', 'Tidak dapat memuat data edit.', 'error'); });
    });

    // --- 9. AJAX LOAD PALLETS ---
    function loadAvailablePallets() {
        let dropdown = $('#tambah_no_palet');
        dropdown.html('<option value="">Memuat Palet...</option>').prop('disabled', true);
        $.ajax({
            url: "{{ route('uji-sir20.get-pallets') }}", 
            type: "GET",
            success: function(response) {
                dropdown.empty().append('<option value="">-- Pilih Nomor Palet --</option>');
                $.each(response, function(key, val) {
                    dropdown.append(`<option value="${val.nomor}">Palet No. ${val.nomor}</option>`);
                });
                dropdown.prop('disabled', false);
            }
        });
    }

    $('#btnTambahData').on('click', function() { loadAvailablePallets(); });
});
</script>
</body>
</html>