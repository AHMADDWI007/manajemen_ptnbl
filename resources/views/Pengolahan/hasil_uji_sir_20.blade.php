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
                        <h1 class="m-0 text-success fw-bold">Hasil Uji SIR 20</h1>
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
                        <strong>Daftar Hasil Uji SIR 20</strong>
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

                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>No. Palet</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Dirt (%)</th>
                                    <th>Ash (%)</th>
                                    <th>VM (%)</th>
                                    <th>Money</th>
                                    <th>Nitrogen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_sir_20 as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $item->no_palet }}</td>
                                        <td class="text-center">{{ $item->po ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pa ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pri ?? '-' }}</td>
                                        <td class="text-center">{{ $item->dirt ?? '-' }}</td>
                                        <td class="text-center">{{ $item->ash ?? '-' }}</td>
                                        <td class="text-center">{{ $item->vm ?? '-' }}</td>
                                        <td class="text-center">{{ isset($item->money) ? number_format($item->money, 2, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item->nitrogen ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('hasil-uji-sir20.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="display:inline;">
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
                                        <td colspan="11" class="text-center text-muted">Belum ada data hasil uji SIR 20.</td>
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
                <form action="{{ route('hasil-uji-sir20.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">Tambah Hasil Uji SIR 20</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>No. Palet</label>
                            <input type="text" name="no_palet" class="form-control" required>
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
                         <div class="form-group">
                            <label>Dirt (%)</label>
                            <input type="number" name="dirt" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Ash (%)</label>
                            <input type="number" name="ash" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>VM (%)</label>
                            <input type="number" name="vm" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Money</label>
                            <input type="number" name="money" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Nitrogen</label>
                            <input type="number" name="nitrogen" class="form-control" step="0.01">
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
                        <dt class="col-sm-4">No. Palet</dt><dd class="col-sm-8" id="detailNoPalet">-</dd>
                        <dt class="col-sm-4">Po</dt><dd class="col-sm-8" id="detailPo">-</dd>
                        <dt class="col-sm-4">Pa</dt><dd class="col-sm-8" id="detailPa">-</dd>
                        <dt class="col-sm-4">PRI</dt><dd class="col-sm-8" id="detailPri">-</dd>
                        <dt class="col-sm-4">Dirt</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                        <dt class="col-sm-4">Ash</dt><dd class="col-sm-8" id="detailAsh">-</dd>
                        <dt class="col-sm-4">VM</dt><dd class="col-sm-8" id="detailVm">-</dd>
                        <dt class="col-sm-4">Money</dt><dd class="col-sm-8" id="detailMoney">-</dd>
                        <dt class="col-sm-4">Nitrogen</dt><dd class="col-sm-8" id="detailNitrogen">-</dd>
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
                        <h5 class="modal-title">Edit Hasil Uji SIR 20</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>No. Palet</label>
                            <input type="text" name="no_palet" id="editNoPalet" class="form-control" required>
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
                        <div class="form-group">
                            <label>Dirt (%)</label>
                            <input type="number" name="dirt" id="editDirt" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Ash (%)</label>
                            <input type="number" name="ash" id="editAsh" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>VM (%)</label>
                            <input type="number" name="vm" id="editVm" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Money</label>
                            <input type="number" name="money" id="editMoney" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Nitrogen</label>
                            <input type="number" name="nitrogen" id="editNitrogen" class="form-control" step="0.01">
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
{{-- PERBAIKAN: Menghapus script manual yang duplikat dan menyebabkan konflik --}}
{{-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
{{-- <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> --}}


<script>
$(document).ready(function() {
    // Kode ini akan berjalan setelah semua script dari @include('template.script') dimuat
    $('#dataTable').DataTable();

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    
    @if (session('success'))
        alert("{{ session('success') }}");
    @endif

    // === DETAIL ===
    $(document).on('click', '.btn-detail', function () {
        var id = $(this).data('id');
        $.get('/hasil-uji-sir20/' + id, function (data) {
            $('#detailNoPalet').text(data.no_palet || '-');
            $('#detailPo').text(data.po || '-');
            $('#detailPa').text(data.pa || '-');
            $('#detailPri').text(data.pri || '-');
            $('#detailDirt').text(data.dirt || '-');
            $('#detailAsh').text(data.ash || '-');
            $('#detailVm').text(data.vm || '-');
            $('#detailMoney').text(data.money ? Number(data.money).toLocaleString('id-ID', { minimumFractionDigits: 2 }) : '-');
            $('#detailNitrogen').text(data.nitrogen || '-');
            $('#modalDetail').modal('show');
        });
    });

    // === EDIT ===
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/hasil-uji-sir20/' + id + '/edit', function (data) {
            $('#editNoPalet').val(data.no_palet);
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            $('#editDirt').val(data.dirt);
            $('#editAsh').val(data.ash);
            $('#editVm').val(data.vm);
            $('#editMoney').val(data.money);
            $('#editNitrogen').val(data.nitrogen);
            $('#formEdit').attr('action', '/hasil-uji-sir20/' + id);
            $('#modalEdit').modal('show');
        });
    });
});
</script>

</body>
</html>

