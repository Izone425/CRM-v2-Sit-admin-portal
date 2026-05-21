<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('hr_account_id')->nullable()->after('sw_id');
            $table->unsignedBigInteger('hr_company_id')->nullable()->after('hr_account_id');
            $table->unsignedBigInteger('hr_user_id')->nullable()->after('hr_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['hr_account_id', 'hr_company_id', 'hr_user_id']);
        });
    }
};
