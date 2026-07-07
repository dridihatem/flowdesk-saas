@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'flow-responsive-nav-active'
        : 'flow-responsive-nav-inactive';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
