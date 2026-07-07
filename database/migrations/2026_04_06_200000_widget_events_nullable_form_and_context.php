<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_events', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });

        Schema::table('widget_events', function (Blueprint $table) {
            $table->foreignUlid('form_id')->nullable()->change();
            $table->json('context')->nullable()->after('ip_address');
        });

        Schema::table('widget_events', function (Blueprint $table) {
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('widget_events')->whereNull('form_id')->delete();

        Schema::table('widget_events', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });

        Schema::table('widget_events', function (Blueprint $table) {
            $table->dropColumn('context');
            $table->foreignUlid('form_id')->nullable(false)->change();
        });

        Schema::table('widget_events', function (Blueprint $table) {
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
        });
    }
};
