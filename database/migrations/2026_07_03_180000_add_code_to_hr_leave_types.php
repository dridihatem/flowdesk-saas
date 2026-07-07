<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->after('company_id');
        });

        DB::table('hr_leave_types')->whereNull('code')->where([
            'days_per_year' => 22,
            'is_paid' => true,
            'color' => 'emerald',
        ])->update(['code' => 'annual']);

        DB::table('hr_leave_types')->whereNull('code')->where([
            'days_per_year' => 10,
            'is_paid' => true,
            'color' => 'rose',
        ])->update(['code' => 'sick']);

        DB::table('hr_leave_types')->whereNull('code')->where([
            'days_per_year' => 0,
            'is_paid' => false,
            'color' => 'slate',
        ])->update(['code' => 'unpaid']);
    }

    public function down(): void
    {
        Schema::table('hr_leave_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
