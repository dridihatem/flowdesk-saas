<?php

use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    PlatformSetting::query()->delete();
    PlatformSetting::query()->create([
        'ai_provider' => 'google',
        'google_api_key_encrypted' => 'test-gemini-key',
        'gemini_model' => 'gemini-2.0-flash',
    ]);
});

function fakeGeminiLineItemsResponse(): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => json_encode([
                                'items' => [
                                    ['description' => 'Website design', 'quantity' => 1, 'unit_price_major' => 1500],
                                    ['description' => 'Hosting (12 mo)', 'quantity' => 12, 'unit_price_major' => 25],
                                ],
                            ])],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 100,
                'candidatesTokenCount' => 50,
                'totalTokenCount' => 150,
            ],
        ]),
    ]);
}

test('invoice draft scan extracts line items from uploaded image', function () {
    fakeGeminiLineItemsResponse();
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('quote.jpg', 800, 600);

    $response = $this->actingAs($user)
        ->post(route('invoices.ai-line-items.scan.draft'), [
            'document' => $file,
            'currency' => 'USD',
            'replace' => true,
        ], ['Accept' => 'application/json']);

    $response->assertOk()
        ->assertJsonPath('items.0.description', 'Website design')
        ->assertJsonPath('items.0.quantity', 1)
        ->assertJsonPath('items.0.unit_major', 1500)
        ->assertJsonPath('items.1.description', 'Hosting (12 mo)')
        ->assertJsonCount(2, 'items')
        ->assertJsonStructure(['disclaimer', 'ai_credits_charged']);
});

test('invoice scan requires a document file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('invoices.ai-line-items.scan.draft'), [
            'currency' => 'USD',
        ])
        ->assertUnprocessable();
});

test('existing invoice scan is tenant scoped', function () {
    fakeGeminiLineItemsResponse();
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create([
        'company_id' => $user->company_id,
        'currency' => 'EUR',
    ]);
    $otherInvoice = Invoice::factory()->create();

    $file = UploadedFile::fake()->image('scan.png');

    $this->actingAs($user)
        ->post(route('invoices.ai-line-items.scan', $otherInvoice), [
            'document' => $file,
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('invoices.ai-line-items.scan', $invoice), [
            'document' => $file,
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('items.0.description', 'Website design');
});

test('proposal draft scan extracts line items', function () {
    fakeGeminiLineItemsResponse();
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $this->actingAs($user)
        ->post(route('proposals.ai-line-items.scan.draft'), [
            'document' => $file,
            'currency' => 'USD',
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('items.0.unit_major', 1500);
});

test('proposal scan on existing quote works for owner company', function () {
    fakeGeminiLineItemsResponse();
    $user = User::factory()->create();
    $proposal = Proposal::factory()->create([
        'company_id' => $user->company_id,
        'currency' => 'USD',
    ]);
    $file = UploadedFile::fake()->image('quote.png');

    $this->actingAs($user)
        ->post(route('proposals.ai-line-items.scan', $proposal), [
            'document' => $file,
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonCount(2, 'items');
});
