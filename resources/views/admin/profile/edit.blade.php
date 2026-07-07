<x-admin-layout :title="__('Profile')">
    <x-flow.page-header
        :title="__('Profile')"
        :description="__('admin_profile_intro')"
    />

    <div class="mt-8 max-w-2xl space-y-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.profile.partials.update-password-form')
        </div>

        <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-6 shadow-sm">
            @include('admin.profile.partials.delete-user-form')
        </div>
    </div>
</x-admin-layout>
