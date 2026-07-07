<x-admin-layout>
    <x-flow.page-header
        :title="__('Invoice payments')"
        :description="__('Payments recorded against company invoices (all tenants). This is not the SaaS subscription billing screen.')"
    />

    <div class="flow-panel overflow-hidden p-0">
        <x-flow.table>
            <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Company') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Invoice') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 text-sm text-start">{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-start">{{ $payment->company?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-sm text-start">{{ $payment->invoice?->number ?? $payment->invoice_id }}</td>
                        <td class="px-4 py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $payment->amount, $payment->currency) }}</span></td>
                        <td class="px-4 py-3 text-sm text-start">{{ \Illuminate\Support\Str::headline($payment->status->name) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('No payments yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </x-flow.table>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-admin-layout>
