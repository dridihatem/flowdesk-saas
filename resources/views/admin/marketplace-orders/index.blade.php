<x-admin-layout :title="__('admin_marketplace_orders_title')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>
    </div>

    <x-flow.page-header :title="__('admin_marketplace_orders_title')" :description="__('admin_marketplace_orders_intro')" />

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label for="reference" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin_marketplace_orders_filter_reference') }}</label>
            <input
                id="reference"
                name="reference"
                type="search"
                value="{{ $selectedReference }}"
                placeholder="{{ __('admin_marketplace_orders_filter_reference_placeholder') }}"
                class="mt-1 min-w-[14rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono"
            />
        </div>
        <div>
            <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
            <select id="status" name="status" class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">{{ __('admin_marketplace_orders_filter_all') }}</option>
                @foreach ($statuses as $statusCase)
                    <option value="{{ $statusCase->value }}" @selected($selectedStatus === $statusCase->value)>{{ $statusCase->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
            {{ __('Search') }}
        </button>
        @if ($selectedReference !== '' || $selectedStatus !== '')
            <a href="{{ route('admin.marketplace-orders.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">{{ __('Clear filters') }}</a>
        @endif
    </form>

    @if ($orders->isEmpty())
        <p class="mt-6 text-sm text-slate-600">{{ __('admin_marketplace_orders_empty') }}</p>
    @else
        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-3 text-start">{{ __('Order') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_marketplace_order_payment_reference') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Customer') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Status') }}</th>
                        <th class="px-3 py-3 text-end">{{ __('Total') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Date') }}</th>
                        <th class="px-3 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $row)
                        <tr>
                            <td class="px-3 py-3 text-start">
                                <p class="font-mono text-xs font-semibold text-slate-900">{{ $row->order_number }}</p>
                                <p class="text-xs text-slate-500">{{ trans_choice('marketing_cart_items_count', $row->items_count, ['count' => $row->items_count]) }}</p>
                            </td>
                            <td class="px-3 py-3 text-start">
                                <p class="font-mono text-xs font-bold text-indigo-700">{{ $row->paymentReferenceLabel() }}</p>
                            </td>
                            <td class="px-3 py-3 text-start">
                                <p class="font-medium text-slate-900">{{ $row->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $row->customer_email }}</p>
                            </td>
                            <td class="px-3 py-3 text-start">
                                @php
                                    $statusClass = match ($row->status) {
                                        \App\Enums\MarketplaceOrderStatus::Paid => 'bg-emerald-100 text-emerald-800',
                                        \App\Enums\MarketplaceOrderStatus::Pending => 'bg-amber-100 text-amber-800',
                                        \App\Enums\MarketplaceOrderStatus::Cancelled => 'bg-slate-100 text-slate-600',
                                        default => 'bg-rose-100 text-rose-800',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $row->status->label() }}</span>
                            </td>
                            <td class="px-3 py-3 text-end font-semibold"><span class="flowdesk-ltr-num tabular-nums font-semibold">{{ $row->formattedTotal() }}</span></td>
                            <td class="px-3 py-3 text-slate-600 text-start">{{ $row->created_at?->translatedFormat('j M Y H:i') }}</td>
                            <td class="px-3 py-3 text-end">
                                <a href="{{ route('admin.marketplace-orders.show', $row) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-admin-layout>
