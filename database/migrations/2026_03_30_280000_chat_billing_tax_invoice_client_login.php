<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'client', 'guard_name' => 'web'],
        );

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->index(['company_id', 'user_id']);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->json('billing')->nullable()->after('provider_commission_client_tiers');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('vat_amount')->default(0)->after('subtotal_amount');
            $table->unsignedBigInteger('fiscal_stamp_amount')->default(0)->after('vat_amount');
        });

        foreach (DB::table('invoices')->cursor() as $row) {
            DB::table('invoices')->where('id', $row->id)->update([
                'subtotal_amount' => $row->amount,
                'vat_amount' => 0,
                'fiscal_stamp_amount' => 0,
            ]);
        }

        Schema::create('chat_threads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->ulid('subject_id');
            $table->timestamps();

            $table->unique(['company_id', 'type', 'subject_id']);
            $table->index(['company_id', 'updated_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['thread_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'vat_amount', 'fiscal_stamp_amount']);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('billing');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Role::query()->where('name', 'client')->where('guard_name', 'web')->delete();
    }
};
