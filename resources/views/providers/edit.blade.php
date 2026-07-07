<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Edit provider') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @isset($commissionSummary)
                @php $pc = $commissionSummary['currency']; @endphp
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-flow.stat-card :label="__('provider_stat_commission_total')" variant="indigo">{{ flowdesk_format_minor((int) $commissionSummary['commission_total_minor'], $pc) }} {{ $pc }}</x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_remitted')" variant="emerald">{{ flowdesk_format_minor((int) $commissionSummary['remitted_minor'], $pc) }} {{ $pc }}</x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_pending_remittance')" variant="amber">{{ flowdesk_format_minor((int) $commissionSummary['pending_remittance_minor'], $pc) }} {{ $pc }}</x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_balance_due')" variant="cyan">{{ flowdesk_format_minor((int) $commissionSummary['balance_due_minor'], $pc) }} {{ $pc }}</x-flow.stat-card>
                </div>
            @endisset

            @if (! empty($pendingRemittances) && $pendingRemittances->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-amber-200/80 bg-white shadow-sm dark:border-amber-900/40 dark:bg-slate-900/50">
                    <div class="border-b border-amber-200/60 bg-amber-50/80 px-5 py-4 dark:border-amber-900/40 dark:bg-amber-950/30">
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_pending_payment_requests') }}</h3>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($pendingRemittances as $remittance)
                            @php $pc = $commissionSummary['currency'] ?? 'USD'; @endphp
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $remittance->amount_minor, $pc) }} {{ $pc }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $remittance->created_at->format('Y-m-d H:i') }} · {{ $remittance->payment_method?->label() }} @if($remittance->reference)· {{ $remittance->reference }}@endif</p>
                                    @if ($remittance->notes)<p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $remittance->notes }}</p>@endif
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-1">
                                    @include('provider.partials.icon-action', [
                                        'formAction' => route('providers.remittance-requests.approve', [$provider, $remittance]),
                                        'label' => __('Approve'),
                                        'icon' => 'fa-solid fa-check',
                                        'variant' => 'success',
                                    ])
                                    @include('provider.partials.icon-action', [
                                        'formAction' => route('providers.remittance-requests.reject', [$provider, $remittance]),
                                        'label' => __('Reject'),
                                        'icon' => 'fa-solid fa-xmark',
                                        'variant' => 'danger',
                                    ])
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($commissionsByCompletedProject))
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                    <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_commissions_by_completed_project') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-fixed text-start text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/80">
                                <tr>
                                    <th class="px-5 py-3 text-start">{{ __('Project') }}</th>
                                    <th class="px-5 py-3 text-end">{{ __('Deal amount') }}</th>
                                    <th class="px-5 py-3 text-end">{{ __('Commission') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($commissionsByCompletedProject as $row)
                                    @php $pc = $commissionSummary['currency'] ?? 'USD'; @endphp
                                    <tr>
                                        <td class="px-5 py-3 text-start">
                                            <a href="{{ route('projects.show', $row['project']) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $row['project']->title }}</a>
                                        </td>
                                        <td class="px-5 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row['deal_minor'], $pc) }} {{ $pc }}</span></td>
                                        <td class="px-5 py-3 text-end font-semibold"><span class="flowdesk-ltr-num tabular-nums font-semibold">{{ flowdesk_format_minor((int) $row['commission_minor'], $pc) }} {{ $pc }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-200 px-5 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ __('provider_no_completed_project_commissions') }}
                </div>
            @endif

            <div class="max-w-2xl rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <form method="POST" action="{{ route('providers.update', $provider) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $provider->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $provider->email)" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    @include('providers.partials.profile-fields', ['provider' => $provider])
                    <div>
                        <x-input-label for="commission_rate" :value="__('Commission rate (%)')" />
                        <x-text-input id="commission_rate" name="commission_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('commission_rate', $provider->commission_rate !== null ? round((float) $provider->commission_rate * 100, 4) : '')" />
                        @include('providers.partials.commission-rate-hint')
                        <x-input-error class="mt-2" :messages="$errors->get('commission_rate')" />
                    </div>
                    <div>
                        <x-input-label for="user_id" :value="__('Linked user (optional)')" />
                        <select id="user_id" name="user_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected(old('user_id', $provider->user_id) == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <x-primary-button>{{ __('Update') }}</x-primary-button>
                        <a href="{{ route('providers.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
