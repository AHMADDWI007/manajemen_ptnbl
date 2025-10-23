<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    {{-- Tambahkan CSS untuk DataTables jika belum ada di template.head --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css"> 
    <style>
        .table th, .table td { vertical-align: middle; }
        .btn-add-user { float: right; }
        /* Pastikan filter DataTable rata kanan */
        .dataTables_filter {
            float: right !important;
            text-align: right !important;
        }
        .dataTables_length {
             float: left !important; /* Rata kiri untuk Show entries */
        }
        .modal { z-index: 1055 !important; } /* Pastikan modal di atas overlay */

        /* Atur jarak tombol aksi jika perlu */
        .action-buttons form,
        .action-buttons button {
            margin-left: 5px; /* Tambah jarak antar tombol */
        }
        .action-buttons form:first-child,
        .action-buttons button:first-child {
            margin-left: 0; /* Hapus jarak untuk tombol pertama */
        }

    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    {{-- Navbar & Sidebar --}}
    @include('template.navbar')
    @include('template.sidebar')

    {{-- Content Wrapper --}}
    <div class="content-wrapper">

        {{-- Header --}}
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-success fw-bold">Manajemen Data Pengguna</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        {{-- Menggunakan data-toggle (Bootstrap 4) --}}
                        <button type="button" class="btn btn-success btn-sm btn-add-user" data-toggle="modal" data-target="#userModal" onclick="openAddModal()">
                            <i class="fas fa-plus-circle"></i> Tambah Pengguna
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Konten --}}
        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Pengguna Sistem</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="usersTable">
                                <thead class="text-center bg-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Jabatan</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $user->fullname }}</td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->jabatan }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $user->role === 'admin' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="action-buttons d-inline-flex"> {{-- Bungkus tombol aksi --}}
                                                {{-- Menggunakan data-toggle (Bootstrap 4) --}}
                                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#userModal" onclick="openEditModal(this)"
                                                    data-id="{{ $user->id }}"
                                                    data-fullname="{{ $user->fullname }}"
                                                    data-username="{{ $user->username }}"
                                                    data-jabatan="{{ $user->jabatan }}"
                                                    data-role="{{ $user->role }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    {{-- Tombol tipe button agar form tidak langsung submit --}}
                                                    <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data pengguna.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <footer class="main-footer">
        @include('template.footer')
    </footer>
</div> {{-- Penutup .wrapper --}}

{{-- Modal Tambah/Edit --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="userModalLabel">Form Pengguna</h5>
                {{-- Menggunakan data-dismiss (Bootstrap 4) --}}
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm" method="POST">
                @csrf
                <div id="method-field"></div> {{-- Untuk @method('PUT') --}}
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" name="jabatan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        {{-- Ganti form-select ke form-control jika pakai Bootstrap 4 --}}
                        <select class="form-control" id="role" name="role" required> 
                            <option value="" disabled selected>Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="passwordHelp">
                            Kosongkan jika tidak ingin mengubah password.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                     {{-- Menggunakan data-dismiss (Bootstrap 4) --}}
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Skrip Lainnya (DataTables, SweetAlert) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
{{-- Gunakan DataTables Bootstrap 4 JS jika template pakai Bootstrap 4 --}}
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script> 
{{-- <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script> --}}


{{-- Panggil Skrip Inti Template DI AKHIR SEBELUM SCRIPT CUSTOM--}}
@include('template.script') 

{{-- Script custom Halaman ini --}}
<script>
    // Inisialisasi DataTable
    $(document).ready(function() {
        $('#usersTable').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data", // Tambahkan ini
                infoFiltered: "(difilter dari _MAX_ total data)", // Tambahkan ini
                zeroRecords: "Data tidak ditemukan", // Tambahkan ini
                paginate: { previous: "Sebelumnya", next: "Berikutnya" }
            },
            // DOM disesuaikan untuk Bootstrap 4 (posisi search & length)
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" + 
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });
    });

    // Tidak perlu 'new bootstrap.Modal', cukup pakai jQuery
    // const userModal = new bootstrap.Modal(document.getElementById('userModal')); 
    const form = document.getElementById('userForm');
    const methodField = document.getElementById('method-field');
    const passwordHelp = document.getElementById('passwordHelp');

    function openAddModal() {
        form.reset();
        form.action = "{{ route('users.store') }}";
        methodField.innerHTML = ""; // Kosongkan method field
        document.getElementById('password').required = true; // Password wajib saat tambah
        passwordHelp.style.display = 'none'; // Sembunyikan helper password
        document.getElementById('userModalLabel').textContent = "Tambah Pengguna Baru";
        $('#userModal').modal('show'); // Tampilkan modal pakai jQuery
    }

    function openEditModal(button) {
        form.reset();
        const id = button.dataset.id;
        // Pastikan URL benar, sesuaikan jika perlu
        form.action = "{{ url('users') }}/" + id; 
        methodField.innerHTML = `<input type="hidden" name="_method" value="PUT">`; // Tambah method PUT
        document.getElementById('fullname').value = button.dataset.fullname;
        document.getElementById('username').value = button.dataset.username;
        document.getElementById('jabatan').value = button.dataset.jabatan;
        document.getElementById('role').value = button.dataset.role;
        document.getElementById('password').required = false; // Password tidak wajib saat edit
        passwordHelp.style.display = 'block'; // Tampilkan helper password
        document.getElementById('userModalLabel').textContent = "Edit Data Pengguna";
        $('#userModal').modal('show'); // Tampilkan modal pakai jQuery
    }

    // SweetAlert konfirmasi hapus
    $(document).on('click', '.btn-delete', function() {
        let form = $(this).closest('form');
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Warna hijau
            cancelButtonColor: '#d33', // Warna merah
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Notifikasi popup jika sukses (dari session)
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2000 // Popup hilang setelah 2 detik
        });
    @endif
</script>
</body>
</html>