<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ __('shared_file_title') }} — {{ config('app.name') }}</title>

        @include('partials.favicon')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-cyan-50 font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 flex justify-center">
                    <x-application-logo :tagline="false" />
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white/90 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm">
                    <div class="flex justify-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-2xl text-amber-600" aria-hidden="true">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <h1 class="mt-4 text-center text-xl font-bold tracking-tight">{{ __('shared_file_title') }}</h1>
                    @if ($share->company?->name)
                        <p class="mt-1 text-center text-sm text-slate-500">{{ __('shared_file_intro', ['company' => $share->company->name]) }}</p>
                    @endif

                    <div class="mt-6 rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                        <p class="break-all text-center font-medium text-slate-900">{{ $file->original_name }}</p>
                        @if ($file->size)
                            <p class="mt-1 text-center text-xs text-slate-500">{{ number_format($file->size / 1024, 1) }} KB</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('share.file.download', $share->token) }}" class="mt-6 space-y-4">
                        @csrf

                        @if ($share->hasPassword())
                            <div>
                                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">{{ __('shared_file_password_prompt') }}</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autofocus
                                    autocomplete="off"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                @error('password')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-indigo-500 hover:to-violet-500">
                            <i class="fa-solid fa-download text-xs" aria-hidden="true"></i>
                            {{ __('shared_file_download') }}
                        </button>
                    </form>

                    @if ($share->expires_at)
                        <p class="mt-4 text-center text-xs text-slate-400">
                            {{ __('project_vault_share_expires', ['date' => $share->expires_at->format('d/m/Y H:i')]) }}
                        </p>
                    @endif
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">© {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </div>
    </body>
</html>
