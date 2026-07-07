@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'flow-nav-link-active'
        : 'flow-nav-link-inactive';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
