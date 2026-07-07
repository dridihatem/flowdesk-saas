<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('company_settings', 'integration_channels')) {
                $table->json('integration_channels')->nullable()->after('marketing');
            }
        });

        if (! Schema::hasColumn('email_marketing_campaigns', 'recipient_scope')) {
            Schema::table('email_marketing_campaigns', function (Blueprint $table) {
                $table->string('recipient_scope', 20)->default('all')->after('audience_id');
            });
        }

        if (! Schema::hasTable('email_marketing_campaign_recipients')) {
            Schema::create('email_marketing_campaign_recipients', function (Blueprint $table) {
                $table->id();
                $table->ulid('email_marketing_campaign_id');
                $table->ulid('email_marketing_audience_contact_id');
                $table->timestamps();

                $table->unique(
                    ['email_marketing_campaign_id', 'email_marketing_audience_contact_id'],
                    'em_camp_recipient_unique'
                );
            });

            Schema::table('email_marketing_campaign_recipients', function (Blueprint $table) {
                $table->foreign('email_marketing_campaign_id', 'em_camprec_camp_fk')
                    ->references('id')->on('email_marketing_campaigns')->cascadeOnDelete();
                $table->foreign('email_marketing_audience_contact_id', 'em_camprec_contact_fk')
                    ->references('id')->on('email_marketing_audience_contacts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_marketing_campaign_recipients');

        Schema::table('email_marketing_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('email_marketing_campaigns', 'recipient_scope')) {
                $table->dropColumn('recipient_scope');
            }
        });

        Schema::table('company_settings', function (Blueprint $table) {
            if (Schema::hasColumn('company_settings', 'integration_channels')) {
                $table->dropColumn('integration_channels');
            }
        });
    }
};
