<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')->update([
            'meta_title' => null,
            'meta_description' => null,
            'og_image' => null,
        ]);

        DB::table('brands')->update([
            'meta_title' => null,
            'meta_description' => null,
        ]);

        DB::table('categories')->update([
            'meta_title' => null,
            'meta_description' => null,
        ]);

        DB::table('vehicle_models')->update([
            'meta_title' => null,
            'meta_description' => null,
        ]);
    }

    public function down(): void
    {
        // Manual meta values cannot be restored automatically.
    }
};
