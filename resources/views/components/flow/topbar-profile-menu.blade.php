@props([
    'variant' => 'topbar',
])
@php
    $user = Auth::user();
    $name = trim((string) $user->name);
    $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 2) {
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[count($words) - 1], 0, 1));
    } else {
        $initials = mb_strtoupper(mb_substr($name !== '' ? $name : '?', 0, 2));
    }
    $showLabel = $variant === 'nav';
@endphp

<x-dropdown
    align="right"
    width="56"
    wrapperClass="relative z-[200]"
    floatingClass="z-[210]"
    contentClasses="py-1 overflow-hidden bg-white/95 backdrop-blur-md dark:bg-slate-800/95 dark:ring-1 dark:ring-slate-600/80"
>
    <x-slot name="trigger">
        <button
            type="button"
            class="flow-topbar-profile-trigger inline-flex items-center gap-2 rounded-xl text-slate-700 transition hover:bg-slate-100/90 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:text-slate-200 dark:hover:bg-slate-800/80"
            @class([
                'px-1.5 py-1' => $variant === 'topbar',
                'border border-slate-200/80 bg-white/80 px-2 py-1.5 shadow-sm dark:border-slate-600 dark:bg-slate-800/80' => $variant === 'nav',
            ])
            aria-haspopup="true"
        >
            <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold uppercase tracking-wide text-white shadow-md shadow-indigo-900/20 ring-2 ring-white/40 dark:ring-slate-900/60"
                aria-hidden="true"
            >{{ $initials }}</span>
            @if ($showLabel)
                <span class="hidden max-w-[10rem] truncate text-sm font-medium sm:inline">{{ $user->name }}</span>
                <i class="fa-solid fa-chevron-down hidden text-[10px] text-slate-400 sm:inline" aria-hidden="true"></i>
            @endif
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
        </div>

        <div class="py-1">
            <x-dropdown-link :href="route('profile.edit')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                <span class="inline-flex items-center gap-2">
                    <i class="fa-regular fa-user w-4 text-center text-slate-400" aria-hidden="true"></i>
                    {{ __('Profile') }}
                </span>
            </x-dropdown-link>
            <x-dropdown-link :href="route('chat.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                <span class="inline-flex items-center gap-2">
                    <i class="fa-solid fa-comments w-4 text-center text-slate-400 text-xs" aria-hidden="true"></i>
                    {{ __('Messages') }}
                </span>
            </x-dropdown-link>
            <x-dropdown-link :href="route('tickets.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                <span class="inline-flex items-center gap-2">
                    <i class="fa-solid fa-ticket w-4 text-center text-slate-400 text-xs" aria-hidden="true"></i>
                    {{ __('Tickets') }}
                </span>
            </x-dropdown-link>

            @if ($user->hasAnyRole(['company_admin', 'team_member']))
                <div class="mx-2 my-1 border-t border-slate-100 dark:border-slate-700"></div>
                <p class="px-4 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Workspace') }}</p>
                <x-dropdown-link :href="route('marketing.hub')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-bullhorn w-4 text-center text-slate-400 text-xs" aria-hidden="true"></i>
                        {{ __('Marketing') }}
                    </span>
                </x-dropdown-link>
                <x-dropdown-link :href="route('email-marketing.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-envelope-open-text w-4 text-center text-slate-400 text-xs" aria-hidden="true"></i>
                        {{ __('Email marketing') }}
                    </span>
                </x-dropdown-link>
                <x-dropdown-link :href="route('settings.workspace')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-gear w-4 text-center text-slate-400 text-xs" aria-hidden="true"></i>
                        {{ __('Company settings') }}
                    </span>
                </x-dropdown-link>
            @endif

            <div class="mx-2 my-1 border-t border-slate-100 dark:border-slate-700"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')" class="text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center text-sm" aria-hidden="true"></i>
                        {{ __('Log Out') }}
                    </span>
                </x-dropdown-link>
            </form>
        </div>
    </x-slot>
</x-dropdown>
