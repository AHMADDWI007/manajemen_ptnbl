<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Lab Bokar</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Rata tengah dan style dasar */
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
            text-align: center;
        }

        /* Penegasan rata tengah untuk ID tabel spesifik */
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
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="m-0 text-success fw-bold">Hasil Uji Lab Bokar</h1>
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white fw-bold">
                        Daftar Hasil Uji Lab Bokar
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
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>No Sampel</th>
                                    <th>K3 (%)</th>
                                    <th>Dirt (%)</th>
                                    <th>Ash (%)</th>
                                    {{-- ====================== --}}
                                    {{-- TAMBAHAN KOLOM HEADER --}}
                                    {{-- ====================== --}}
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    {{-- ====================== --}}
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_lab as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->suplier }}</td>
                                        <td>{{ $item->no_sampel }}</td>
                                        <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
                                        <td>{{ is_numeric($item->dirt) ? (fmod($item->dirt, 1) == 0 ? (int)$item->dirt : $item->dirt) : '-' }}</td>
                                        <td>{{ is_numeric($item->ask) ? (fmod($item->ask, 1) == 0 ? (int)$item->ask : $item->ask) : '-' }}</td>
                                        {{-- ==================== --}}
                                        {{-- TAMBAHAN KOLOM DATA --}}
                                        {{-- ==================== --}}
                                        <td>{{ is_numeric($item->po) ? (fmod($item->po, 1) == 0 ? (int)$item->po : $item->po) : '-' }}</td>
                                        <td>{{ is_numeric($item->pa) ? (fmod($item->pa, 1) == 0 ? (int)$item->pa : $item->pa) : '-' }}</td>
                                        <td>{{ is_numeric($item->pri) ? (fmod($item->pri, 1) == 0 ? (int)$item->pri : $item->pri) : '-' }}</td>
                                        {{-- ==================== --}}
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"> <i class="fas fa-eye"></i> </button>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"> <i class="fas fa-edit"></i> </button>
                                                <form action="{{ route('hasil_uji_lab_bokar.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block; margin:0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus"> <i class="fas fa-trash"></i> </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- Update colspan menjadi 11 --}}
                                        <td colspan="11" class="text-center text-muted">Belum ada data hasil uji lab.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('hasil_uji_lab_bokar.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Tambah Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                    <div class="form-group"><label>Supplier</label><input type="text" name="suplier" class="form-control" required></div>
                    <div class="form-group"><label>No Sampel</label><input type="text" name="no_sampel" class="form-control" required></div>
                    <div class="form-group"><label>K3 (%)</label><input type="number" name="k3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Ash (%)</label><input type="number" name="ask" class="form-control" step="0.01"></div>
                    {{-- ============================ --}}
                    {{-- TAMBAHAN INPUT MODAL TAMBAH --}}
                    {{-- ============================ --}}
                    <div class="form-group"><label>Po</label><input type="number" name="po" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" class="form-control" step="0.01"></div>
                    {{-- ============================ --}}
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
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Detail Hasil Uji</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Tanggal</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailTanggal">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Supplier</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailSupplier">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>No Sampel</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailNoSampel">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>K3 (%)</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailK3">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Dirt (%)</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailDirt">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Ash (%)</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailAsk">-</dd>
                    
                    {{-- ================== --}}
                    {{-- TAMBAHAN DETAIL --}}
                    {{-- ================== --}}
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Po</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailPo">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>Pa</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailPa">-</dd>
                    
                    <dt class="col-sm-4 d-flex justify-content-between">
                        <span>PRI</span><span>:</span>
                    </dt>
                    <dd class="col-sm-8" id="detailPri">-</dd>
                    {{-- ================== --}}
                </dl>
            </div>
        </div>
    </div>
</div>
{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Edit Hasil Uji Lab</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" id="editTanggal" class="form-control" required></div>
                    <div class="form-group"><label>Supplier</label><input type="text" name="suplier" id="editSupplier" class="form-control" required></div>
                    <div class="form-group"><label>No Sampel</label><input type="text" name="no_sampel" id="editNoSampel" class="form-control" required></div>
                    <div class="form-group"><label>K3 (%)</label><input type="number" name="k3" id="editK3" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Dirt (%)</label><input type="number" name="dirt" id="editDirt" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Ash (%)</label><input type="number" name="ask" id="editAsk" class="form-control" step="0.01"></div>
                     {{-- ========================== --}}
                    {{-- TAMBAHAN INPUT MODAL EDIT --}}
                    {{-- ========================== --}}
                    <div class="form-group"><label>Po</label><input type="number" name="po" id="editPo" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>Pa</label><input type="number" name="pa" id="editPa" class="form-control" step="0.01"></div>
                    <div class="form-group"><label>PRI</label><input type="number" name="pri" id="editPri" class="form-control" step="0.01"></div>
                    {{-- ========================== --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="saveEditBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SCRIPTS --}}

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
        e.preventDefault();
        fpMin.setDate("today");
        fpMax.setDate("today");
        setTimeout(function() { table.search('').draw(); }, 100);
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // DETAIL
   // DETAIL
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        var url = "{{ url('hasil_uji_lab_bokar') }}/" + id;
        $.get(url, function(data){
            
            // --- PERBAIKAN BARU DI SINI ---
            var tanggalFormatted = '-'; // Default
            if (data.tanggal) {
                // 1. Coba parse tanggal apa adanya
                var dateObj = new Date(data.tanggal);

                // 2. Cek apakah hasil parse-nya valid
                if (isNaN(dateObj.getTime())) {
                    // Jika tidak valid (hasilnya NaN), biarkan '-'
                    tanggalFormatted = '-'; 
                } else {
                    // Jika valid, format ke bahasa Indonesia
                    tanggalFormatted = dateObj.toLocaleDateString('id-ID', { 
                        day: '2-digit', 
                        month: 'long', 
                        year: 'numeric',
                        // Gunakan UTC agar konsisten jika inputnya YYYY-MM-DD
                        timeZone: 'UTC' 
                    });
                }
            }
            $('#detailTanggal').text(tanggalFormatted);
            // --- AKHIR PERBAIKAN ---

            $('#detailSupplier').text(data.suplier ?? '-');
            $('#detailNoSampel').text(data.no_sampel ?? '-');
           $('#detailK3').text(data.k3 ? ((Math.floor(data.k3) == data.k3 ? parseInt(data.k3) : data.k3) + ' %') : '-');
            $('#detailDirt').text(data.dirt ? ((Math.floor(data.dirt) == data.dirt ? parseInt(data.dirt) : data.dirt) + ' %') : '-');
            $('#detailAsk').text(data.ask ? ((Math.floor(data.ask) == data.ask ? parseInt(data.ask) : data.ask) + ' %') : '-');
             // ===========================================
             // TAMBAHAN JS DETAIL (Po, Pa, PRI)
             // ===========================================
            $('#detailPo').text(data.po ? (Math.floor(data.po) == data.po ? parseInt(data.po) : data.po) : '-');
            $('#detailPa').text(data.pa ? (Math.floor(data.pa) == data.pa ? parseInt(data.pa) : data.pa) : '-');
            $('#detailPri').text(data.pri ? (Math.floor(data.pri) == data.pri ? parseInt(data.pri) : data.pri) : '-');
            // ===========================================
            $('#modalDetail').modal('show');
        }).fail(function(){ alert('Gagal memuat detail.'); });
    });

    // EDIT
    $(document).on('click','.btn-edit',function(){
        var id = $(this).data('id');
        var urlGet = "{{ url('hasil_uji_lab_bokar') }}/" + id + "/edit";
        var urlPost = "{{ url('hasil_uji_lab_bokar') }}/" + id;
        $.get(urlGet, function(data){
            $('#editTanggal').val(data.tanggal);
            $('#editSupplier').val(data.suplier);
            $('#editNoSampel').val(data.no_sampel);
            $('#editK3').val(data.k3);
            $('#editDirt').val(data.dirt);
            $('#editAsk').val(data.ask);
             // ===========================================
             // TAMBAHAN JS EDIT (Po, Pa, PRI)
             // ===========================================
            $('#editPo').val(data.po);
            $('#editPa').val(data.pa);
            $('#editPri').val(data.pri);
            // ===========================================
            $('#formEdit').attr('action', urlPost);
            $('#modalEdit').modal('show');
        }).fail(function(){ alert('Gagal memuat data edit.'); });
    });
});
</script>
</body>
</html>