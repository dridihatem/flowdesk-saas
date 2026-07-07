<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('number')->nullable()->after('name');
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('vat_amount')->default(0)->after('subtotal_amount');
            $table->unsignedBigInteger('fiscal_stamp_amount')->default(0)->after('vat_amount');
            $table->text('internal_notes')->nullable()->after('valid_until');
            $table->text('customer_notes')->nullable()->after('internal_notes');
            $table->timestamp('sent_at')->nullable()->after('customer_notes');

            $table->index(['company_id', 'number']);
        });

        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount');
            $table->unsignedBigInteger('total_amount');
            $table->timestamps();

            $table->index(['proposal_id']);
            $table->index(['company_id']);
        });

        if (Schema::hasTable('proposals')) {
            DB::table('proposals')->where('subtotal_amount', 0)->where('amount', '>', 0)->update([
                'subtotal_amount' => DB::raw('amount'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_items');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'number']);
            $table->dropColumn([
                'number',
                'subtotal_amount',
                'vat_amount',
                'fiscal_stamp_amount',
                'internal_notes',
                'customer_notes',
                'sent_at',
            ]);
        });
    }
};
