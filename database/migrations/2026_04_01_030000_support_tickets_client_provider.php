<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignUlid('client_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            $table->foreignUlid('provider_id')->nullable()->after('client_id')->constrained('providers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['provider_id']);
        });
    }
};
