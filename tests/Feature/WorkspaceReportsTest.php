<?php

use App\Models\Invoice;
use App\Models\User;

test('workspace user can view company reports', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reports.index'))->assertOk();
});

test('workspace user can download invoices csv export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reports.export', ['type' => 'invoices']))->assertOk();
});

test('workspace user can download invoices pdf zip export', function () {
    $user = User::factory()->create();
    $company = $user->company;
    Invoice::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->get(route('reports.export', ['type' => 'invoices-pdf']))
        ->assertOk()
        ->assertHeader('content-type', 'application/zip');
});

test('invoices pdf zip export redirects when no invoices in range', function () {
    $user = User::factory()->create();
    $from = now()->subYears(2)->format('Y-m-d');
    $to = now()->subYears(2)->format('Y-m-d');

    $this->actingAs($user)
        ->get(route('reports.export', [
            'type' => 'invoices-pdf',
            'from' => $from,
            'to' => $to,
        ]))
        ->assertRedirect(route('reports.index', ['from' => $from, 'to' => $to]))
        ->assertSessionHasErrors('export');
});

test('invoices pdf zip export includes invoices created on the last day of range', function () {
    $user = User::factory()->create();
    $company = $user->company;
    $day = now()->subDays(3);

    Invoice::factory()->create([
        'company_id' => $company->id,
        'created_at' => $day->copy()->setTime(15, 30),
    ]);

    $this->actingAs($user)
        ->get(route('reports.export', [
            'type' => 'invoices-pdf',
            'from' => $day->format('Y-m-d'),
            'to' => $day->format('Y-m-d'),
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/zip');
});
