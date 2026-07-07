@php
    $addr = is_array($client->address) ? $client->address : [];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ __('Edit client') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $client->name }}</p>
            </div>
            <a href="{{ route('clients.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Back to clients') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel p-6 sm:p-8">
                <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="client_name" :value="__('Name')" />
                        <x-text-input id="client_name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $client->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="client_email" :value="__('Email')" />
                            <x-text-input id="client_email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $client->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="client_phone" :value="__('Phone')" />
                            <x-text-input id="client_phone" name="phone" type="text" class="mt-2 block w-full" :value="old('phone', $client->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="client_status" :value="__('client_status_label')" />
                        <select id="client_status" name="status" class="flow-input mt-2 block w-full">
                            @foreach (\App\Enums\ClientStatus::cases() as $statusOption)
                                @php
                                    $currentStatus = old('status', $client->status?->value ?? \App\Enums\ClientStatus::Active->value);
                                @endphp
                                <option value="{{ $statusOption->value }}" @selected($currentStatus === $statusOption->value)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="client_source" :value="__('client_source_label')" />
                        <select id="client_source" name="source" class="flow-input mt-2 block w-full">
                            <option value="">{{ __('client_source_placeholder') }}</option>
                            @foreach (\App\Enums\ClientSource::cases() as $sourceCase)
                                <option value="{{ $sourceCase->value }}" @selected(old('source', $client->source) === $sourceCase->value)>{{ $sourceCase->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="client_line1" :value="__('Address line')" />
                        <x-text-input id="client_line1" name="address_line1" type="text" class="mt-2 block w-full" :value="old('address_line1', $addr['line1'] ?? '')" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="client_city" :value="__('City')" />
                            <x-text-input id="client_city" name="address_city" type="text" class="mt-2 block w-full" :value="old('address_city', $addr['city'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="client_country" :value="__('Country (ISO)')" />
                            <x-text-input id="client_country" name="address_country" type="text" class="mt-2 block w-full uppercase" maxlength="8" :value="old('address_country', $addr['country'] ?? '')" placeholder="US, FR…" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40" x-data="{ pwd: '{{ old('portal_password', '') }}' }">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('client_portal_access_heading') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            @if ($client->user)
                                {{ __('client_portal_has_account') }}
                            @else
                                {{ __('client_portal_access_help') }}
                            @endif
                        </p>
                        <div class="mt-3">
                            <x-input-label for="client_portal_password" :value="__('client_portal_password_label')" />
                            <div class="mt-2 flex gap-2">
                                <x-text-input id="client_portal_password" name="portal_password" type="text" class="block w-full" autocomplete="new-password" x-model="pwd" />
                                <x-secondary-button
                                    type="button"
                                    class="shrink-0 !normal-case"
                                    x-on:click="pwd = (() => { const c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'; return Array.from(crypto.getRandomValues(new Uint8Array(12))).map((b) => c[b % c.length]).join(''); })()"
                                >{{ __('client_portal_password_generate') }}</x-secondary-button>
                            </div>
                            <x-input-error :messages="$errors->get('portal_password')" class="mt-2" />
                        </div>
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300" x-show="pwd !== ''" x-cloak>
                            <input type="checkbox" name="portal_send_credentials" value="1" @checked(old('portal_send_credentials')) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900" />
                            {{ __('client_portal_send_credentials_label') }}
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('clients.index') }}">
                            <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                        </a>
                        <x-primary-button type="submit" class="!normal-case">{{ __('Save changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
