@php($vaultFiles = $project->files->where('is_vault', true))
<div>
    <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
        <i class="fa-solid fa-vault text-amber-500" aria-hidden="true"></i>
        {{ __('project_vault_title') }}
    </h3>
    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('project_vault_intro') }}</p>

    @if ($vaultFiles->isEmpty())
        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('project_vault_empty') }}</p>
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($vaultFiles as $file)
                <li class="rounded-xl border border-amber-200/70 bg-amber-50/40 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-xl text-amber-600 dark:bg-amber-950/60 dark:text-amber-300" aria-hidden="true">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-slate-900 dark:text-white">{{ $file->original_name }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="rounded-md bg-white/80 px-2 py-0.5 font-medium text-slate-700 ring-1 ring-amber-200/70 dark:bg-slate-900/60 dark:text-slate-300 dark:ring-amber-900/40">{{ $file->categoryEnum()->label() }}</span>
                                @if ($file->size)
                                    <span>{{ number_format($file->size / 1024, 1) }} KB</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ route('projects.vault.download', [$project, $file]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                <i class="fa-solid fa-download text-[10px]" aria-hidden="true"></i>
                                {{ __('project_vault_download') }}
                            </a>
                            <form method="POST" action="{{ route('projects.files.destroy', [$project, $file]) }}" onsubmit="return confirm({{ json_encode(__('Remove this file?')) }})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </div>

                    {{-- Existing share links --}}
                    @if ($file->shares->isNotEmpty())
                        <div class="mt-3 space-y-2 border-t border-amber-200/60 pt-3 dark:border-amber-900/40">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('project_vault_share_links') }}</p>
                            @foreach ($file->shares as $share)
                                <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white/70 p-2 text-xs ring-1 ring-slate-200/80 dark:bg-slate-900/50 dark:ring-slate-700" x-data="{ copied: false }">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold',
                                        'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' => $share->hasPassword(),
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => ! $share->hasPassword(),
                                    ])>
                                        <i @class(['fa-solid text-[9px]', 'fa-key' => $share->hasPassword(), 'fa-link' => ! $share->hasPassword()]) aria-hidden="true"></i>
                                        {{ $share->hasPassword() ? __('project_vault_share_protected') : __('project_vault_share_public') }}
                                    </span>
                                    <input type="text" readonly value="{{ $share->publicUrl() }}" class="min-w-0 flex-1 rounded-md border-slate-200 bg-slate-50 px-2 py-1 font-mono text-[11px] text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300" @click="$el.select()" />
                                    <span class="text-slate-500 dark:text-slate-400">{{ __('project_vault_share_downloads', ['count' => (int) $share->download_count]) }}</span>
                                    @if ($share->expires_at)
                                        <span @class(['text-slate-500 dark:text-slate-400', '!text-rose-500' => $share->isExpired()])>
                                            {{ __('project_vault_share_expires', ['date' => $share->expires_at->format('d/m/Y H:i')]) }}
                                        </span>
                                    @endif
                                    <button type="button" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400" @click="navigator.clipboard.writeText(@js($share->publicUrl())); copied = true; setTimeout(() => copied = false, 2000)">
                                        <span x-show="! copied">{{ __('project_vault_share_copy') }}</span>
                                        <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400">{{ __('project_vault_share_copied') }}</span>
                                    </button>
                                    <form method="POST" action="{{ route('projects.vault.shares.destroy', [$project, $file, $share]) }}" onsubmit="return confirm({{ json_encode(__('project_vault_share_revoke_confirm')) }})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('project_vault_share_revoke') }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Create a share link --}}
                    <div class="mt-3" x-data="{ open: false }">
                        <button type="button" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400" @click="open = ! open">
                            <i class="fa-solid fa-share-nodes text-[10px]" aria-hidden="true"></i>
                            {{ __('project_vault_share_create') }}
                        </button>
                        <form x-cloak x-show="open" method="POST" action="{{ route('projects.vault.shares.store', [$project, $file]) }}" class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                            @csrf
                            <div class="min-w-[160px] flex-1">
                                <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('project_vault_share_password') }}</label>
                                <input type="text" name="password" minlength="4" maxlength="255" autocomplete="off" class="flow-input-select block w-full text-sm" placeholder="{{ __('project_vault_share_password_placeholder') }}" />
                            </div>
                            <div class="min-w-[140px]">
                                <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('project_vault_share_expiry') }}</label>
                                <select name="expires_in" class="flow-input-select block w-full text-sm">
                                    <option value="0">{{ __('project_vault_share_expiry_never') }}</option>
                                    <option value="1">{{ __('project_vault_share_expiry_day') }}</option>
                                    <option value="7">{{ __('project_vault_share_expiry_week') }}</option>
                                    <option value="30">{{ __('project_vault_share_expiry_month') }}</option>
                                </select>
                            </div>
                            <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('project_vault_share_create_button') }}</x-secondary-button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Upload to vault --}}
    <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
        @csrf
        <input type="hidden" name="vault" value="1" />
        <div class="min-w-[160px] flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400" for="vault_cat">{{ __('File category') }}</label>
            <select id="vault_cat" name="category" class="flow-input-select block w-full text-sm" required>
                @foreach (\App\Enums\ProjectFileCategory::cases() as $cat)
                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0 flex-1 sm:min-w-[200px]">
            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400" for="vault_file">{{ __('File') }}</label>
            <input
                id="vault_file"
                type="file"
                name="file"
                required
                class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-amber-700 hover:file:bg-amber-100 dark:text-slate-300 dark:file:bg-slate-700 dark:file:text-amber-200"
            />
        </div>
        <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('project_vault_upload') }}</x-secondary-button>
    </form>
</div>
