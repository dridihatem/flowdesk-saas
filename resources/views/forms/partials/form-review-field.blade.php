@php
    $placeholder = $field->meta['placeholder'] ?? __('Preview');
    $options = $field->meta['options'] ?? [];
@endphp

@if ($field->type === 'heading')
    <p class="text-sm font-bold {{ $mutedClass }}">{{ $field->name }}</p>
@elseif ($field->type === 'paragraph')
    <p class="text-xs {{ $mutedClass }}">{{ $field->name }}</p>
@else
    <div class="space-y-1.5">
        <label class="block text-xs font-medium {{ $mutedClass }}">
            {{ $field->name }}
            @if ($field->required)
                <span class="text-rose-500 dark:text-rose-400">*</span>
            @endif
        </label>

        @if ($field->type === 'textarea')
            <textarea
                rows="2"
                disabled
                class="block w-full px-2.5 py-2 text-sm {{ $inputClass }}"
                placeholder="{{ $placeholder }}"
            ></textarea>

        @elseif ($field->type === 'select')
            <select disabled class="block w-full px-2.5 py-2 text-sm {{ $inputClass }}">
                <option>{{ $placeholder ?: __('Select an option') }}</option>
                @foreach ($options as $opt)
                    <option>{{ $opt }}</option>
                @endforeach
            </select>

        @elseif ($field->type === 'radio')
            <div class="flex flex-col gap-1.5">
                @forelse ($options as $opt)
                    <label class="inline-flex items-center gap-2 text-xs {{ $mutedClass }}">
                        <input type="radio" disabled class="border-slate-300 text-[var(--flow-preview-accent)] dark:border-slate-600" />
                        {{ $opt }}
                    </label>
                @empty
                    <label class="inline-flex items-center gap-2 text-xs {{ $mutedClass }}">
                        <input type="radio" disabled class="border-slate-300 dark:border-slate-600" />
                        {{ __('Choose one') }}
                    </label>
                @endforelse
            </div>

        @elseif ($field->type === 'checkbox')
            <div class="flex flex-col gap-1.5">
                @forelse ($options as $opt)
                    <label class="inline-flex items-center gap-2 text-xs {{ $mutedClass }}">
                        <input type="checkbox" disabled class="rounded border-slate-300 text-[var(--flow-preview-accent)] dark:border-slate-600" />
                        {{ $opt }}
                    </label>
                @empty
                    <label class="inline-flex items-center gap-2 text-xs {{ $mutedClass }}">
                        <input type="checkbox" disabled class="rounded border-slate-300 dark:border-slate-600" />
                        {{ __('Checkbox') }}
                    </label>
                @endforelse
            </div>

        @elseif ($field->type === 'file')
            <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-4 dark:border-slate-600">
                <div class="text-center">
                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    <p class="mt-1 text-[11px] font-medium {{ $mutedClass }}">{{ __('Choose a file') }}</p>
                    <p class="text-[10px] {{ $mutedClass }}">{{ __('or drag and drop') }}</p>
                </div>
            </div>

        @elseif ($field->type === 'date')
            <input
                type="date"
                disabled
                class="block w-full px-2.5 py-2 text-sm {{ $inputClass }}"
            />

        @else
            <input
                type="{{ in_array($field->type, ['email', 'number', 'tel', 'url'], true) ? $field->type : 'text' }}"
                disabled
                class="block w-full px-2.5 py-2 text-sm {{ $inputClass }}"
                placeholder="{{ $placeholder }}"
            />
        @endif
    </div>
@endif
