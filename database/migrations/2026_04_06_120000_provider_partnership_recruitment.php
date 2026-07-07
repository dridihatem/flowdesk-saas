<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('provider_recruitment_slug', 64)->nullable()->unique()->after('slug');
            $table->boolean('provider_recruitment_enabled')->default(false)->after('provider_recruitment_slug');
            $table->text('provider_partnership_terms')->nullable()->after('provider_recruitment_enabled');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('partnership_status', 32)->default('active')->after('user_id');
            $table->timestamp('partnership_provider_signed_at')->nullable()->after('partnership_status');
            $table->timestamp('partnership_company_signed_at')->nullable()->after('partnership_provider_signed_at');
            $table->foreignId('partnership_company_signer_user_id')->nullable()->after('partnership_company_signed_at')->constrained('users')->nullOnDelete();
        });

        DB::table('providers')->whereNull('partnership_status')->update(['partnership_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['partnership_company_signer_user_id']);
            $table->dropColumn([
                'partnership_status',
                'partnership_provider_signed_at',
                'partnership_company_signed_at',
                'partnership_company_signer_user_id',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'provider_recruitment_slug',
                'provider_recruitment_enabled',
                'provider_partnership_terms',
            ]);
        });
    }
};
