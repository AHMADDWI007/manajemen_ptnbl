<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        .table th, .table td { vertical-align: middle; }
        .btn-add-user { float: right; }
        .dataTables_filter {
            float: right !important;
            text-align: right !important;
        }
        .modal { z-index: 1055 !important; }
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
                        <button type="button" class="btn btn-success btn-sm btn-add-user" onclick="openAddModal()">
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
                                    @foreach($users as $user)
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
                                            <button class="btn btn-warning btn-sm" onclick="openEditModal(this)"
                                                data-id="{{ $user->id }}"
                                                data-fullname="{{ $user->fullname }}"
                                                data-username="{{ $user->username }}"
                                                data-jabatan="{{ $user->jabatan }}"
                                                data-role="{{ $user->role }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
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
</div>

{{-- Modal Tambah/Edit --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="userModalLabel">Form Pengguna</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm" method="POST">
                @csrf
                <div id="method-field"></div>
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
                        <select class="form-select" id="role" name="role" required>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Script --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

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
                paginate: { previous: "Sebelumnya", next: "Berikutnya" }
            },
            dom:
                "<'row'<'col-sm-6'l><'col-sm-6'f>>" + // posisi tombol filter di kanan atas
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>"
        });
    });

    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userForm');
    const methodField = document.getElementById('method-field');
    const passwordHelp = document.getElementById('passwordHelp');

    function openAddModal() {
        form.reset();
        form.action = "{{ route('users.store') }}";
        methodField.innerHTML = "";
        document.getElementById('password').required = true;
        passwordHelp.style.display = 'none';
        document.getElementById('userModalLabel').textContent = "Tambah Pengguna Baru";
        userModal.show();
    }

    function openEditModal(button) {
        form.reset();
        const id = button.dataset.id;
        form.action = `/users/${id}`;
        methodField.innerHTML = `<input type="hidden" name="_method" value="PUT">`;
        document.getElementById('fullname').value = button.dataset.fullname;
        document.getElementById('username').value = button.dataset.username;
        document.getElementById('jabatan').value = button.dataset.jabatan;
        document.getElementById('role').value = button.dataset.role;
        document.getElementById('password').required = false;
        passwordHelp.style.display = 'block';
        document.getElementById('userModalLabel').textContent = "Edit Data Pengguna";
        userModal.show();
    }

    // SweetAlert konfirmasi hapus
    $(document).on('click', '.btn-delete', function() {
        let form = $(this).closest('form');
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Notifikasi popup jika sukses
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2000
        });
    @endif
</script>
</body>
</html>
