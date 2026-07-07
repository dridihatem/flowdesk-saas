<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard & widgets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div
                class="rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm"
                x-data="dashboardLayoutEditor({
                    rows: @js($widgets),
                    saveUrl: @js(route('settings.dashboard.update')),
                    csrf: @js(csrf_token()),
                    redirectUrl: @js(route('settings.dashboard')),
                })"
            >
                <h3 class="text-lg font-medium text-flow-text">{{ __('Dashboard widgets') }}</h3>
                <p class="mt-1 text-sm text-flow-text-muted">{{ __('Drag to reorder. Toggle visibility. Save to apply.') }}</p>

                <ul class="mt-4 space-y-2" x-ref="sortList">
                    <template x-for="row in rows" :key="row.key">
                        <li
                            class="flex items-center gap-3 rounded-lg border border-flow-border bg-flow-surface-muted px-3 py-2"
                            :data-widget-key="row.key"
                        >
                            <button type="button" data-drag-handle class="cursor-grab text-flow-text-muted hover:text-flow-text" aria-label="{{ __('Reorder') }}">⠿</button>
                            <span class="flex-1 text-sm font-medium text-flow-text" x-text="row.label"></span>
                            <label class="flex items-center gap-2 text-sm text-flow-text-muted">
                                <input type="checkbox" class="rounded border-gray-300 text-indigo-600" :checked="row.enabled" @change="row.enabled = $event.target.checked">
                                {{ __('Visible') }}
                            </label>
                        </li>
                    </template>
                </ul>

                <div class="mt-4 flex flex-wrap gap-3">
                    <x-primary-button type="button" @click="saveLayout()">{{ __('Save layout') }}</x-primary-button>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('View dashboard') }}</a>
                </div>
            </div>

            <div class="rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm">
                <h3 class="text-lg font-medium text-flow-text">{{ __('UI presets') }}</h3>
                <p class="mt-1 text-sm text-flow-text-muted">{{ __('Save the current theme and widget layout as a named preset, or restore one.') }}</p>

                <form method="POST" action="{{ route('settings.ui-presets.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="preset_name" :value="__('Preset name')" />
                        <x-text-input id="preset_name" name="name" type="text" class="mt-1 block w-full" required maxlength="100" placeholder="{{ __('e.g. Sales focus') }}" />
                    </div>
                    <x-primary-button>{{ __('Save preset') }}</x-primary-button>
                </form>

                @if (count($presets))
                    <ul class="mt-6 divide-y divide-flow-border border-t border-flow-border">
                        @foreach ($presets as $preset)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <p class="font-medium text-flow-text">{{ $preset['name'] }}</p>
                                    @if (! empty($preset['created_at']))
                                        <p class="text-xs text-flow-text-muted">{{ $preset['created_at'] }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('settings.ui-presets.activate', $preset['id']) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Apply') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('settings.ui-presets.destroy', $preset['id']) }}" onsubmit="return confirm({{ json_encode(__('Delete this preset?')) }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950/40">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-flow-text-muted">{{ __('No presets yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
