<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $inquiry->subject }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel p-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                        <x-flow.badge variant="primary">{{ \Illuminate\Support\Str::headline($inquiry->status->name) }}</x-flow.badge>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($inquiry->project_id && $inquiry->project)
                            <a href="{{ route('projects.show', $inquiry->project) }}">
                                <x-secondary-button type="button">{{ __('View project') }}</x-secondary-button>
                            </a>
                        @else
                            <form method="POST" action="{{ route('inquiries.convert-project', $inquiry) }}">
                                @csrf
                                <x-primary-button type="submit">{{ __('Convert to project') }}</x-primary-button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('inquiries.destroy', $inquiry) }}" onsubmit="return confirm({{ json_encode(__('Delete this inquiry?')) }})">
                            @csrf
                            @method('DELETE')
                            <x-secondary-button type="submit" class="!border-rose-300 !text-rose-700 dark:!border-rose-800 dark:!text-rose-300">{{ __('Delete') }}</x-secondary-button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('inquiries.update', $inquiry) }}" class="flex flex-wrap items-end gap-4 border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
                    @csrf
                    @method('PUT')
                    <div class="min-w-[12rem] flex-1">
                        <x-input-label for="status" :value="__('Update status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            @foreach (\App\Enums\InquiryStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($inquiry->status === $case)>{{ \Illuminate\Support\Str::headline($case->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                </form>

                <dl class="grid gap-4 sm:grid-cols-2 border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
                    @if ($inquiry->contact_name || $inquiry->contact_email || $inquiry->contact_phone)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Contact') }}</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $inquiry->contact_name ?? '—' }}</dd>
                            @if ($inquiry->contact_email)
                                <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $inquiry->contact_email }}</dd>
                            @endif
                            @if ($inquiry->contact_phone)
                                <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $inquiry->contact_phone }}</dd>
                            @endif
                        </div>
                    @endif
                    @if ($inquiry->source)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Channel / source') }}</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">{{ $inquiry->source }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($inquiry->message)
                    <div class="border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Message') }}</p>
                        <p class="mt-2 whitespace-pre-wrap text-slate-700 dark:text-slate-300">{{ $inquiry->message }}</p>
                    </div>
                @endif
            </div>

            <a href="{{ route('inquiries.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">← {{ __('Back to inquiries') }}</a>
        </div>
    </div>
</x-app-layout>
