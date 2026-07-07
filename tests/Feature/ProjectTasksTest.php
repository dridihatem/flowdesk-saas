<?php

use App\Enums\ProjectFileCategory;
use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('company user can view kanban and gantt and create task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->get(route('projects.tasks.kanban', $project))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('projects.tasks.gantt', $project))
        ->assertOk();

    $this->actingAs($user)
        ->post(route('projects.tasks.store', $project), [
            'title' => 'First task',
            'status' => TaskStatus::Todo->value,
        ])
        ->assertRedirect();

    expect($project->tasks()->count())->toBe(1);
});

test('tasks can be reordered via json', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['company_id' => $user->company_id]);
    $t1 = $project->tasks()->create([
        'company_id' => $project->company_id,
        'title' => 'A',
        'status' => TaskStatus::Todo,
        'sort_order' => 0,
    ]);
    $t2 = $project->tasks()->create([
        'company_id' => $project->company_id,
        'title' => 'B',
        'status' => TaskStatus::Todo,
        'sort_order' => 1,
    ]);

    $this->actingAs($user)
        ->postJson(route('projects.tasks.reorder', $project), [
            'columns' => [
                'todo' => [$t2->id, $t1->id],
                'in_progress' => [],
                'review' => [],
                'done' => [],
            ],
        ])
        ->assertOk();

    expect($t2->fresh()->sort_order)->toBe(0)
        ->and($t1->fresh()->sort_order)->toBe(1);
});

test('task can store amount currency and billable', function () {
    $user = User::factory()->create(['default_currency' => 'USD']);
    $project = Project::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->post(route('projects.tasks.store', $project), [
            'title' => 'Billable item',
            'status' => TaskStatus::Todo->value,
            'amount' => '199.50',
            'currency' => 'EUR',
            'billable' => '1',
        ])
        ->assertRedirect();

    $task = $project->tasks()->first();
    expect($task)->not->toBeNull()
        ->and($task->amount_cents)->toBe(19950)
        ->and($task->currency)->toBe('EUR')
        ->and($task->billable)->toBeTrue();
});

test('task file can be uploaded', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $project = Project::factory()->create(['company_id' => $user->company_id]);
    $task = $project->tasks()->create([
        'company_id' => $project->company_id,
        'title' => 'With file',
        'status' => TaskStatus::Todo,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('projects.tasks.files.store', [$project, $task]), [
            'file' => UploadedFile::fake()->create('doc.pdf', 120, 'application/pdf'),
            'category' => ProjectFileCategory::Document->value,
        ])
        ->assertRedirect();

    expect($task->files()->count())->toBe(1);
});

test('task stores scope price mode and timer endpoints work', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['company_id' => $user->company_id]);

    $this->actingAs($user)
        ->post(route('projects.tasks.store', $project), [
            'title' => 'Scoped',
            'status' => TaskStatus::Todo->value,
            'scope' => TaskScope::Extra->value,
            'price_mode' => TaskPriceMode::Additive->value,
            'amount' => '50.00',
        ])
        ->assertRedirect();

    $task = $project->tasks()->first();
    expect($task->scope)->toBe(TaskScope::Extra)
        ->and($task->price_mode)->toBe(TaskPriceMode::Additive);

    $this->actingAs($user);
    $this->travelTo(now());

    $this->postJson(route('projects.tasks.tracking.start', [$project, $task]))
        ->assertOk()
        ->assertJsonPath('running', true);

    $this->travel(3)->seconds();

    $this->postJson(route('projects.tasks.tracking.pause', [$project, $task]))
        ->assertOk()
        ->assertJsonPath('running', false);

    $task->refresh();
    expect($task->tracking_started_at)->toBeNull()
        ->and((int) $task->tracking_accumulated_seconds)->toBeGreaterThanOrEqual(3);
});

test('other company cannot access project tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.tasks.kanban', $project))
        ->assertNotFound();
});
