@if (session('registration'))
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100" role="status">
        @if (! empty(session('registration.oauth')))
            <p class="font-semibold">{{ __('Signed in with your social account. You can manage connected providers from profile settings later.') }}</p>
        @else
            <p class="font-semibold">{{ __('Save these credentials — they are shown only once.') }}</p>
        @endif
        <dl class="mt-2 space-y-1 font-mono text-xs sm:text-sm">
            <div><dt class="inline font-medium">{{ __('Workspace subdomain') }}:</dt> <dd class="inline">{{ session('registration.subdomain') }}</dd></div>
            <div><dt class="inline font-medium">{{ __('Tenant URL') }}:</dt> <dd class="inline break-all">{{ session('registration.tenant_url') }}</dd></div>
            @if (empty(session('registration.oauth')))
                <div><dt class="inline font-medium">{{ __('Company API token') }}:</dt> <dd class="inline break-all">{{ session('registration.company_api_token') }}</dd></div>
                <div><dt class="inline font-medium">{{ __('Sanctum token (Bearer)') }}:</dt> <dd class="inline break-all">{{ session('registration.sanctum_token') }}</dd></div>
            @endif
        </dl>
    </div>
@endif
