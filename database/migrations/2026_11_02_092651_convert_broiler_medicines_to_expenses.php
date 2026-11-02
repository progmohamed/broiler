<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('broiler_entries')->where('kind', 'medicine')->update(['kind' => 'expense', 'category' => 'medicines']);
    }

    public function down(): void
    {
        throw new RuntimeException('Medicine consolidation cannot be reversed without the pre-migration backup; existing and converted expenses are indistinguishable.');
    }
};
