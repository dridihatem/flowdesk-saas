<?php

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TND integer amounts were stored in millimes (×1000 per dinar). They are now whole dinars (scale 1).
     */
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $div = function (string $column): Expression {
            return match (DB::getDriverName()) {
                'pgsql' => DB::raw('FLOOR(('.$column.')::bigint / 1000)'),
                default => DB::raw('FLOOR('.$column.' / 1000)'),
            };
        };

        foreach (['amount', 'subtotal_amount', 'vat_amount', 'fiscal_stamp_amount'] as $col) {
            if (Schema::hasColumn('invoices', $col)) {
                DB::table('invoices')->where('currency', 'TND')->where($col, '>', 0)->update([
                    $col => $div($col),
                ]);
            }
        }

        if (Schema::hasTable('invoice_items')) {
            $tndInvoiceIds = DB::table('invoices')->where('currency', 'TND')->pluck('id');
            foreach (['unit_amount', 'total_amount'] as $col) {
                if (Schema::hasColumn('invoice_items', $col)) {
                    DB::table('invoice_items')
                        ->whereIn('invoice_id', $tndInvoiceIds)
                        ->where($col, '>', 0)
                        ->update([$col => $div($col)]);
                }
            }
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
            DB::table('payments')->where('currency', 'TND')->where('amount', '>', 0)->update([
                'amount' => $div('amount'),
            ]);
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'amount')) {
            DB::table('transactions')->where('currency', 'TND')->where('amount', '>', 0)->update([
                'amount' => $div('amount'),
            ]);
        }

        if (Schema::hasTable('proposals') && Schema::hasColumn('proposals', 'amount')) {
            DB::table('proposals')->where('currency', 'TND')->where('amount', '>', 0)->update([
                'amount' => $div('amount'),
            ]);
        }

        if (Schema::hasTable('projects') && Schema::hasTable('companies')) {
            $tndCompanyIds = DB::table('companies')
                ->whereRaw('UPPER(TRIM(COALESCE(default_currency, \'\'))) = ?', ['TND'])
                ->pluck('id');
            foreach (['final_price', 'negotiated_price'] as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    DB::table('projects')
                        ->whereIn('company_id', $tndCompanyIds)
                        ->whereNotNull($col)
                        ->where($col, '>', 0)
                        ->update([$col => $div($col)]);
                }
            }
        }

        if (Schema::hasTable('project_tasks') && Schema::hasColumn('project_tasks', 'amount_cents')) {
            $tndCompanyIds = DB::table('companies')
                ->whereRaw('UPPER(TRIM(COALESCE(default_currency, \'\'))) = ?', ['TND'])
                ->pluck('id');
            $tndProjectIds = DB::table('projects')->whereIn('company_id', $tndCompanyIds)->pluck('id');

            DB::table('project_tasks')
                ->where('currency', 'TND')
                ->whereNotNull('amount_cents')
                ->where('amount_cents', '>', 0)
                ->update(['amount_cents' => $div('amount_cents')]);

            DB::table('project_tasks')
                ->whereNull('currency')
                ->whereIn('project_id', $tndProjectIds)
                ->whereNotNull('amount_cents')
                ->where('amount_cents', '>', 0)
                ->update(['amount_cents' => $div('amount_cents')]);
        }

        if (Schema::hasTable('plan_period_prices') && Schema::hasTable('plans') && Schema::hasColumn('plan_period_prices', 'price_minor')) {
            $tndPlanIds = DB::table('plans')
                ->whereRaw('UPPER(TRIM(COALESCE(currency, \'\'))) = ?', ['TND'])
                ->pluck('id');
            DB::table('plan_period_prices')
                ->whereIn('plan_id', $tndPlanIds)
                ->where('price_minor', '>', 0)
                ->update(['price_minor' => $div('price_minor')]);
        }

        if (Schema::hasTable('company_settings')) {
            Company::query()
                ->whereRaw('UPPER(TRIM(COALESCE(default_currency, \'\'))) = ?', ['TND'])
                ->pluck('id')
                ->each(function (string $companyId): void {
                    $row = CompanySetting::query()->where('company_id', $companyId)->first();
                    if ($row === null) {
                        return;
                    }
                    $billing = $row->billing;
                    if (! is_array($billing)) {
                        return;
                    }
                    $m = (int) ($billing['fiscal_stamp_minor'] ?? 0);
                    if ($m > 0) {
                        $billing['fiscal_stamp_minor'] = intdiv($m, 1000);
                        $row->billing = $billing;
                        $row->save();
                    }
                });
        }
    }

    public function down(): void
    {
        //
    }
};
