<footer class="relative z-10 border-t border-slate-200 bg-white py-14">
    <div class="mx-auto max-w-12xl px-6 sm:px-10 lg:px-12">
        <div class="flex flex-col items-center gap-8 text-center sm:flex-row sm:items-start sm:justify-between sm:text-start">
            <div class="max-w-sm">
                <p class="text-base font-semibold text-slate-900">{{ config('app.name') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __('Welcome footer tagline') }}</p>
            </div>
            <div class="flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm sm:justify-end">
                <a href="{{ route('marketing.terms') }}" class="font-medium text-slate-600 transition hover:text-slate-900">{{ __('Terms of service') }}</a>
                <a href="{{ route('marketing.privacy') }}" class="font-medium text-slate-600 transition hover:text-slate-900">{{ __('Privacy policy') }}</a>
                <a href="{{ route('marketing.cookies') }}" class="font-medium text-slate-600 transition hover:text-slate-900">{{ __('Cookie policy') }}</a>
                <a href="{{ route('marketing.legal') }}" class="font-medium text-slate-600 transition hover:text-slate-900">{{ __('Legal notices') }}</a>
            </div>
        </div>
        <p class="mt-10 border-t border-slate-100 pt-8 text-center text-xs text-slate-500 sm:text-start">© {{ date('Y') }} {{ config('app.name') }} - {{ __('All rights reserved') }}</p>
    </div>
</footer>
