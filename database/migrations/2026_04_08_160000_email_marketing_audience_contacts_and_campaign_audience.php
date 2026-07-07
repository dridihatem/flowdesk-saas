<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('email_marketing_audience_contacts');

        Schema::create('email_marketing_audience_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('audience_id')->constrained('email_marketing_audiences')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['audience_id', 'email']);
        });

        if (! Schema::hasColumn('email_marketing_campaigns', 'audience_id')) {
            Schema::table('email_marketing_campaigns', function (Blueprint $table) {
                $table->foreignUlid('audience_id')->nullable()->after('company_id')->constrained('email_marketing_audiences')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('email_marketing_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audience_id');
        });

        Schema::dropIfExists('email_marketing_audience_contacts');
    }
};
