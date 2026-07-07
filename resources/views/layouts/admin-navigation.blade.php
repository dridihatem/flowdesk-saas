@php($flowdeskTheme = $flowdeskTheme ?? [])
<header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/75 backdrop-blur-md">
    <div class="flex max-w-12xl w-full flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2">
                @if (! empty($flowdeskTheme['logo_url']))
                    <img src="{{ $flowdeskTheme['logo_url'] }}" alt="" class="h-8 w-auto max-w-[140px] object-contain" />
                @else
                    <x-application-logo class="!gap-2" :tagline="false" />
                @endif
            </a>
            <nav class="hidden items-center gap-1 text-sm font-medium md:flex">
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Overview') }}</a>
                <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 {{ request()->routeIs('admin.companies.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-regular fa-building text-sm opacity-90" aria-hidden="true"></i>
                    <span>{{ __('Companies') }}</span>
                </a>
                <a href="{{ route('admin.platform-appearance.edit') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.platform-appearance.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Workspace theme') }}</a>
                <a href="{{ route('admin.plans.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.plans.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Subscription plans') }}</a>
                <a href="{{ route('admin.marketplace-modules.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.marketplace-modules.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('admin_marketplace_modules_title') }}</a>
                <a href="{{ route('admin.marketplace-orders.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.marketplace-orders.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('admin_marketplace_orders_title') }}</a>
                <a href="{{ route('admin.payment-gateways.edit') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.payment-gateways.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Payment gateways') }}</a>
                <a href="{{ route('admin.payments.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.payments.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Invoice payments') }}</a>
            </nav>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <form method="POST" action="{{ route('locale.update') }}" class="inline">
                @csrf
                <label class="sr-only" for="admin_locale">{{ __('Language') }}</label>
                <select id="admin_locale" name="locale" class="rounded-lg border border-slate-800 bg-slate-900/60 px-2 py-1.5 text-slate-200" onchange="this.form.submit()">
                    @foreach (config('flowdesk.locales', ['en']) as $loc)
                        <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ __('locale.name.'.$loc) }}</option>
                    @endforeach
                </select>
            </form>
            <span class="hidden text-slate-300 sm:inline">{{ Auth::user()->name }}</span>
            <a href="{{ route('admin.profile.edit') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.profile.*') ? 'bg-red-600/20 text-white ring-1 ring-red-500/30' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}">{{ __('Profile') }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-white/5 hover:text-white">{{ __('Log Out') }}</button>
            </form>
        </div>
    </div>
</header>
