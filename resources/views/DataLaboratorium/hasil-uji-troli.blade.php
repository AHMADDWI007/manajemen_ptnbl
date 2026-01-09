<!DOCTYPE html>
<html lang="id">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Troli</title>
    
    {{-- CSS DataTables Bootstrap 4 (Agar sinkron dengan AdminLTE) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .table-bordered th, .table-bordered td { 
            border: 1px solid #dee2e6; 
            vertical-align: middle; 
            white-space: nowrap; 
            text-align: center; 
        }
        #dataTable th, #dataTable td { 
            text-align: center !important; 
            vertical-align: middle !important; 
        }
        .action-buttons { 
            display: flex; 
            justify-content: center; 
            gap: 5px; 
        }
        /* Fix Pagination Bootstrap 4 */
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
                <h1 class="m-0 text-success fw-bold">Hasil Uji Troli</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambahTroli">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        Daftar Hasil Uji Troli
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">
                                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                            </ul></div>
                        @endif

                        {{-- FILTER TANGGAL (VERSI RAPI) --}}
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
                                <thead class="bg-light"> 
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>No. Trolly</th>
                                        <th>K3 (%)</th>
                                        <th>Po</th>
                                        <th>Pa</th>
                                        <th>PRI</th>
                                        <th>Jam Sample</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data_troli as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                            <td>{{ $item->no_trolly }}</td>
                                            <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
                                            <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                            <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                            <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                            <td>{{ $item->jam_sample ? \Carbon\Carbon::parse($item->jam_sample)->format('H:i') : '-' }}</td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id_hasil_uji_lab_troli }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id_hasil_uji_lab_troli }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <form action="{{ route('hasil-uji-troli.destroy', $item->id_hasil_uji_lab_troli) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline-block; margin:0;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
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

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

@include('template.script') 

{{-- MODAL TAMBAH --}}
<div class="modal fade" id="modalTambahTroli" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('hasil-uji-troli.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white"> 
                    <h5 class="modal-title fw-bold">Tambah Hasil Uji Troli</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label>No. Trolly</label><input type="text" name="no_trolly" class="form-control" required></div>
                    <div class="form-group"><label>K3</label><input type="number" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Po</label><input type="number" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Jam Sample</label><input type="time" name="jam_sample" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
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
                <h5 class="modal-title">Detail Hasil Uji Troli</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 d-flex justify-content-between"><span>Tanggal</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailTanggal">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>No. Trolly</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailNoTrolly">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>K3</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailK3">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>Po</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailPo">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>Pa</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailPa">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>PRI</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailPri">-</dd>
                    <dt class="col-sm-5 d-flex justify-content-between"><span>Jam Sample</span><span>:</span></dt>
                    <dd class="col-sm-7" id="detailJamSample">-</dd>
                </dl>
            </div>
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
                    <h5 class="modal-title">Edit Hasil Uji Troli</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                    <div class="form-group"><label>No. Trolly</label><input type="text" name="no_trolly" id="editNoTrolly" class="form-control" required></div>
                    <div class="form-group"><label>K3</label><input type="number" name="k3" id="editK3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Po</label><input type="number" name="po" id="editPo" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" id="editPa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" id="editPri" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Jam Sample</label><input type="time" name="jam_sample" id="editJamSample" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JS DataTables Bootstrap 4 --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {
    
    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

    var fpMin, fpMax;

    function parseDMY(dateStr){
        var parts = dateStr.split('-'); if(parts.length!==3) return null; return new Date(parts[2], parts[1]-1, parts[0]);
    }

    fpMin = flatpickr("#min-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });
    fpMax = flatpickr("#max-date", { altInput: true, altFormat: "d/m/Y", dateFormat: "Y-m-d", defaultDate: "today" });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        var min = $('#min-date').val(), max = $('#max-date').val(), tableDateStr = data[1] || '';
        if (!tableDateStr || tableDateStr === '-') return true; var tableDate = parseDMY(tableDateStr); if (!tableDate) return true;
        var minDate = min ? new Date(min + 'T00:00:00') : null, maxDate = max ? new Date(max + 'T23:59:59') : null;
        if ((!minDate || tableDate >= minDate) && (!maxDate || tableDate <= maxDate)) return true; return false;
    });
    
    // Inisialisasi DataTable (Bootstrap 4 Style)
    var table = $('#dataTable').DataTable({
        "order": [[1,"desc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        }
    });

    table.draw();

    $('#filter-btn').on('click', function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e){
        e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today");
        setTimeout(function() { table.search('').draw(); }, 100);
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Helper Number Format
    function formatNumber(num) {
        if (num === null || num === undefined || num === '') return '-';
        if (!$.isNumeric(num)) return '-';
        return (num % 1 === 0) ? parseInt(num) : parseFloat(num);
    }

    // DETAIL
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id'); var url = "{{ url('hasil-uji-troli') }}/" + id;
        $.get(url, function (data) {
            var tanggalFormatted = '-';
            if (data.tanggal) {
                var dateObj = new Date(data.tanggal);
                if (!isNaN(dateObj.getTime())) {
                    tanggalFormatted = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' });
                }
            }
            $('#detailTanggal').text(tanggalFormatted);
            $('#detailNoTrolly').text(data.no_trolly || '-'); 
            $('#detailK3').text(data.k3 ? (Math.floor(data.k3) == data.k3 ? parseInt(data.k3) : data.k3) + ' %' : '-');
            $('#detailPo').text(formatNumber(data.po));
            $('#detailPa').text(formatNumber(data.pa));
            $('#detailPri').text(formatNumber(data.pri));
            var jam = data.jam_sample ? data.jam_sample.substring(0, 5) : '-';
            $('#detailJamSample').text(jam); 
            $('#modalDetail').modal('show');
        }).fail(function() { alert('Gagal mengambil data detail. Cek URL atau route.'); });
    });

    // EDIT
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil-uji-troli') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil-uji-troli') }}/" + id;
        $.get(urlGet, function (data) {
            $('#editTanggal').val(data.tanggal); $('#editNoTrolly').val(data.no_trolly); 
            $('#editK3').val(data.k3); $('#editPo').val(data.po); $('#editPa').val(data.pa); 
            $('#editPri').val(data.pri);
            $('#editJamSample').val(data.jam_sample ? data.jam_sample.substring(0, 5) : ''); 
            $('#formEdit').attr('action', urlPost);
            $('#modalEdit').modal('show');
        }).fail(function() { alert('Gagal mengambil data edit. Cek URL atau route.'); });
    });
});
</script>

</body>
</html>