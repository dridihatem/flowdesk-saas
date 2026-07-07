@extends('layouts.marketing')

@section('title', __('About us').' — '.config('app.name'))
@section('meta_description', __('Marketing about meta'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Company'),
        'title' => __('About us'),
        'lead' => __('Marketing about lead'),
        'sub' => __('Marketing about growth lead'),
        'maxWidth' => 'max-w-6xl',
    ])

    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 py-16 sm:px-10 lg:px-12">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Marketing about growth eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ __('Marketing about growth title') }}</h2>
            </div>

            <div class="mx-auto mt-12 grid max-w-5xl gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-8">
                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-blue-50 text-blue-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ __('Marketing about small title') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('Marketing about small body') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-8">
                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ __('Marketing about large title') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('Marketing about large body') }}</p>
                </div>
            </div>

            <div class="mx-auto mt-10 grid max-w-5xl gap-4 sm:grid-cols-3">
                @foreach ([
                    __('Marketing about growth stat1'),
                    __('Marketing about growth stat2'),
                    __('Marketing about growth stat3'),
                ] as $stat)
                    <div class="rounded-lg border border-slate-200 bg-white px-5 py-4 text-center text-sm font-medium text-slate-700">
                        {{ $stat }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-6 py-16 sm:px-10 lg:px-12">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-8">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Our mission') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('Marketing about mission') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-8">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Our vision') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('Marketing about vision') }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900 p-8 text-white">
                    <h2 class="text-lg font-semibold">{{ __('Our clients') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ __('Marketing about clients') }}</p>
                </div>
            </div>

            <div class="mt-12 flex flex-wrap gap-3">
                <a href="{{ route('marketing.contact') }}" class="inline-flex rounded-md bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Talk to us') }}</a>
                <a href="{{ route('register') }}" class="inline-flex rounded-md border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">{{ __('Start a workspace') }}</a>
            </div>
        </div>
    </section>
@endsection
