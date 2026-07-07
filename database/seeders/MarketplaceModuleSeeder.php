<?php

namespace Database\Seeders;

use App\Enums\MarketplaceModuleBillingPeriod;
use App\Enums\MarketplaceModuleCategory;
use App\Models\MarketplaceModule;
use Illuminate\Database\Seeder;

class MarketplaceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'slug' => 'qatar-hr-core',
                'name' => 'HR Core',
                'description' => 'Leave, contracts, and employee records for growing teams.',
                'detail_content' => 'Centralize HR workflows: employee profiles, leave requests, contract templates, and org chart basics.',
                'category' => MarketplaceModuleCategory::Hr,
                'price_minor' => 3500,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'users',
                'feature_bullets' => ['Leave management', 'Contract templates', 'Employee directory'],
                'sort_order' => 15,
            ],
            [
                'slug' => 'qatar-finance-ledger',
                'name' => 'Finance Ledger',
                'description' => 'Chart of accounts, journals, and basic P&L for SMEs.',
                'detail_content' => 'Light accounting for startups: chart of accounts, journal entries, expense categories, and monthly P&L export.',
                'category' => MarketplaceModuleCategory::Finance,
                'price_minor' => 4200,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'coins',
                'feature_bullets' => ['Chart of accounts', 'Journal entries', 'P&L export'],
                'sort_order' => 12,
            ],
            [
                'slug' => 'qatar-property-listings',
                'name' => 'Property Listings',
                'description' => 'Manage sale and rental listings with zones, photos, and client links.',
                'detail_content' => "Install a complete property listings workspace inside FlowDesk.\n\nIdeal for agencies and broker networks in Qatar and the GCC: manage sale and rental inventory, link owners to client records, and push deals into projects and invoices.",
                'category' => MarketplaceModuleCategory::RealEstate,
                'price_minor' => 4900,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'building',
                'feature_bullets' => ['Doha zone presets', 'QAR pricing', 'Link to projects & clients'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'qatar-catalog-lite',
                'name' => 'Catalog Lite',
                'description' => 'Product SKUs, stock, and categories for light e-commerce.',
                'category' => MarketplaceModuleCategory::Ecommerce,
                'price_minor' => 3900,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'doc',
                'feature_bullets' => ['SKU & variants', 'Stock movements', 'Order inbox ready'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'qatar-pos-register',
                'name' => 'POS Register',
                'description' => 'Lightweight point of sale with daily Z-report and VAT.',
                'category' => MarketplaceModuleCategory::Pos,
                'price_minor' => 5900,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'coins',
                'feature_bullets' => ['Cash & card sessions', 'Receipt PDF', 'Multi-branch ready'],
                'sort_order' => 30,
            ],
            [
                'slug' => 'qatar-delivery-dispatch',
                'name' => 'Delivery Dispatch',
                'description' => 'Last-mile routes, couriers, and COD reconciliation.',
                'category' => MarketplaceModuleCategory::Delivery,
                'price_minor' => 4500,
                'currency' => 'USD',
                'billing_period' => MarketplaceModuleBillingPeriod::Monthly,
                'icon' => 'bolt',
                'feature_bullets' => ['Zone-based fees', 'Proof of delivery', 'COD tracking'],
                'sort_order' => 40,
            ],
        ];

        foreach ($rows as $row) {
            MarketplaceModule::query()->updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'is_published' => true,
                    'target_countries' => ['QA', 'AE', 'SA', 'KW', 'BH', 'OM'],
                ]),
            );
        }
    }
}
