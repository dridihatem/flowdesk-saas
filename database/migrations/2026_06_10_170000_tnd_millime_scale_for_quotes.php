<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TND storage uses millimes again (scale 1000). Amounts saved as whole dinars must be ×1000.
     */
    public function up(): void
    {
        $mul = function (string $table, array $columns, ?string $currencyColumn = 'currency'): void {
            if (! Schema::hasTable($table)) {
                return;
            }
            foreach ($columns as $col) {
                if (! Schema::hasColumn($table, $col)) {
                    continue;
                }
                $q = DB::table($table)->where($col, '>', 0);
                if ($currencyColumn && Schema::hasColumn($table, $currencyColumn)) {
                    $q->where($currencyColumn, 'TND');
                }
                $q->update([$col => DB::raw($col.' * 1000')]);
            }
        };

        $mul('proposals', ['amount', 'subtotal_amount', 'vat_amount', 'fiscal_stamp_amount']);

        if (Schema::hasTable('proposal_items') && Schema::hasTable('proposals')) {
            DB::table('proposal_items')
                ->whereIn('proposal_id', DB::table('proposals')->where('currency', 'TND')->pluck('id'))
                ->where('unit_amount', '>', 0)
                ->update([
                    'unit_amount' => DB::raw('unit_amount * 1000'),
                    'total_amount' => DB::raw('total_amount * 1000'),
                ]);
        }

        $mul('invoices', ['amount', 'subtotal_amount', 'vat_amount', 'fiscal_stamp_amount']);
        if (Schema::hasTable('invoice_items') && Schema::hasTable('invoices')) {
            DB::table('invoice_items')
                ->whereIn('invoice_id', DB::table('invoices')->where('currency', 'TND')->pluck('id'))
                ->where('unit_amount', '>', 0)
                ->update([
                    'unit_amount' => DB::raw('unit_amount * 1000'),
                    'total_amount' => DB::raw('total_amount * 1000'),
                ]);
        }
    }

    public function down(): void
    {
        // Irreversible without losing sub-dinar precision.
    }
};
