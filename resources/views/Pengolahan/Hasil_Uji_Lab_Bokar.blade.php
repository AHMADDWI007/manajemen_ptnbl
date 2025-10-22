<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil Uji Lab Bokar</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
        }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
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
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="filter-btn" class="btn btn-primary btn-sm">Filter</button>&nbsp;
                                <button id="reset-filter" class="btn btn-secondary btn-sm ms-3">Reset</button>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-bordered table-striped text-center align-middle" id="dataTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>No Sampel</th>
                                    <th>K3 (%)</th>
                                    <th>Dirt (%)</th>
                                    <th>Ash (%)</th>
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
            {{-- PERBAIKAN FORMAT ANGKA DIMULAI DI SINI --}}
            <td>{{ is_numeric($item->k3) ? (fmod($item->k3, 1) == 0 ? (int)$item->k3 : $item->k3) : '-' }}</td>
            <td>{{ is_numeric($item->dirt) ? (fmod($item->dirt, 1) == 0 ? (int)$item->dirt : $item->dirt) : '-' }}</td>
            <td>{{ is_numeric($item->ask) ? (fmod($item->ask, 1) == 0 ? (int)$item->ask : $item->ask) : '-' }}</td>
            {{-- PERBAIKAN FORMAT ANGKA BERAKHIR DI SINI --}}
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
            <td colspan="8" class="text-center text-muted">Belum ada data hasil uji lab.</td>
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

{{-- ... (Semua Modal Anda tetap sama) ... --}}
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
            {{-- ======================================= --}}
            {{-- PERUBAHAN WARNA HEADER DARI bg-info KE bg-success --}}
            {{-- ======================================= --}}
            <div class="modal-header bg-success text-white"> 
                <h5 class="modal-title">Detail Hasil Uji</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8" id="detailTanggal">-</dd>
                    <dt class="col-sm-4">Supplier</dt><dd class="col-sm-8" id="detailSupplier">-</dd>
                    <dt class="col-sm-4">No Sampel</dt><dd class="col-sm-8" id="detailNoSampel">-</dd>
                    <dt class="col-sm-4">K3 (%)</dt><dd class="col-sm-8" id="detailK3">-</dd>
                    <dt class="col-sm-4">Dirt (%)</dt><dd class="col-sm-8" id="detailDirt">-</dd>
                    <dt class="col-sm-4">Ash (%)</dt><dd class="col-sm-8" id="detailAsk">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT (Header sudah bg-success) --}}
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
                    <div class="form-group"><label>Ask (%)</label><input type="number" name="ask" id="editAsk" class="form-control" step="0.01"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" id="saveEditBtn">Simpan Perubahan</button>
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
{{-- ============================================= --}}
{{--        KODE JAVASCRIPT YANG DIPERBARUI         --}}
{{-- ============================================= --}}
<script>
$(document).ready(function() {

    // Simpan instance flatpickr untuk di-reset nanti
    var fpMin;
    var fpMax;

    // Fungsi parsing tanggal d-m-Y (Ini tetap sama)
    function parseDMY(dateStr){
        var parts = dateStr.split('-');
        if(parts.length!==3) return null;
        return new Date(parts[2], parts[1]-1, parts[0]);
    }

    // ==================================================
    // BARU: Inisialisasi Flatpickr
    // ==================================================
    
    // Inisialisasi Flatpickr untuk "Dari Tanggal"
    fpMin = flatpickr("#min-date", {
        altInput: true,       // Buat input visual yang berbeda
        altFormat: "d/m/Y",   // Format tampilan DD/MM/YYYY
        dateFormat: "Y-m-d",  // Format nilai asli YYYY-MM-DD (untuk filter)
        defaultDate: "today", // Set tanggal default hari ini
        // locale: "id"       // (Opsional) aktifkan jika Anda menyertakan file l10n/id.js
    });

    // Inisialisasi Flatpickr untuk "Sampai Tanggal"
    fpMax = flatpickr("#max-date", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        defaultDate: "today",
        // locale: "id"
    });
    // ==================================================


    // Custom filter tanggal (Ini tetap sama, sudah benar)
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
        
        // Mengambil nilai YYYY-MM-DD dari input asli (bukan yg terlihat)
        var min = $('#min-date').val(); 
        var max = $('#max-date').val(); 

        var tableDateStr = data[1]; 
        
        if (!tableDateStr || tableDateStr === '-') {
            return true;
        }

        var tableDate = parseDMY(tableDateStr); 
        
        if (!tableDate) {
            return true; 
        }

        var minDate = min ? new Date(min + 'T00:00:00') : null;
        var maxDate = max ? new Date(max + 'T23:59:59') : null;

        if (
            (!minDate || tableDate >= minDate) &&
            (!maxDate || tableDate <= maxDate)
        ) {
            return true; 
        }
        
        return false; 
    });
    

    // Inisialisasi DataTable
    var table = $('#dataTable').DataTable({
        "order": [[1,"desc"]]
    });
    
    // Terapkan filter "Hari Ini" saat halaman dimuat
    // Kita panggil table.draw() setelah flatpickr selesai di-set
    table.draw();


    // Search custom (Ini tetap sama)
    $('#search-box').on('keyup', function(){
        table.search(this.value).draw();
    });
    

    // Tombol Filter (Ini tetap sama)
    $('#filter-btn').on('click', function(e){
        e.preventDefault(); 
        table.draw(); 
    });

    // ==================================================
    // DIPERBARUI: Tombol Reset
    // ==================================================
    $('#reset-filter').on('click', function(e){
        e.preventDefault();
        
        // 1. Set tanggal flatpickr kembali ke "hari ini"
        fpMin.setDate("today");
        fpMax.setDate("today");
        
        // 2. Kosongkan search box
        $('#search-box').val('');
        
        // 3. Terapkan filter (search dikosongkan, filter tanggal kembali ke 'hari ini')
        // Diberi sedikit delay agar flatpickr selesai set tanggal sebelum draw
        setTimeout(function() {
            table.search('').draw();
        }, 100); 
    });
    // ==================================================


    // DETAIL (Tidak ada perubahan)
    $(document).on('click','.btn-detail',function(){
        var id = $(this).data('id');
        $.get('/hasil-uji-lab/'+id,function(data){
            var tgl = new Date(data.tanggal + 'T00:00:00').toLocaleDateString('id-ID', { timeZone: 'UTC' });
            $('#detailTanggal').text(tgl);
            $('#detailSupplier').text(data.suplier ?? '-');
            $('#detailNoSampel').text(data.no_sampel ?? '-');
            $('#detailK3').text(data.k3 ?? '-');
            $('#detailDirt').text(data.dirt ?? '-');
            $('#detailAsk').text(data.ask ?? '-');
            $('#modalDetail').modal('show');
        });
    });

    // EDIT (Tidak ada perubahan)
    $(document).on('click','.btn-edit',function(){
        var id = $(this).data('id');
        $.get('/hasil-uji-lab/'+id+'/edit',function(data){
            $('#editTanggal').val(data.tanggal);
            $('#editSupplier').val(data.suplier);
            $('#editNoSampel').val(data.no_sampel);
            $('#editK3').val(data.k3);
            $('#editDirt').val(data.dirt);
            $('#editAsk').val(data.ask);
            $('#formEdit').attr('action','/hasil_uji_lab_bokar/'+id);
            $('#modalEdit').modal('show');
        });
    });

});
</script>
</body>
</html>