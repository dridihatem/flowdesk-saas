<?php

use App\Models\Client;
use App\Models\InstalledModule;
use App\Models\User;
use App\Services\ModuleInstallerService;
use App\Services\PlatformLlmRouter;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    if (! class_exists(ZipArchive::class)) {
        $this->markTestSkipped('ZipArchive extension required.');
    }

    $this->seed(RoleSeeder::class);
});

/**
 * @param  array<string, string>  $files
 */
function integrationModuleZip(array $files): string
{
    $path = sys_get_temp_dir().'/flowdesk-module-integration-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    expect($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();

    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return $path;
}

function integrationModuleManifest(): string
{
    return json_encode([
        'slug' => 'integration-test-mod',
        'name' => 'Integration Test Module',
        'version' => '1.0.0',
        'description' => 'Tests CRM links and Nova AI from a zip module.',
        'integrations' => [
            'clients' => true,
            'invoices' => true,
            'calendar' => true,
        ],
        'ai' => [
            'modes' => ['summary', 'client_email'],
            'label' => 'Nova test label',
        ],
    ], JSON_THROW_ON_ERROR);
}

function integrationModuleMigration(): string
{
    return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_integration_test_records')) {
            return;
        }

        Schema::create('module_integration_test_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_integration_test_records');
    }
};
PHP;
}

function integrationModuleIndexBlade(): string
{
    return <<<'BLADE'
@php
    $companyId = auth()->user()->company_id;
    $rows = \Illuminate\Support\Facades\DB::table('module_integration_test_records')
        ->where('company_id', $companyId)
        ->get();
@endphp
<div data-module-integration-test>
    <h3 class="text-lg font-semibold">{{ $module->name }}</h3>
    <ul>
        @foreach ($rows as $row)
            @php
                $client = $row->client_id
                    ? \Illuminate\Support\Facades\DB::table('clients')
                        ->where('company_id', $companyId)
                        ->where('id', $row->client_id)
                        ->first()
                    : null;
            @endphp
            <li>
                <span>{{ $row->title }}</span>
                @if ($client)
                    <a href="{{ route('clients.edit', $client->id) }}" class="module-client-link">{{ $client->name }}</a>
                @endif
            </li>
        @endforeach
    </ul>
    <a href="{{ route('calendar.index') }}" class="module-calendar-link">Calendar</a>
    <a href="{{ $novaAssistantUrl }}" class="module-nova-chat-link">Nova chat</a>
    <span class="module-nova-suggest-url">{{ $novaSuggestUrl }}</span>
    <div
        x-data="{
            async pingNova() {
                const res = await fetch(@js($novaSuggestUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({ mode: 'summary', context: 'Module integration test' }),
                });
                return res.ok;
            }
        }"
        data-nova-ready
    ></div>
</div>
BLADE;
}

test('module install runs migrations and stores integrations manifest', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    $company = $user->company;

    $zipPath = integrationModuleZip([
        'module.json' => integrationModuleManifest(),
        'views/index.blade.php' => integrationModuleIndexBlade(),
        'database/migrations/2026_06_10_120000_create_module_integration_test_records_table.php' => integrationModuleMigration(),
    ]);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $company,
        new UploadedFile($zipPath, 'integration-test-mod.zip', 'application/zip', null, true),
    );

    @unlink($zipPath);

    expect($module->slug)->toBe('integration-test-mod');
    expect($module->manifest['integrations']['clients'] ?? false)->toBeTrue();
    expect($module->manifest['ai']['modes'] ?? [])->toContain('summary');
    expect(Schema::hasTable('module_integration_test_records'))->toBeTrue();

    app(ModuleInstallerService::class)->uninstall($module);
});

test('installed module page links to core CRM and exposes Nova URLs', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    $company = $user->company;
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Integration Client SA',
    ]);

    $zipPath = integrationModuleZip([
        'module.json' => integrationModuleManifest(),
        'views/index.blade.php' => integrationModuleIndexBlade(),
        'database/migrations/2026_06_10_120000_create_module_integration_test_records_table.php' => integrationModuleMigration(),
    ]);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $company,
        new UploadedFile($zipPath, 'integration-test-mod.zip', 'application/zip', null, true),
    );
    @unlink($zipPath);

    DB::table('module_integration_test_records')->insert([
        'id' => (string) Str::ulid(),
        'company_id' => $company->id,
        'client_id' => $client->id,
        'title' => 'Linked listing',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('modules.show', $module->slug))
        ->assertOk()
        ->assertSee('data-module-integration-test', false)
        ->assertSee('Integration Client SA')
        ->assertSee(route('clients.edit', $client->id), false)
        ->assertSee(route('calendar.index'), false)
        ->assertSee(route('assistant.index'), false)
        ->assertSee(route('assistant.suggest'), false)
        ->assertSee('data-nova-ready', false);

    app(ModuleInstallerService::class)->uninstall($module);
});

test('module user can call Nova suggest endpoint used by module views', function () {
    $user = User::factory()->create();
    $user->syncRoles(['team_member']);
    $company = $user->company;

    $this->mock(PlatformLlmRouter::class, function ($mock) {
        $mock->shouldReceive('isAvailable')->andReturn(true);
        $mock->shouldReceive('suggest')->once()->andReturn([
            'suggestion' => 'Module-aware summary.',
            'model' => 'test-model',
            'input_tokens' => 10,
            'output_tokens' => 20,
            'total_tokens' => 30,
        ]);
    });

    $this->actingAs($user)
        ->postJson(route('assistant.suggest'), [
            'mode' => 'summary',
            'context' => 'Module: Integration Test — Client: ACME',
        ])
        ->assertOk()
        ->assertJsonPath('suggestion', 'Module-aware summary.');
});

test('settings modules page shows integration and Nova badges from manifest', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    InstalledModule::query()->create([
        'company_id' => $user->company_id,
        'slug' => 'badge-test-mod',
        'name' => 'Badge Test',
        'version' => '1.0.0',
        'manifest' => [
            'integrations' => ['clients' => true, 'calendar' => true],
            'ai' => ['modes' => ['summary']],
        ],
        'storage_path' => 'workspaces/'.$user->company_id.'/modules/badge-test-mod',
        'is_enabled' => true,
        'installed_at' => now(),
    ]);

    File::ensureDirectoryExists(storage_path('app/workspaces/'.$user->company_id.'/modules/badge-test-mod/views'));
    File::put(
        storage_path('app/workspaces/'.$user->company_id.'/modules/badge-test-mod/views/index.blade.php'),
        '<div>Badge test</div>',
    );

    $this->actingAs($user)
        ->get(route('settings.modules'))
        ->assertOk()
        ->assertSee('id="module-search"', false)
        ->assertSee('clients')
        ->assertSee('calendar')
        ->assertSee('Nova: summary');
});

test('single page module gets automatic settings tab and core settings view', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $zipPath = integrationModuleZip([
        'module.json' => integrationModuleManifest(),
        'views/index.blade.php' => integrationModuleIndexBlade(),
        'database/migrations/2026_06_10_120000_create_module_integration_test_records_table.php' => integrationModuleMigration(),
    ]);

    $module = app(ModuleInstallerService::class)->installFromZip(
        $user->company,
        new UploadedFile($zipPath, 'integration-test-mod.zip', 'application/zip', null, true),
    );
    @unlink($zipPath);

    $slugs = collect($module->navigationPages())->pluck('slug')->all();
    expect($slugs)->toContain('', 'settings');

    $this->actingAs($user)
        ->get(route('modules.show', ['slug' => $module->slug, 'page' => 'settings']))
        ->assertOk()
        ->assertSee('data-module-settings', false)
        ->assertSee(__('module_settings_integrations_heading'), false);

    app(ModuleInstallerService::class)->uninstall($module);
});

afterEach(function () {
    $leftovers = glob(sys_get_temp_dir().'/flowdesk-module-integration-*.zip') ?: [];
    foreach ($leftovers as $file) {
        @unlink($file);
    }
});
