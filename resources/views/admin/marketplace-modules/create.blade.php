<x-admin-layout :title="__('admin_marketplace_module_create')">
    <a href="{{ route('admin.marketplace-modules.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
        {{ __('admin_marketplace_modules_title') }}
    </a>

    <x-flow.page-header :title="__('admin_marketplace_module_create')" />

    <form method="POST" action="{{ route('admin.marketplace-modules.store') }}" enctype="multipart/form-data" class="mt-6 max-w-3xl space-y-6">
        @csrf
        @include('admin.marketplace-modules._form')
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
</x-admin-layout>
