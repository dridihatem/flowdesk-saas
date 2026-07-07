<x-admin-layout>
    <x-flow.page-header
        :title="__('Users')"
        :description="__('Manage platform and workspace users. Roles control access (platform admin, company admin, team member, provider, client).')"
    />

    <div class="flow-panel p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:max-w-sm">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$q" placeholder="name@email.com" />
                </div>
                <div class="w-full sm:max-w-xs">
                    <x-input-label for="role" :value="__('Role')" />
                    <select id="role" name="role" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        <option value="">{{ __('All roles') }}</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>{{ __('Filter') }}</span>
                </button>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    <span>{{ __('Reset') }}</span>
                </a>
            </div>
        </form>
    </div>

    <div class="mt-6 flow-panel overflow-hidden p-0">
        <x-flow.table>
            <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Company') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Roles') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Verified') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 text-slate-800">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 text-start">
                            <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-start">
                            {{ $user->company?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-start">
                            <div class="flex flex-wrap gap-2">
                                @forelse ($user->roles as $r)
                                    <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700">{{ $r->name }}</span>
                                @empty
                                    <span class="text-sm text-slate-500">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-start">
                            @if ($user->email_verified_at)
                                <span class="inline-flex items-center gap-2 text-emerald-700">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    <span>{{ __('Yes') }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 text-slate-500">
                                    <i class="fa-regular fa-circle" aria-hidden="true"></i>
                                    <span>{{ __('No') }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a
                                href="{{ route('admin.users.edit', $user) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                                title="{{ __('Edit user') }}"
                                aria-label="{{ __('Edit user') }}"
                            >
                                <i class="fa-regular fa-pen-to-square text-sm" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('No users found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </x-flow.table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</x-admin-layout>

