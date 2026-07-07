@php
    $currency = $summary['currency'];
    $amountLabel = __('Amount').' ('.$currency.')';
    $paymentMethods = [
        \App\Enums\RemittanceMethod::BankTransfer,
        \App\Enums\RemittanceMethod::Cash,
        \App\Enums\RemittanceMethod::Check,
        \App\Enums\RemittanceMethod::Sepa,
        \App\Enums\RemittanceMethod::Other,
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Provider portal') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('provider_payment_requests') }}</h2>
            </div>
            <a href="{{ route('provider.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-flow.stat-card :label="__('provider_stat_commission_total')" variant="indigo">
                    {{ flowdesk_format_minor((int) $summary['commission_total_minor'], $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_remitted')" variant="emerald">
                    {{ flowdesk_format_minor((int) $summary['remitted_minor'], $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_pending_remittance')" variant="amber">
                    {{ flowdesk_format_minor((int) $summary['pending_remittance_minor'], $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_balance_due')" variant="cyan">
                    {{ flowdesk_format_minor((int) $summary['balance_due_minor'], $currency) }} {{ $currency }}
                </x-flow.stat-card>
            </div>

            <div class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                        <div class="border-b border-slate-200/80 bg-gradient-to-r from-emerald-50/80 via-white to-white px-5 py-4 dark:border-slate-700/80 dark:from-emerald-950/30 dark:via-slate-900/50">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_submit_payment_request') }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('provider_submit_payment_request_help', ['balance' => flowdesk_format_minor((int) $summary['balance_due_minor'], $currency).' '.$currency]) }}</p>
                        </div>
                        <form method="POST" action="{{ route('provider.remittance-requests.store') }}" class="space-y-5 p-5 sm:p-6">
                            @csrf
                            <div>
                                <x-input-label for="amount" :value="$amountLabel" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="payment_method" :value="__('Payment method')" />
                                <select id="payment_method" name="payment_method" class="flow-input-select mt-1 block w-full text-sm" required>
                                    <option value="">{{ __('Select an option') }}</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="reference" :value="__('Reference (optional)')" />
                                <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="{{ __('provider_payment_reference_placeholder') }}" />
                            </div>
                            <div>
                                <x-input-label for="notes" :value="__('Notes (optional)')" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">{{ old('notes') }}</textarea>
                            </div>
                            @if ($summary['balance_due_minor'] <= 0)
                                <x-primary-button type="submit" disabled>{{ __('Submit payment request') }}</x-primary-button>
                            @else
                                <x-primary-button type="submit">{{ __('Submit payment request') }}</x-primary-button>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                        <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_payment_requests_history') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed text-start text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40">
                                        <th class="px-5 py-3 text-start">{{ __('Date') }}</th>
                                        <th class="px-5 py-3 text-end">{{ __('Amount') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Method') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Reference') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($requests as $request)
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $request->created_at->format('Y-m-d H:i') }}</span></td>
                                            <td class="px-5 py-4 text-end font-medium text-slate-900 dark:text-white"><span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $request->amount_minor, $currency) }} {{ $currency }}</span></td>
                                            <td class="px-5 py-4 text-slate-700 dark:text-slate-300 text-start">{{ $request->payment_method?->label() ?? '—' }}</td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-start">{{ $request->reference ?? '—' }}</td>
                                            <td class="px-5 py-4 text-start"><x-flow.badge :variant="$request->status->badgeVariant()">{{ $request->status->label() }}</x-flow.badge></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('provider_no_payment_requests') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($requests->hasPages())
                            <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $requests->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
