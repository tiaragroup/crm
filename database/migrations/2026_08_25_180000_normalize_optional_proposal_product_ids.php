<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quote_items')
            ->where('product_id', 0)
            ->update(['product_id' => null]);

        DB::table('quote_menu_items')
            ->where('product_id', 0)
            ->update(['product_id' => null]);
    }

    public function down(): void
    {
        // Zero was an invalid sentinel value, so it should not be restored.
    }
};
