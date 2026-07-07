@extends('layouts.marketing')

@section('title', config('app.name').' — '.$module->name)
@section('meta_description', Str::limit(strip_tags($module->description ?? $module->name), 160))

@section('content')
    <section class="bg-slate-50 pb-24">
        <div class="relative overflow-hidden border-b border-slate-200 bg-white">
            @if ($module->coverUrl())
                <div class="absolute inset-0">
                    <img src="{{ $module->coverUrl() }}" alt="" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-900/40 to-slate-900/20"></div>
                </div>
            @else
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-slate-800 to-slate-900"></div>
            @endif

            <div class="relative mx-auto max-w-6xl px-6 py-16 sm:px-10 lg:px-12 lg:py-20">
                <a href="{{ route('marketing.modules', $catalogQuery) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                    <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                    {{ __('marketing_modules_back_catalog') }}
                </a>

                <div class="mt-8 flex flex-wrap items-end gap-6">
                    @if ($module->imageUrl())
                        <img src="{{ $module->imageUrl() }}" alt="{{ $module->name }}" class="h-20 w-20 rounded-2xl border-2 border-white/30 object-cover shadow-lg sm:h-24 sm:w-24" />
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-3xl text-white shadow-lg sm:h-24 sm:w-24">
                            @include('marketing.partials.feature-icon', ['name' => $module->icon ?: 'puzzle'])
                        </div>
                    @endif
                    <div class="min-w-0 flex-1 text-white">
                        <p class="text-xs font-semibold uppercase tracking-widest text-white/70">{{ $module->category->label() }}</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $module->name }}</h1>
                        @if ($module->description)
                            <p class="mt-3 max-w-3xl text-base leading-relaxed text-white/90">{{ $module->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-6 pt-10 sm:px-10 lg:px-12">
            <div class="grid gap-10 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-10">
                    @if ($module->detail_content)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <h2 class="text-lg font-semibold text-slate-900">{{ __('marketing_modules_detail_overview') }}</h2>
                            <div class="prose prose-slate mt-4 max-w-none text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $module->detail_content }}</div>
                        </div>
                    @endif

                    @if ($module->featureList() !== [])
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <h2 class="text-lg font-semibold text-slate-900">{{ __('marketing_modules_detail_features') }}</h2>
                            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                                @foreach ($module->featureList() as $bullet)
                                    <li class="flex gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-4 py-3 text-sm text-slate-700">
                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600" aria-hidden="true">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                        </span>
                                        <span>{{ $bullet }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! $module->detail_content && $module->featureList() === [])
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center">
                            <p class="text-sm text-slate-600">{{ __('marketing_modules_detail_empty') }}</p>
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-1">
                    <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Price') }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $module->formattedDisplayPrice($displayCurrency) }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $module->billing_period->label() }}</p>

                        <form method="POST" action="{{ route('marketing.cart.add', $module) }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="currency" value="{{ $displayCurrency }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
                                {{ __('marketing_modules_add_to_cart') }}
                            </button>
                        </form>

                        <a href="{{ route('marketing.contact') }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-800">
                            <i class="fa-regular fa-envelope text-sm text-indigo-500" aria-hidden="true"></i>
                            {{ __('Contact sales') }}
                        </a>

                        <dl class="mt-8 space-y-3 border-t border-slate-100 pt-6 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ __('Category') }}</dt>
                                <dd class="font-medium text-slate-900">{{ $module->category->label() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ __('Display currency') }}</dt>
                                <dd class="font-medium text-slate-900">{{ $currencyLabels[$displayCurrency] ?? $displayCurrency }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
