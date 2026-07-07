@props([
    'mod',
    'displayCurrency',
    'catalogQuery' => [],
])

@php
    $detailUrl = route('modules.show', array_merge(['slug' => $mod->slug], $catalogQuery));
    $heroImage = $mod->coverUrl() ?: $mod->imageUrl();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
    <a href="{{ $detailUrl }}" class="relative block overflow-hidden">
        @if ($heroImage)
            <div class="aspect-[16/9] overflow-hidden bg-slate-100">
                <img
                    src="{{ $heroImage }}"
                    alt="{{ $mod->name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    loading="lazy"
                />
            </div>
        @else
            <div class="flex aspect-[16/9] flex-col items-center justify-center gap-3 bg-gradient-to-br from-indigo-50 via-white to-slate-100 text-indigo-600">
                @include('marketing.partials.feature-icon', ['name' => $mod->icon ?: 'puzzle', 'class' => 'h-14 w-14'])
                <span class="text-xs font-semibold uppercase tracking-widest text-indigo-400/80">{{ $mod->category->label() }}</span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <div class="flex items-start gap-3">
            @if ($mod->imageUrl())
                <img src="{{ $mod->imageUrl() }}" alt="" class="h-11 w-11 shrink-0 rounded-xl border border-slate-200 object-cover shadow-sm" loading="lazy" />
            @else
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                    @include('marketing.partials.feature-icon', ['name' => $mod->icon ?: 'puzzle', 'class' => 'h-6 w-6'])
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-slate-900">
                    <a href="{{ $detailUrl }}" class="hover:text-indigo-700">{{ $mod->name }}</a>
                </h3>
                <p class="mt-0.5 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $mod->category->label() }}</p>
            </div>
        </div>

        @if ($mod->description)
            <p class="mt-3 flex-1 text-sm leading-relaxed text-slate-600 line-clamp-2">{{ $mod->description }}</p>
        @endif

        @if ($mod->featureList() !== [])
            <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                @foreach (array_slice($mod->featureList(), 0, 3) as $bullet)
                    <li class="flex gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-xs text-indigo-500" aria-hidden="true"></i>
                        <span>{{ $bullet }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-5 border-t border-slate-100 pt-4">
            <p class="text-2xl font-bold tabular-nums text-slate-900">{{ $mod->formattedDisplayPrice($displayCurrency) }}</p>
            <p class="mt-0.5 text-xs font-medium uppercase tracking-wide text-slate-500">{{ $mod->billing_period->label() }}</p>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <a href="{{ $detailUrl }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-800">
                    <i class="fa-solid fa-circle-info text-xs text-indigo-500" aria-hidden="true"></i>
                    {{ __('marketing_modules_view_details') }}
                </a>
                <form method="POST" action="{{ route('marketing.cart.add', $mod) }}">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $displayCurrency }}">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        <i class="fa-solid fa-cart-plus text-xs" aria-hidden="true"></i>
                        {{ __('marketing_modules_add_to_cart') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</article>
