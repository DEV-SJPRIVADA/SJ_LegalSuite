<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/logo solo.png') }}">

        <title>{{ config('app.name', 'SJ LegalSuite') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-200">
            <div class="flex flex-col items-center">
                <a href="/" wire:navigate class="block">
                    <x-application-logo class="h-20 w-auto" />
                </a>
                <div class="mt-3 text-center">
                    <p class="text-sm font-semibold text-slate-700 tracking-wide">SJ LegalSuite</p>
                    <p class="text-[11px] text-slate-500 uppercase tracking-widest">Gestión jurídica disciplinaria</p>
                </div>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-lg overflow-hidden sm:rounded-xl ring-1 ring-slate-200">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
