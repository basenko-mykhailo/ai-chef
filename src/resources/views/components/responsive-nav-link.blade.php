@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-accent text-start text-base font-medium text-cream bg-white/10 focus:outline-none focus:text-cream focus:bg-white/15 focus:border-brand-accent-light transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-cream/75 hover:text-cream hover:bg-white/5 hover:border-cream/30 focus:outline-none focus:text-cream focus:bg-white/5 focus:border-cream/30 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
