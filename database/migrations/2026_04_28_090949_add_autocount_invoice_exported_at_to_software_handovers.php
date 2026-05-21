<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->timestamp('autocount_invoice_exported_at')->nullable()->after('autocount_invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->dropColumn('autocount_invoice_exported_at');
        });
    }
};
