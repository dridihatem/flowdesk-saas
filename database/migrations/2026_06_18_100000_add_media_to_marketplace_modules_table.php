<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('icon');
            $table->string('cover_path')->nullable()->after('image_path');
            $table->text('detail_content')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'cover_path', 'detail_content']);
        });
    }
};
