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
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambahSir"> 
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">Daftar Hasil Uji Sir 20</div>
                    <div class="card-body">
                        @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul></div>
                        @endif

                        {{-- FILTER TANGGAL (VERSI RAPI - TETAP SAMA) --}}
                        <div class="row mb-3 align-items-end">
                            <div class="col-auto">
                                <label for="min-date" class="form-label small fw-bold mb-1">Dari Tanggal:</label>
                                <input type="text" id="min-date" class="form-control form-control-sm" placeholder="dd/mm/yyyy" style="width: 140px;">
                            </div>
                            <div class="col-auto">
                                <label for="max-date" class="form-label small fw-bold mb-1">Sampai Tanggal:</label>
                                <input type="text" id="max-date" class="form-control form-control-sm" placeholder="dd/mm/yyyy" style="width: 140px;">
                            </div>
                            <div class="col-auto">
                                <button id="filter-btn" class="btn btn-primary btn-sm fw-bold mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                                <button id="reset-filter" class="btn btn-secondary btn-sm fw-bold"><i class="fas fa-undo mr-1"></i> Reset</button>
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="dataTable" style="width:100%">
                                <thead class="text-center bg-light"> 
                                    <tr>
                                        <th>No</th><th>Tanggal</th><th>Jenis Kemasan</th><th>No. Palet</th>
                                        <th>Po</th><th>Pa</th><th>PRI</th><th>Dirt(%)</th><th>Ash(%)</th>
                                        <th>VM(%)</th><th>Money</th><th>Nitrogen(%)</th><th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data_sir_20 as $item) 
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $item->jenis_kemasan ?? '-' }}</td>
                                            <td>{{ $item->no_palet }}</td>
                                            <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                            <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                            <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                            <td>{{ is_numeric($item->dirt) ? (fmod($item->dirt, 1) == 0 ? (int)$item->dirt : $item->dirt) : '-' }}</td> 
                                            <td>{{ is_numeric($item->ash) ? (fmod($item->ash, 1) == 0 ? (int)$item->ash : $item->ash) : '-' }}</td>
                                            <td>{{ is_numeric($item->vm) ? (fmod($item->vm, 1) == 0 ? (int)$item->vm : $item->vm) : '-' }}</td>
                                            <td>{{ is_numeric($item->money) ? (fmod($item->money, 1) == 0 ? (int)$item->money : $item->money) : '-' }}</td>
                                            <td>{{ is_numeric($item->nitrogen) ? (fmod($item->nitrogen, 1) == 0 ? (int)$item->nitrogen : $item->nitrogen) : '-' }}</td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_hasil_uji_lab_sir_20 }}"><i class="fas fa-eye"></i></button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id_hasil_uji_lab_sir_20 }}"><i class="fas fa-edit"></i></button>
                                                    <form action="{{ route('hasil-uji-sir20.destroy', $item->id_hasil_uji_lab_sir_20) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')" style="display:inline-block; margin:0;"> 
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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
</div>

@include('template.script') 

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahSir" tabindex="-1" role="dialog" aria-hidden="true">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('hasil-uji-sir20.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal Produksi</label><input type="date" name="tanggal" id="tambah_tanggal" class="form-control" required></div>
                    <div class="form-group">
                        <label>Jenis Kemasan</label>
                        <select name="jenis_kemasan" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="MB5">MB5</option>
                            <option value="SW">SW</option>
                        </select>
                    </div>
                    {{-- REVISI: NO PALET JADI DROPDOWN --}}
                    <div class="form-group">
                        <label>No. Palet</label>
                        <select name="no_palet" id="tambah_no_palet" class="form-control" required>
                            <option value="">-- Pilih Nomor Palet --</option>
                            @foreach ($palletOptions as $no)
                                <option value="{{ $no }}">Palet No. {{ $no }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Po</label><input type="number" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" class="form-control" step="0.001"></div>
                    <div class="form-group"><label>Ash (%)</label><input type="number" name="ash" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>VM (%)</label><input type="number" name="vm" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Mooney</label><input type="number" name="money" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Nitrogen (%)</label><input type="number" name="nitrogen" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
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
                    <h5 class="modal-title">Edit Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                    <div class="form-group">
                        <label>Jenis Kemasan</label>
                        <select name="jenis_kemasan" id="editJenisKemasan" class="form-control" required>
                            <option value="MB5">MB5</option>
                            <option value="SW">SW</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>No. Palet</label>
                        <select name="no_palet" id="editNoPalet" class="form-control" required>
                            {{-- Diisi via JS --}}
                        </select>
                    </div>
                    <div class="form-group"><label>Po</label><input type="number" name="po" id="editPo" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" id="editPa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" id="editPri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" id="editDirt" class="form-control" step="0.001"></div>
                    <div class="form-group"><label>Ash (%)</label><input type="number" name="ash" id="editAsh" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>VM (%)</label><input type="number" name="vm" id="editVm" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Mooney</label><input type="number" name="money" id="editMoney" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Nitrogen (%)</label><input type="number" name="nitrogen" id="editNitrogen" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
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

    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

    // --- LOGIKA FILTER TANGGAL (TETAP SAMA) ---
    var fpMin, fpMax;
    function parseDMY(dateStr){
        var parts = dateStr.split('-'); if(parts.length!==3) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }
    fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat:"Y-m-d", defaultDate: "today" });
    fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat:"Y-m-d", defaultDate: "today" });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(), max = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!tableDateStr || tableDateStr==='-') return true; var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = min ? new Date(min + 'T00:00:00') : null, maxDate = max ? new Date(max + 'T23:59:59') : null;
        if((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });

    var table = $('#dataTable').DataTable({
        "order": [[1,"desc"]],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" }
    });

    table.draw();
    $('#filter-btn').click(function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').click(function(e){ e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today"); setTimeout(function(){ table.search('').draw(); },100); });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // --- LOGIKA AJAX UPDATE NOMOR PALET BERDASARKAN TANGGAL MODAL ---
    function fetchPallets(tanggal, targetDropdown, selectedVal = null) {
        if (!tanggal) return;
        $.ajax({
            url: "{{ route('uji-sir20.get-pallets') }}", // Pastikan route ini ada di web.php
            type: "GET",
            data: { tanggal: tanggal },
            success: function(response) {
                let dropdown = $(targetDropdown);
                dropdown.empty().append('<option value="">-- Pilih Nomor Palet --</option>');
                $.each(response, function(key, val) {
                    let selected = (val.nomor == selectedVal) ? 'selected' : '';
                    dropdown.append('<option value="'+val.nomor+'" '+selected+'>Palet No. '+val.nomor+'</option>');
                });
            }
        });
    }

    $('#tambah_tanggal').on('change', function() {
        fetchPallets($(this).val(), '#tambah_no_palet');
    });

    $('#editTanggal').on('change', function() {
        fetchPallets($(this).val(), '#editNoPalet');
    });

    // EDIT
    $(document).on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil-uji-sir20') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil-uji-sir20') }}/" + id;
        $.get(urlGet, function(data){
            $('#editTanggal').val(data.tanggal); 
            $('#editJenisKemasan').val(data.jenis_kemasan);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa); 
            $('#editPri').val(data.pri); 
            $('#editDirt').val(data.dirt);
            $('#editAsh').val(data.ash); 
            $('#editVm').val(data.vm); 
            $('#editMoney').val(data.money);
            $('#editNitrogen').val(data.nitrogen); 
            $('#formEdit').attr('action', urlPost); 

            // Load No Palet untuk Modal Edit
            fetchPallets(data.tanggal, '#editNoPalet', data.no_palet);

            $('#modalEdit').modal('show'); 
        });
    });

    // DETAIL (TETAP SAMA)
    $(document).on('click', '.btn-detail', function(){
        var id = $(this).data('id');
        var url = "{{ url('hasil-uji-sir20') }}/" + id;
        $.get(url, function(data){
            $('#detailTanggal').text(new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }));
            $('#detailJenisKemasan').text(data.jenis_kemasan || '-');
            $('#detailNoPalet').text(data.no_palet || '-');
            function formatNumber(num) { if (!$.isNumeric(num)) return '-'; return (num % 1 === 0) ? parseInt(num) : parseFloat(num); }
            $('#detailPo').text(formatNumber(data.po));
            $('#detailPa').text(formatNumber(data.pa));
            $('#detailPri').text(formatNumber(data.pri));
            $('#detailDirt').text(formatNumber(data.dirt) !== '-' ? formatNumber(data.dirt) + ' %' : '-');
            $('#detailAsh').text(formatNumber(data.ash) !== '-' ? formatNumber(data.ash) + ' %' : '-');
            $('#detailVm').text(formatNumber(data.vm) !== '-' ? formatNumber(data.vm) + ' %' : '-');
            $('#detailMoney').text(formatNumber(data.money));
            $('#detailNitrogen').text(formatNumber(data.nitrogen) !== '-' ? formatNumber(data.nitrogen) + ' %' : '-');
            $('#modalDetail').modal('show'); 
        });
    });
});
</script>
</body>
</html>