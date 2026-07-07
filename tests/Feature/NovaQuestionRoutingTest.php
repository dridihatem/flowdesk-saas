<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\Project;
use App\Services\NovaQuestionIntentService;
use App\Services\NovaWebSearchService;

test('nova intent classifies general knowledge questions', function () {
    $company = Company::factory()->create();
    $intent = app(NovaQuestionIntentService::class);

    $result = $intent->analyze($company, 'What is the name of the music of Metallica?');

    expect($result['intent'])->toBe('general')
        ->and($result['use_web_search'])->toBeTrue();
});

test('nova intent classifies workspace questions', function () {
    $company = Company::factory()->create();
    $intent = app(NovaQuestionIntentService::class);

    $result = $intent->analyze($company, 'How many unpaid invoices do we have this month?');

    expect($result['intent'])->toBe('workspace')
        ->and($result['use_web_search'])->toBeFalse();
});

test('nova intent matches project names in workspace questions', function () {
    $company = Company::factory()->create();
    $project = Project::factory()->create([
        'company_id' => $company->id,
        'title' => 'Website Redesign Alpha',
    ]);

    $intent = app(NovaQuestionIntentService::class);
    $result = $intent->analyze($company, 'Tell me about the Website Redesign Alpha project status');

    expect($result['intent'])->toBe('workspace')
        ->and($result['project_ids'])->toContain($project->id);
});

test('nova intent matches client names in workspace questions', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Acme Holdings',
    ]);

    $intent = app(NovaQuestionIntentService::class);
    $result = $intent->analyze($company, 'What projects does Acme Holdings have?');

    expect($result['intent'])->toBe('workspace')
        ->and($result['client_ids'])->toContain($client->id);
});

test('nova web search returns formatted snippets when api responds', function () {
    Illuminate\Support\Facades\Http::fake([
        'api.duckduckgo.com/*' => Http::response([
            'Heading' => 'Metallica',
            'AbstractText' => 'Metallica is an American heavy metal band.',
            'AbstractURL' => 'https://example.com/metallica',
            'RelatedTopics' => [],
        ]),
    ]);

    $snippets = app(NovaWebSearchService::class)->searchSnippets('Metallica music');

    expect($snippets)->toContain('Metallica')
        ->and($snippets)->toContain('heavy metal');
});
