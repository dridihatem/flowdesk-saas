<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('layout', 32)->default('simple')->after('status');
            $table->json('meta')->nullable()->after('layout');
            $table->unsignedInteger('widget_version')->default(1)->after('meta');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->unsignedTinyInteger('step')->default(0)->after('sort_order');
        });

        Schema::create('widget_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('event', 32);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'form_id', 'created_at']);
            $table->index(['company_id', 'event', 'created_at']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignUlid('provider_id')->nullable()->after('project_id')->constrained('providers')->nullOnDelete();
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->unsignedBigInteger('commission_amount_minor')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropColumn('commission_amount_minor');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::dropIfExists('widget_events');

        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('step');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['layout', 'meta', 'widget_version']);
        });
    }
};
