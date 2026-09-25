<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'SJ LegalSuite') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <x-corporate-fonts />
        @vite(['resources/js/app.js'])
    </head>
    <body class="font-sans text-sj-blue antialiased bg-gradient-to-br from-slate-50 via-white to-sj-blue/5 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 py-10">
            <div class="text-center mb-8">
                <a href="/" class="inline-block">
                    <x-application-logo class="h-16 w-auto mx-auto" />
                </a>
                <p class="mt-2 text-sm font-semibold text-sj-blue tracking-wide">SJ LegalSuite</p>
                <p class="text-[11px] text-slate-500 uppercase tracking-widest">Aporte de documentación · Licitaciones</p>
            </div>
            {{ $slot }}
        </div>
    </body>
</html>
