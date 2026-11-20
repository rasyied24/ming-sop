<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-800">{{ config('app.name') }}</h1>
            <p class="text-sm text-slate-500">Sistem Pengelolaan SOP Internal</p>
        </div>

        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            {{ $slot }}
        </div>
    </div>
</body>
</html>