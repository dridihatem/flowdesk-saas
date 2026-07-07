@extends('layouts.marketing')

@section('title', config('app.name').' — '.__('Welcome meta title'))
@section('meta_description', __('Welcome meta description'))

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-cyan-50">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_20%_0%,rgba(79,70,229,0.12),transparent)]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -end-24 top-0 h-96 w-96 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -start-24 bottom-0 h-80 w-80 rounded-full bg-cyan-200/50 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-6xl px-6 py-16 sm:px-10 lg:px-12 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/80 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-indigo-700 shadow-sm backdrop-blur">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500" aria-hidden="true"></span>
                        {{ __('Welcome hero badge') }}
                    </div>
                    <h1 class="mt-6 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.35rem] lg:leading-[1.1]">
                        {{ __('Run your agency from') }}
                        <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-cyan-500 bg-clip-text text-transparent">{{ __('one workspace') }}</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                        {{ __('Welcome hero lead') }}
                    </p>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-500">
                        {{ __('Welcome hero sublead') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center rounded-md bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-700 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:from-indigo-500 hover:via-violet-500 hover:to-indigo-600"
                            >{{ __('Create workspace') }}</a>
                        @endif
                        <a
                            href="{{ route('marketing.pricing') }}"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-7 py-3.5 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50"
                        >{{ __('View pricing') }}</a>
                    </div>
                    @if (Route::has('login'))
                        <p class="mt-5 text-sm text-slate-500">
                            {{ __('Already have an account?') }}
                            <a href="{{ route('login') }}" class="font-semibold text-slate-800 transition hover:text-blue-800">{{ __('Sign in') }}</a>
                        </p>
                    @endif
                </div>

                <div class="relative">
                    <div class="rounded-2xl border border-indigo-100/80 bg-gradient-to-br from-white via-indigo-50/40 to-cyan-50/60 p-6 shadow-xl shadow-indigo-900/[0.08] sm:p-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Welcome hero panel eyebrow') }}</p>
                        <p class="mt-3 text-xl font-semibold text-slate-900">{{ __('Welcome hero panel title') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Welcome hero panel body') }}</p>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['label' => __('Welcome hero stat1 label'), 'value' => __('Welcome hero stat1 value')],
                                ['label' => __('Welcome hero stat2 label'), 'value' => __('Welcome hero stat2 value')],
                                ['label' => __('Welcome hero stat3 label'), 'value' => __('Welcome hero stat3 value')],
                                ['label' => __('Welcome hero stat4 label'), 'value' => __('Welcome hero stat4 value')],
                            ] as $stat)
                                <div class="rounded-lg border border-slate-200/80 bg-white p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $stat['value'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50/80 px-4 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-indigo-600 to-violet-600 text-white">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </span>
                            <p class="text-sm font-medium text-blue-900">{{ __('Welcome hero panel ai note') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- AI workflow --}}
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 py-20 sm:px-10 lg:px-12">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Welcome how eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('Welcome how title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-600">{{ __('Welcome how lead') }}</p>
            </div>

            <ol class="relative mx-auto mt-16 grid max-w-5xl gap-8 lg:grid-cols-3">
                <div class="pointer-events-none absolute inset-x-0 top-10 hidden h-px bg-slate-200 lg:block" aria-hidden="true"></div>
                @foreach ([
                    ['n' => '01', 't' => __('Welcome how step1 title'), 'b' => __('Welcome how step1 body'), 'ai' => __('Welcome how step1 ai')],
                    ['n' => '02', 't' => __('Welcome how step2 title'), 'b' => __('Welcome how step2 body'), 'ai' => __('Welcome how step2 ai')],
                    ['n' => '03', 't' => __('Welcome how step3 title'), 'b' => __('Welcome how step3 body'), 'ai' => __('Welcome how step3 ai')],
                ] as $step)
                    <li class="relative rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="flex items-center gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-slate-900 text-xs font-bold text-white">{{ $step['n'] }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-800">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                {{ __('AI') }}
                            </span>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-slate-900">{{ $step['t'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['b'] }}</p>
                        <p class="mt-4 border-t border-slate-100 pt-4 text-xs font-medium leading-relaxed text-blue-900/80">{{ $step['ai'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Capabilities --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-20 sm:px-10 lg:px-12">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Platform') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('Welcome section features title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-600">{{ __('Welcome section features lead') }}</p>
            </div>

            <div class="mx-auto mt-14 grid max-w-5xl gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'building', 'title' => __('Welcome feature multitenant title'), 'body' => __('Welcome feature multitenant body')],
                    ['icon' => 'doc', 'title' => __('Welcome feature proposals title'), 'body' => __('Welcome feature proposals body')],
                    ['icon' => 'bolt', 'title' => __('Welcome feature automation title'), 'body' => __('Welcome feature ai body')],
                    ['icon' => 'chart', 'title' => __('Welcome feature analytics title'), 'body' => __('Welcome feature analytics body')],
                    ['icon' => 'mail', 'title' => __('Welcome feature leads title'), 'body' => __('Welcome feature leads body')],
                    ['icon' => 'users', 'title' => __('Welcome feature providers title'), 'body' => __('Welcome feature providers body')],
                ] as $card)
                    <div class="rounded-lg border border-slate-200 bg-white p-6 text-start transition hover:border-slate-300">
                        <div class="flex h-9 w-9 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                            @include('marketing.partials.feature-icon', ['name' => $card['icon']])
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ $card['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $card['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-slate-900">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center sm:px-10 lg:px-12">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('Welcome cta title') }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-slate-300">{{ __('Welcome cta body') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-md bg-white px-7 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">{{ __('Create workspace') }}</a>
                @endif
                <a href="{{ route('marketing.contact') }}" class="inline-flex items-center rounded-md border border-slate-600 px-7 py-3 text-sm font-semibold text-white transition hover:border-slate-500 hover:bg-slate-800">{{ __('Contact sales') }}</a>
            </div>
        </div>
    </section>
@endsection
