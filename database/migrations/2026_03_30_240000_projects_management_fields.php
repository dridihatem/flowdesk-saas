<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
        });

        DB::table('projects')->where('status', 'active')->update(['status' => 'in_progress']);
        DB::table('projects')->where('status', 'on_hold')->update(['status' => 'pending']);
        DB::table('projects')->where('status', 'cancelled')->update(['status' => 'draft']);

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('final_price')->nullable();
            $table->unsignedBigInteger('negotiated_price')->nullable();
            $table->date('final_deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'final_price', 'negotiated_price', 'final_deadline']);
        });

        DB::table('projects')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('projects')->where('status', 'pending')->update(['status' => 'on_hold']);

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
        });
    }
};
