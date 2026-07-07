@extends('layouts.marketing')

@section('title', config('app.name').' — '.__('marketing_checkout_pending_title'))
@section('meta_description', __('marketing_checkout_pending'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Checkout'),
        'title' => __('marketing_checkout_pending_title'),
        'lead' => __('marketing_checkout_pending_lead', ['number' => $order->order_number]),
        'maxWidth' => 'max-w-4xl',
        'centered' => true,
    ])

    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-4xl px-6 py-12 pb-24 sm:px-10 lg:px-12">
            @if (session('status'))
                <div class="mb-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <i class="fa-solid fa-clock text-xl" aria-hidden="true"></i>
            </div>

            <div class="mt-10 grid gap-8 lg:grid-cols-5">
                <div class="lg:col-span-3 space-y-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                            <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                            {{ __('marketing_checkout_bank_instructions') }}
                        </h2>

                        @include('marketing.partials.payment-reference', [
                            'reference' => $order->paymentReferenceLabel(),
                            'copyId' => 'pending-payment-reference',
                        ])

                        @if (collect($bankDetails)->filter()->isNotEmpty())
                            <dl class="mt-5 space-y-3 text-sm text-amber-950">
                                @if ($bankDetails['holder'] !== '')
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('admin_payment_bank_account_holder') }}</dt>
                                        <dd class="mt-0.5 font-medium">{{ $bankDetails['holder'] }}</dd>
                                    </div>
                                @endif
                                @if ($bankDetails['bank_name'] !== '')
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('admin_payment_bank_name') }}</dt>
                                        <dd class="mt-0.5 font-medium">{{ $bankDetails['bank_name'] }}</dd>
                                    </div>
                                @endif
                                @if ($bankDetails['rib'] !== '')
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('admin_payment_bank_rib') }}</dt>
                                        <dd class="mt-0.5 font-mono text-base font-semibold tracking-wide">{{ $bankDetails['rib'] }}</dd>
                                    </div>
                                @endif
                                @if ($bankDetails['bic'] !== '')
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('admin_payment_bank_bic') }}</dt>
                                        <dd class="mt-0.5 font-mono font-medium">{{ $bankDetails['bic'] }}</dd>
                                    </div>
                                @endif
                                @if ($bankDetails['extra'] !== '')
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-amber-800/80">{{ __('admin_payment_bank_extra_instructions') }}</dt>
                                        <dd class="mt-0.5 whitespace-pre-line">{{ $bankDetails['extra'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        @elseif (is_string($bankInstructions) && trim($bankInstructions) !== '')
                            <div class="prose prose-sm mt-5 max-w-none whitespace-pre-line text-amber-950">{{ $bankInstructions }}</div>
                        @endif

                        <p class="mt-5 text-xs text-amber-800">{{ __('marketing_checkout_pay_bank_help') }}</p>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Order summary') }}</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($order->items as $item)
                                <li class="flex justify-between gap-3 text-sm">
                                    <span class="text-slate-700">{{ $item->module_name }}</span>
                                    <span class="shrink-0 font-semibold tabular-nums text-slate-900">{{ $item->formattedPrice() }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-5 flex justify-between border-t border-slate-100 pt-4">
                            <span class="font-semibold text-slate-900">{{ __('Total') }}</span>
                            <span class="text-lg font-bold tabular-nums text-slate-900">{{ $order->formattedTotal() }}</span>
                        </div>

                        <a href="{{ route('marketing.modules') }}" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-800">
                            <i class="fa-solid fa-puzzle-piece text-xs text-indigo-500" aria-hidden="true"></i>
                            {{ __('marketing_cart_browse_modules') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
