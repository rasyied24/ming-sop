@props(['size' => 'h-6', 'color' => 'text-[#38a1e8]'])

{{--
    Logo MING SOP. Satu-satunya sumber logo: dipakai halaman login dan top bar.
    Warna bawaan #38a1e8 (sama dengan logo di halaman login), bisa diganti lewat prop "color".
--}}
<svg {{ $attributes->class([$size, 'w-auto', $color]) }} viewBox="0 0 96 40" fill="none"
     stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M4 34 L18 8 L32 34 L46 8 L60 34 L74 8 L92 34" />
</svg>