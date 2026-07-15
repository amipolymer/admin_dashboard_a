@php
$assetPrefix = config('app.asset_prefix').'/';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>APPL | Edigital Application</title>
    <link rel="icon" type="image/png" href="{{ url($assetPrefix.'assets/theme/src/images/logo/favicon-icon.png') }}">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="{{ url($assetPrefix.'assets/theme/vendors/styles/core.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ url($assetPrefix.'assets/theme/src/plugins/jvectormap/jquery-jvectormap-2.0.3.css')}}">
    <link rel="stylesheet" type="text/css"
        href="{{ url($assetPrefix.'assets/theme/vendors/styles/icon-font.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url($assetPrefix.'assets/theme/vendors/styles/style.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url($assetPrefix.'assets/theme/src/styles/mobile-css.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ url($assetPrefix.'assets/theme/src/plugins/sweetalert2/sweetalert2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url($assetPrefix.'assets/table/css/mermaid.min.css')}}" />
    @stack('styles')
    <style>
    td.gridjs-td.gridjs-message.gridjs-error {
        display: none;
    }
    </style>
</head>

<body>
    {{-- Loader --}}
    {{-- <div class="pre-loader">
            <div class="pre-loader-box">
                <div class="loader-logo">
                    <img src="{{ asset($assetPrefix.'assets/theme/vendors/images/deskapp-logo.svg') }}" alt="">
    </div>
    <div class='loader-progress' id="progress_div">
        <div class='bar' id='bar1'></div>
    </div>
    <div class='percent' id='percent1'>0%</div>
    <div class="loading-text">
        Loading...
    </div>
    </div>
    </div> --}}

    {{-- header import --}}
    @if(Auth::check())
    @endauth
    {{-- Mobile Menu Overlay --}}
    <div class="mobile-menu-overlay"></div>
    <div class="container">
        <!-- <div class="pd-ltr-10"> -->
        {{-- Pages load --}}
        @yield('main_content')
        @if(Auth::check())
        @if(Auth::user()->role == 'superadmin')
        @endif

        @endauth
        {{-- Footer --}}
    </div>
    </div>
    <!-- JS -->
    @stack('js')
    <script src="{{ url($assetPrefix.'assets/theme/vendors/scripts/core.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/vendors/scripts/script.min.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/vendors/scripts/process.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/vendors/scripts/layout-settings.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/src/plugins/apexcharts/apexcharts.min.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/vendors/scripts/dashboard.js') }}" defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/src/plugins/jvectormap/jquery-jvectormap-world-mill-en.js') }}"
        defer></script>
    <script src="{{ url($assetPrefix.'assets/theme/src/vendors/scripts/dashboard2.js') }}" defer></script>
    <!-- buttons for Export datatable (using asset() for correct URL and defer for faster loading) -->
    @if (env('MENU_SHOW') !== 'Yes')
    <script>
    // Disable Right-Click
    document.addEventListener('contextmenu', e => e.preventDefault());

    // Disable F12, Ctrl+Shift+I/J/C, Ctrl+U
    document.addEventListener('keydown', e => {
        if (
            e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key)) ||
            (e.ctrlKey && e.key === 'U')
        ) {
            e.preventDefault();
        }
    });

    // Extra: Block dragging elements
    document.addEventListener('dragstart', e => e.preventDefault());
    </script>

    <script>
    let idleTimer;

    function startIdleTimer() {
        idleTimer = setTimeout(function() {
            window.location.href = "/logout"; // logout URL
        }, 300000); // 1 minute
    }

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        startIdleTimer();
    }

    // Start timer when page loads
    window.onload = function() {
        startIdleTimer();
    };

    // Detect user activity
    document.addEventListener("mousemove", resetIdleTimer);
    document.addEventListener("keydown", resetIdleTimer);
    document.addEventListener("click", resetIdleTimer);
    </script>

    @endif



</body>

</html>