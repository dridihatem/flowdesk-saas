<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('zoom_account_id', 120)->nullable()->after('google_calendar_connected_at');
            $table->text('zoom_client_id_encrypted')->nullable()->after('zoom_account_id');
            $table->text('zoom_client_secret_encrypted')->nullable()->after('zoom_client_id_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'zoom_account_id',
                'zoom_client_id_encrypted',
                'zoom_client_secret_encrypted',
            ]);
        });
    }
};
