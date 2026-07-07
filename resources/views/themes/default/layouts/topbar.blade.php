<div class="min-h-screen">
    @include('layouts.navigation', ['flowdeskTheme' => $flowdeskTheme])

    @isset($header)
        <header class="flow-chrome-header border-b backdrop-blur-md">
            <div class="max-w-12xl w-full py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    @if (! empty($flowdeskBreadcrumbs ?? []))
        <div class="flow-chrome-breadcrumb border-b backdrop-blur-sm">
            <div class="max-w-12xl w-full px-4 py-2.5 sm:px-6 lg:px-8">
                <x-flow.breadcrumb-bar :items="$flowdeskBreadcrumbs" :back="$flowdeskBreadcrumbBack ?? null" />
            </div>
        </div>
    @endif

    <main class="pb-16">
        <x-flow.trial-banner />
        {{ $slot }}
    </main>
    @auth
        <x-flow.chat-messenger-widget />
    @endauth
</div>
