@php
    use App\Enums\InvoiceStatus;

    $canManageInvoices = auth()->user()->hasAnyRole(['company_admin', 'team_member']);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ __('Invoices') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Invoices list lead') }}</p>
            </div>
        </div>
    </x-slot>

    <div
        class="py-10"
        x-data="invoiceIndexSlidePanel(@js($initialPreviewPanelUrl))"
        x-init="$watch('open', v => document.documentElement.classList.toggle('overflow-hidden', v))"
        @keydown.escape.window="close()"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('invoices.index') }}" class="flex w-full flex-col gap-3 sm:flex-1 sm:flex-row sm:flex-wrap sm:items-end">
                    @if (request()->filled('preview'))
                        <input type="hidden" name="preview" value="{{ request()->query('preview') }}" />
                    @endif
                    <div class="min-w-[140px] flex-1 sm:max-w-xs">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="inv_filter_q">{{ __('Search') }}</label>
                        <input
                            id="inv_filter_q"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('Number or client') }}"
                            class="flow-input block w-full text-sm"
                        />
                    </div>
                    <div class="min-w-[140px] flex-1 sm:max-w-[11rem]">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="inv_filter_status">{{ __('Document status') }}</label>
                        <select id="inv_filter_status" name="status" class="flow-input-select block w-full text-sm">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach (InvoiceStatus::cases() as $invSt)
                                <option value="{{ $invSt->value }}" @selected(request('status') === $invSt->value)>{{ $invSt->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[140px] flex-1 sm:max-w-[11rem]">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="inv_filter_settlement">{{ __('Payment status') }}</label>
                        <select id="inv_filter_settlement" name="settlement" class="flow-input-select block w-full text-sm">
                            <option value="all" @selected(request('settlement', 'all') === 'all')>{{ __('All') }}</option>
                            <option value="paid" @selected(request('settlement') === 'paid')>{{ __('settlement.paid') }}</option>
                            <option value="partial" @selected(request('settlement') === 'partial')>{{ __('settlement.partial') }}</option>
                            <option value="unpaid" @selected(request('settlement') === 'unpaid')>{{ __('settlement.unpaid') }}</option>
                            <option value="overdue" @selected(request('settlement') === 'overdue')>{{ __('settlement.overdue') }}</option>
                        </select>
                    </div>
                    <div class="min-w-[160px] flex-1 sm:max-w-[14rem]">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="inv_filter_client">{{ __('Client') }}</label>
                        <select id="inv_filter_client" name="client_id" class="flow-input-select block w-full text-sm">
                            <option value="">{{ __('All clients') }}</option>
                            @foreach ($clients as $cl)
                                <option value="{{ $cl->id }}" @selected(request('client_id') === $cl->id)>{{ $cl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('Apply filters') }}</x-secondary-button>
                        <a href="{{ route('invoices.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Reset') }}</a>
                    </div>
                </form>
                @if ($canManageInvoices)
                    <a href="{{ route('invoices.create') }}" class="shrink-0">
                        <x-primary-button type="button" class="!normal-case inline-flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice-dollar text-sm" aria-hidden="true"></i>
                            {{ __('New invoice') }}
                        </x-primary-button>
                    </a>
                @endif
            </div>

            <div class="overflow-hidden z-101 rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/40 dark:ring-white/[0.06]">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50/95 to-white px-4 py-3 dark:border-slate-700/80 dark:from-slate-800/50 dark:to-slate-900/40">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('All invoices') }}</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Click a row to open details in the side panel.') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-400">
                                <th class="px-4 py-3 text-start">{{ __('Invoice reference column') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Document status') }}</th>
                                <th class="hidden px-4 py-3 text-start md:table-cell">{{ __('Payment status') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Client') }}</th>
                                <th class="hidden px-4 py-3 text-end sm:table-cell">{{ __('Due date') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Invoice total inc VAT column') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @forelse ($invoices as $inv)
                                @php
                                    $st = $inv->status;
                                    $badgeVariant = match ($st) {
                                        InvoiceStatus::Paid => 'success',
                                        InvoiceStatus::Overdue => 'danger',
                                        InvoiceStatus::Cancelled => 'slate',
                                        InvoiceStatus::Draft => 'slate',
                                        InvoiceStatus::Sent => 'primary',
                                    };
                                    $settleKey = $inv->paymentSettlementKey();
                                    $settleVariant = match ($settleKey) {
                                        'paid' => 'success',
                                        'partial' => 'primary',
                                        'unpaid' => 'slate',
                                        'overdue' => 'danger',
                                        'cancelled' => 'slate',
                                        default => 'slate',
                                    };
                                @endphp
                                <tr
                                    role="button"
                                    tabindex="0"
                                    data-panel-url="{{ route('invoices.preview-panel', $inv) }}"
                                    data-invoice-id="{{ $inv->id }}"
                                    @click="openRow($el)"
                                    @keydown.enter.prevent="openRow($el)"
                                    @keydown.space.prevent="openRow($el)"
                                    class="cursor-pointer transition-colors hover:bg-slate-50/90 dark:hover:bg-slate-800/25"
                                >
                                    <td class="px-4 py-3 text-start">
                                        <span class="font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $inv->number ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-start">
                                        <x-flow.badge :variant="$badgeVariant" class="text-[10px]">{{ $st->label() }}</x-flow.badge>
                                    </td>
                                    <td class="hidden px-4 py-3 text-start md:table-cell">
                                        <x-flow.badge :variant="$settleVariant" class="text-[10px]">{{ $inv->paymentSettlementLabel() }}</x-flow.badge>
                                    </td>
                                    <td class="px-4 py-3 text-start text-slate-700 dark:text-slate-300">{{ $inv->client?->name ?? '—' }}</td>
                                    <td class="hidden px-4 py-3 text-end text-xs text-slate-600 dark:text-slate-400 sm:table-cell">
                                        <span class="flowdesk-ltr-num tabular-nums">{{ $inv->due_date?->format('Y-m-d') ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end text-xs font-medium text-slate-900 dark:text-white">
                                        <span class="flowdesk-ltr-num tabular-nums">
                                            {{ flowdesk_format_minor((int) $inv->amount, flowdesk_invoice_currency($inv)) }}
                                            <span class="font-normal text-slate-500 dark:text-slate-400">{{ flowdesk_invoice_currency($inv) }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('No invoices in list yet') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Invoices list empty hint') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $invoices->links() }}</div>
        </div>

        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-slate-900/40 dark:bg-black/50"
            style="display: none;"
            @click="close()"
            aria-hidden="true"
        ></div>

        {{-- Slide-over --}}
        <div
            x-show="open"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 end-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 dark:ring-white/10"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            :aria-busy="loading"
            @click.stop
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/50">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Invoice detail') }}</p>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/90 text-slate-600 transition hover:bg-white hover:text-slate-900 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                    @click="close()"
                    aria-label="{{ __('Close panel') }}"
                >
                    <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                </button>
            </div>
            <div class="relative flex min-h-0 flex-1 flex-col">
                <div x-show="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-slate-900/80" style="display: none;">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('Working…') }}</p>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto" x-html="html"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('invoiceIndexSlidePanel', (initialPanelUrl) => ({
                    open: false,
                    loading: false,
                    html: '',
                    loadError: @json(__('Could not load invoice preview.')),
                    async init() {
                        if (initialPanelUrl) {
                            await this.loadFromUrl(initialPanelUrl);
                        }
                    },
                    async openRow(el) {
                        const url = el?.dataset?.panelUrl;
                        const id = el?.dataset?.invoiceId;
                        if (!url) {
                            return;
                        }
                        await this.loadFromUrl(url);
                        if (id) {
                            const u = new URL(window.location.href);
                            u.searchParams.set('preview', id);
                            window.history.replaceState(null, '', u);
                        }
                    },
                    async loadFromUrl(url) {
                        this.open = true;
                        this.loading = true;
                        this.html = '';
                        try {
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            const r = await fetch(url, {
                                headers: {
                                    Accept: 'text/html',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token || '',
                                },
                                credentials: 'same-origin',
                            });
                            if (!r.ok) {
                                throw new Error('fail');
                            }
                            this.html = await r.text();
                        } catch (e) {
                            this.html =
                                '<div class="p-4 text-sm text-rose-600 dark:text-rose-400">' + this.loadError + '</div>';
                        } finally {
                            this.loading = false;
                        }
                    },
                    close() {
                        this.open = false;
                        const u = new URL(window.location.href);
                        u.searchParams.delete('preview');
                        window.history.replaceState(null, '', u);
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
