<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distributor_v2')) {
            DB::statement('CREATE TABLE distributor_v2 LIKE reseller_v2');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_v2');
    }
};
