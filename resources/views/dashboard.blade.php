<x-app-layout>
    <x-slot name="header">
        <h2 class="flow-font-display text-lg font-semibold leading-tight text-flow-text">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="flow-page-shell">
        <div class="flow-dashboard-hero relative z-0 mb-8">
            <div class="relative z-10 max-w-2xl">
                <p class="flow-font-display flow-dashboard-hero-title text-xl font-semibold tracking-tight sm:text-2xl">
                    {{ __('Welcome back') }}
                </p>
                <p class="flow-dashboard-hero-subtitle mt-2 text-sm leading-relaxed">
                    {{ __('Dashboard hero subtitle') }}
                </p>
            </div>
        </div>
        <div class="space-y-6">
                @foreach ($widgets as $widget)
                    @if ($widget['enabled'])
                        @includeIf('dashboard.widgets.'.$widget['key'], [
                            'metrics' => $metrics ?? [],
                            'dashboardChart' => $dashboardChart ?? [],
                            'nova' => $nova ?? null,
                        ])
                    @endif
                @endforeach
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/dashboard-charts.js')
    @endpush
</x-app-layout>
