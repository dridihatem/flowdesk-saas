<?php

use App\Models\InstalledModule;
use App\Models\User;
use App\Services\ModuleInstallerService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }

    $this->seed(RoleSeeder::class);
});

$bundleZip = dirname(__DIR__, 2).'/storage/stubs/qatar/zips/qatar-real-estate.zip';

test('qatar real estate bundle zip installs with all tables', function () use ($bundleZip) {
    if (! is_file($bundleZip)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($bundleZip, 'qatar-real-estate.zip', 'application/zip', null, true),
    );

    expect($module->slug)->toBe('qatar-real-estate');
    expect($module->isBundle())->toBeTrue();
    expect($module->includesModules())->toHaveCount(3);
    expect(Schema::hasTable('module_property_listings'))->toBeTrue();
    expect(Schema::hasTable('module_property_viewings'))->toBeTrue();
    expect(Schema::hasTable('module_deal_splits'))->toBeTrue();
});

test('bundle module renders sub-pages and navigation', function () use ($bundleZip) {
    if (! is_file($bundleZip)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($bundleZip, 'qatar-real-estate.zip', 'application/zip', null, true),
    );

    $pages = $module->navigationPages();
    expect($pages)->not->toBeEmpty();
    expect(collect($pages)->pluck('slug')->all())->toContain('', 'listings', 'viewings', 'commissions', 'settings');

    $this->actingAs($user)
        ->get(route('modules.show', $module->slug))
        ->assertOk()
        ->assertSee('Includes', false)
        ->assertSee('Property Listings', false);

    $this->actingAs($user)
        ->get(route('modules.show', ['slug' => $module->slug, 'page' => 'listings']))
        ->assertOk()
        ->assertSee(module_trans($module, 'add_listing'), false);
});

test('standalone module shows part of bundle in manifest', function () {
    $user = User::factory()->create();
    $module = InstalledModule::query()->create([
        'company_id' => $user->company_id,
        'slug' => 'qatar-property-listings',
        'name' => 'Property Listings',
        'version' => '1.0.0',
        'manifest' => [
            'part_of_bundle' => ['slug' => 'qatar-real-estate', 'name' => 'Qatar Real Estate Suite'],
            'related_modules' => [
                ['slug' => 'qatar-real-estate', 'name' => 'Full suite', 'paid' => false, 'included' => true],
            ],
        ],
        'storage_path' => 'workspaces/test/listings',
        'is_enabled' => true,
        'installed_at' => now(),
    ]);

    expect($module->partOfBundle()['slug'])->toBe('qatar-real-estate');
    expect($module->relatedModules())->toHaveCount(1);
});
