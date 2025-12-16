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
                <button class="btn btn-success btn-sm fw-bold shadow-sm" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Input / Koreksi Data
                </button>
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
                                        <th rowspan="2" width="8%">Aksi</th>
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
                                            $isVirtual = is_null($item->id); 
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
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                                        Aksi
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        
                                                        {{-- 1. DETAIL (Selalu Ada) --}}
                                                        <a class="dropdown-item btn-detail" href="javascript:void(0)" data-item='{{ $jsonData }}'>
                                                            <i class="fas fa-eye text-info mr-2"></i> Detail
                                                        </a>

                                                        {{-- 2. RESET (Hanya jika data sudah disimpan/bukan virtual) --}}
                                                        @if(!$isVirtual)
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('bahan-proses.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin mereset data rektifikasi ini?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-undo mr-2"></i> Reset
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        {{-- TIDAK ADA TOMBOL INPUT DI SINI --}}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="11" class="text-center text-muted py-3">Belum ada data untuk tanggal ini.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($data_produksi->isNotEmpty())
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="3" class="text-center font-weight-bold">Jumlah</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_awal'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_masuk'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_keluar'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['produksi_sir20'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['rekfif'], 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_akhir'], 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
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
                                        <td class="text-center font-weight-bold align-middle" style="font-size: 0.9em; color: #664d03;">
                                            {{ number_format($grandTotalKeterangan, 0, ',', '.') }}
                                        </td>
                                        <td></td>
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
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Data WIP Masuk, Keluar, dan Produksi dihitung <b>OTOMATIS</b>. Gunakan form ini hanya jika ada selisih stok (Rektifikasi).
                    </div>
                    
                    <input type="hidden" name="tanggal_input" value="{{ $selectedDate->format('Y-m-d') }}">
                    
                    <div class="form-group mb-3">
                        <label>Pilih Tahapan Proses (Uraian)</label>
                        <select name="uraian" class="form-control font-weight-bold" required>
                            <option value="" disabled selected>-- Pilih Uraian --</option>
                            @foreach(['Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)', 'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)', 'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'] as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3 bg-warning p-3 rounded border border-warning">
                        <label class="text-dark font-weight-bold">Rektif (Kg)</label>
                        <input type="number" name="rekfif" class="form-control font-weight-bold" step="0.01" placeholder="0">
                        <small class="text-dark">
                            Masukkan nilai <b>Positif (+)</b> untuk menambah stok, atau <b>Negatif (-)</b> untuk mengurangi stok.
                        </small>
                    </div>

                    {{-- KALKULATOR BANTU (OPSIONAL) --}}
                    <div class="calculator-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="calculator-title"><i class="fas fa-calculator"></i> Kalkulator Fisik (Bantu Hitung)</span>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4">
                                <label class="small">Trolley (x490)</label>
                                <input type="number" id="calc_trolley" class="form-control form-control-sm" placeholder="Jml" oninput="hitungFisik()">
                            </div>
                            <div class="col-4">
                                <label class="small">Pallet (x1260)</label>
                                <input type="number" id="calc_pallet" class="form-control form-control-sm" placeholder="Jml" oninput="hitungFisik()">
                            </div>
                            <div class="col-4">
                                <label class="small">Tambahan (Kg)</label>
                                <input type="number" id="calc_tambahan" class="form-control form-control-sm" placeholder="Kg" oninput="hitungFisik()">
                            </div>
                        </div>
                        <div class="mt-2 text-right">
                            <small>Total Fisik:</small> 
                            <strong class="text-success" style="font-size: 1.1em;" id="calc_total_fisik">0</strong> 
                            <small>Kg</small>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" placeholder="Alasan koreksi..."></textarea>
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
});
</script>
</body>
</html>