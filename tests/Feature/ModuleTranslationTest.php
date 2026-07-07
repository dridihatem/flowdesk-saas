<?php

use App\Models\InstalledModule;
use App\Models\User;
use App\Services\ModuleInstallerService;
use App\Services\ModuleSecurityScanner;
use App\Services\ModuleTranslationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }

    $this->seed(RoleSeeder::class);
});

test('module security scanner accepts lang json files', function () {
    $zipPath = sys_get_temp_dir().'/flowdesk-lang-mod-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('module.json', json_encode([
        'slug' => 'lang-test-mod',
        'name' => 'Lang Test',
        'version' => '1.0.0',
    ]));
    $zip->addFromString('views/index.blade.php', '<div>{{ module_trans($module, "hello") }}</div>');
    $zip->addFromString('lang/en.json', json_encode(['hello' => 'Hello']));
    $zip->addFromString('lang/fr.json', json_encode(['hello' => 'Bonjour']));
    $zip->close();

    $zip = new ZipArchive;
    $zip->open($zipPath);
    app(ModuleSecurityScanner::class)->scanZipArchive($zip, '');
    $zip->close();
    @unlink($zipPath);

    expect(true)->toBeTrue();
});

test('module security scanner rejects unsupported locale json', function () {
    $zipPath = sys_get_temp_dir().'/flowdesk-lang-bad-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('module.json', json_encode([
        'slug' => 'lang-bad-mod',
        'name' => 'Lang Bad',
        'version' => '1.0.0',
    ]));
    $zip->addFromString('views/index.blade.php', '<div>ok</div>');
    $zip->addFromString('lang/de.json', json_encode(['hello' => 'Hallo']));
    $zip->close();

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module translations load and resolve by locale', function () {
    $user = User::factory()->create();
    $module = InstalledModule::query()->create([
        'company_id' => $user->company_id,
        'slug' => 'demo-lang-mod',
        'name' => 'Demo Lang',
        'version' => '1.0.0',
        'manifest' => ['slug' => 'demo-lang-mod'],
        'storage_path' => 'workspaces/'.$user->company_id.'/modules/demo-lang-mod',
        'is_enabled' => true,
        'installed_at' => now(),
    ]);

    $langDir = storage_path('app/'.$module->storage_path.'/lang');
    File::ensureDirectoryExists($langDir);
    file_put_contents($langDir.'/en.json', json_encode(['greeting' => 'Hello']));
    file_put_contents($langDir.'/fr.json', json_encode(['greeting' => 'Bonjour']));

    $service = app(ModuleTranslationService::class);

    app()->setLocale('fr');
    $service->register($module);
    expect(module_trans($module, 'greeting'))->toBe('Bonjour');

    app()->setLocale('en');
    $service->register($module);
    expect(module_trans($module, 'greeting'))->toBe('Hello');

    File::deleteDirectory(storage_path('app/workspaces/'.$user->company_id.'/modules/demo-lang-mod'));
});

test('installed module localized name uses manifest locales', function () {
    $module = new InstalledModule([
        'slug' => 'x-mod',
        'name' => 'Default Name',
        'description' => 'Default desc',
        'manifest' => [
            'locales' => [
                'fr' => ['name' => 'Nom FR', 'description' => 'Desc FR'],
            ],
        ],
    ]);

    app()->setLocale('fr');
    expect($module->localizedName())->toBe('Nom FR');
    expect($module->localizedDescription())->toBe('Desc FR');

    app()->setLocale('en');
    expect($module->localizedName())->toBe('Default Name');
});

test('property listings zip with lang installs and renders translated string', function () {
    $zipPath = dirname(__DIR__, 2).'/storage/stubs/qatar/zips/qatar-property-listings.zip';
    if (! is_file($zipPath)) {
        $this->markTestSkipped('Rebuild qatar zips first.');
    }

    $user = User::factory()->create(['locale' => 'fr']);
    $user->syncRoles(['company_admin']);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($zipPath, 'qatar-property-listings.zip', 'application/zip', null, true),
    );

    app()->setLocale('fr');

    $this->actingAs($user)
        ->get(route('modules.show', $module->slug))
        ->assertOk()
        ->assertSee('Ajouter une annonce', false);
});
