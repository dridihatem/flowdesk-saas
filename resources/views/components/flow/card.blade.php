@props(['title' => null])
<div {{ $attributes->merge(['class' => 'rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm']) }}>
    @isset($title)
        <p class="text-sm text-flow-text-muted">{{ $title }}</p>
    @endisset
    <div @class(['mt-2' => isset($title)]) class="text-flow-text">
        {{ $slot }}
    </div>
</div>
