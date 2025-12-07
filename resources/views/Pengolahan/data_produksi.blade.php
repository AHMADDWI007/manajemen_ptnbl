<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bahan Dalam Proses</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; white-space: nowrap; padding: 0.5rem; vertical-align: middle !important; }
        .table thead th { text-align: center !important; background-color: #f8f9fa; font-weight: bold; }
        .text-left { text-align: left !important; } .text-center { text-align: center !important; }
        .font-italic { font-style: italic; } .text-muted { color: #6c757d !important; }
        .action-buttons { display: flex; justify-content: center; gap: 5px; }
        /* Style untuk Kalkulator */
        .calculator-box { background-color: #e2e3e5; border-radius: 5px; padding: 10px; margin-top: 15px; border: 1px dashed #adb5bd; }
        .calculator-title { font-size: 0.9rem; font-weight: bold; color: #495057; margin-bottom: 8px; }
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
                <button class="btn btn-success btn-sm fw-bold" data-toggle="modal" data-target="#modalTambah">
                    <i class="fas fa-plus-circle"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <strong class="my-auto">Daftar Bahan Dalam Proses</strong>
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
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse ($data_produksi as $item)
                                    @php $isVirtual = is_null($item->id); @endphp
                                    <tr class="{{ $isVirtual ? 'text-muted font-italic' : '' }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $selectedDate->format('d-m-Y') }}</td>
                                        <td class="text-left pl-2">{{ $item->uraian ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->saldo_awal ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->wip_masuk ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->wip_keluar ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->produksi_sir20 ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->rekfif != 0 ? number_format($item->rekfif, 2, ',', '.') : '-' }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($item->saldo_akhir ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center pl-2">{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                @if(!$isVirtual)
                                                    <button type="button" class="btn btn-info btn-sm btn-detail" data-id="{{ $item->id }}" title="Detail"><i class="fas fa-eye"></i></button>
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" data-id="{{ $item->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                                    <form action="{{ route('bahan-proses.destroy', $item->id) }}" method="POST" class="form-delete" style="display:inline;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-success btn-sm btn-proces" 
                                                            data-uraian="{{ $item->uraian }}" 
                                                            {{-- Data untuk kalkulator --}}
                                                            data-saldo-awal="{{ $item->saldo_awal }}"
                                                            data-wip-masuk="{{ $item->wip_masuk }}"
                                                            title="Simpan / Proses">
                                                        <i class="fas fa-check"></i> Proses
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="11" class="text-center text-muted py-3">Belum ada data untuk tanggal ini.</td></tr>
                                @endforelse
                                </tbody>
                                @if($data_produksi->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-center font-weight-bold">Jumlah</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_awal'], 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_masuk'], 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['wip_keluar'], 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['produksi_sir20'], 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['rekfif'], 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($totals['saldo_akhir'], 2, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>

                                    {{-- ✅ BARIS 2: TOTAL GABUNGAN (SESUAI PERMINTAAN) --}}
                                    <tr style="border-top: 2px solid #dee2e6; background-color: #fffbe6;">
                                        <td colspan="8" class="text-center font-weight-bold align-middle">
                                            Total Persediaan (Neraca Massa):<br>
                                            <small class="text-muted font-weight-normal">
                                                (Bokar: {{ number_format($detail_bokar, 0, ',', '.') }} + 
                                                Maturasi: {{ number_format($detail_maturasi, 0, ',', '.') }} + 
                                                WIP: {{ number_format($detail_wip, 0, ',', '.') }})
                                            </small>
                                        </td>
                                        
                                        {{-- Di Bawah Saldo Akhir --}}
                                        <td class="text-center font-weight-bold align-middle" style="font-size: 1.1em; color: #0f5132;">
                                            {{ number_format($grandTotalSaldoAkhir, 2, ',', '.') }}
                                        </td>
                                        
                                        {{-- Di Bawah Keterangan (WIP + Maturasi) --}}
                                        <td class="text-center font-weight-bold align-middle" style="font-size: 0.9em; color: #664d03;">
                                            {{ number_format($grandTotalKeterangan, 2, ',', '.') }}
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

{{-- ================= MODAL TAMBAH ================= --}}
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('bahan-proses.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Input Data Bahan Proses</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tanggal_input" value="{{ $selectedDate->format('Y-m-d') }}">
                    <div class="form-group mb-3">
                        <label>Uraian</label>
                        <select name="uraian" id="add_uraian" class="form-control" required>
                            <option value="" disabled selected>-- Pilih Uraian --</option>
                            @foreach(['Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)', 'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)', 'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'] as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Hidden Inputs for Calculation --}}
                    <input type="hidden" id="add_saldo_awal" value="0">
                    <input type="hidden" id="add_wip_masuk" value="0">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>WIP Keluar (Kg)</label>
                                <input type="number" name="wip_keluar" id="add_wip_keluar" class="form-control" step="0.01" placeholder="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Produksi SIR20 (Kg)</label>
                                <input type="number" name="produksi_sir20" id="add_produksi" class="form-control" step="0.01" placeholder="0" value="0">
                                <small class="text-muted">Hanya untuk tahap akhir.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Rektif (Kg)</label>
                        <input type="number" name="rekfif" id="add_rekfif" class="form-control" step="0.01" placeholder="0" value="0">
                        <small class="text-muted">Koreksi manual atau hasil kalkulator.</small>
                    </div>

                    {{-- ✅ KALKULATOR BANTU STOCK OPNAME --}}
                    <div class="calculator-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="calculator-title"><i class="fas fa-calculator"></i> Kalkulator Stock Opname (Fisik)</span>
                            <button type="button" class="btn btn-xs btn-primary" id="btnHitungRektif">Hitung Selisih & Isi Rektif</button>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4">
                                <label><small>Jml Trolley</small></label>
                                <input type="number" id="calc_trolley" class="form-control form-control-sm" placeholder="0" oninput="hitungFisik()">
                                <small class="text-muted">x 490 Kg</small>
                            </div>
                            <div class="col-4">
                                <label><small>Jml Pallet</small></label>
                                <input type="number" id="calc_pallet" class="form-control form-control-sm" placeholder="0" oninput="hitungFisik()">
                                <small class="text-muted">x 1.260 Kg</small>
                            </div>
                            <div class="col-4">
                                <label><small>Tambahan (Kg)</small></label>
                                <input type="number" id="calc_tambahan" class="form-control form-control-sm" placeholder="0" oninput="hitungFisik()">
                            </div>
                        </div>
                        <div class="mt-2 text-right">
                            <strong>Total Fisik: <span id="calc_total_fisik" class="text-success">0</span> Kg</strong>
                        </div>
                    </div>
                    {{-- END KALKULATOR --}}

                    <div class="form-group mt-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
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

{{-- MODAL EDIT --}}
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEdit" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit / Koreksi Data</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label>Uraian</label>
                        <input type="text" name="uraian" id="editUraian" class="form-control" readonly>
                    </div>
                    
                    {{-- Hidden inputs for Calculation --}}
                    <input type="hidden" id="edit_hidden_saldo_awal">
                    <input type="hidden" id="edit_hidden_wip_masuk">

                    <div class="row">
                        <div class="col-6 mb-2">
                            <label>Saldo Awal</label>
                            <input type="number" name="saldo_awal" id="editSaldoAwal" class="form-control" step="0.01" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label>WIP Masuk</label>
                            <input type="number" name="wip_masuk" id="editWipMasuk" class="form-control" step="0.01" readonly>
                        </div>
                        <div class="col-6 mb-2">
                            <label>WIP Keluar</label>
                            <input type="number" name="wip_keluar" id="editWipKeluar" class="form-control" step="0.01">
                        </div>
                        <div class="col-6 mb-2">
                            <label>Produksi SIR20</label>
                            <input type="number" name="produksi_sir20" id="editProduksi" class="form-control" step="0.01">
                        </div>
                        <div class="col-6 mb-2">
                            <label>Rektif</label>
                            <input type="number" name="rekfif" id="editRekfif" class="form-control" step="0.01">
                        </div>
                        <div class="col-6 mb-2">
                            <label>Saldo Akhir</label>
                            <input type="number" name="saldo_akhir" id="editSaldoAkhir" class="form-control" step="0.01" readonly>
                        </div>
                    </div>

                    {{-- ✅ KALKULATOR BANTU (EDIT MODE) --}}
                    <div class="calculator-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="calculator-title"><i class="fas fa-calculator"></i> Kalkulator Stock Opname</span>
                            <button type="button" class="btn btn-xs btn-warning" id="btnHitungRektifEdit">Hitung & Isi Rektif</button>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4">
                                <input type="number" id="calc_edit_trolley" class="form-control form-control-sm" placeholder="Jml Trolley" oninput="hitungFisikEdit()">
                            </div>
                            <div class="col-4">
                                <input type="number" id="calc_edit_pallet" class="form-control form-control-sm" placeholder="Jml Pallet" oninput="hitungFisikEdit()">
                            </div>
                            <div class="col-4">
                                <input type="number" id="calc_edit_tambahan" class="form-control form-control-sm" placeholder="Tambahan Kg" oninput="hitungFisikEdit()">
                            </div>
                        </div>
                        <div class="mt-2 text-right">
                            <strong>Total Fisik: <span id="calc_edit_total_fisik" class="text-success">0</span> Kg</strong>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="editKeterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ... Modal Detail (Sama) ... --}}
@include('template.script')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
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

    // --- TOMBOL PROSES (VIRTUAL) ---
    $(document).on('click', '.btn-proces', function () {
        var uraian = $(this).data('uraian');
        // Ambil data hidden dari atribut tombol
        var saldoAwal = $(this).data('saldo-awal');
        var wipMasuk = $(this).data('wip-masuk');
        
        $('#modalTambah form')[0].reset();
        $('#modalTambah select[name="uraian"]').val(uraian);
        
        // Set data hidden untuk kalkulator
        $('#add_saldo_awal').val(saldoAwal);
        $('#add_wip_masuk').val(wipMasuk);
        
        // Reset kalkulator
        $('#calc_trolley, #calc_pallet, #calc_tambahan').val('');
        $('#calc_total_fisik').text('0');

        $('#modalTambah').modal('show');
    });

    // --- JS LOGIKA KALKULATOR (TAMBAH) ---
    window.hitungFisik = function() {
        let trolley = parseFloat($('#calc_trolley').val()) || 0;
        let pallet = parseFloat($('#calc_pallet').val()) || 0;
        let add = parseFloat($('#calc_tambahan').val()) || 0;
        
        let totalFisik = (trolley * 490) + (pallet * 1260) + add;
        $('#calc_total_fisik').text(totalFisik.toLocaleString('id-ID'));
    }

    $('#btnHitungRektif').on('click', function() {
        let saldoAwal = parseFloat($('#add_saldo_awal').val()) || 0;
        let wipMasuk = parseFloat($('#add_wip_masuk').val()) || 0;
        let wipKeluar = parseFloat($('#add_wip_keluar').val()) || 0;
        let produksi = parseFloat($('#add_produksi').val()) || 0;
        
        // Rumus Sistem: Awal + Masuk - Keluar - Produksi
        let stokSistem = saldoAwal + wipMasuk - wipKeluar - produksi;
        
        // Rumus Fisik
        let trolley = parseFloat($('#calc_trolley').val()) || 0;
        let pallet = parseFloat($('#calc_pallet').val()) || 0;
        let add = parseFloat($('#calc_tambahan').val()) || 0;
        let stokFisik = (trolley * 490) + (pallet * 1260) + add;

        // Rektif = Fisik - Sistem
        let rektif = stokFisik - stokSistem;
        $('#add_rekfif').val(rektif.toFixed(2));
        
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success', 
            title: 'Rektif dihitung: ' + rektif.toFixed(2) + ' Kg',
            showConfirmButton: false, timer: 1500
        });
    });

    // --- JS LOGIKA KALKULATOR (EDIT) ---
    window.hitungFisikEdit = function() {
        let trolley = parseFloat($('#calc_edit_trolley').val()) || 0;
        let pallet = parseFloat($('#calc_edit_pallet').val()) || 0;
        let add = parseFloat($('#calc_edit_tambahan').val()) || 0;
        let totalFisik = (trolley * 490) + (pallet * 1260) + add;
        $('#calc_edit_total_fisik').text(totalFisik.toLocaleString('id-ID'));
    }

    $('#btnHitungRektifEdit').on('click', function() {
        let saldoAwal = parseFloat($('#editSaldoAwal').val()) || 0;
        let wipMasuk = parseFloat($('#editWipMasuk').val()) || 0;
        let wipKeluar = parseFloat($('#editWipKeluar').val()) || 0;
        let produksi = parseFloat($('#editProduksi').val()) || 0;
        
        let stokSistem = saldoAwal + wipMasuk - wipKeluar - produksi;
        
        let trolley = parseFloat($('#calc_edit_trolley').val()) || 0;
        let pallet = parseFloat($('#calc_edit_pallet').val()) || 0;
        let add = parseFloat($('#calc_edit_tambahan').val()) || 0;
        let stokFisik = (trolley * 490) + (pallet * 1260) + add;

        let rektif = stokFisik - stokSistem;
        $('#editRekfif').val(rektif.toFixed(2));
    });

    // --- EDIT DATA ---
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        $.get('/bahan-proses/' + id + '/edit', function (data) {
            $('#editUraian').val(data.uraian); 
            $('#editSaldoAwal').val(data.saldo_awal);
            $('#editWipMasuk').val(data.wip_masuk);
            $('#editWipKeluar').val(data.wip_keluar);
            $('#editProduksi').val(data.produksi_sir20);
            $('#editRekfif').val(data.rekfif);
            $('#editSaldoAkhir').val(data.saldo_akhir);
            $('#editKeterangan').val(data.keterangan);
            
            // Reset Kalkulator
            $('#calc_edit_trolley, #calc_edit_pallet, #calc_edit_tambahan').val('');
            $('#calc_edit_total_fisik').text('0');

            $('#formEdit').attr('action', '/bahan-proses/' + id);
            $('#modalEdit').modal('show');
        });
    });
});
</script>
</body>
</html>