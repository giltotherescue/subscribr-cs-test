<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('assessment.title') }}</title>

    {{-- Fonts: Roboto Mono for headings (matches Subscribr), Roboto for body --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen antialiased">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>

    <footer class="assessment-footer mt-16 py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-sand-500">
                &copy; {{ date('Y') }} Subscribr
            </p>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
