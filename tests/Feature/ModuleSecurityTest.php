<?php

use App\Models\Company;
use App\Models\User;
use App\Services\ModuleInstallerService;
use App\Services\ModuleSecurityScanner;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }
});

/**
 * @param  array<string, string>  $files
 */
function moduleZipFromFiles(array $files): string
{
    $path = sys_get_temp_dir().'/flowdesk-module-test-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    expect($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();

    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return $path;
}

function validModuleManifest(): string
{
    return json_encode([
        'slug' => 'test-secure-mod',
        'name' => 'Secure Test Module',
        'version' => '1.0.0',
    ], JSON_THROW_ON_ERROR);
}

test('module security scanner accepts a minimal valid package', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => '<div>Hello {{ $module->name }}</div>',
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);
    app(ModuleSecurityScanner::class)->scanZipArchive($zip, '');
    $zip->close();
    @unlink($zipPath);

    expect(true)->toBeTrue();
});

test('module security scanner rejects path traversal in zip', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => '<div>ok</div>',
        '../evil.blade.php' => 'bad',
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module security scanner rejects standalone php backdoor', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => '<div>ok</div>',
        'backdoor.php' => '<?php eval($_GET["c"]);',
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module security scanner rejects eval in blade view', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => "@php eval('echo 1;'); @endphp",
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module security scanner rejects db raw in blade view', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => "@php DB::raw('DROP TABLE users'); @endphp",
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module security scanner rejects executable magic bytes', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => '<div>ok</div>',
        'assets/icon.png' => "MZ\x90\x00fake exe",
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module security scanner rejects unsafe migration sql', function () {
    $zipPath = moduleZipFromFiles([
        'module.json' => validModuleManifest(),
        'views/index.blade.php' => '<div>ok</div>',
        'database/migrations/2026_06_10_000001_evil.php' => <<<'PHP'
<?php
use Illuminate\Support\Facades\DB;
return new class extends Illuminate\Database\Migrations\Migration {
    public function up(): void {
        DB::unprepared('DROP TABLE users');
    }
    public function down(): void {}
};
PHP,
    ]);

    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(fn () => app(ModuleSecurityScanner::class)->scanZipArchive($zip, ''))
        ->toThrow(RuntimeException::class);

    $zip->close();
    @unlink($zipPath);
});

test('module installer rejects malicious zip upload', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['company_admin']);

    $zipPath = moduleZipFromFiles([
        'module.json' => json_encode(['slug' => 'evil-mod', 'name' => 'Evil', 'version' => '1.0.0']),
        'views/index.blade.php' => '<div>ok</div>',
        'virus.exe' => 'MZfake',
    ]);

    $upload = new UploadedFile($zipPath, 'evil.zip', 'application/zip', null, true);

    expect(fn () => app(ModuleInstallerService::class)->installFromZip($company, $upload))
        ->toThrow(RuntimeException::class);

    @unlink($zipPath);
});

test('module registry rejects unsafe page paths', function () {
    $scanner = app(ModuleSecurityScanner::class);

    expect(fn () => $scanner->assertSafeViewPage('../secret'))
        ->toThrow(RuntimeException::class);

    expect($scanner->assertSafeViewPage('reports/summary'))->toBe('reports/summary');
});

test('sample quick notes module passes security scan', function () {
    $zipPath = storage_path('stubs/quick-notes-module.zip');
    if (! is_file($zipPath)) {
        $this->markTestSkipped('quick-notes-module.zip stub missing.');
    }

    $zip = new ZipArchive;
    $zip->open($zipPath);
    app(ModuleSecurityScanner::class)->scanZipArchive($zip, 'module-sample/');
    $zip->close();

    expect(true)->toBeTrue();
});

afterEach(function () {
    $leftovers = glob(sys_get_temp_dir().'/flowdesk-module-test-*.zip') ?: [];
    foreach ($leftovers as $file) {
        @unlink($file);
    }
});
