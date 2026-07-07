<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Enter the code from your authenticator app, or a recovery code.') }}
    </p>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Authentication code')" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
