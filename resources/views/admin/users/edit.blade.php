<x-admin-layout :title="$user->name">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route('admin.users.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Back to users') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="$user->name"
        :description="$user->email"
    />

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="flow-panel lg:col-span-5 p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Profile') }}</h3>

            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Email verified') }}</dt>
                    <dd class="font-semibold text-slate-900">{{ $user->email_verified_at ? __('Yes') : __('No') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Company') }}</dt>
                    <dd class="font-semibold text-slate-900">{{ $user->company?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Two-factor') }}</dt>
                    <dd class="font-semibold text-slate-900">{{ $user->hasTwoFactorEnabled() ? __('Enabled') : __('Not enabled') }}</dd>
                </div>
            </dl>
        </div>

        <div class="flow-panel lg:col-span-7 p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Roles') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Select roles to control where the user can sign in and what they can access.') }}</p>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($roles as $r)
                        <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm">
                            <span class="inline-flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200">
                                    <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                                </span>
                                <span class="font-mono">{{ $r }}</span>
                            </span>
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $r }}"
                                class="h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                @checked($user->hasRole($r))
                            />
                        </label>
                    @endforeach
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                        <span>{{ __('Save roles') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

