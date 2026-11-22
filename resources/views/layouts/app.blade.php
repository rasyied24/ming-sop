<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
    @php
        $user = auth()->user();
        $usesDirectory = $user->hasRole(\App\Enums\UserRole::PicSop, \App\Enums\UserRole::Manager);
        $fallback = $usesDirectory ? 'sops.manage' : 'sops.index';

        $currentRoute = request()->route()?->getName();

        // Detail SOP bisa dibuka dari beberapa halaman. Menu yang menyala mengikuti asalnya (?from=).
        if ($currentRoute === 'sops.show') {
            $currentRoute = match (request()->query('from')) {
                'persetujuan' => $user->hasRole(\App\Enums\UserRole::Manager) ? 'approvals.index' : $fallback,
                'daftar' => 'sops.index',
                default => $fallback,
            };
        }

        // Detail laporan: Auditor menyorot Laporan Masuk, karyawan menyorot Laporan Saya.
        if ($currentRoute === 'reports.show') {
            $currentRoute = $user->hasRole(\App\Enums\UserRole::Auditor) ? 'reports.inbox' : 'reports.index';
        }
    @endphp

    {{-- Top bar --}}
    <header class="fixed inset-x-0 top-0 z-40 flex h-16 items-center justify-between bg-slate-800 px-4 text-white">
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-md p-1.5 hover:bg-slate-700 lg:hidden"
                    @click="sidebarOpen = ! sidebarOpen" aria-label="Buka menu">
                <x-icon name="bars-3" size="size-6" />
            </button>

            <a href="{{ route($user->role->homeRoute()) }}" wire:navigate class="flex items-center gap-2 text-lg font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-500 text-sm font-bold">M</span>
                {{ config('app.name') }}
            </a>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right leading-tight sm:block">
                <p class="text-sm font-medium">{{ $user->name }}</p>
                <p class="text-xs text-slate-300">{{ $user->role->label() }}</p>
            </div>
            <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold"
                  title="{{ $user->name }}">
                {{ $user->initials() }}
            </span>
        </div>
    </header>

    {{-- Overlay untuk layar kecil --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-20 bg-slate-900/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside class="fixed bottom-0 left-0 top-16 z-30 flex w-64 flex-col bg-slate-800 text-slate-200 transition-transform lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach ($user->role->navigation() as $item)
                @php($active = in_array($currentRoute, $item['active'], true))

                <a href="{{ route($item['route']) }}" wire:navigate
                   @class([
                       'flex items-center gap-3 rounded-md border-l-4 px-3 py-2.5 text-sm font-medium',
                       'border-brand-500 bg-slate-700 text-white' => $active,
                       'border-transparent text-slate-300 hover:bg-slate-700/60 hover:text-white' => ! $active,
                   ])>
                    <x-icon :name="$item['icon']" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-slate-700 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700/60 hover:text-white">
                    <x-icon name="logout" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Konten --}}
    <main class="pt-16 lg:pl-64">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</body>
</html>