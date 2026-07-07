@extends('layouts.marketing')

@section('title', __('Features').' — '.config('app.name'))
@section('meta_description', __('Marketing features meta'))

@section('content')
    {{-- Hero --}}
    @include('marketing.partials.hero', [
        'eyebrow' => __('Product'),
        'title' => __('Everything in'),
        'titleAccent' => __('one workspace'),
        'lead' => __('Marketing features lead'),
        'sub' => __('Marketing features ai note'),
    ])

    {{-- All features --}}
    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 py-16 sm:px-10 lg:px-12">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('marketing_features_all_title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-600">{{ __('marketing_features_all_lead') }}</p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'users', 't' => __('Clients & projects'), 'b' => __('Marketing features clients')],
                    ['icon' => 'doc', 't' => __('Proposals & e-sign'), 'b' => __('Marketing features proposals')],
                    ['icon' => 'coins', 't' => __('Invoicing & payments'), 'b' => __('Marketing features invoices')],
                    ['icon' => 'mail', 't' => __('Forms & lead inbox'), 'b' => __('Marketing features forms')],
                    ['icon' => 'building', 't' => __('Providers & commissions'), 'b' => __('Marketing features providers')],
                    ['icon' => 'chart', 't' => __('Analytics & reporting'), 'b' => __('Marketing features analytics')],
                    ['icon' => 'mail', 't' => __('marketing_feature_email_title'), 'b' => __('marketing_feature_email_body')],
                    ['icon' => 'bolt', 't' => __('marketing_feature_ai_title'), 'b' => __('marketing_feature_ai_body')],
                    ['icon' => 'calendar', 't' => __('marketing_feature_calendar_title'), 'b' => __('marketing_feature_calendar_body')],
                    ['icon' => 'portal', 't' => __('marketing_feature_portal_title'), 'b' => __('marketing_feature_portal_body')],
                    ['icon' => 'chat', 't' => __('marketing_feature_support_title'), 'b' => __('marketing_feature_support_body')],
                    ['icon' => 'users', 't' => __('marketing_feature_team_title'), 'b' => __('marketing_feature_team_body')],
                    ['icon' => 'palette', 't' => __('marketing_feature_branding_title'), 'b' => __('marketing_feature_branding_body')],
                    ['icon' => 'shield', 't' => __('marketing_feature_security_title'), 'b' => __('marketing_feature_security_body')],
                    ['icon' => 'puzzle', 't' => __('marketing_feature_modules_title'), 'b' => __('marketing_feature_modules_body')],
                ] as $item)
                    <div class="group rounded-xl border border-slate-200 bg-white p-6 text-start shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm shadow-indigo-600/20">
                            @include('marketing.partials.feature-icon', ['name' => $item['icon']])
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $item['t'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['b'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="inline-flex rounded-md bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:from-indigo-500 hover:via-violet-500 hover:to-indigo-600">{{ __('Create workspace') }}</a>
                <a href="{{ route('marketing.about') }}" class="inline-flex rounded-md border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">{{ __('About us') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ — how it works --}}
    <section class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-3xl px-6 py-16 sm:px-10 lg:px-12 lg:py-20">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-700">{{ __('marketing_faq_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ __('marketing_faq_title') }}</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-600">{{ __('marketing_faq_lead') }}</p>
            </div>

            <div class="mt-10 space-y-3">
                @foreach ([
                    ['q' => __('marketing_faq_q1'), 'a' => __('marketing_faq_a1')],
                    ['q' => __('marketing_faq_q2'), 'a' => __('marketing_faq_a2')],
                    ['q' => __('marketing_faq_q3'), 'a' => __('marketing_faq_a3')],
                    ['q' => __('marketing_faq_q4'), 'a' => __('marketing_faq_a4')],
                    ['q' => __('marketing_faq_q5'), 'a' => __('marketing_faq_a5')],
                    ['q' => __('marketing_faq_q6'), 'a' => __('marketing_faq_a6')],
                    ['q' => __('marketing_faq_q7'), 'a' => __('marketing_faq_a7')],
                ] as $faq)
                    <details class="group rounded-xl border border-slate-200 bg-white shadow-sm transition open:border-indigo-200 open:shadow-md">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-4 text-start text-sm font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">
                            {{ $faq['q'] }}
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-open:rotate-180 group-open:bg-indigo-50 group-open:text-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </span>
                        </summary>
                        <p class="border-t border-slate-100 px-6 py-4 text-sm leading-relaxed text-slate-600">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>

            <div class="mt-10 rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-violet-50/60 to-cyan-50 p-6 text-center">
                <p class="text-sm font-medium text-slate-700">{{ __('marketing_faq_more_help') }}</p>
                <a href="{{ route('marketing.contact') }}" class="mt-3 inline-flex rounded-md bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Contact sales') }}</a>
            </div>
        </div>
    </section>
@endsection
