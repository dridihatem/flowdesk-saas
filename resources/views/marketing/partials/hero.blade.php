@php
    $heroCentered = $centered ?? true;
    $heroMax = $maxWidth ?? 'max-w-4xl';
@endphp
<section class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-cyan-50">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_30%_0%,rgba(79,70,229,0.12),transparent)]" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -end-24 -top-10 h-80 w-80 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -start-24 bottom-0 h-64 w-64 rounded-full bg-cyan-200/40 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500" aria-hidden="true"></div>

    <div @class(['relative mx-auto px-6 py-16 sm:px-10 lg:px-12 lg:py-20', $heroMax, 'text-center' => $heroCentered])>
        @if (! empty($eyebrow))
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
            {{ $title }}@if (! empty($titleAccent)) <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500 bg-clip-text text-transparent">{{ $titleAccent }}</span>@endif
        </h1>
        @if (! empty($meta))
            <p class="mt-2 text-sm text-slate-500">{{ $meta }}</p>
        @endif
        @if (! empty($lead))
            <p @class(['mt-5 max-w-2xl text-lg leading-relaxed text-slate-600', 'mx-auto' => $heroCentered])>{{ $lead }}</p>
        @endif
        @if (! empty($sub))
            <p @class(['mt-3 max-w-2xl text-sm leading-relaxed text-slate-500', 'mx-auto' => $heroCentered])>{{ $sub }}</p>
        @endif
    </div>
</section>
