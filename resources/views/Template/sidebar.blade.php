<aside class="main-sidebar elevation-4" style="background-color: #355E3B;">
    <a href="#" class="brand-link d-flex align-items-center" style="background-color: #2E8B57; color: #fff;">
        <img src="{{ asset('gambar/nb_icon.png') }}" alt="Logo" class="brand-image img-circle elevation-3"
            style="opacity:.9; background-color:#fff; padding:3px;">
        <span class="brand-text fw-bolder text-white ms-2" style="font-size: 15px; letter-spacing: 0.5px;">
            PT. NUSANTARA BATULICIN
        </span>
    </a>

    <div class="sidebar">
        <div class="user-panel d-flex align-items-center mt-3 pb-3 mb-3 border-bottom"
            style="border-color: rgba(255,255,255,0.2);">
            <div class="image">
                <img src="{{ asset('gambar/user.png') }}" class="img-circle elevation-2" alt="User Image"
                    style="width:45px; height:45px; object-fit:cover; background:#fff; padding:2px;">
            </div>
            <div class="info ms-2">
                <a href="#" class="d-block text-white fw-bold" style="font-size: 16px;">Administrasi</a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="{{ url('/beranda') }}" class="nav-link {{ request()->is('beranda') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Beranda</p>
                    </a>
                </li>

                {{-- =================================== --}}
                {{-- 1. DATA LABORATORIUM --}}
                {{-- =================================== --}}
                @php
                    // Logika Deteksi Menu Induk Laboratorium
                    $isLabOpen = request()->is(
                        'hasil-uji-bokar', 'hasil-uji-bokar/*', // Spesifik agar tidak kena 'diolah'
                        'hasil-uji-bokar-diolah*', 
                        'hasil-uji-maturasi*', 
                        'hasil-uji-troli*', 
                        'hasil-uji-sir20*'
                    );
                @endphp
                <li id="menu-laboratorium" class="nav-item has-treeview {{ $isLabOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isLabOpen ? 'active' : '' }}">
                        <i class="nav-icon fas fa-vial"></i>
                        <p>
                            Data Laboratorium
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            {{-- Gunakan pengecekan spesifik agar tidak bentrok dengan 'diolah' --}}
                            <a href="{{ url('/hasil-uji-bokar') }}" class="nav-link {{ request()->is('hasil-uji-bokar', 'hasil-uji-bokar/*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Bokar Diterima</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-bokar-diolah') }}" class="nav-link {{ request()->is('hasil-uji-bokar-diolah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Bokar Diolah</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-maturasi') }}" class="nav-link {{ request()->is('hasil-uji-maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-troli') }}" class="nav-link {{ request()->is('hasil-uji-troli*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Troli</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-sir20') }}" class="nav-link {{ request()->is('hasil-uji-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji SIR 20</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- =================================== --}}
                {{-- 2. DATA PENGOLAHAN --}}
                {{-- =================================== --}}
                @php
                    $isPengolahanOpen = request()->is('pengolahan-basah*', 'maturasi*', 'bahan-proses*');
                @endphp
                <li id="menu-pengolahan" class="nav-item has-treeview {{ $isPengolahanOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isPengolahanOpen ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sync-alt"></i>
                        <p>
                            Data Pengolahan
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/pengolahan-basah') }}" class="nav-link {{ request()->is('pengolahan-basah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pengolahan Basah</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/maturasi') }}" class="nav-link {{ request()->is('maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pengolahan Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/bahan-proses') }}" class="nav-link {{ request()->is('bahan-proses*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bahan Dalam Proses</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- =================================== --}}
                {{-- 3. DATA PRODUKSI --}}
                {{-- =================================== --}}
                @php
                    // Cek apakah sedang membuka salah satu menu di bawah ini
                    $isProduksiOpen = request()->is(
                        'produksi-sir20*',      // Laporan Harian
                        'data-sir*',            // Data Gudang (Route Baru)
                        'penjualan-sir20*'      // Penjualan
                    ); 
                @endphp

                <li id="menu-produksi" class="nav-item has-treeview {{ $isProduksiOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isProduksiOpen ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>
                            Data Produksi
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        
                        {{-- 1. PRODUKSI SIR 20 (Laporan Harian / Mesin) --}}
                        <li class="nav-item">
                            <a href="{{ url('/produksi-sir20') }}" class="nav-link {{ request()->is('produksi-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Produksi SIR 20</p>
                            </a>
                        </li>

                        {{-- 2. DATA GUDANG & MUTU (URL Disesuaikan dengan Route 'data-sir') --}}
                        <li class="nav-item">
                            <a href="{{ url('/data-sir') }}" class="nav-link {{ request()->is('data-sir', 'data-sir/*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Gudang & Mutu</p>
                            </a>
                        </li>

                        {{-- 3. PENJUALAN SIR 20 --}}
                        <li class="nav-item">
                            <a href="{{ url('/penjualan-sir20') }}" class="nav-link {{ request()->is('penjualan-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Penjualan SIR 20</p>
                            </a>
                        </li>
                        
                    </ul>
                </li>

                {{-- Data Pengguna --}}
                <li class="nav-item">
                    <a href="{{ url('/data-pengguna') }}" class="nav-link {{ request()->is('data-pengguna*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Data Pengguna</p>
                    </a>
                </li>
                
                {{-- Data Lainnya --}}
                @php
                    $isLainnyaOpen = request()->is('data-lainnya*');
                @endphp
                <li id="menu-lainnya" class="nav-item has-treeview {{ $isLainnyaOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isLainnyaOpen ? 'active' : '' }}">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>
                            Data Lainnya
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/data-lainnya/truck') }}" class="nav-link {{ request()->is('data-lainnya/truck*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Truck</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/data-lainnya/audit-trail') }}" class="nav-link {{ request()->is('data-lainnya/audit-trail*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Audit Trail</p>
                            </a>
                        </li>
                    </ul>
                 </li> 

                 <li class="nav-item">
                    {{-- Pastikan URL ini sesuai dengan route laporan harian di web.php --}}
                    <a href="{{ url('/laporan-harian') }}" class="nav-link {{ request()->is('laporan-harian*') ? 'active' : '' }}"> 
                        <i class="nav-icon fas fa-book"></i>
                        <p>Laporan Harian</p>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="{{ url('/persetujuan') }}" class="nav-link {{ request()->is('persetujuan*') ? 'active' : '' }}"> 
                         <i class="nav-icon fas fa-clipboard-list"></i>
                         <p>Persetujuan</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="mt-auto mb-3 px-3">
         <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Yakin ingin logout?');">
             @csrf
             <button type="submit" class="btn w-100 d-flex align-items-center justify-content-center"
                 style="background-color: #8B0000; color: #fff; font-weight: bold; border: none; border-radius: 8px; padding: 10px;">
                 <i class="fas fa-sign-out-alt me-2"></i> Logout
             </button>
         </form>
     </div>

    <style>
        .nav-sidebar .nav-link { color: white !important; }
        .nav-sidebar .nav-link:hover { background-color: #3CB371 !important; }
        .nav-sidebar .nav-item>.nav-link.active { background-color: #FFD700 !important; color: #355E3B !important; font-weight: bold; }
        .nav-sidebar .nav-treeview { padding-left: 20px; display: none; }
        .nav-sidebar .menu-open > .nav-treeview { display: block; }
        .nav-sidebar .nav-treeview>.nav-item>.nav-link { color: #f8f9fa !important; }
        .nav-sidebar .nav-treeview>.nav-item>.nav-link.active { background-color: #2E8B57 !important; color: white !important; }
        .nav-sidebar .nav-header { font-size: 0.9rem; }

        .main-sidebar {
           height: 100vh !important; 
           position: fixed !important; 
           top: 0;
           left: 0;
           display: flex; 
           flex-direction: column; 
           overflow-y: auto; 
        }
        .sidebar {
            flex-grow: 1; 
            overflow-y: auto; 
        }
        .main-sidebar .mt-auto {
            margin-top: auto !important; 
        }

        .content-wrapper {
             margin-left: 250px; 
        }
        
        @media (max-width: 768px) {
            .content-wrapper { margin-left: 0; }
        }
    </style>
</aside>