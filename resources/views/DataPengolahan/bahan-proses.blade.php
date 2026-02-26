<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bahan Dalam Proses (WIP)</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        /* Style Tabel Standar */
        .table-bordered th, .table-bordered td { 
            border: 1px solid #dee2e6; 
            white-space: nowrap; 
            padding: 0.5rem; 
            vertical-align: middle !important; 
            font-size: 0.9rem; 
        }
        
        /* Header Rata Tengah & Bold */
        .table thead th { 
            text-align: center !important; 
            background-color: #f8f9fa; 
            font-weight: bold; 
            text-transform: uppercase;
        }

        /* Isi Tabel Rata Tengah (Kecuali Uraian) */
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }
        .font-weight-bold { font-weight: 700 !important; }

        /* Style untuk tombol Aksi Dropdown */
        .action-buttons { display: flex; justify-content: center; gap: 5px; }

        /* Style Kalkulator di Modal */
        .calculator-box { background-color: #e2e3e5; border-radius: 5px; padding: 10px; margin-top: 15px; border: 1px dashed #adb5bd; }
        .calculator-title { font-size: 0.9rem; font-weight: bold; color: #495057; margin-bottom: 8px; }
        
        /* Input Readonly */
        .form-control[readonly] { background-color: #e9ecef; color: #495057; cursor: default; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Bahan Dalam Proses (WIP)</h3>
                
                {{-- TOMBOL INPUT KHUSUS DI ATAS (SESUAI PERMINTAAN) --}}
                {{-- 🔥 SEMBUNYIKAN TOMBOL INPUT JIKA ROLE USER --}}
                @if(auth()->user()->role != 'user')
                <button class="btn btn-success btn-sm fw-bold shadow-sm" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Input / Koreksi Data
                </button>
                @endif
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong class="my-auto">Daftar Bahan Dalam Proses</strong>
                        
                        {{-- FILTER TANGGAL --}}
                        <form action="{{ route('bahan-proses.index') }}" method="GET" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white font-weight-normal">Tanggal:</label>
                            <input type="date" name="filter_tanggal" id="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selectedDate->format('Y-m-d') }}" onchange="this.form.submit()" style="max-width: 160px;">
                        </form>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
                        @endif
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover dt-responsive nowrap" id="dataTable" width="100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th rowspan="2" width="5%">No.</th>
                                        <th rowspan="2">Tanggal</th>
                                        <th rowspan="2">Uraian</th>
                                        <th rowspan="2">Saldo Awal</th>
                                        <th colspan="2">WIP (Kg)</th> 
                                        <th rowspan="2">Produksi<br>SIR20</th>
                                        <th rowspan="2">Rektif</th>
                                        <th rowspan="2">Saldo Akhir</th>
                                        <th rowspan="2">Keterangan</th>
                                        {{-- 🔥 SEMBUNYIKAN HEADER AKSI JIKA ROLE USER --}}
                                        @if(auth()->user()->role != 'user')
                                        <th rowspan="2" width="8%">Aksi</th>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data_produksi as $item)
                                        @php 
                                            // Cek apakah data virtual (belum disimpan ke DB)
                                            // 🔥 PERBAIKAN: Gunakan nama Primary Key yang benar (id_bahan_proses)
                                            $isVirtual = is_null($item->id_bahan_proses);
                                            $jsonData = json_encode($item);
                                        @endphp
                                        
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center">{{ $selectedDate->format('d-m-Y') }}</td>
                                            <td class="text-left pl-2 font-weight-bold">{{ $item->uraian ?? '-' }}</td>
                                            <td class="text-center">{{ number_format($item->saldo_awal ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($item->wip_masuk ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($item->wip_keluar ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($item->produksi_sir20 ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item->rekfif != 0 ? number_format($item->rekfif, 0, ',', '.') : '-' }}</td>
                                            <td class="text-center font-weight-bold">{{ number_format($item->saldo_akhir ?? 0, 0, ',', '.') }}</td>
                                            <td class="text-center pl-2">{{ $item->keterangan != '-' ? $item->keterangan : '-' }}</td>
                                            
                                            {{-- DROPDOWN AKSI (HANYA DETAIL & RESET) --}}
                                            {{-- 🔥 SEMBUNYIKAN KOLOM AKSI JIKA ROLE USER --}}
                                            @if(auth()->user()->role != 'user')
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="dropdownMenu{{ $loop->iteration }}" data-toggle="dropdown" aria-expanded="false">
                                                        Aksi
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu{{ $loop->iteration }}">
                                                        
                                                        {{-- 1. DETAIL (Selalu Ada) --}}
                                                        <a class="dropdown-item btn-detail" href="javascript:void(0)" 
                                                        data-item='{{ $jsonData }}'>
                                                            <i class="fas fa-eye text-info mr-2"></i> Detail
                                                        </a>

                                                        {{-- HANYA MUNCUL JIKA DATA SUDAH TERSIMPAN DI DB --}}
                                                        @if(!$isVirtual)
                                                            
                                                            {{-- 2. EDIT SALDO AWAL / AKHIR --}}
                                                            <a class="dropdown-item btn-edit-setup" href="javascript:void(0)" 
                                                            data-item='{{ $jsonData }}'>
                                                                <i class="fas fa-edit text-warning mr-2"></i> Edit Saldo
                                                            </a>

                                                            {{-- 3. RESET DATA KE 0 --}}
                                                            <form action="{{ route('bahan-proses.destroy', $item->id_bahan_proses) }}" method="POST" 
                                                                class="reset-form" style="display:inline;" 
                                                                onsubmit="return confirm('Yakin ingin mereset data proses ini kembali ke 0?');">
                                                                @csrf 
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-undo mr-2"></i> Reset
                                                                </button>
                                                            </form>
                                                            
                                                        @endif
                                                        
                                                    </div>
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="11" class="text-center text-muted py-3">Belum ada data untuk tanggal ini.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($data_produksi->isNotEmpty())
                                <tfoot>
                                    <tr class="bg-light">
                                        {{-- 🔥 Logika Colspan Dinamis: Jika User maka colspan 3 (No, Tgl, Uraian) --}}
                                        {{-- Jika Admin maka tetap sama, tapi kita sesuaikan agar total kolom di body pas --}}
                                        <td colspan="3" class="text-center font-weight-bold">Jumlah</td>
                                        
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_awal'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_masuk'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_keluar'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['produksi_sir20'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['rekfif'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_akhir'], 0, ',', '.') }}</td>
                                        
                                        {{-- Kolom Keterangan --}}
                                        <td></td>

                                        {{-- 🔥 KUNCI PERBAIKAN: Sembunyikan sel Aksi jika role User --}}
                                        @if(auth()->user()->role != 'user')
                                            <td></td>
                                        @endif
                                    </tr>

                                    {{-- Baris Total Persediaan --}}
                                    <tr style="border-top: 2px solid #dee2e6; background-color: #e8f5e9;">
                                        <td colspan="8" class="text-center font-weight-bold align-middle">
                                            TOTAL PERSEDIAAN (Bokar + Maturasi + WIP):<br>
                                            <small class="text-muted font-weight-normal">
                                                (Bokar: {{ number_format($detail_bokar, 0, ',', '.') }} + 
                                                Maturasi: {{ number_format($detail_maturasi, 0, ',', '.') }} + 
                                                WIP: {{ number_format($detail_wip, 0, ',', '.') }})
                                            </small>
                                        </td>
                                        <td class="text-center font-weight-bold align-middle" style="font-size: 1.1em; color: #0f5132;">
                                            {{ number_format($grandTotalSaldoAkhir, 0, ',', '.') }}
                                        </td>
                                        
                                        {{-- 🔥 Gunakan colspan dinamis untuk sisa kolom (Keterangan + Aksi) --}}
                                        <td colspan="{{ (auth()->user()->role == 'user') ? 1 : 2 }}" class="text-center font-weight-bold align-middle" style="font-size: 0.9em; color: #664d03;">
                                            {{ number_format($grandTotalKeterangan, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="main-footer"> @include('template.footer') </footer>
</div>

{{-- ================= MODAL EDIT SETUP SALDO ================= --}}
<div class="modal fade" id="modalEditSetup" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- Action form akan diisi oleh JavaScript --}}
            <form id="formEditSetup" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit"></i> Setup Saldo (Opname)</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary py-2" style="font-size: 0.9em;">
                        Gunakan fitur ini <b>hanya untuk inisialisasi awal</b> atau koreksi stok fisik lapangan. Perbedaan Saldo Akhir akan otomatis dicatat sebagai <b>Rektifikasi</b>.
                    </div>
                    
                    <div class="form-group">
                        <label>Tahapan Proses (Uraian)</label>
                        <input type="text" id="setupUraian" class="form-control font-weight-bold bg-light" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Saldo Awal (Kg)</label>
                                <input type="number" name="saldo_awal" id="setupSaldoAwal" class="form-control" step="1" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="text-success font-weight-bold">Target Saldo Akhir (Kg)</label>
                                <input type="number" name="saldo_akhir" id="setupSaldoAkhir" class="form-control border-success" step="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Update Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= MODAL INPUT REKTIF (SATU-SATUNYA INPUT) ================= --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('bahan-proses.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Input Koreksi Stok (Rektifikasi)</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2" style="font-size: 0.9em;">
                        <i class="fas fa-info-circle"></i> Isi kolom <b>WIP Keluar</b> untuk memindahkan barang ke proses selanjutnya. Jika ada salah input sebelumnya, cukup ganti angkanya dan simpan ulang.
                    </div>
                    
                    <input type="hidden" name="tanggal_input" value="{{ $selectedDate->format('Y-m-d') }}">
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th>Tahapan Proses (Uraian)</th>
                                    <th width="30%">WIP Keluar (Kg)</th>
                                    <th width="20%">Rektif (Kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($masterUraian as $u)
                                    @php
                                        // Cari apakah data ini sudah ada di database untuk tanggal tersebut
                                        $existing = $data_produksi->firstWhere('uraian', $u);
                                    @endphp
                                    <tr>
                                        <td class="align-middle font-weight-bold" style="font-size: 0.9em;">
                                            {{ $u }}
                                        </td>
                                        <td>
                                            {{-- Penamaan name menggunakan array: wip_keluar[Nama Uraian] --}}
                                            <input type="number" name="wip_keluar[{{ $u }}]" 
                                                   class="form-control form-control-sm text-center text-success font-weight-bold" 
                                                   step="1" placeholder="Kosong..."
                                                   value="{{ $existing && $existing->wip_keluar > 0 ? $existing->wip_keluar : '' }}">
                                        </td>
                                        <td>
                                            <input type="number" name="rekfif[{{ $u }}]" 
                                                   class="form-control form-control-sm text-center" 
                                                   step="1" placeholder="+/-"
                                                   value="{{ $existing && $existing->rekfif != 0 ? $existing->rekfif : '' }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= MODAL DETAIL (READ ONLY) ================= --}}
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">Detail Bahan Proses</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Tanggal</dt><dd class="col-sm-7">{{ $selectedDate->format('d M Y') }}</dd>
                    <dt class="col-sm-5">Uraian</dt><dd class="col-sm-7 font-weight-bold" id="detUraian">-</dd>
                    <div class="col-12"><hr class="my-1"></div>
                    <dt class="col-sm-5">Saldo Awal</dt><dd class="col-sm-7" id="detSaldoAwal">-</dd>
                    <dt class="col-sm-5">WIP Masuk</dt><dd class="col-sm-7" id="detWipMasuk">-</dd>
                    <dt class="col-sm-5">WIP Keluar</dt><dd class="col-sm-7" id="detWipKeluar">-</dd>
                    <dt class="col-sm-5 text-success">Produksi SIR20</dt><dd class="col-sm-7 text-success font-weight-bold" id="detProduksi">-</dd>
                    <div class="col-12"><hr class="my-1"></div>
                    <dt class="col-sm-5 text-warning">Rektif</dt><dd class="col-sm-7 text-warning font-weight-bold" id="detRektif">-</dd>
                    <dt class="col-sm-5 text-primary">Saldo Akhir</dt><dd class="col-sm-7 text-primary font-weight-bold" id="detSaldoAkhir">-</dd>
                    <div class="col-12"><hr class="my-1"></div>
                    <dt class="col-sm-5">Keterangan</dt><dd class="col-sm-7" id="detKeterangan">-</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@include('template.script')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    var table = $('#dataTable').DataTable({
        "searching": false, "ordering": false, "paging": false, "info": false, "responsive": true, "autoWidth": false 
    });

    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", showConfirmButton: false, timer: 2000 });
    @endif

    // Format Angka Helper
    const fmt = (num) => new Intl.NumberFormat('id-ID').format(num || 0);

    // --- LOGIKA TOMBOL DETAIL ---
    $(document).on('click', '.btn-detail', function() {
        let data = $(this).data('item'); // Ambil JSON dari tombol
        
        $('#detUraian').text(data.uraian);
        $('#detSaldoAwal').text(fmt(data.saldo_awal) + ' Kg');
        $('#detWipMasuk').text(fmt(data.wip_masuk) + ' Kg');
        $('#detWipKeluar').text(fmt(data.wip_keluar) + ' Kg');
        $('#detProduksi').text(fmt(data.produksi_sir20) + ' Kg');
        $('#detRektif').text(fmt(data.rekfif) + ' Kg');
        $('#detSaldoAkhir').text(fmt(data.saldo_akhir) + ' Kg');
        $('#detKeterangan').text(data.keterangan || '-');
        
        $('#modalDetail').modal('show');
    });

    // --- LOGIKA KALKULATOR (Di Modal Tambah) ---
    window.hitungFisik = function() {
        let trolley = parseFloat($('#calc_trolley').val()) || 0;
        let pallet = parseFloat($('#calc_pallet').val()) || 0;
        let add = parseFloat($('#calc_tambahan').val()) || 0;
        
        let totalFisik = (trolley * 490) + (pallet * 1260) + add;
        $('#calc_total_fisik').text(fmt(totalFisik));
        // Catatan: Di modal input manual, kita tidak otomatis mengisi field Rektif
        // karena kita tidak tahu Saldo Sistem saat ini secara realtime tanpa ajax.
        // User harus menghitung selisihnya manual berdasarkan data di tabel.
    }

    // --- LOGIKA MODAL EDIT SETUP SALDO ---
    $(document).on('click', '.btn-edit-setup', function() {
        let data = $(this).data('item'); 
        
        $('#setupUraian').val(data.uraian);
        $('#setupSaldoAwal').val(data.saldo_awal || 0);
        $('#setupSaldoAkhir').val(data.saldo_akhir || 0);
        
        // Arahkan action form ke route update
        let url = "{{ url('bahan-proses') }}/" + data.id_bahan_proses;
        $('#formEditSetup').attr('action', url);
        
        $('#modalEditSetup').modal('show');
    });
    
});
</script>
</body>
</html>