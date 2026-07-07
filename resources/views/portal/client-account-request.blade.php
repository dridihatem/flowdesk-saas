<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Invite colleague') }}</h2>
            <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to portal') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                        <div class="border-b border-slate-200/80 bg-gradient-to-r from-indigo-50/80 via-white to-white px-5 py-4 dark:border-slate-700/80 dark:from-indigo-950/30 dark:via-slate-900/50 dark:to-slate-900/40">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('New invitation') }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                {{ __('Suggest a colleague for their own client portal access. Your workspace team will review the request before any account is created.') }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('portal.client-account-requests.store') }}" class="space-y-5 p-5 sm:p-6">
                            @csrf
                            <div>
                                <x-input-label for="name" :value="__('Full name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="phone" :value="__('Phone (optional)')" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="notes" :value="__('Note to the company (optional)')" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-700/80 dark:bg-slate-800/40">
                                <input type="checkbox" name="add_to_chat" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900" @checked(old('add_to_chat')) />
                                <span class="text-sm text-slate-700 dark:text-slate-300">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ __('portal_invite_add_to_chat') }}</span>
                                    <span class="mt-0.5 block text-slate-500 dark:text-slate-400">{{ __('portal_invite_add_to_chat_help') }}</span>
                                </span>
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <x-primary-button type="submit">{{ __('Submit request') }}</x-primary-button>
                                <a href="{{ route('chat.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                    <i class="fa-solid fa-comments text-xs" aria-hidden="true"></i>
                                    {{ __('Team chat') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                        <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Invitations sent') }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_invitations_list_help') }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed text-start text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40 dark:text-slate-400">
                                        <th class="px-5 py-3 text-start">{{ __('Name') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Email') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Team chat') }}</th>
                                        <th class="px-5 py-3 text-start">{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($invitations as $invitation)
                                        @php
                                            $colleagueUserId = (int) ($invitation->createdClient?->user_id ?? 0);
                                            $inChat = $colleagueUserId > 0 && in_array($colleagueUserId, $chatParticipantIds, true);
                                        @endphp
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                            <td class="px-5 py-4 font-medium text-slate-900 dark:text-white text-start">{{ $invitation->name }}</td>
                                            <td class="px-5 py-4 text-slate-700 dark:text-slate-300 text-start">{{ $invitation->email }}</td>
                                            <td class="px-5 py-4 text-start">
                                                @php
                                                    $statusVariant = match ($invitation->status) {
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'warning',
                                                    };
                                                @endphp
                                                <x-flow.badge :variant="$statusVariant">{{ $invitation->statusLabel() }}</x-flow.badge>
                                            </td>
                                            <td class="px-5 py-4 text-start">
                                                @if ($invitation->status === 'approved' && $colleagueUserId > 0)
                                                    @if ($inChat)
                                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                                            {{ __('In team chat') }}
                                                        </span>
                                                    @else
                                                        <form method="POST" action="{{ route('portal.client-account-requests.add-to-chat', $invitation) }}">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800/50 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-950/60">
                                                                <i class="fa-solid fa-user-plus text-[10px]" aria-hidden="true"></i>
                                                                {{ __('Add to chat') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @elseif ($invitation->add_to_chat && $invitation->status === 'pending')
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('When approved') }}</span>
                                                @else
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $invitation->created_at->format('Y-m-d') }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-12 text-center text-slate-600 dark:text-slate-400">{{ __('portal_no_invitations_yet') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
