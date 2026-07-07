<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('Team & roles') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <x-flow.page-header
                class="mb-8"
                :title="__('People in this workspace')"
                :description="__('Add accounts and assign roles. Only company admins can manage the team.')"
            />

            <div class="flow-panel overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        <thead class="bg-slate-50/90 dark:bg-slate-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">{{ __('Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">{{ __('Email') }}</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">{{ __('Role') }}</th>
                                <th scope="col" class="relative px-6 py-3 text-start"><span class="sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 bg-white/50 dark:divide-slate-700/70 dark:bg-slate-900/20">
                            @foreach ($users as $u)
                                @php($currentRole = $u->roles->first()?->name ?? 'team_member')
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900 dark:text-slate-100 text-start">{{ $u->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600 dark:text-slate-400 text-start">{{ $u->email }}</td>
                                    <td class="px-6 py-4 text-start">
                                        @if ($u->id === auth()->id())
                                            <span class="text-sm text-slate-600 dark:text-slate-400">{{ $roleOptions[$currentRole] ?? $currentRole }}</span>
                                            <p class="mt-1 text-xs text-slate-500">{{ __('Use profile or ask another admin to change your role.') }}</p>
                                        @else
                                            <form method="POST" action="{{ route('settings.team.update', $u) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                @method('PUT')
                                                <label class="sr-only" for="role-{{ $u->id }}">{{ __('Role') }}</label>
                                                <select id="role-{{ $u->id }}" name="role" class="flow-input-select text-sm">
                                                    @foreach ($roleOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <x-secondary-button type="submit">{{ __('Save') }}</x-secondary-button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-end text-sm">
                                        @if ($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('settings.team.destroy', $u) }}" onsubmit="return confirm({{ json_encode(__('Remove this user from the workspace?')) }});">
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button type="submit">{{ __('Remove') }}</x-danger-button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-10 flow-panel p-8">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Invite a teammate') }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('They can sign in immediately with this email and password.') }}</p>

                <form method="POST" action="{{ route('settings.team.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="max-w-md">
                        <x-input-label for="role" :value="__('Role')" />
                        <select id="role" name="role" class="flow-input-select mt-1 block w-full">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Add user') }}</x-primary-button>
                </form>
            </div>

            <x-input-error :messages="$errors->get('delete')" class="mt-6" />
        </div>
    </div>
</x-app-layout>
