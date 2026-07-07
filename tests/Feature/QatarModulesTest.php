<?php

use App\Models\User;
use App\Services\ModuleInstallerService;
use App\Services\ModuleSecurityScanner;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }

    $this->seed(RoleSeeder::class);
});

$zipsDir = dirname(__DIR__, 2).'/storage/stubs/qatar/zips';

test('qatar module zips pass security scan', function () use ($zipsDir) {
    $zips = glob($zipsDir.'/*.zip') ?: [];
    expect($zips)->not->toBeEmpty();

    $scanner = app(ModuleSecurityScanner::class);

    foreach ($zips as $zipPath) {
        $zip = new ZipArchive;
        expect($zip->open($zipPath))->toBeTrue();
        $scanner->scanZipArchive($zip, '');
        $zip->close();
    }
})->group('qatar-modules');

test('qatar property listings zip installs with CRM tables', function () use ($zipsDir) {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $zipPath = $zipsDir.'/qatar-property-listings.zip';
    expect(is_file($zipPath))->toBeTrue();

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($zipPath, 'qatar-property-listings.zip', 'application/zip', null, true),
    );

    expect($module->slug)->toBe('qatar-property-listings');
    expect($module->manifest['integrations']['clients'] ?? false)->toBeTrue();
    expect($module->manifest['ai']['modes'] ?? [])->not->toBeEmpty();
    expect(Schema::hasTable('module_property_listings'))->toBeTrue();

    app(ModuleInstallerService::class)->uninstall($module);
})->group('qatar-modules');
