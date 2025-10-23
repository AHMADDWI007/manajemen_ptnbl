<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <style>
        .table-bordered th, 
        .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle; 
            white-space: nowrap;
            text-align: center;    
        }

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
                        <h1 class="m-0 text-success fw-bold">Hasil Uji Sir 20</h1>
                    </div>
                    <div class="col-sm-6"> 
                        <ol class="breadcrumb float-sm-right"> 
                            <li class="breadcrumb-item">
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalTambahSir"> 
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
                        <strong>Daftar Hasil Uji Sir 20</strong>
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
                                    <th>Jenis Kemasan</th> {{-- TAMBAHAN --}}
                                    <th>No. Palet</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Dirt(%)</th>
                                    <th>Ash(%)</th>
                                    <th>VM(%)</th>
                                    <th>Money</th>
                                    <th>Nitrogen(%)</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_sir_20 as $item) 
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->jenis_kemasan ?? '-' }}</td> {{-- TAMBAHAN --}}
                                        <td>{{ $item->no_palet }}</td>
                                        {{-- Format Angka Diperbaiki --}}
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
                                                <button class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                <button class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                {{-- Route Diperbaiki --}}
                                                <form action="{{ route('hasil-uji-sir20.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus?')" style="display:inline-block;"> 
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted">Belum ada data.</td> {{-- Colspan jadi 13 --}}
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahSir" tabindex="-1" role="dialog" aria-hidden="true">
         <div class="modal-dialog" role="document">
            <div class="modal-content">
                {{-- Route Diperbaiki --}}
                <form action="{{ route('hasil_uji_sir_20.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold">Tambah Hasil Uji SIR 20</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                        <div class="form-group"><label>Jenis Kemasan</label><input type="text" name="jenis_kemasan" class="form-control" placeholder="Contoh: Plastik, Kayu..."></div> {{-- TAMBAHAN --}}
                        <div class="form-group"><label>No. Palet</label><input type="text" name="no_palet" class="form-control" required></div>
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

    <div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
         <div class="modal-dialog" role="document">
            <div class="modal-content">
                 <div class="modal-header bg-success text-white"> 
                    <h5 class="modal-title">Detail Hasil Uji SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                        <dt class="col-sm-4">Jenis Kemasan</dt><dd class="col-sm-8" id="detailJenisKemasan">-</dd> {{-- TAMBAHAN --}}
                        <dt class="col-sm-4">No. Palet</dt><dd class="col-sm-8" id="detailNoPalet">-</dd>
                        <dt class="col-sm-4">Po</dt><dd class="col-sm-8" id="detailPo">-</dd>
                        <dt class="col-sm-4">Pa</dt><dd class="col-sm-8" id="detailPa">-</dd>
                        <dt class="col-sm-4">PRI</dt><dd class="col-sm-8" id="detailPri">-</dd>
                        <dt class="col-sm-4">Dirt</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                        <dt class="col-sm-4">Ash</dt><dd class="col-sm-8" id="detailAsh">-</dd>
                        <dt class="col-sm-4">VM</dt><dd class="col-sm-8" id="detailVm">-</dd>
                        <dt class="col-sm-4">Mooney</dt><dd class="col-sm-8" id="detailMoney">-</dd>
                        <dt class="col-sm-4">Nitrogen</dt><dd class="col-sm-8" id="detailNitrogen">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="formEdit" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-success text-white"> 
                        <h5 class="modal-title">Edit Hasil Uji SIR 20</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                        <div class="form-group"><label>Jenis Kemasan</label><input type="text" name="jenis_kemasan" id="editJenisKemasan" class="form-control" placeholder="Contoh: Plastik, Kayu..."></div> {{-- TAMBAHAN --}}
                        <div class="form-group"><label>No. Palet</label><input type="text" name="no_palet" id="editNoPalet" class="form-control" required></div>
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

    @include('template.footer')
</div>

{{-- @include('template.script') --}} 

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function(){

    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

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

    var table = $('#dataTable').DataTable({"order":[[1,"desc"]]});
    table.draw();

    $('#filter-btn').click(function(e){ e.preventDefault(); table.draw(); });
    $('#reset-filter').click(function(e){ e.preventDefault(); fpMin.setDate("today"); fpMax.setDate("today"); setTimeout(function(){ table.search('').draw(); },100); });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // DETAIL
    $(document).on('click', '.btn-detail', function(){
        var id = $(this).data('id');
        var url = "{{ url('hasil_uji_sir_20') }}/" + id; // URL Diperbaiki
        $.get(url, function(data){
            $('#detailTanggal').text(new Date(data.tanggal + 'T00:00:00Z').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'UTC' }));
            $('#detailJenisKemasan').text(data.jenis_kemasan || '-'); // TAMBAHAN
            $('#detailNoPalet').text(data.no_palet || '-');
            
            function formatNumber(num) {
                if (!$.isNumeric(num)) return '-';
                // Gunakan fmod versi JS (operato %)
                return (num % 1 === 0) ? parseInt(num) : parseFloat(num);
            }

            $('#detailPo').text(formatNumber(data.po));
            $('#detailPa').text(formatNumber(data.pa));
            $('#detailPri').text(formatNumber(data.pri));
            $('#detailDirt').text(formatNumber(data.dirt));
            $('#detailAsh').text(formatNumber(data.ash));
            $('#detailVm').text(formatNumber(data.vm));
            $('#detailMoney').text(formatNumber(data.money));
            $('#detailNitrogen').text(formatNumber(data.nitrogen));

            $('#modalDetail').modal('show'); 
        }).fail(function(){ alert('Gagal memuat detail.'); });
    });

    // EDIT
    $(document).on('click', '.btn-edit', function(){
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil_uji_sir_20') }}/" + id + "/edit"; // URL Diperbaiki
        var urlPost = "{{ url('hasil_uji_sir_20') }}/" + id; // URL Diperbaiki
        $.get(urlGet, function(data){
            $('#editTanggal').val(data.tanggal); 
            $('#editJenisKemasan').val(data.jenis_kemasan); // TAMBAHAN
            $('#editNoPalet').val(data.no_palet); 
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa); 
            $('#editPri').val(data.pri); 
            $('#editDirt').val(data.dirt);
            $('#editAsh').val(data.ash); 
            $('#editVm').val(data.vm); 
            $('#editMoney').val(data.money);
            $('#editNitrogen').val(data.nitrogen); 
            $('#formEdit').attr('action', urlPost); 
            $('#modalEdit').modal('show'); 
        }).fail(function(){ alert('Gagal memuat data edit.'); });
    });

});
</script>
</body>
</html>