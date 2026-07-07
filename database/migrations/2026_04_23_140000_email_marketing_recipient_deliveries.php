<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_marketing_recipient_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('company_id');
            $table->ulid('email_marketing_campaign_id');
            $table->ulid('email_marketing_audience_contact_id')->nullable();
            $table->string('recipient_email', 255);
            $table->string('kind', 20)->default('mass');
            $table->string('tracking_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();

            $table->index(['email_marketing_campaign_id', 'kind'], 'em_rd_campaign_kind');
        });

        Schema::table('email_marketing_recipient_deliveries', function (Blueprint $table) {
            $table->foreign('company_id', 'em_rd_company_fk')
                ->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('email_marketing_campaign_id', 'em_rd_campaign_fk')
                ->references('id')->on('email_marketing_campaigns')->cascadeOnDelete();
            $table->foreign('email_marketing_audience_contact_id', 'em_rd_a_contact_fk')
                ->references('id')->on('email_marketing_audience_contacts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_marketing_recipient_deliveries');
    }
};
