<div class="mb-6 flex flex-wrap items-center justify-end gap-2">
    <a href="{{ route('clients.create') }}">
        <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-sm" aria-hidden="true"></i>
            {{ __('New client') }}
        </x-secondary-button>
    </a>
    <a href="{{ route('projects.create') }}">
        <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-sm" aria-hidden="true"></i>
            {{ __('New project') }}
        </x-secondary-button>
    </a>
    <a href="{{ route('invoices.create') }}">
        <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-sm" aria-hidden="true"></i>
            {{ __('New invoice') }}
        </x-secondary-button>
    </a>
    <a href="{{ route('calendar.index') }}">
        <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
            <i class="fa-solid fa-calendar text-sm" aria-hidden="true"></i>
            {{ __('Calendar') }}
        </x-secondary-button>
    </a>
</div>
