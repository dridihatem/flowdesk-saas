<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentEntryKind;
use App\Enums\PaymentStatus;
use App\Enums\ProjectSource;
use App\Enums\ProjectStatus;
use App\Enums\RemittanceMethod;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\TenantStorageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $starter = Plan::query()->where('slug', 'starter')->first();
        $pro = Plan::query()->where('slug', 'pro')->first();
        $enterprise = Plan::query()->where('slug', 'enterprise')->first();

        // --- Company 1: Daweb Company (Tunisia) ---
        $daweb = Company::query()->firstOrCreate(
            ['subdomain' => 'dawebcompany'],
            [
                'name' => 'Daweb Company',
                'slug' => 'daweb-company',
                'default_locale' => 'fr',
                'default_currency' => 'TND',
                'country' => 'TN',
                'contact_email' => 'contact@dawebcompany.tn',
                'tax_id' => 'TN-1234567-A',
                'phone' => '+216 50 000 000',
                'website' => 'https://dawebcompany.tn',
                'industry' => 'Agency',
                'address_line1' => 'Rue de la République',
                'city' => 'Tunis',
                'postal_code' => '1000',
                'is_enabled' => true,
            ],
        );

        CompanySetting::query()->firstOrCreate(
            ['company_id' => $daweb->id],
            [
                'branding' => ['logo_url' => null],
                'theme' => [
                    'layout_type' => 'sidebar',
                    'primary_color' => '#dc2626',
                    'secondary_color' => '#0f172a',
                    'font_family' => 'Figtree',
                    'dark_mode' => 'light',
                ],
                'dashboard' => [],
            ],
        );

        app(TenantStorageService::class)->bootstrap($daweb);

        $dawebOwner = User::query()->firstOrCreate(
            ['email' => 'owner@dawebcompany.tn'],
            [
                'name' => 'Youssef Ben Ali',
                'password' => $password,
                'company_id' => $daweb->id,
                'locale' => $daweb->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $dawebOwner->syncRoles(['company_admin']);

        $dawebStaff = User::query()->firstOrCreate(
            ['email' => 'team@dawebcompany.tn'],
            [
                'name' => 'Ines Team',
                'password' => $password,
                'company_id' => $daweb->id,
                'locale' => $daweb->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $dawebStaff->syncRoles(['team_member']);

        $dawebProviderUser = User::query()->firstOrCreate(
            ['email' => 'provider@dawebcompany.tn'],
            [
                'name' => 'Sami Provider',
                'password' => $password,
                'company_id' => $daweb->id,
                'locale' => $daweb->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $dawebProviderUser->syncRoles(['business_provider']);

        $dawebProvider = Provider::query()->firstOrCreate(
            ['user_id' => $dawebProviderUser->id],
            [
                'company_id' => $daweb->id,
                'name' => $dawebProviderUser->name,
                'email' => $dawebProviderUser->email,
                'commission_rate' => 0.12,
                'phone' => '+216 55 000 000',
                'website' => 'https://provider-portfolio.tn',
                'job_title' => 'Business provider',
                'description' => 'Brings qualified leads and helps close deals.',
            ],
        );

        $dawebClientUser = User::query()->firstOrCreate(
            ['email' => 'client@northwind.tn'],
            [
                'name' => 'Northwind Client',
                'password' => $password,
                'company_id' => $daweb->id,
                'locale' => $daweb->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $dawebClientUser->syncRoles(['client']);

        $northwind = Client::query()->firstOrCreate(
            ['company_id' => $daweb->id, 'email' => 'client@northwind.tn'],
            [
                'user_id' => $dawebClientUser->id,
                'name' => 'Northwind TN',
                'phone' => '+216 70 000 000',
                'address' => ['line1' => 'Zone industrielle', 'city' => 'Ben Arous', 'country' => 'TN'],
            ],
        );

        if ($pro) {
            Subscription::query()->updateOrCreate(
                ['company_id' => $daweb->id, 'status' => 'active'],
                [
                    'plan_id' => $pro->id,
                    'trial_ends_at' => now()->addDays(7),
                    'current_period_end' => now()->addMonth(),
                ],
            );
            $daweb->forceFill(['plan_id' => $pro->id])->saveQuietly();
        }

        $p1 = Project::query()->firstOrCreate(
            ['company_id' => $daweb->id, 'title' => 'E-commerce website revamp'],
            [
                'client_id' => $northwind->id,
                'provider_id' => $dawebProvider->id,
                'created_by' => $dawebOwner->id,
                'status' => ProjectStatus::InProgress,
                'source' => ProjectSource::Internal,
                'description' => 'Redesign storefront + checkout and improve performance.',
                'final_price' => 5500,
                'negotiated_price' => 5200,
                'final_deadline' => now()->addWeeks(6)->toDateString(),
            ],
        );

        $inv1 = Invoice::query()->firstOrCreate(
            ['company_id' => $daweb->id, 'number' => 'INV-TN-000001'],
            [
                'client_id' => $northwind->id,
                'status' => InvoiceStatus::Sent,
                'subtotal_amount' => 120000,
                'vat_amount' => 0,
                'fiscal_stamp_amount' => 0,
                'amount' => 120000,
                'currency' => 'TND',
                'due_date' => now()->addDays(14)->toDateString(),
            ],
        );

        InvoiceItem::query()->firstOrCreate(
            ['invoice_id' => $inv1->id, 'description' => 'Design & delivery (phase 1)'],
            [
                'company_id' => $daweb->id,
                'quantity' => 1,
                'unit_amount' => 120000,
                'total_amount' => 120000,
            ],
        );

        Payment::query()->firstOrCreate(
            ['company_id' => $daweb->id, 'invoice_id' => $inv1->id, 'external_id' => 'flouci_demo_txn_1'],
            [
                'amount' => 120000,
                'currency' => 'TND',
                'status' => PaymentStatus::Completed,
                'payment_kind' => PaymentEntryKind::Standard,
                'payment_method' => RemittanceMethod::Flouci,
                'provider' => 'flouci',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
        );

        // --- Company 2: Flowqil Studio (EU) ---
        $studio = Company::query()->firstOrCreate(
            ['subdomain' => 'flowdeskstudio'],
            [
                'name' => 'Flowqil Studio',
                'slug' => 'flowdesk-studio',
                'default_locale' => 'en',
                'default_currency' => 'EUR',
                'country' => 'FR',
                'contact_email' => 'hello@flowdeskstudio.com',
                'tax_id' => 'FR-999999999',
                'phone' => '+33 1 00 00 00 00',
                'website' => 'https://flowdeskstudio.com',
                'industry' => 'Studio',
                'address_line1' => '10 Rue Exemple',
                'city' => 'Paris',
                'postal_code' => '75001',
                'is_enabled' => true,
            ],
        );

        CompanySetting::query()->firstOrCreate(
            ['company_id' => $studio->id],
            [
                'branding' => ['logo_url' => null],
                'theme' => [
                    'layout_type' => 'sidebar',
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#64748b',
                    'font_family' => 'Figtree',
                    'dark_mode' => 'light',
                ],
                'dashboard' => [],
            ],
        );

        app(TenantStorageService::class)->bootstrap($studio);

        $studioOwner = User::query()->firstOrCreate(
            ['email' => 'owner@flowdeskstudio.com'],
            [
                'name' => 'Alex Studio',
                'password' => $password,
                'company_id' => $studio->id,
                'locale' => $studio->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $studioOwner->syncRoles(['company_admin']);

        $studioClientUser = User::query()->firstOrCreate(
            ['email' => 'client@contoso.eu'],
            [
                'name' => 'Contoso Client',
                'password' => $password,
                'company_id' => $studio->id,
                'locale' => $studio->default_locale,
                'email_verified_at' => now(),
            ],
        );
        $studioClientUser->syncRoles(['client']);

        $contoso = Client::query()->firstOrCreate(
            ['company_id' => $studio->id, 'email' => 'client@contoso.eu'],
            [
                'user_id' => $studioClientUser->id,
                'name' => 'Contoso EU',
                'phone' => '+33 6 00 00 00 00',
                'address' => ['line1' => '1 Avenue Demo', 'city' => 'Paris', 'country' => 'FR'],
            ],
        );

        if ($starter) {
            Subscription::query()->updateOrCreate(
                ['company_id' => $studio->id, 'status' => 'active'],
                [
                    'plan_id' => $starter->id,
                    'trial_ends_at' => null,
                    'current_period_end' => now()->addMonth(),
                ],
            );
            $studio->forceFill(['plan_id' => $starter->id])->saveQuietly();
        }

        $p2 = Project::query()->firstOrCreate(
            ['company_id' => $studio->id, 'title' => 'Marketing landing pages'],
            [
                'client_id' => $contoso->id,
                'provider_id' => null,
                'created_by' => $studioOwner->id,
                'status' => ProjectStatus::Pending,
                'source' => ProjectSource::Internal,
                'description' => 'Three landing pages with SEO + analytics.',
                'final_price' => 3200,
                'negotiated_price' => 3200,
                'final_deadline' => now()->addWeeks(3)->toDateString(),
            ],
        );

        $inv2 = Invoice::query()->firstOrCreate(
            ['company_id' => $studio->id, 'number' => 'INV-EU-000001'],
            [
                'client_id' => $contoso->id,
                'status' => InvoiceStatus::Paid,
                'subtotal_amount' => 320000,
                'vat_amount' => 0,
                'fiscal_stamp_amount' => 0,
                'amount' => 320000,
                'currency' => 'EUR',
                'due_date' => now()->subDays(2)->toDateString(),
            ],
        );

        InvoiceItem::query()->firstOrCreate(
            ['invoice_id' => $inv2->id, 'description' => 'Landing pages package'],
            [
                'company_id' => $studio->id,
                'quantity' => 1,
                'unit_amount' => 320000,
                'total_amount' => 320000,
            ],
        );

        Payment::query()->firstOrCreate(
            ['company_id' => $studio->id, 'invoice_id' => $inv2->id, 'external_id' => 'stripe_demo_txn_1'],
            [
                'amount' => 320000,
                'currency' => 'EUR',
                'status' => PaymentStatus::Completed,
                'payment_kind' => PaymentEntryKind::Standard,
                'payment_method' => RemittanceMethod::Stripe,
                'provider' => 'stripe',
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
        );

        // --- Optional: add an enterprise tenant for screenshots ---
        if ($enterprise) {
            $corp = Company::query()->firstOrCreate(
                ['subdomain' => 'globex'],
                [
                    'name' => 'Globex Corporation',
                    'slug' => 'globex-corporation',
                    'default_locale' => 'en',
                    'default_currency' => 'USD',
                    'country' => 'US',
                    'contact_email' => 'billing@globex.com',
                    'tax_id' => 'US-12-3456789',
                    'is_enabled' => true,
                ],
            );

            app(TenantStorageService::class)->bootstrap($corp);

            $corpOwner = User::query()->firstOrCreate(
                ['email' => 'owner@globex.com'],
                [
                    'name' => 'Jordan Globex',
                    'password' => $password,
                    'company_id' => $corp->id,
                    'locale' => $corp->default_locale,
                    'email_verified_at' => now(),
                ],
            );
            $corpOwner->syncRoles(['company_admin']);

            Subscription::query()->updateOrCreate(
                ['company_id' => $corp->id, 'status' => 'active'],
                [
                    'plan_id' => $enterprise->id,
                    'trial_ends_at' => null,
                    'current_period_end' => now()->addMonth(),
                ],
            );
            $corp->forceFill(['plan_id' => $enterprise->id])->saveQuietly();
        }
    }
}
