<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F4C5C">
    <title>@hasSection('title')@yield('title') | {{ $site->get('site_name') }}@else{{ $site->get('site_name') }} | {{ $site->get('site_tagline') }}@endif</title>
    <meta name="description" content="@yield('meta_description', $site->get('meta_description'))">

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">

    @include('components.navbar')

    <main id="main">
        @include('partials.flash')

        @yield('content')
    </main>

    @include('components.footer')

    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
