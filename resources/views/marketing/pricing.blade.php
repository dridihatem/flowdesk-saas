@extends('layouts.marketing')

@section('title', config('app.name').' — '.__('Pricing'))
@section('meta_description', __('marketing_pricing_meta'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Pricing'),
        'title' => __('Plans for every workspace'),
        'lead' => __('marketing_pricing_hero_lead', [
            'days' => $trialDays,
            'plan' => $trialPlanName,
        ]),
        'maxWidth' => 'max-w-6xl',
        'centered' => true,
    ])

    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 pb-24 pt-12 sm:px-10 lg:px-12">
            <div class="mx-auto mb-10 max-w-3xl rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50 via-violet-50/70 to-cyan-50 px-6 py-5 text-center">
                <p class="text-sm font-semibold text-indigo-950">{{ __('marketing_pricing_trial_title', ['days' => $trialDays, 'plan' => $trialPlanName]) }}</p>
                <p class="mt-1.5 text-sm leading-relaxed text-indigo-900/85">{{ __('marketing_pricing_trial_body') }}</p>
            </div>

            <div class="mx-auto max-w-6xl">
                @include('partials.plan-pricing', [
                    'planRows' => $planRows,
                    'displayCurrency' => $displayCurrency,
                    'supportedCurrencies' => $supportedCurrencies,
                    'currencyLabels' => $currencyLabels,
                    'formAction' => route('marketing.pricing'),
                    'corporate' => true,
                    'labeledFeatures' => true,
                ])
            </div>

            <div class="mx-auto mt-20 max-w-3xl text-center">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ __('marketing_pricing_platform_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('marketing_pricing_platform_lead') }}</p>
            </div>

            <div class="mx-auto mt-10 grid max-w-6xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                    ['icon' => 'puzzle', 't' => __('marketing_feature_modules_title'), 'b' => __('marketing_feature_modules_body')],
                ] as $item)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm shadow-indigo-600/20">
                            @include('marketing.partials.feature-icon', ['name' => $item['icon']])
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $item['t'] }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $item['b'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mx-auto mt-8 max-w-2xl text-center text-xs text-slate-500">{{ __('marketing_pricing_limits_note') }}</p>

            <div class="mx-auto mt-16 max-w-2xl rounded-lg border border-slate-200 bg-white p-8 text-center">
                <p class="text-sm text-slate-600">{{ __('Need a custom bundle or invoice billing?') }}</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex rounded-md bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Create workspace') }}</a>
                    @endif
                    <a href="{{ route('marketing.contact') }}" class="inline-flex rounded-md border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">{{ __('Contact sales') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
