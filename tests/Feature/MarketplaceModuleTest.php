<?php

use App\Enums\MarketplaceModuleBillingPeriod;
use App\Enums\MarketplaceModuleCategory;
use App\Enums\MarketplaceOrderStatus;
use App\Mail\MarketplaceOrderPaidMail;
use App\Models\MarketplaceModule;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\InvoicePaymentGatewayService;
use App\Services\MarketplaceCartService;
use App\Services\MarketplaceCheckoutService;
use App\Services\MarketplaceOrderFulfillmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('marketing module detail page is public', function () {
    MarketplaceModule::query()->create([
        'slug' => 'detail-mod',
        'name' => 'Detail Module',
        'description' => 'Short summary',
        'detail_content' => 'Full overview of the module for marketing.',
        'category' => MarketplaceModuleCategory::RealEstate,
        'price_minor' => 4900,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'feature_bullets' => ['Feature A', 'Feature B'],
        'is_published' => true,
        'sort_order' => 0,
    ]);

    $this->get(route('modules.show', ['slug' => 'detail-mod']))
        ->assertOk()
        ->assertSee('Detail Module')
        ->assertSee('Full overview of the module')
        ->assertSee('Feature A');
});

test('unpublished module detail returns 404', function () {
    MarketplaceModule::query()->create([
        'slug' => 'secret-mod',
        'name' => 'Secret',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => false,
        'sort_order' => 0,
    ]);

    $this->get(route('modules.show', ['slug' => 'secret-mod']))
        ->assertNotFound();
});

test('marketing modules page groups modules by category', function () {
    MarketplaceModule::query()->create([
        'slug' => 'finance-mod',
        'name' => 'Finance Module',
        'category' => MarketplaceModuleCategory::Finance,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    MarketplaceModule::query()->create([
        'slug' => 'hr-mod',
        'name' => 'HR Module',
        'category' => MarketplaceModuleCategory::Hr,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 1,
    ]);

    $this->get(route('marketing.modules'))
        ->assertOk()
        ->assertSeeInOrder([
            __('marketplace_module_category.finance'),
            'Finance Module',
            __('marketplace_module_category.hr'),
            'HR Module',
        ]);
});

test('marketing modules page filters by category', function () {
    MarketplaceModule::query()->create([
        'slug' => 'finance-only',
        'name' => 'Finance Only',
        'category' => MarketplaceModuleCategory::Finance,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    MarketplaceModule::query()->create([
        'slug' => 'hr-hidden',
        'name' => 'HR Hidden',
        'category' => MarketplaceModuleCategory::Hr,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 1,
    ]);

    $this->get(route('marketing.modules', ['category' => 'finance']))
        ->assertOk()
        ->assertSee('Finance Only')
        ->assertDontSee('HR Hidden');
});

test('marketing modules page is public', function () {
    MarketplaceModule::query()->create([
        'slug' => 'demo-module',
        'name' => 'Demo Module',
        'description' => 'Test module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 2900,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
        'target_countries' => null,
    ]);

    $this->get(route('marketing.modules'))
        ->assertOk()
        ->assertSee('Demo Module');
});

test('marketing modules filters by country', function () {
    MarketplaceModule::query()->create([
        'slug' => 'qatar-only',
        'name' => 'Qatar Only Module',
        'category' => MarketplaceModuleCategory::RealEstate,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
        'target_countries' => ['QA'],
    ]);

    MarketplaceModule::query()->create([
        'slug' => 'global-mod',
        'name' => 'Global Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 1,
        'target_countries' => null,
    ]);

    $this->get(route('marketing.modules', ['region' => 'gcc', 'country' => 'QA']))
        ->assertOk()
        ->assertSee('Qatar Only Module')
        ->assertSee('Global Module');

    $this->get(route('marketing.modules', ['region' => 'gcc', 'country' => 'AE']))
        ->assertOk()
        ->assertSee('Global Module')
        ->assertDontSee('Qatar Only Module');
});

test('marketing modules sets currency from region', function () {
    MarketplaceModule::query()->create([
        'slug' => 'priced-mod',
        'name' => 'Priced Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 10000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    $this->get(route('marketing.modules', ['region' => 'gcc', 'country' => 'QA']))
        ->assertOk()
        ->assertSee('QAR');
});

test('unpublished modules are hidden on marketing page', function () {
    MarketplaceModule::query()->create([
        'slug' => 'hidden-module',
        'name' => 'Hidden Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => false,
        'sort_order' => 0,
    ]);

    $this->get(route('marketing.modules'))
        ->assertOk()
        ->assertDontSee('Hidden Module');
});

test('platform admin can manage marketplace modules', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)
        ->get(route('admin.marketplace-modules.index'))
        ->assertOk();

    $this->actingAs($user)
        ->post(route('admin.marketplace-modules.store'), [
            'slug' => 'admin-test-mod',
            'name' => 'Admin Test Module',
            'description' => 'Created in test',
            'category' => 'ecommerce',
            'price' => '49,00',
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'icon' => 'doc',
            'feature_bullets' => "Line one\nLine two",
            'sort_order' => 5,
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.marketplace-modules.index'));

    $mod = MarketplaceModule::query()->where('slug', 'admin-test-mod')->first();
    expect($mod)->not->toBeNull();
    expect($mod->price_minor)->toBe(4900);
    expect($mod->feature_bullets)->toBe(['Line one', 'Line two']);
});

test('company admin cannot access marketplace modules admin', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->get(route('admin.marketplace-modules.index'))
        ->assertForbidden();
});

test('visitor can add module to cart and view cart', function () {
    $module = MarketplaceModule::query()->create([
        'slug' => 'cart-mod',
        'name' => 'Cart Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 1500,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    $this->post(route('marketing.cart.add', $module), ['currency' => 'USD'])
        ->assertRedirect(route('marketing.cart'));

    $this->get(route('marketing.cart'))
        ->assertOk()
        ->assertSee('Cart Module');
});

test('marking marketplace order paid sends fulfillment email', function () {
    Mail::fake();

    $order = MarketplaceOrder::query()->create([
        'order_number' => 'MK-MAIL-01',
        'status' => MarketplaceOrderStatus::Pending,
        'customer_name' => 'Jane Buyer',
        'customer_email' => 'buyer@example.com',
        'total_minor' => 2000,
        'currency' => 'USD',
        'metadata' => ['payment_method' => 'bank'],
    ]);

    MarketplaceOrderItem::query()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_module_id' => null,
        'module_slug' => 'test-mod',
        'module_name' => 'Test Module',
        'price_minor' => 2000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly->value,
    ]);

    app(MarketplaceCheckoutService::class)->markPaid($order);

    Mail::assertSent(MarketplaceOrderPaidMail::class, function ($mail) {
        return $mail->hasTo('buyer@example.com');
    });

    expect($order->fresh()->metadata['fulfilled_at'] ?? null)->not->toBeNull();
});

test('platform admin can list and mark marketplace orders paid', function () {
    $admin = User::factory()->platformAdmin()->create();

    $order = MarketplaceOrder::query()->create([
        'order_number' => 'MK-TEST-01',
        'status' => MarketplaceOrderStatus::Pending,
        'customer_name' => 'Jane Buyer',
        'customer_email' => 'buyer@example.com',
        'total_minor' => 2000,
        'currency' => 'USD',
        'metadata' => ['payment_method' => 'bank'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.marketplace-orders.index'))
        ->assertOk()
        ->assertSee('MK-TEST-01')
        ->assertSee('buyer@example.com');

    $this->actingAs($admin)
        ->put(route('admin.marketplace-orders.status', $order), ['status' => 'paid'])
        ->assertRedirect(route('admin.marketplace-orders.show', $order));

    expect($order->fresh()->status)->toBe(MarketplaceOrderStatus::Paid);
    expect($order->fresh()->paid_at)->not->toBeNull();
});

test('bank transfer instructions include configured rib fields', function () {
    $row = PlatformSetting::query()->first() ?? new PlatformSetting;
    $row->payment_credentials = [
        'bank_account_holder' => 'FlowDesk SAS',
        'bank_name' => 'BNP Paribas',
        'bank_rib' => 'FR76 3000 4000 0100 0000 0000 000',
        'bank_bic' => 'BNPAFRPP',
    ];
    $row->save();

    $text = app(InvoicePaymentGatewayService::class)->bankTransferInstructions();

    expect($text)->toContain('FlowDesk SAS');
    expect($text)->toContain('BNP Paribas');
    expect($text)->toContain('FR76 3000 4000 0100 0000 0000 000');
});

test('bank checkout creates pending order with payment reference', function () {
    $row = PlatformSetting::query()->first() ?? new PlatformSetting;
    $row->payment_credentials = [
        'bank_account_holder' => 'FlowDesk',
        'bank_rib' => 'FR76 3000 4000 0100 0000 0000 000',
    ];
    $row->save();

    $module = MarketplaceModule::query()->create([
        'slug' => 'bank-mod-ref',
        'name' => 'Bank Module Ref',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 2000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    app(MarketplaceCartService::class)->add($module, 'USD');

    $this->post(route('marketing.checkout.store'), [
        'name' => 'Jane Buyer',
        'email' => 'ref-buyer@example.com',
        'company' => 'Acme',
        'payment_method' => 'bank',
    ])->assertRedirect();

    $order = MarketplaceOrder::query()->where('customer_email', 'ref-buyer@example.com')->first();
    expect($order)->not->toBeNull();
    expect($order->payment_reference)->toBe($order->order_number);
    expect($order->paymentReferenceLabel())->toBe($order->order_number);

    $this->get(route('marketing.checkout.pending', $order))
        ->assertOk()
        ->assertSee($order->paymentReferenceLabel());
});

test('platform admin can search marketplace orders by payment reference', function () {
    $admin = User::factory()->platformAdmin()->create();

    MarketplaceOrder::query()->create([
        'order_number' => 'MK-REF-AAA',
        'payment_reference' => 'MK-REF-AAA',
        'status' => MarketplaceOrderStatus::Pending,
        'customer_name' => 'Ref Buyer',
        'customer_email' => 'ref@example.com',
        'total_minor' => 2000,
        'currency' => 'USD',
    ]);

    MarketplaceOrder::query()->create([
        'order_number' => 'MK-OTHER-01',
        'payment_reference' => 'MK-OTHER-01',
        'status' => MarketplaceOrderStatus::Pending,
        'customer_name' => 'Other Buyer',
        'customer_email' => 'other@example.com',
        'total_minor' => 1000,
        'currency' => 'USD',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.marketplace-orders.index', ['reference' => 'REF-AAA']))
        ->assertOk()
        ->assertSee('MK-REF-AAA')
        ->assertDontSee('MK-OTHER-01');
});

test('bank checkout creates pending order and clears cart on stripe success path', function () {
    $row = PlatformSetting::query()->first() ?? new PlatformSetting;
    $row->payment_credentials = [
        'bank_account_holder' => 'FlowDesk',
        'bank_rib' => 'FR76 3000 4000 0100 0000 0000 000',
    ];
    $row->save();

    $module = MarketplaceModule::query()->create([
        'slug' => 'bank-mod',
        'name' => 'Bank Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 2000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    app(MarketplaceCartService::class)->add($module, 'USD');

    $this->post(route('marketing.checkout.store'), [
        'name' => 'Jane Buyer',
        'email' => 'buyer@example.com',
        'company' => 'Acme',
        'payment_method' => 'bank',
    ])->assertRedirect();

    $order = MarketplaceOrder::query()->where('customer_email', 'buyer@example.com')->first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe(MarketplaceOrderStatus::Pending);
    expect($order->items)->toHaveCount(1);
});

test('paid order download requires valid signature', function () {
    $module = MarketplaceModule::query()->create([
        'slug' => 'dl-mod',
        'name' => 'Download Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
        'is_published' => true,
        'sort_order' => 0,
        'zip_path' => 'marketplace-modules/fake/package.zip',
    ]);

    $order = MarketplaceOrder::query()->create([
        'order_number' => 'MK-TEST-001',
        'status' => MarketplaceOrderStatus::Paid,
        'customer_name' => 'Test',
        'customer_email' => 't@example.com',
        'total_minor' => 1000,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    $order->items()->create([
        'marketplace_module_id' => $module->id,
        'module_slug' => $module->slug,
        'module_name' => $module->name,
        'price_minor' => 1000,
        'currency' => 'USD',
        'billing_period' => 'monthly',
    ]);

    $this->get(route('marketing.order.download', ['order' => $order->id, 'module' => $module->id]))
        ->assertForbidden();

    $url = URL::signedRoute('marketing.order.download', ['order' => $order->id, 'module' => $module->id]);
    $this->get($url)->assertNotFound();
});

test('company settings modules page lists purchased modules with download', function () {
    $user = User::factory()->create();
    $company = $user->company;

    $zipRelative = 'marketplace-modules/test/purchased-mod.zip';
    Storage::disk('local')->put($zipRelative, 'fake-zip-content');

    $module = MarketplaceModule::query()->create([
        'slug' => 'purchased-mod',
        'name' => 'Purchased Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 2500,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime,
        'is_published' => true,
        'sort_order' => 0,
        'zip_path' => $zipRelative,
    ]);

    $order = MarketplaceOrder::query()->create([
        'order_number' => 'MK-PUR-01',
        'payment_reference' => 'MK-PUR-01',
        'status' => MarketplaceOrderStatus::Paid,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'company_id' => $company->id,
        'total_minor' => 2500,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    $order->items()->create([
        'marketplace_module_id' => $module->id,
        'module_slug' => $module->slug,
        'module_name' => $module->name,
        'price_minor' => 2500,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime->value,
    ]);

    $this->actingAs($user)
        ->get(route('settings.modules'))
        ->assertOk()
        ->assertSee(__('settings_modules_purchased_heading'))
        ->assertSee('Purchased Module')
        ->assertSee('MK-PUR-01');

    $this->actingAs($user)
        ->get(route('settings.modules.purchased.download', $module))
        ->assertOk()
        ->assertDownload('purchased-mod.zip');
});

test('company admin can remove purchased module from list', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    $company = $user->company;

    $module = MarketplaceModule::query()->create([
        'slug' => 'remove-me-mod',
        'name' => 'Remove Me Module',
        'category' => MarketplaceModuleCategory::General,
        'price_minor' => 2500,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime,
        'is_published' => true,
        'sort_order' => 0,
    ]);

    $order = MarketplaceOrder::query()->create([
        'order_number' => 'MK-RM-01',
        'payment_reference' => 'MK-RM-01',
        'status' => MarketplaceOrderStatus::Paid,
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'company_id' => $company->id,
        'total_minor' => 2500,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    $item = $order->items()->create([
        'marketplace_module_id' => $module->id,
        'module_slug' => $module->slug,
        'module_name' => $module->name,
        'price_minor' => 2500,
        'currency' => 'USD',
        'billing_period' => MarketplaceModuleBillingPeriod::OneTime->value,
    ]);

    $this->actingAs($user)
        ->get(route('settings.modules'))
        ->assertOk()
        ->assertSee('Remove Me Module');

    $this->actingAs($user)
        ->delete(route('settings.modules.purchased.destroy', $item))
        ->assertRedirect(route('settings.modules'))
        ->assertSessionHas('status');

    expect(app(MarketplaceOrderFulfillmentService::class)->purchasedModulesForCompany($company))->toBeEmpty();
});
