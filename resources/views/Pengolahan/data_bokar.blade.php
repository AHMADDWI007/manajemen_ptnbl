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
        /* Style untuk btn-group tidak lagi diperlukan */
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Data Pengolahan Bokar</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>

            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered text-center align-middle" id="dataTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Bak Maturasi</th>
                                    <th>Jenis</th>
                                    <th>Berat Truck (Kg)</th>
                                    <th>Berat Timbang (Kg)</th>
                                    <th>Netto Basah (Kg)</th>
                                    <th>K3 (%)</th>
                                    <th>Netto Kering (Kg)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data_basah as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->bak_maturasi }}</td>
                                        <td>{{ $item->jenis }}</td>
                                        <td>{{ number_format($item->berat_truck, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->berat_timbang, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->netto_basah, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->k3, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->netto_kering, 2, ',', '.') }}</td>
                                        <td>
                                            {{-- PERBAIKAN 1: Memberi jarak pada tombol aksi --}}
                                            <div class="d-flex justify-content-center" style="gap: 5px;">
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('bokar.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')" style="margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            {{-- PERBAIKAN 2: Menyesuaikan tata letak tfoot --}}
                            <tfoot class="fw-bold bg-light">
                                <tr>
                                    <td colspan="8"></td>
                                    <td class="text-end">Total DS :</td>
                                    <td class="text-center">{{ number_format($total_ds, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="8"></td>
                                    <td class="text-end">Total PT :</td>
                                    <td class="text-center">{{ number_format($total_pt, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="8"></td>
                                    <td class="text-end">Jumlah :</td>
                                    <td class="text-center">{{ number_format($jumlah_total, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">@include('template.footer')</footer>
</div>

@include('template.script')

@if(session('success'))
<script>alert("{{ session('success') }}");</script>
@endif

{{-- 🔹 Modal Tambah --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('bokar.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Tambah Data Pengolahan Bokar</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">@include('pengolahan.form')</div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- 🔹 Modal Edit --}}
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEdit" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Data Pengolahan Bokar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">@include('pengolahan.form')</div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- 🔹 Modal Detail --}}
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Pengolahan Bokar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
        </div>
    </div>
</div>

<script>
    $(function() {
        // tombol edit
        $('.btn-edit').click(function() {
            const id = $(this).data('id');
            $.get(`/bokar/${id}/edit`, function(data) {
                $('#formEdit').attr('action', `/bokar/${id}`);
                $('#modalEdit input[name="tanggal"]').val(data.tanggal);
                $('#modalEdit input[name="bak_maturasi"]').val(data.bak_maturasi);
                $('#modalEdit input[name="jenis"]').val(data.jenis);
                $('#modalEdit input[name="berat_truck"]').val(data.berat_truck);
                $('#modalEdit input[name="berat_timbang"]').val(data.berat_timbang);
                $('#modalEdit input[name="netto_basah"]').val(data.netto_basah);
                $('#modalEdit input[name="k3"]').val(data.k3);
                $('#modalEdit input[name="netto_kering"]').val(data.netto_kering);
                $('#modalEdit').modal('show');
            });
        });

        // tombol detail
        $('.btn-detail').click(function() {
            const id = $(this).data('id');
            $.get(`/bokar/${id}`, function(data) {
                let html = `
                    <p><strong>Tanggal:</strong> ${data.tanggal}</p>
                    <p><strong>Bak Maturasi:</strong> ${data.bak_maturasi}</p>
                    <p><strong>Jenis:</strong> ${data.jenis}</p>
                    <p><strong>Berat Truck:</strong> ${data.berat_truck} Kg</p>
                    <p><strong>Berat Timbang:</strong> ${data.berat_timbang} Kg</p>
                    <p><strong>Netto Basah:</strong> ${data.netto_basah} Kg</p>
                    <p><strong>K3:</strong> ${data.k3}%</p>
                    <p><strong>Netto Kering:</strong> ${data.netto_kering} Kg</p>`;
                $('#detailContent').html(html);
                $('#modalDetail').modal('show');
            });
        });
    });
</script>

</body>
</html>

