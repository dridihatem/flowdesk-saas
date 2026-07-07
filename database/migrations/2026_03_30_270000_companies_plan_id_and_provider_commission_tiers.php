<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('default_currency')->constrained('plans')->nullOnDelete();
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->json('provider_commission_client_tiers')->nullable()->after('security');
        });

        foreach (Company::query()->cursor() as $company) {
            $planId = $company->subscriptions()
                ->where('status', 'active')
                ->latest('id')
                ->value('plan_id');
            if ($planId !== null) {
                $company->forceFill(['plan_id' => $planId])->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('provider_commission_client_tiers');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
