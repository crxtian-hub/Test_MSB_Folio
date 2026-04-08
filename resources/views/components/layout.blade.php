@props([
    'title' => config('app.name', 'Laravel'),
    'barba' => false,
    'namespace' => null,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('MSB_Favicon.svg') }}">
</head>
<body data-authenticated="{{ auth()->check() ? '1' : '0' }}">
    
    {{-- <div class="gridContainer">
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
        <div class="gridCol"></div>
    </div>
    <button class="gridButton">
        *
    </button> --}}

    @isset($persistent)
        {{ $persistent }}
    @endisset
    
    @if ($barba)
        <div data-barba="wrapper">
            <main
                data-barba="container"
                @if ($namespace) data-barba-namespace="{{ $namespace }}" @endif
            >
                {{ $slot }}
            </main>
        </div>
    @else
        {{ $slot }}
    @endif
    
    <x-copyright />
    
    
    
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/gsap.min.js"></script>
    @if ($barba)
        <script src="https://unpkg.com/@barba/core"></script>
    @endif
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
