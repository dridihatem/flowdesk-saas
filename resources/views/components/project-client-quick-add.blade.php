@props([
    'storeUrl' => null,
    'selectId' => 'client_id',
])
@php
    $quickUrl = $storeUrl ?? route('clients.quick-store');
    $clientQuickConfig = [
        'quickUrl' => $quickUrl,
        'selectId' => $selectId,
        'csrf' => csrf_token(),
        'i18n' => [
            'required' => __('The client name is required.'),
            'pageExpired' => __('Page expired, please refresh and try again.'),
            'notAllowed' => __('You are not allowed to create clients.'),
            'couldNot' => __('Could not create client.'),
            'network' => __('Network error.'),
        ],
    ];
    $clientQuickConfigId = 'client-quick-'.str_replace('-', '', \Illuminate\Support\Str::uuid()->toString());
    $clientQuickConfigJson = json_encode(
        $clientQuickConfig,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
    );
@endphp
{{-- In <script>, never use {{ json_encode }} for JS: e() turns " into &quot; and breaks code. Use {!! json_encode() !!} for both the key and value. --}}
<script>
    window._flowdeskClientQuick = window._flowdeskClientQuick || Object.create(null);
    window._flowdeskClientQuick[{!! json_encode($clientQuickConfigId) !!}] = {!! $clientQuickConfigJson !!};
</script>
<div
    class="mt-1 flex flex-wrap items-end gap-2"
    x-data="projectClientQuickAdd((window._flowdeskClientQuick && window._flowdeskClientQuick['{{ $clientQuickConfigId }}']) || {})"
>
    <div class="min-w-0 flex-1">
        {{ $slot }}
    </div>
    <button
        type="button"
        class="inline-flex shrink-0 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
        @click="modalOpen = true; err = null"
    >
        <i class="fa-solid fa-user-plus me-1.5 text-[11px]" aria-hidden="true"></i>
        {{ __('New client') }}
    </button>

    <div
        x-show="modalOpen"
        x-cloak
        data-wizard-ignore-validation
        class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/50 p-4"
        @keydown.escape.window="modalOpen = false"
    >
        <div
            class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-600 dark:bg-slate-900"
            @click.outside="modalOpen = false"
        >
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Add client') }}</h3>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Creates a client record and selects it for this project.') }}</p>
            <div class="mt-4 space-y-4" data-wizard-ignore-validation>
                <div>
                    <x-input-label for="qc_name" :value="__('Name')" />
                    <x-text-input id="qc_name" type="text" class="mt-1 block w-full" autocomplete="off" />
                </div>
                <div>
                    <x-input-label for="qc_email" :value="__('Email (optional)')" />
                    <x-text-input id="qc_email" type="email" class="mt-1 block w-full" autocomplete="off" />
                </div>
                <div>
                    <x-input-label for="qc_phone" :value="__('Phone (optional)')" />
                    <x-text-input id="qc_phone" type="text" class="mt-1 block w-full" autocomplete="off" />
                </div>
                <p x-show="err" x-text="err" class="text-sm text-rose-600 dark:text-rose-400"></p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="modalOpen = false">
                        {{ __('Cancel') }}
                    </button>
                    <x-primary-button type="button" @click="quickSubmit()" x-bind:disabled="busy">
                        <span x-show="!busy">{{ __('Create & select') }}</span>
                        <span x-show="busy" x-cloak>{{ __('Saving…') }}</span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>
</div>
