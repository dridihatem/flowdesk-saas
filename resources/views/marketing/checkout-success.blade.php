@extends('layouts.marketing')

@section('title', config('app.name').' — '.__('marketing_checkout_success_title'))

@section('content')
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-2xl px-6 text-center sm:px-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <i class="fa-solid fa-check text-xl" aria-hidden="true"></i>
            </div>
            <h1 class="mt-6 text-2xl font-bold text-slate-900">{{ __('marketing_checkout_success_title') }}</h1>
            <p class="mt-3 text-sm text-slate-600">{{ __('marketing_checkout_success_lead', ['number' => $order->order_number]) }}</p>

            <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 text-left shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('marketing_checkout_downloads') }}</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($order->items as $item)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50/50 px-4 py-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $item->module_name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->formattedPrice() }}</p>
                            </div>
                            @if ($item->module?->zip_path)
                                <a
                                    href="{{ URL::signedRoute('marketing.order.download', ['order' => $order->id, 'module' => $item->marketplace_module_id]) }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                >
                                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                                    {{ __('Download') }}
                                </a>
                            @else
                                <span class="text-xs text-slate-500">{{ __('marketing_checkout_no_zip') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            @if (Route::has('settings.modules') && auth()->user()?->hasRole('company_admin'))
                <a href="{{ route('settings.modules') }}" class="mt-8 inline-flex rounded-md border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">{{ __('marketing_checkout_install_workspace') }}</a>
            @endif

            <a href="{{ route('marketing.modules') }}" class="mt-4 inline-flex rounded-md bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ __('marketing_cart_browse_modules') }}</a>
        </div>
    </section>
@endsection
