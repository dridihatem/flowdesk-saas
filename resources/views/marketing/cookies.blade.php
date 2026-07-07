@extends('layouts.marketing')

@section('title', __('Cookie policy').' — '.config('app.name'))
@section('meta_description', __('Marketing cookies meta'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Legal'),
        'title' => __('Cookie policy'),
        'meta' => __('Legal last updated', ['date' => now()->translatedFormat('j F Y')]),
        'lead' => __('Marketing cookies lead'),
    ])

    <section class="mx-auto max-w-4xl px-6 py-16 sm:px-10 lg:px-12">
        <div class="space-y-8">
            <div class="rounded-2xl border border-slate-200/90 bg-white p-8 shadow-sm ring-1 ring-slate-900/[0.03]">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Marketing cookies s1 title') }}</h2>
                <p class="mt-3 text-slate-600 leading-relaxed">{{ __('Marketing cookies s1 body') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/90 bg-white p-8 shadow-sm ring-1 ring-slate-900/[0.03]">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Marketing cookies s2 title') }}</h2>
                <p class="mt-3 text-slate-600 leading-relaxed">{{ __('Marketing cookies s2 body') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200/90 bg-white p-8 shadow-sm ring-1 ring-slate-900/[0.03]">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Marketing cookies s3 title') }}</h2>
                <p class="mt-3 text-slate-600 leading-relaxed">{{ __('Marketing cookies s3 body') }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-8 ring-1 ring-amber-900/[0.06]">
                <p class="text-sm text-amber-950/90 leading-relaxed">{{ __('Marketing legal disclaimer') }}</p>
            </div>
        </div>

        <p class="mt-10">
            <a href="{{ route('home') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('Legal back home') }}</a>
        </p>
    </section>
@endsection
