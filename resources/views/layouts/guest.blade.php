<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

        {{-- Panel kiri (branding) — disembunyikan di layar kecil --}}
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 text-white lg:flex lg:flex-col lg:justify-between">
            {{-- Pola garis halus di latar --}}
            <svg class="pointer-events-none absolute inset-0 h-full w-full text-white/[0.06]" aria-hidden="true">
                <defs>
                    <pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">
                        <path d="M48 0H0V48" fill="none" stroke="currentColor" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>

            <div class="relative flex flex-1 flex-col items-center justify-center px-12">
                {{-- Logo (ganti dengan logo asli Anda bila ada) --}}
                <x-logo size="h-12" />

                <h1 class="mt-5 text-4xl font-bold tracking-tight">MING SOP</h1>
                <p class="mt-2 text-sm text-slate-400">Aplikasi Internal Pengelolaan SOP Perusahaan</p>

                <ul class="mt-14 space-y-6 text-left">
                    <li class="flex items-start gap-4">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold">SOP Lifecycle Management</p>
                            <p class="text-xs text-slate-400">Draf, Tinjauan, Persetujuan, Publikasi</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold">Audit Trail &amp; Kepatuhan</p>
                            <p class="text-xs text-slate-400">Pelacakan versi &amp; konfirmasi baca karyawan</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold">Pelaporan &amp; Evaluasi</p>
                            <p class="text-xs text-slate-400">Monitoring kepatuhan &amp; pelaporan kendala</p>
                        </div>
                    </li>
                </ul>
            </div>

            <p class="relative px-12 pb-8 text-xs text-slate-400">
                &copy; {{ date('Y') }} MING SOP Center. Semua Hak Dilindungi.
            </p>
        </aside>

        {{-- Panel kanan (form) --}}
        <main class="relative flex flex-col px-4 py-6 sm:px-8">
            <div class="flex flex-1 items-center justify-center py-8">
                <div class="w-full max-w-md">
                    {{-- Logo ringkas untuk layar kecil, saat panel kiri disembunyikan --}}
                    <p class="mb-6 text-center text-xl font-bold text-slate-800 lg:hidden">MING SOP Hub</p>

                    <div class="rounded-xl bg-white p-8 shadow-lg shadow-slate-200/70 ring-1 ring-slate-200">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>