<!DOCTYPE html>
<html lang="id">
<head>
    @include('Template.head')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    {{-- Navbar --}}
    @include('Template.navbar')

    {{-- Sidebar --}}
    @include('Template.sidebar')

    {{-- Konten Halaman --}}
    <div class="content-wrapper p-3" style="background-color: #f4f6f9;">
        @yield('content')
    </div>

    {{-- Footer --}}
    @include('Template.footer')
</div>

@include('Template.script')
</body>
</html>
