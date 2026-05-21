<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reseller_inquiries', function (Blueprint $table) {
            $table->string('reject_attachment_path')->nullable()->after('reject_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_inquiries', function (Blueprint $table) {
            $table->dropColumn('reject_attachment_path');
        });
    }
};
