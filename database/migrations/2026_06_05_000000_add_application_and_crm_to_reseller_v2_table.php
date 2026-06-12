<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_application_id')->nullable()->after('id');
            $table->json('modules')->nullable()->after('advanced_modules');
            $table->unsignedSmallInteger('headcount')->nullable()->after('modules');
            $table->integer('hr_account_id')->nullable()->after('headcount');
            $table->integer('hr_company_id')->nullable()->after('hr_account_id');
            $table->integer('hr_user_id')->nullable()->after('hr_company_id');
            $table->integer('crm_buffer_license_id')->nullable()->after('hr_user_id');

            $table->index('partner_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropIndex(['partner_application_id']);
            $table->dropColumn([
                'partner_application_id',
                'modules',
                'headcount',
                'hr_account_id',
                'hr_company_id',
                'hr_user_id',
                'crm_buffer_license_id',
            ]);
        });
    }
};
