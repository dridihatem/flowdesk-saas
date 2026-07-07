<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->string('category', 32)->default('other')->after('project_id');
            $table->string('thumb_path')->nullable()->after('path');
        });

        Schema::table('project_task_files', function (Blueprint $table) {
            $table->string('category', 32)->default('other')->after('project_task_id');
            $table->string('thumb_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn(['category', 'thumb_path']);
        });

        Schema::table('project_task_files', function (Blueprint $table) {
            $table->dropColumn(['category', 'thumb_path']);
        });
    }
};
