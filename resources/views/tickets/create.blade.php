<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('New ticket') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel p-8">
                <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="subject" :value="__('Subject')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" required maxlength="255" autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>

                    @if ($isStaff)
                        <div>
                            <x-input-label for="related_type" :value="__('Related to')" />
                            <select id="related_type" name="related_type" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="none" @selected(old('related_type', 'none') === 'none')>{{ __('None') }}</option>
                                <option value="client" @selected(old('related_type') === 'client')>{{ __('Client') }}</option>
                                <option value="provider" @selected(old('related_type') === 'provider')>{{ __('Business provider') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('related_type')" />
                        </div>

                        <div id="wrap-client" class="{{ old('related_type') === 'client' ? '' : 'hidden' }}">
                            <x-input-label for="client_id" :value="__('Select client')" />
                            <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ __('— Choose —') }}</option>
                                @foreach ($clients as $c)
                                    <option value="{{ $c->id }}" @selected(old('client_id') === $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                        </div>

                        <div id="wrap-provider" class="{{ old('related_type') === 'provider' ? '' : 'hidden' }}">
                            <x-input-label for="provider_id" :value="__('Select provider')" />
                            <select id="provider_id" name="provider_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ __('— Choose —') }}</option>
                                @foreach ($providers as $p)
                                    <option value="{{ $p->id }}" @selected(old('provider_id') === $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('provider_id')" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="message" :value="__('Message')" />
                        <textarea id="message" name="message" rows="8" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required maxlength="20000">{{ old('message') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('message')" />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button>{{ __('Submit ticket') }}</x-primary-button>
                        <a href="{{ route('tickets.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if ($isStaff)
        @push('scripts')
            <script>
                (function () {
                    var rt = document.getElementById('related_type');
                    var wc = document.getElementById('wrap-client');
                    var wp = document.getElementById('wrap-provider');
                    if (!rt || !wc || !wp) return;
                    function sync() {
                        var v = rt.value;
                        wc.classList.toggle('hidden', v !== 'client');
                        wp.classList.toggle('hidden', v !== 'provider');
                    }
                    rt.addEventListener('change', sync);
                    sync();
                })();
            </script>
        @endpush
    @endif
</x-app-layout>
