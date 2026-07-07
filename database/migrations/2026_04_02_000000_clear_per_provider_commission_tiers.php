<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('providers')->whereNotNull('commission_tiers')->update(['commission_tiers' => null]);
    }

    public function down(): void
    {
        // Data not restored; tiers were deprecated in favour of workspace settings + fixed provider rate.
    }
};
