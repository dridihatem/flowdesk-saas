<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id');
            $table->string('email')->nullable();
            $table->text('avatar_url')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('locale', 8)->nullable()->after('remember_token');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('default_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('country');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
