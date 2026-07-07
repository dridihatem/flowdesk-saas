<x-admin-layout :title="__('admin_marketplace_orders_title')">
    <div class="mb-6">
        <a href="{{ route('admin.marketplace-orders.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('admin_marketplace_orders_title') }}</span>
        </a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    <x-flow.page-header
        :title="$order->order_number"
        :description="__('admin_marketplace_order_detail_intro', ['email' => $order->customer_email])"
    />

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Customer') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">{{ __('Name') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('Email') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $order->customer_email }}</dd>
                    </div>
                    @if ($order->customer_company)
                        <div>
                            <dt class="text-slate-500">{{ __('Company') }}</dt>
                            <dd class="font-medium text-slate-900">{{ $order->customer_company }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500">{{ __('Date') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $order->created_at?->translatedFormat('j F Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Modules') }}</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div>
                                <p class="font-medium text-slate-900">{{ $item->module_name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->module_slug }} · {{ __('marketplace_module_billing.'.$item->billing_period) }}</p>
                            </div>
                            <p class="font-semibold tabular-nums text-slate-900">{{ $item->formattedPrice() }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex justify-between border-t border-slate-100 pt-4 text-base font-bold text-slate-900">
                    <span>{{ __('Total') }}</span>
                    <span>{{ $order->formattedTotal() }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-indigo-800">{{ __('admin_marketplace_order_payment_reference') }}</h2>
                @include('marketing.partials.payment-reference', [
                    'reference' => $order->paymentReferenceLabel(),
                    'copyId' => 'admin-order-payment-reference',
                ])
                <p class="mt-3 text-xs text-indigo-800">{{ __('admin_marketplace_order_payment_reference_help') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</h2>
                <p class="mt-3 text-lg font-semibold text-slate-900">{{ $order->status->label() }}</p>
                @if ($order->paid_at)
                    <p class="mt-1 text-xs text-slate-500">{{ __('admin_marketplace_order_paid_at', ['date' => $order->paid_at->translatedFormat('j F Y H:i')]) }}</p>
                @endif
                @if (is_array($order->metadata) && ($order->metadata['payment_method'] ?? null) === 'bank')
                    <p class="mt-3 text-sm text-slate-600">{{ __('admin_marketplace_order_payment_bank') }}</p>
                @endif

                @if (! $order->isPaid())
                    <form method="POST" action="{{ route('admin.marketplace-orders.status', $order) }}" class="mt-6 space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ __('admin_marketplace_order_mark_paid') }}
                        </button>
                    </form>
                    @if ($order->status !== \App\Enums\MarketplaceOrderStatus::Cancelled)
                        <form method="POST" action="{{ route('admin.marketplace-orders.status', $order) }}" class="mt-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('admin_marketplace_order_cancel') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
