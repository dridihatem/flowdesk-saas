<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('name');
            $table->string('phone', 64)->nullable()->after('contact_email');
            $table->string('website')->nullable()->after('phone');
            $table->string('tax_id')->nullable()->after('website');
            $table->string('address_line1')->nullable()->after('tax_id');
            $table->string('city')->nullable()->after('address_line1');
            $table->string('postal_code', 32)->nullable()->after('city');
            $table->string('industry')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email',
                'phone',
                'website',
                'tax_id',
                'address_line1',
                'city',
                'postal_code',
                'industry',
            ]);
        });
    }
};
