<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('api_token_hash', 64)->nullable()->after('default_currency');
            $table->string('api_token_hint', 12)->nullable()->after('api_token_hash');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['api_token_hash', 'api_token_hint']);
        });
    }
};
