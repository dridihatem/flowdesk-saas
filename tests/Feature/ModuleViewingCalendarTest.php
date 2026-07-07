<?php

use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use App\Services\ModuleInstallerService;
use App\Services\ModuleSettingsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }

    $this->seed(RoleSeeder::class);
});

$bundleZip = dirname(__DIR__, 2).'/storage/stubs/qatar/zips/qatar-real-estate.zip';

test('module viewing syncs to calendar when integration enabled', function () use ($bundleZip) {
    if (! is_file($bundleZip)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($bundleZip, 'qatar-real-estate.zip', 'application/zip', null, true),
    );

    expect(Schema::hasColumn('module_property_viewings', 'calendar_event_id'))->toBeTrue();

    app(ModuleSettingsService::class)->saveIntegrations($module, ['calendar' => true]);
    $module->refresh();

    $scheduledAt = now()->addDays(2)->format('Y-m-d H:i:s');

    $this->actingAs($user)
        ->post(route('modules.actions', $module->slug), [
            'module_action' => 'store_viewing',
            'return_page' => 'viewings',
            'property_title' => 'Pearl 2BR',
            'zone' => 'The Pearl',
            'scheduled_at' => $scheduledAt,
        ])
        ->assertRedirect(route('modules.show', ['slug' => $module->slug, 'page' => 'viewings']));

    $viewing = DB::table('module_property_viewings')
        ->where('company_id', $user->company_id)
        ->where('property_title', 'Pearl 2BR')
        ->first();

    expect($viewing)->not->toBeNull();
    expect($viewing->calendar_event_id)->not->toBeNull();

    $event = WorkspaceCalendarEvent::query()->find($viewing->calendar_event_id);
    expect($event)->not->toBeNull();
    expect($event->source_type)->toBe('module_property_viewing');
    expect($event->kind)->toBe('appointment');

    app(ModuleInstallerService::class)->uninstall($module);
});

test('module viewing can be updated and deleted', function () use ($bundleZip) {
    if (! is_file($bundleZip)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($bundleZip, 'qatar-real-estate.zip', 'application/zip', null, true),
    );

    $this->actingAs($user)
        ->post(route('modules.actions', $module->slug), [
            'module_action' => 'store_viewing',
            'return_page' => 'viewings',
            'property_title' => 'Old title',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $viewing = DB::table('module_property_viewings')
        ->where('company_id', $user->company_id)
        ->where('property_title', 'Old title')
        ->first();

    $this->actingAs($user)
        ->post(route('modules.actions', $module->slug), [
            'module_action' => 'update_viewing',
            'return_page' => 'viewings',
            'viewing_id' => $viewing->id,
            'property_title' => 'New title',
            'zone' => 'West Bay',
        ])
        ->assertRedirect();

    expect(DB::table('module_property_viewings')->where('id', $viewing->id)->value('property_title'))
        ->toBe('New title');

    $this->actingAs($user)
        ->post(route('modules.actions', $module->slug), [
            'module_action' => 'delete_viewing',
            'return_page' => 'viewings',
            'viewing_id' => $viewing->id,
        ])
        ->assertRedirect();

    expect(DB::table('module_property_viewings')->where('id', $viewing->id)->exists())->toBeFalse();

    app(ModuleInstallerService::class)->uninstall($module);
});

test('module settings page saves calendar integration toggle', function () use ($bundleZip) {
    if (! is_file($bundleZip)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($bundleZip, 'qatar-real-estate.zip', 'application/zip', null, true),
    );

    $this->actingAs($user)
        ->get(route('modules.show', ['slug' => $module->slug, 'page' => 'settings']))
        ->assertOk()
        ->assertSee(__('module_settings_integrations_heading'), false);

    $this->actingAs($user)
        ->post(route('modules.actions', $module->slug), [
            'module_action' => 'save_integrations',
            'return_page' => 'settings',
            'integrations' => ['calendar' => '1', 'clients' => '1'],
        ])
        ->assertRedirect(route('modules.show', ['slug' => $module->slug, 'page' => 'settings']));

    $module->refresh();
    expect(app(ModuleSettingsService::class)->isIntegrationEnabled($module, $user->company, 'calendar'))->toBeTrue();

    app(ModuleInstallerService::class)->uninstall($module);
});
