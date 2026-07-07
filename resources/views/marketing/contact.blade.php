@extends('layouts.marketing')

@section('title', __('Contact').' — '.config('app.name'))
@section('meta_description', __('Marketing contact meta'))

@section('content')
    @include('marketing.partials.hero', [
        'eyebrow' => __('Contact'),
        'title' => __('Get in touch'),
        'lead' => __('Marketing contact lead'),
        'maxWidth' => 'max-w-6xl',
    ])

    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-6 pb-20 pt-10 sm:px-10 lg:px-12">
            @if (session('status'))
                <div class="mb-8 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
                {{-- Contact info --}}
                <div class="space-y-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-8">
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Marketing contact info title') }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ __('Marketing contact info body') }}</p>

                        <dl class="mt-8 space-y-5">
                            @if (is_string($contactEmail) && $contactEmail !== '' && filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Email') }}</dt>
                                    <dd class="mt-1">
                                        <a href="mailto:{{ $contactEmail }}" class="text-sm font-medium text-slate-900 transition hover:text-blue-800">{{ $contactEmail }}</a>
                                    </dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Marketing contact response label') }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ __('Marketing contact response time') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sales') }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ __('Marketing contact sales note') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-lg border border-slate-800 bg-slate-900 p-8 text-white">
                        <h3 class="text-base font-semibold">{{ __('Marketing contact message title') }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ __('Marketing contact message body') }}</p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-300">
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" aria-hidden="true"></span>
                                {{ __('Marketing contact message point1') }}
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" aria-hidden="true"></span>
                                {{ __('Marketing contact message point2') }}
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400" aria-hidden="true"></span>
                                {{ __('Marketing contact message point3') }}
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Form --}}
                <div class="rounded-lg border border-slate-200 bg-white p-6 sm:p-8">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Marketing contact form title') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Marketing contact form lead') }}</p>

                    <form method="POST" action="{{ route('marketing.contact.store') }}" class="mt-8 space-y-5">
                        @csrf
                        <div>
                            <x-input-label for="contact_name" :value="__('Name')" />
                            <x-text-input id="contact_name" name="name" type="text" class="mt-2 block w-full" :value="old('name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="contact_email" :value="__('Email')" />
                            <x-text-input id="contact_email" name="email" type="email" class="mt-2 block w-full" :value="old('email')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>
                        <div>
                            <x-input-label for="contact_company" :value="__('Company (optional)')" />
                            <x-text-input id="contact_company" name="company" type="text" class="mt-2 block w-full" :value="old('company')" />
                            <x-input-error class="mt-2" :messages="$errors->get('company')" />
                        </div>
                        <div>
                            <x-input-label for="contact_message" :value="__('Message')" />
                            <textarea
                                id="contact_message"
                                name="message"
                                rows="5"
                                required
                                class="flow-input mt-2 block w-full"
                            >{{ old('message') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('message')" />
                        </div>

                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <x-input-label for="contact_captcha" :value="__('Math CAPTCHA')" />
                            <p class="mt-1 text-sm font-medium text-slate-800">{{ $captcha['question'] }}</p>
                            <input type="hidden" name="_captcha_token" value="{{ $captcha['token'] }}" />
                            <x-text-input
                                id="contact_captcha"
                                name="_captcha_answer"
                                type="number"
                                inputmode="numeric"
                                class="mt-3 block w-full max-w-[10rem]"
                                :value="old('_captcha_answer')"
                                required
                                autocomplete="off"
                            />
                            <p class="mt-2 text-xs text-slate-500">{{ __('Marketing contact captcha hint') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('_captcha_answer')" />
                        </div>

                        <x-primary-button class="w-full justify-center !rounded-md !bg-slate-900 !py-3 !text-sm !font-semibold !normal-case !tracking-normal hover:!bg-slate-800 sm:w-auto">{{ __('Send message') }}</x-primary-button>
                    </form>

                    <p class="mt-6 text-xs text-slate-500">{{ __('Marketing contact privacy note') }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
