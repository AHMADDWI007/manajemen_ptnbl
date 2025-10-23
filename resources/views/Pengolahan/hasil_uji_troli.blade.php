<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Aturan Global */
        .table-bordered th, 
        .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
            text-align: center; /* Aturan dasar rata tengah */
        }

        /* Aturan Spesifik Rata Tengah */
        #dataTable th, 
        #dataTable td {
             text-align: center !important; 
             vertical-align: middle !important; 
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px; 
        }
    </style>

</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h3 class="m-0 text-success fw-bold">Hasil Uji Troli</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambahTroli">
                                    <i class="fas fa-plus-circle"></i> Tambah Data
                                </button>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Hasil Uji Troli</strong>
                    </div>
                    <div class="card-body table-responsive">

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="min-date">Dari Tanggal:</label>
                                <input type="text" id="min-date" class="form-control form-control-sm" placeholder="Pilih tanggal...">
                            </div>
                            <div class="col-md-3">
                                <label for="max-date">Sampai Tanggal:</label>
                                <input type="text" id="max-date" class="form-control form-control-sm" placeholder="Pilih tanggal...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2"> 
                                <button id="filter-btn" class="btn btn-primary btn-sm">Filter</button>&nbsp;
                                <button id="reset-filter" class="btn btn-secondary btn-sm">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light"> 
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No. Trolly</th>
                                    <th>K3(%)</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Jam Sample</th>
                                    {{-- <th>Lama Pengeringan</th> --}} {{-- KOLOM DIHAPUS --}}
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_troli as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->no_trolly }}</td>
                                        <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
                                        <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                        <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                        <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                        <td>{{ $item->jam_sample ? \Carbon\Carbon::parse($item->jam_sample)->format('H:i') : '-' }}</td>
                                        {{-- <td>{{ is_numeric($item->lama_pengeringan) ? (fmod($item->lama_pengeringan, 1) == 0 ? (int)$item->lama_pengeringan : $item->lama_pengeringan) : '-' }}</td> --}} {{-- DATA DIHAPUS --}}
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                <form action="{{ route('hasil_uji_troli.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline-block; margin:0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Belum ada data hasil uji troli.</td> {{-- COLSPAN DIUBAH MENJADI 9 --}}
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH --}}
    <div class="modal fade" id="modalTambahTroli" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('hasil_uji_troli.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white"> 
                        <h5 class="modal-title fw-bold">Tambah Hasil Uji Troli</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                        <div class="form-group"><label>No. Trolly</label><input type="text" name="no_trolly" class="form-control" required></div>
                        <div class="form-group"><label>K3</label><input type="number" name="k3" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Po</label><input type="number" name="po" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Pa</label><input type="number" name="pa" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>PRI</label><input type="number" name="pri" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Jam Sample</label><input type="time" name="jam_sample" class="form-control"></div>
                        {{-- <div class="form-group"><label>Lama Pengeringan (Jam)</label><input type="number" name="lama_pengeringan" class="form-control" step="0.1"></div> --}} {{-- INPUT DIHAPUS --}}
                    </div>
                    <div class="modal-footer"> {{-- Pastikan div ini ada --}}
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
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Tanggal</dt><dd class="col-sm-7" id="detailTanggal">-</dd>
                        <dt class="col-sm-5">No. Trolly</dt><dd class="col-sm-7" id="detailNoTrolly">-</dd>
                        <dt class="col-sm-5">K3</dt><dd class="col-sm-7" id="detailK3">-</dd>
                        <dt class="col-sm-5">Po</dt><dd class="col-sm-7" id="detailPo">-</dd>
                        <dt class="col-sm-5">Pa</dt><dd class="col-sm-7" id="detailPa">-</dd>
                        <dt class="col-sm-5">PRI</dt><dd class="col-sm-7" id="detailPri">-</dd>
                        <dt class="col-sm-5">Jam Sample</dt><dd class="col-sm-7" id="detailJamSample">-</dd>
                        {{-- <dt class="col-sm-5">Lama Pengeringan</dt><dd class="col-sm-7" id="detailLamaPengeringan">-</dd> --}} {{-- DETAIL DIHAPUS --}}
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
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-success text-white"> 
                        <h5 class="modal-title">Edit Hasil Uji Troli</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                        <div class="form-group"><label>No. Trolly</label><input type="text" name="no_trolly" id="editNoTrolly" class="form-control" required></div>
                        <div class="form-group"><label>K3</label><input type="number" name="k3" id="editK3" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Po</label><input type="number" name="po" id="editPo" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Pa</label><input type="number" name="pa" id="editPa" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>PRI</label><input type="number" name="pri" id="editPri" class="form-control" step="0.01"></div>
                        <div class="form-group"><label>Jam Sample</label><input type="time" name="jam_sample" id="editJamSample" class="form-control"></div>
                        {{-- <div class="form-group"><label>Lama Pengeringan (Jam)</label><input type="number" name="lama_pengeringan" id="editLamaPengeringan" class="form-control" step="0.1"></div> --}} {{-- INPUT DIHAPUS --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <footer class="main-footer">@include('template.footer')</footer>
</div>

{{-- @include('template.script') --}}

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
    
    var table = $('#dataTable').DataTable({"order": [[1,"desc"]]});
    table.draw();

    $('#filter-btn').on('click', function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').on('click', function(e){
        e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today");
        setTimeout(function() { table.search('').draw(); }, 100);
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // DETAIL
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id'); var url = "{{ url('hasil_uji_troli') }}/" + id;
        $.get(url, function (data) {
            $('#detailTanggal').text(new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }));
            $('#detailNoTrolly').text(data.no_trolly || '-'); 
            $('#detailK3').text(data.k3 ? (Math.floor(data.k3) == data.k3 ? parseInt(data.k3) : data.k3) : '-');
            $('#detailPo').text(data.po ? (Math.floor(data.po) == data.po ? parseInt(data.po) : data.po) : '-');
            $('#detailPa').text(data.pa ? (Math.floor(data.pa) == data.pa ? parseInt(data.pa) : data.pa) : '-');
            $('#detailPri').text(data.pri ? (Math.floor(data.pri) == data.pri ? parseInt(data.pri) : data.pri) : '-');
            var jam = data.jam_sample ? data.jam_sample.substring(0, 5) : '-';
            $('#detailJamSample').text(jam); 
            // $('#detailLamaPengeringan').text(data.lama_pengeringan || '-'); // BARIS DIHAPUS
            $('#modalDetail').modal('show');
        }).fail(function() { alert('Gagal mengambil data detail. Cek URL atau route.'); });
    });

    // EDIT
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil_uji_troli') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_troli') }}/" + id;
        $.get(urlGet, function (data) {
            $('#editTanggal').val(data.tanggal); $('#editNoTrolly').val(data.no_trolly); 
            $('#editK3').val(data.k3); $('#editPo').val(data.po); $('#editPa').val(data.pa); 
            $('#editPri').val(data.pri);
            $('#editJamSample').val(data.jam_sample ? data.jam_sample.substring(0, 5) : ''); 
            // $('#editLamaPengeringan').val(data.lama_pengeringan); // BARIS DIHAPUS
            $('#formEdit').attr('action', urlPost);
            $('#modalEdit').modal('show');
        }).fail(function() { alert('Gagal mengambil data edit. Cek URL atau route.'); });
    });
});
</script>

</body>
</html>