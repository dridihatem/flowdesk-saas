@php($flowdeskTheme = $flowdeskTheme ?? [])
@php($navActive = 'relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition')
@php($navInactive = 'text-slate-300 hover:bg-white/5 hover:text-white')
@php($navOn = 'bg-emerald-600 text-white shadow-lg shadow-emerald-950/40 ring-1 ring-white/10')
@php($iconMuted = 'text-slate-400 group-hover:text-slate-200')
@php($iconOn = 'text-white')
<div class="min-h-screen bg-slate-100 text-slate-900">
    <div class="flex w-full">
        <aside
            class="sticky top-0 hidden h-screen w-72 shrink-0 flex-col border-e border-slate-800/80 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 px-4 py-6 shadow-xl shadow-slate-950/50 md:flex"
        >
            <a
                href="{{ route('admin.dashboard') }}"
                class="group shrink-0 flex items-center gap-3 rounded-2xl border border-white/5 bg-white/5 px-3 py-3 transition hover:bg-white/10"
            >
                @if (! empty($flowdeskTheme['logo_url']))
                    <img src="{{ $flowdeskTheme['logo_url'] }}" alt="" class="h-8 w-auto max-w-[160px] object-contain brightness-0 invert" />
                @else
                    <x-application-logo class="!gap-2" :tagline="false" :inverse="true" />
                @endif
                <span class="sr-only">{{ __('Platform admin') }}</span>
            </a>

            <div class="mt-6 min-h-0 flex-1 overflow-y-auto overscroll-y-contain pe-1 [-ms-overflow-style:none] [scrollbar-width:thin] [scrollbar-color:rgba(148,163,184,0.45)_transparent] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-600/80">
            <div class="mt-2">
                <div class="px-2 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">
                    {{ __('Platform') }}
                </div>
                <nav class="mt-3 space-y-1">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.dashboard') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.dashboard') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-chart-bar w-4 {{ request()->routeIs('admin.dashboard') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Overview') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.reports.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.reports.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-file-lines w-4 {{ request()->routeIs('admin.reports.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Reports') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.companies.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.companies.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.companies.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-building w-4 {{ request()->routeIs('admin.companies.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Companies') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.plans.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.plans.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.plans.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-credit-card w-4 {{ request()->routeIs('admin.plans.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Subscription plans') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.marketplace-modules.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.marketplace-modules.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.marketplace-modules.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-puzzle-piece w-4 {{ request()->routeIs('admin.marketplace-modules.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('admin_marketplace_modules_title') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.marketplace-orders.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.marketplace-orders.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.marketplace-orders.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-bag-shopping w-4 {{ request()->routeIs('admin.marketplace-orders.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('admin_marketplace_orders_title') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.payments.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.payments.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.payments.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-money-bill-1 w-4 {{ request()->routeIs('admin.payments.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Invoice payments') }}</span>
                    </a>
                </nav>
            </div>

            <div class="mt-8">
                <div class="px-2 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">
                    {{ __('Settings') }}
                </div>
                <nav class="mt-3 space-y-1">
                    <a
                        href="{{ route('admin.platform-appearance.edit') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.platform-appearance.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.platform-appearance.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-pen-to-square w-4 {{ request()->routeIs('admin.platform-appearance.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Default workspace theme') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.themes.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.themes.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.themes.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-palette w-4 {{ request()->routeIs('admin.themes.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Theme library') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.invoice-pdf-templates.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.invoice-pdf-templates.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.invoice-pdf-templates.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-file-pdf w-4 {{ request()->routeIs('admin.invoice-pdf-templates.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('admin_invoice_pdf_templates_nav') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.email-template-models.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.email-template-models.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.email-template-models.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-envelope-open w-4 {{ request()->routeIs('admin.email-template-models.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('admin_email_template_models_nav') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.payment-gateways.edit') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.payment-gateways.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.payment-gateways.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-plug-circle-bolt w-4 {{ request()->routeIs('admin.payment-gateways.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Payment gateways') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.platform-settings.edit') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.platform-settings.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.platform-settings.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-sliders w-4 {{ request()->routeIs('admin.platform-settings.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Platform settings') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.developer-docs.index') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.developer-docs.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.developer-docs.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-solid fa-book w-4 {{ request()->routeIs('admin.developer-docs.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Developer documentation') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.profile.edit') }}"
                        class="{{ $navActive }} {{ request()->routeIs('admin.profile.*') ? $navOn : $navInactive }} group"
                    >
                        <span
                            class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-e-full bg-emerald-400 {{ request()->routeIs('admin.profile.*') ? 'opacity-100' : 'opacity-0' }}"
                        ></span>
                        <i
                            class="fa-regular fa-user w-4 {{ request()->routeIs('admin.profile.*') ? $iconOn : $iconMuted }}"
                            aria-hidden="true"
                        ></i>
                        <span class="truncate">{{ __('Profile') }}</span>
                    </a>
                </nav>
            </div>
            </div>

            <div class="mt-4 shrink-0 pt-4">
                <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-3 shadow-inner">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ __('Signed in') }}</div>
                    <div class="mt-2 text-sm font-semibold text-white">{{ Auth::user()->name }}</div>
                    <div class="mt-0.5 text-xs text-slate-400">{{ Auth::user()->email }}</div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <a
                            href="{{ route('admin.profile.edit') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-300 hover:bg-white/10 hover:text-white {{ request()->routeIs('admin.profile.*') ? 'bg-white/10 text-white' : '' }}"
                        >
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                            {{ __('Profile') }}
                        </a>
                        <form method="POST" action="{{ route('locale.update') }}">
                            @csrf
                            <label class="sr-only" for="admin_locale">{{ __('Language') }}</label>
                            <select
                                id="admin_locale"
                                name="locale"
                                class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1.5 text-xs text-slate-200"
                                onchange="this.form.submit()"
                            >
                                @foreach (config('flowdesk.locales', ['en']) as $loc)
                                    <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-300 hover:bg-white/10 hover:text-white"
                            >
                                {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <div
                class="hidden items-center justify-between gap-3 border-b border-slate-200/90 bg-white/95 px-4 py-3 shadow-sm backdrop-blur md:flex"
            >
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-400">{{ __('Platform admin') }}</div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}"
                        title="{{ __('Admin dashboard') }}"
                    >
                        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                        <span class="hidden lg:inline">{{ __('Dashboard') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.profile.edit') }}"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.profile.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}"
                        title="{{ __('Profile') }}"
                    >
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <span class="hidden lg:inline">{{ __('Profile') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        title="{{ __('Notifications') }}"
                        aria-label="{{ __('Notifications') }}"
                    >
                        <i class="fa-regular fa-bell text-base" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div
                class="flex items-center justify-between border-b border-slate-200/90 bg-white/95 px-4 py-4 shadow-sm backdrop-blur md:hidden"
            >
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2">
                    <x-application-logo class="!gap-2" :tagline="false" />
                </a>
                <a href="{{ route('admin.companies.index') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">{{ __('Menu') }} →</a>
            </div>

            <div class="px-4 py-10 sm:px-6 lg:px-8">
                @if (! empty($flowdeskBreadcrumbs ?? []))
                    <div class="mb-6">
                        <x-flow.breadcrumb-bar :items="$flowdeskBreadcrumbs" :back="$flowdeskBreadcrumbBack ?? null" />
                    </div>
                @endif
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/[0.04] sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
