<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('talent_tenants')->updateOrInsert(
            ['slug' => 'raeburn-group'],
            ['name' => 'The Raeburn Group', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        // Do not remove a tenant that may now own candidate or recruitment data.
    }
};
