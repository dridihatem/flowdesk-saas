<?php

use App\Models\User;
use App\Services\DeveloperDocumentationService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('platform admin can open developer documentation', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.developer-docs.index'))
        ->assertOk()
        ->assertSee(__('Developer documentation'), false)
        ->assertSee('app/', false);
});

test('workspace user cannot open developer documentation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.developer-docs.index'))
        ->assertForbidden();
});

test('developer documentation service flattens file tree', function () {
    $service = app(DeveloperDocumentationService::class);

    $rows = $service->flattenTree([
        'app/' => [
            'Models/' => 'Eloquent models',
            'Services/' => 'Business logic',
        ],
    ]);

    expect($rows)->toHaveCount(3);
    expect($rows[1]['path'])->toBe('Models/');
    expect($rows[1]['hint'])->toBe('Eloquent models');
});
