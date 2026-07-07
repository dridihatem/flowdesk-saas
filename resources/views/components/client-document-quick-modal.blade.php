@props([
    'intro',
])

<div
    x-show="quickOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
    @keydown.escape.window="quickOpen = false"
    @click.self="quickOpen = false"
>
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900" @click.stop>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Quick add client') }}</h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $intro }}</p>

        <div class="mt-4 space-y-4">
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_name">{{ __('Name') }} *</label>
                <input id="quick_client_name" type="text" x-model="quickName" autocomplete="off" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_email">{{ __('Email') }}</label>
                    <input id="quick_client_email" type="email" x-model="quickEmail" autocomplete="off" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_phone">{{ __('Phone') }}</label>
                    <input id="quick_client_phone" type="text" x-model="quickPhone" autocomplete="off" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_source">{{ __('Source') }}</label>
                <select id="quick_client_source" x-model="quickSource" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">{{ __('— Optional —') }}</option>
                    @foreach (\App\Enums\ClientSource::cases() as $sourceCase)
                        <option value="{{ $sourceCase->value }}">{{ $sourceCase->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_address">{{ __('Address') }}</label>
                <input id="quick_client_address" type="text" x-model="quickAddressLine1" autocomplete="off" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_city">{{ __('City') }}</label>
                    <input id="quick_client_city" type="text" x-model="quickAddressCity" autocomplete="off" class="mt-1 block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="quick_client_country">{{ __('Country') }}</label>
                    <input id="quick_client_country" type="text" x-model="quickAddressCountry" maxlength="8" autocomplete="off" placeholder="FR, US…" class="mt-1 block w-full rounded-lg border-slate-300 uppercase dark:border-slate-600 dark:bg-slate-800" />
                </div>
            </div>
        </div>

        <p class="mt-3 text-xs text-rose-600 dark:text-rose-400" x-show="quickError" x-text="quickError"></p>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600" @click="quickOpen = false">{{ __('Cancel') }}</button>
            <button
                type="button"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                :disabled="quickLoading || !quickName.trim()"
                @click="submitQuickClient()"
            >
                <span x-show="!quickLoading">{{ __('Save') }}</span>
                <span x-show="quickLoading" x-cloak>{{ __('Saving…') }}</span>
            </button>
        </div>
    </div>
</div>
