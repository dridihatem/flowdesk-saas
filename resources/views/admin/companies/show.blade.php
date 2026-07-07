<x-admin-layout :title="$company->name">
    <div class="mb-6">
        <a
            href="{{ route('admin.companies.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Back to companies') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="$company->name"
        :description="__('Workspace profile and subscription. Customer-facing quotes and invoices are managed inside this company account.')"
    />

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="flow-panel p-6">
            <h3 class="font-semibold text-slate-900">{{ __('Workspace status') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('Enable or disable this company workspace.') }}</p>

            <form method="POST" action="{{ route('admin.companies.status', $company) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">{{ __('Status') }}</div>
                        @if ($company->is_enabled)
                            <div class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                <span>{{ __('Enabled') }}</span>
                            </div>
                        @else
                            <div class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-rose-700">
                                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                                <span>{{ __('Disabled') }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="is_enabled" value="0" />
                            <input type="checkbox" name="is_enabled" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500" @checked($company->is_enabled) />
                            <span>{{ __('Enabled') }}</span>
                        </label>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                            <span>{{ __('Save') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <x-input-label for="disabled_reason" :value="__('Disabled reason (optional)')" />
                    <x-text-input id="disabled_reason" name="disabled_reason" class="mt-1 block w-full" :value="old('disabled_reason', $company->disabled_reason)" placeholder="Non-payment, abuse, test account..." />
                    <x-input-error :messages="$errors->get('disabled_reason')" class="mt-2" />
                </div>
            </form>
        </div>

        <div class="flow-panel p-6">
            <h3 class="font-semibold text-slate-900">{{ __('Subscription plan (manual)') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('Set plan manually and optionally lock it to prevent auto-sync from subscriptions.') }}</p>

            <form method="POST" action="{{ route('admin.companies.plan', $company) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="plan_id" :value="__('Plan')" />
                    <select id="plan_id" name="plan_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        <option value="">{{ __('No plan') }}</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $company->plan_id) === (string) $plan->id)>
                                {{ $plan->name }} ({{ $plan->slug }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('plan_id')" class="mt-2" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="plan_locked" value="0" />
                    <input type="checkbox" name="plan_locked" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500" @checked(old('plan_locked', $company->plan_locked) ? true : false) />
                    <span>{{ __('Lock plan (disable subscription auto-sync)') }}</span>
                </label>

                <div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                        <span>{{ __('Save') }}</span>
                    </button>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                    <div class="font-semibold text-slate-700">{{ __('Current plan') }}</div>
                    <div class="mt-1">
                        <span class="font-semibold text-slate-900">{{ $company->plan?->name ?? '—' }}</span>
                        @if ($company->plan)
                            <span class="ms-2 font-mono text-slate-500">{{ $company->plan->slug }}</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-6 flow-panel p-6">
        <h3 class="font-semibold text-slate-900">{{ __('Contact company') }}</h3>
        <p class="mt-1 text-sm text-slate-600">{{ __('Send a message to the company activity feed (appears under Activity).') }}</p>

        <form method="POST" action="{{ route('admin.companies.notice', $company) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <x-input-label for="notice_message" :value="__('Message')" />
                <textarea id="notice_message" name="message" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="Maintenance notice, billing reminder, onboarding info...">{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                <i class="fa-regular fa-paper-plane" aria-hidden="true"></i>
                <span>{{ __('Send notice') }}</span>
            </button>
        </form>
    </div>

    <div class="mt-6 flow-panel p-6">
        <h3 class="font-semibold text-slate-900">{{ __('Remove company') }}</h3>
        <p class="mt-1 text-sm text-slate-600">
            {{ __('This will disable and remove the workspace. To confirm, type the subdomain:') }}
            <span class="font-mono font-semibold text-slate-900">{{ $company->subdomain }}</span>
        </p>

        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" class="mt-4 space-y-4">
            @csrf
            @method('DELETE')

            <div>
                <x-input-label for="confirm" :value="__('Confirm subdomain')" />
                <x-text-input id="confirm" name="confirm" class="mt-1 block w-full max-w-md" :value="old('confirm')" placeholder="{{ $company->subdomain }}" required />
                <x-input-error :messages="$errors->get('confirm')" class="mt-2" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="hidden" name="send_email" value="0" />
                <input type="checkbox" name="send_email" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500" @checked(old('send_email', true)) />
                <span>{{ __('Send email notification to company') }}</span>
            </label>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700"
                onclick="return confirm('{{ __('Remove this company? This cannot be undone.') }}')"
            >
                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                <span>{{ __('Remove company') }}</span>
            </button>
        </form>
    </div>

    <div class="grid gap-6 mt-6 lg:grid-cols-2">
        <div class="flow-panel p-6 space-y-3 text-sm">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Company details') }}</h3>
            <dl class="grid gap-2">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Subdomain') }}</dt><dd class="font-mono">{{ $company->subdomain }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Language') }}</dt><dd class="font-mono">{{ strtoupper($company->default_locale ?? '—') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Default currency') }}</dt><dd>{{ $company->default_currency }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Country') }}</dt><dd>{{ $company->country ?? '—' }}</dd></div>
                @if ($company->contact_email)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Company email') }}</dt><dd>{{ $company->contact_email }}</dd></div>
                @endif
                @if ($company->phone)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Phone') }}</dt><dd>{{ $company->phone }}</dd></div>
                @endif
                @if ($company->website)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Website') }}</dt><dd>{{ $company->website }}</dd></div>
                @endif
                @if ($company->tax_id)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('VAT / TVA') }}</dt><dd>{{ $company->tax_id }}</dd></div>
                @endif
                @if ($company->address_line1 || $company->city)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Address') }}</dt><dd class="text-end">{{ collect([$company->address_line1, $company->city, $company->postal_code])->filter()->join(', ') ?: '—' }}</dd></div>
                @endif
                @if ($company->industry)
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Industry') }}</dt><dd>{{ $company->industry }}</dd></div>
                @endif
            </dl>
            @if (config('flowdesk.tenant_base_domain'))
                <p class="pt-4 text-xs text-slate-500">
                    {{ __('Tenant URL') }}:
                    <span class="font-mono">{{ flowdesk_tenant_url($company, '/dashboard') }}</span>
                </p>
            @endif
        </div>
        <div class="flow-panel p-6 space-y-3 text-sm">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Usage counts') }}</h3>
            <dl class="grid gap-2">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Users') }}</dt><dd>{{ number_format($company->users_count) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Projects') }}</dt><dd>{{ number_format($company->projects_count) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Clients') }}</dt><dd>{{ number_format($company->clients_count) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Proposals (quotes)') }}</dt><dd>{{ number_format($company->proposals_count) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __('Invoices') }}</dt><dd>{{ number_format($company->invoices_count) }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-8 flow-panel p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Subscriptions') }}</h3>
        @if ($subscriptions->isEmpty())
            <p class="mt-2 text-sm text-slate-500">{{ __('No subscription rows.') }}</p>
        @else
            <ul class="mt-4 space-y-2">
                @foreach ($subscriptions as $sub)
                    <li class="flex flex-wrap justify-between gap-2 rounded-lg border border-slate-200/80 px-4 py-3 dark:border-slate-700">
                        <span class="font-medium">{{ $sub->plan?->name ?? '—' }}</span>
                        <span class="text-sm text-slate-500">{{ __('Status') }}: {{ $sub->status }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-admin-layout>
