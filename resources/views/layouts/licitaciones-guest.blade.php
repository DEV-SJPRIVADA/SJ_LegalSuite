<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'SJ LegalSuite') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <div class="text-center mb-8">
                <a href="/" class="inline-block">
                    <x-application-logo class="h-16 w-auto mx-auto" />
                </a>
                <p class="mt-2 text-sm font-semibold text-slate-700 tracking-wide">SJ LegalSuite</p>
                <p class="text-[11px] text-slate-500 uppercase tracking-widest">Aporte de documentación · Licitaciones</p>
            </div>
            {{ $slot }}
        </div>
    </body>
</html>
