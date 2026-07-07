@php
    $here = request()->route()?->getName();
    $cartCount = app(\App\Services\MarketplaceCartService::class)->count();
@endphp
<header class="relative z-20 border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-12xl flex-wrap items-center justify-between gap-4 px-6 py-4 sm:px-10 lg:px-12">
        <a href="{{ route('home') }}" class="rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-900/20">
            <x-application-logo :tagline="false" class="!gap-2" />
        </a>
        <nav class="flex flex-wrap items-center justify-end gap-1 text-sm font-medium sm:gap-2" aria-label="{{ __('Primary') }}">
            <a
                href="{{ route('home') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => $here === 'home',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $here !== 'home',
                ])
            >{{ __('Home') }}</a>
            <a
                href="{{ route('marketing.features') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => $here === 'marketing.features',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $here !== 'marketing.features',
                ])
            >{{ __('Features') }}</a>
            <a
                href="{{ route('marketing.about') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => $here === 'marketing.about',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $here !== 'marketing.about',
                ])
            >{{ __('About us') }}</a>
            <a
                href="{{ route('marketing.pricing') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => $here === 'marketing.pricing',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $here !== 'marketing.pricing',
                ])
            >{{ __('Pricing') }}</a>
            <a
                href="{{ route('marketing.modules') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => in_array($here, ['marketing.modules', 'modules.show'], true),
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! in_array($here, ['marketing.modules', 'modules.show'], true),
                ])
            >{{ __('Modules') }}</a>
            <a
                href="{{ route('marketing.contact') }}"
                @class([
                    'rounded-md px-3 py-2 transition',
                    'bg-slate-100 text-slate-900' => $here === 'marketing.contact',
                    'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $here !== 'marketing.contact',
                ])
            >{{ __('Contact') }}</a>
            <span class="hidden h-4 w-px bg-slate-200 sm:inline-block" aria-hidden="true"></span>

            <div class="ms-1 inline-flex items-center gap-2">
                <a
                    href="{{ route('marketing.cart') }}"
                    @class([
                        'relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50/80 transition',
                        'bg-white text-slate-900 shadow-sm' => in_array($here, ['marketing.cart', 'marketing.checkout', 'marketing.checkout.pending'], true),
                        'text-slate-600 hover:bg-white hover:text-slate-900' => ! in_array($here, ['marketing.cart', 'marketing.checkout', 'marketing.checkout.pending'], true),
                    ])
                    aria-label="{{ __('marketing_cart_nav_label', ['count' => $cartCount]) }}"
                    title="{{ __('Cart') }}"
                >
                    <i class="fa-solid fa-cart-shopping text-sm" aria-hidden="true"></i>
                    @if ($cartCount > 0)
                        <span class="absolute -end-1 -top-1 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-indigo-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">{{ $cartCount }}</span>
                    @endif
                </a>

                <span class="h-6 w-px bg-slate-200" aria-hidden="true"></span>

                <form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50/80 px-2">
                    @csrf
                    <label for="marketing_locale" class="sr-only">{{ __('Language') }}</label>
                    <select
                        id="marketing_locale"
                        name="locale"
                        class="h-9 rounded-md border-0 bg-transparent pe-1 ps-1 text-xs font-medium text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0"
                        onchange="this.form.submit()"
                    >
                        @foreach (config('flowdesk.locales', ['en']) as $loc)
                            <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ __('locale.name.'.$loc) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">{{ __('Log in') }}</a>
            @endif
            @if (Route::has('register'))
                <a
                    href="{{ route('register') }}"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >{{ __('Get started') }}</a>
            @endif
        </nav>
    </div>
</header>
