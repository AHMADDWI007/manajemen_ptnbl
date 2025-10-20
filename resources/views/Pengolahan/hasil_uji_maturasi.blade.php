<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
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
                        <h1 class="m-0 text-success fw-bold">Hasil Uji Maturasi</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambah">
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
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Hasil Uji Maturasi</strong>
                    </div>
                    <div class="card-body table-responsive">
                         @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        {{-- BAGIAN FILTER TANGGAL --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="min-date">Dari Tanggal:</label>
                                <input type="date" id="min-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label for="max-date">Sampai Tanggal:</label>
                                <input type="date" id="max-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                 <button id="filter-btn" class="btn btn-primary btn-sm me-2">Filter</button>
                                 <button id="reset-filter" class="btn btn-secondary btn-sm" style="margin-left: 8px;">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No. Kamar</th>
                                    <th>K3</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_maturasi as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->no_kamar }}</td>
                                        <td class="text-center">{{ $item->k3 ?? '-' }}</td>
                                        <td class="text-center">{{ $item->po ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pa ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pri ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('hasil-uji-maturasi.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada data hasil uji maturasi.</td>
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
    <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('hasil-uji-maturasi.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">Tambah Hasil Uji Maturasi</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>No. Kamar</label>
                            <input type="text" name="no_kamar" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>K3</label>
                            <input type="number" name="k3" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Po</label>
                            <input type="number" name="po" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Pa</label>
                            <input type="number" name="pa" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>PRI</label>
                            <input type="number" name="pri" class="form-control" step="0.01">
                        </div>
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
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Detail Hasil Uji</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                        <dt class="col-sm-4">No. Kamar</dt><dd class="col-sm-8" id="detailNoKamar">-</dd>
                        <dt class="col-sm-4">K3</dt><dd class="col-sm-8" id="detailK3">-</dd>
                        <dt class="col-sm-4">Po</dt><dd class="col-sm-8" id="detailPo">-</dd>
                        <dt class="col-sm-4">Pa</dt><dd class="col-sm-8" id="detailPa">-</dd>
                        <dt class="col-sm-4">PRI</dt><dd class="col-sm-8" id="detailPri">-</dd>
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
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Edit Hasil Uji Maturasi</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                         <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" id="editTanggal" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>No. Kamar</label>
                            <input type="text" name="no_kamar" id="editNoKamar" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>K3</label>
                            <input type="number" name="k3" id="editK3" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Po</label>
                            <input type="number" name="po" id="editPo" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Pa</label>
                            <input type="number" name="pa" id="editPa" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>PRI</label>
                            <input type="number" name="pri" id="editPri" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div>

@include('template.script')
{{-- Memuat script penting secara manual untuk memastikan fungsionalitas --}}
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable dan simpan dalam variabel
    var table = $('#dataTable').DataTable({
        "searching": false // Menonaktifkan kotak search bawaan
    });

    // ----- LOGIKA FILTER TANGGAL -----
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var minStr = $('#min-date').val();
            var maxStr = $('#max-date').val();
            var dateStr = data[1] || ''; // Mengambil data dari kolom ke-2 (indeks 1), yaitu 'Tanggal'

            // Jika filter kosong atau data tanggal tidak ada, tampilkan baris
            if ( ( minStr === '' && maxStr === '' ) || dateStr === '-' ) {
                return true;
            }
            
            // Konversi tanggal dari format dd-mm-yyyy (di tabel) ke objek Date
            var parts = dateStr.split('-');
            if (parts.length !== 3) return false;
            var tableDate = new Date(parts[2], parts[1] - 1, parts[0]);

            // Konversi tanggal dari filter (yyyy-mm-dd) ke objek Date
            var min = minStr ? new Date(minStr) : null;
            var max = maxStr ? new Date(maxStr) : null;
            
            // Atur jam pada max date ke akhir hari agar perbandingan inklusif
            if (max) max.setHours(23, 59, 59, 999);

            // Logika untuk membandingkan tanggal
            if (
                ( min === null && max === null ) ||
                ( min === null && tableDate <= max ) ||
                ( min <= tableDate && max === null ) ||
                ( min <= tableDate && tableDate <= max )
            ) {
                return true;
            }
            return false;
        }
    );

    // Event listener untuk TOMBOL FILTER
    $('#filter-btn').on('click', function() {
        table.draw();
    });
    
    // Tombol untuk mereset filter
    $('#reset-filter').on('click', function() {
        $('#min-date').val('');
        $('#max-date').val('');
        table.draw();
    });

    // ----- LOGIKA NOTIFIKASI & MODAL -----
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    
    @if (session('success'))
        alert("{{ session('success') }}");
    @endif

    // === DETAIL ===
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        $.get('/hasil-uji-maturasi/' + id, function (data) {
            $('#detailTanggal').text(new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }));
            $('#detailNoKamar').text(data.no_kamar || '-');
            $('#detailK3').text(data.k3 || '-');
            $('#detailPo').text(data.po || '-');
            $('#detailPa').text(data.pa || '-');
            $('#detailPri').text(data.pri || '-');
            $('#modalDetail').modal('show');
        });
    });

    // === EDIT ===
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/hasil-uji-maturasi/' + id + '/edit', function (data) {
            $('#editTanggal').val(data.tanggal);
            $('#editNoKamar').val(data.no_kamar);
            $('#editK3').val(data.k3);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            $('#formEdit').attr('action', '/hasil-uji-maturasi/' + id);
            $('#modalEdit').modal('show');
        });
    });
});
</script>

</body>
</html>

