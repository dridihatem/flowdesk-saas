<div class="space-y-8">
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Final deadline') }}</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $project->final_deadline?->format('Y-m-d') ?? '—' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Negotiated price') }}</dt>
            <dd class="mt-1 tabular-nums text-slate-900 dark:text-white">
                @if ($project->negotiated_price !== null)
                    {{ flowdesk_format_minor((int) $project->negotiated_price, $projectMoneyCurrency) }} {{ $projectMoneyCurrency }}
                @else
                    —
                @endif
            </dd>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Final price (internal)') }}</dt>
            <dd class="mt-1 tabular-nums text-slate-900 dark:text-white">
                @if ($project->final_price !== null)
                    {{ flowdesk_format_minor((int) $project->final_price, $projectMoneyCurrency) }} {{ $projectMoneyCurrency }}
                @else
                    —
                @endif
            </dd>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:col-span-2 lg:col-span-3 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Default currency for amounts') }}</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $workspaceMoneyCurrency }} — {{ __('Used when a task has no currency set') }}</dd>
        </div>
        @if ($project->client_id)
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 sm:col-span-2 lg:col-span-3 dark:border-slate-600/50 dark:bg-slate-800/30">
                <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Client price confirmation') }}</dt>
                <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                    @if ($project->isClientPriceConfirmed())
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ __('Confirmed') }}</span>
                            <span class="text-slate-600 dark:text-slate-400">{{ $project->client_price_confirmed_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</span>
                        </span>
                    @else
                        <span class="text-amber-800 dark:text-amber-200">{{ __('Waiting for client to confirm the agreed price in the portal.') }}</span>
                    @endif
                </dd>
            </div>
        @endif
    </dl>

    @if ($project->client_id && $project->clientAgreedPriceMinor() !== null && (int) $project->clientAgreedPriceMinor() > 0)
        @php
            $agreedMinor = (int) $project->clientAgreedPriceMinor();
            $sumInstallments = (int) $project->installments->sum('amount_minor');
        @endphp
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/30 p-5 dark:border-slate-600/50 dark:bg-slate-800/20">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <i class="fa-solid fa-calendar-days text-indigo-500" aria-hidden="true"></i>
                {{ __('Installment payments') }}
            </h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Installment payments help') }}</p>
            <x-input-error :messages="$errors->get('installment')" class="mt-3" />

            @if (! $project->isClientPriceConfirmed())
                <p class="mt-4 rounded-lg border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                    {{ __('Installments unlock after client confirms price') }}
                </p>
            @else
                <div class="mt-4 flex flex-wrap items-baseline gap-3 text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('Agreed project total') }}:</span>
                    <span class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor($agreedMinor, $projectMoneyCurrency) }} {{ $projectMoneyCurrency }}</span>
                    <span class="text-slate-400">·</span>
                    <span class="text-slate-600 dark:text-slate-400">{{ __('Installments sum') }}:</span>
                    <span class="font-semibold tabular-nums {{ $sumInstallments === $agreedMinor ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-200' }}">{{ flowdesk_format_minor($sumInstallments, $projectMoneyCurrency) }} {{ $projectMoneyCurrency }}</span>
                    @if ($sumInstallments !== $agreedMinor)
                        <span class="text-xs text-amber-800 dark:text-amber-200">({{ __('Adjust installment amounts so the sum matches the agreed total.') }})</span>
                    @endif
                </div>

                @if ($project->installments->isNotEmpty())
                    <div class="mt-6 space-y-6">
                        @foreach ($project->installments as $inst)
                            <div class="rounded-xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-600/50 dark:bg-slate-900/40">
                                <form method="POST" action="{{ route('projects.installments.update', [$project, $inst]) }}" class="grid gap-3 md:grid-cols-12 md:items-end">
                                    @csrf
                                    @method('PATCH')
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Due date') }}</label>
                                        <x-text-input type="date" name="due_date" class="mt-1 block w-full" :value="old('due_date', $inst->due_date->format('Y-m-d'))" required />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Amount') }}</label>
                                        <x-text-input type="text" name="amount" inputmode="decimal" class="mt-1 block w-full" :value="old('amount', flowdesk_major_amount_for_input((int) $inst->amount_minor, $projectMoneyCurrency))" required />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Payment method for client') }}</label>
                                        <select name="payment_method" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                                            @foreach (\App\Enums\ProjectInstallmentPaymentMethod::cases() as $m)
                                                <option value="{{ $m->value }}" @selected(old('payment_method', $inst->payment_method->value) === $m->value)>{{ $m->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Installment label optional') }}</label>
                                        <x-text-input type="text" name="label" class="mt-1 block w-full" :value="old('label', $inst->label)" />
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 md:col-span-2">
                                        <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('Save') }}</x-secondary-button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('projects.installments.destroy', [$project, $inst]) }}" class="mt-3 inline" onsubmit="return confirm({{ json_encode(__('Remove this installment?')) }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('Remove installment') }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('projects.installments.store', $project) }}" class="mt-6 grid gap-4 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30 md:grid-cols-12 md:items-end">
                    @csrf
                    <p class="md:col-span-12 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Add installment') }}</p>
                    <div class="md:col-span-3">
                        <x-input-label for="inst_due_date" :value="__('Due date')" />
                        <x-text-input id="inst_due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date')" required />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="inst_amount" :value="__('Amount')" />
                        <x-text-input id="inst_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('amount')" required />
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="inst_method" :value="__('Payment method for client')" />
                        <select id="inst_method" name="payment_method" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" required>
                            @foreach (\App\Enums\ProjectInstallmentPaymentMethod::cases() as $m)
                                <option value="{{ $m->value }}" @selected(old('payment_method') === $m->value)>{{ $m->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="inst_label" :value="__('Installment label optional')" />
                        <x-text-input id="inst_label" name="label" type="text" class="mt-1 block w-full" :value="old('label')" />
                    </div>
                    <div class="md:col-span-1">
                        <x-primary-button type="submit" class="w-full !normal-case">{{ __('Add') }}</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
