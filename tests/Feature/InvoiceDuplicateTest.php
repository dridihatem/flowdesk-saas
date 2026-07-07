<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;

test('company user can duplicate an invoice', function () {
    $user = User::factory()->create();
    $company = $user->company;
    $invoice = Invoice::factory()->create(['company_id' => $company->id]);
    InvoiceItem::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'description' => 'Line A',
        'quantity' => 1,
        'unit_amount' => 1000,
        'total_amount' => 1000,
    ]);

    $this->actingAs($user)
        ->post(route('invoices.duplicate', $invoice))
        ->assertRedirect();

    expect(Invoice::query()->withoutGlobalScope('tenant')->where('company_id', $company->id)->count())->toBe(2);
});

test('invoice preview panel returns html fragment', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create([
        'company_id' => $user->company_id,
        'number' => 'INV-PANEL-TEST',
    ]);

    $this->actingAs($user)
        ->get(route('invoices.preview-panel', $invoice), ['Accept' => 'text/html', 'X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertSee('INV-PANEL-TEST', false);
});

test('invoice preview panel is denied for another company invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.preview-panel', $invoice))
        ->assertNotFound();
});
