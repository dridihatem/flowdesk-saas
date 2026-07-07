@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && window.jQuery('#description').length) {
                window.jQuery('#description').summernote({
                    height: 220,
                    dialogsInBody: true,
                    placeholder: @json(__('Describe scope, deliverables, and internal notes…')),
                });
            }
        });
    </script>
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Edit project') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel p-8">
                <form method="POST" action="{{ route('provider.projects.update', $project) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $project->title)" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            @foreach (\App\Enums\ProjectStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('status', $project->status->value) === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="client_id" :value="__('Client')" />
                        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('None') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id', $project->client_id) === $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="negotiated_price" :value="__('Negotiated price (:cur)', ['cur' => $project->company?->default_currency ?? 'USD'])" />
                        <x-text-input id="negotiated_price" name="negotiated_price" type="text" inputmode="decimal" class="mt-1 block w-full flowdesk-amount" :value="old('negotiated_price', $project->negotiated_price !== null ? flowdesk_major_amount_for_input((int) $project->negotiated_price, $project->company?->default_currency ?? 'USD') : '')" />
                        <x-input-error :messages="$errors->get('negotiated_price')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="final_deadline" :value="__('Final deadline')" />
                        <input id="final_deadline" type="date" name="final_deadline" value="{{ old('final_deadline', $project->final_deadline?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
                        <x-input-error :messages="$errors->get('final_deadline')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" class="mt-2 block w-full">{{ old('description', $project->description) }}</textarea>
                    </div>
                    <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
