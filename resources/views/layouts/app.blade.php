<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    @php($user = auth()->user())

    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-8">
                <a href="{{ route($user->role->homeRoute()) }}" wire:navigate
                   class="text-lg font-bold text-indigo-600">
                    {{ config('app.name') }}
                </a>

                <div class="flex gap-1">
                    @foreach ($user->role->navigation() as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate
                           @class([
                               'rounded-md px-3 py-2 text-sm font-medium',
                               'bg-indigo-50 text-indigo-700' => request()->routeIs($item['route']),
                               'text-slate-600 hover:bg-slate-100' => ! request()->routeIs($item['route']),
                           ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right leading-tight">
                    <p class="text-sm font-medium">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">{{ $user->role->label() }} · {{ $user->department }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>