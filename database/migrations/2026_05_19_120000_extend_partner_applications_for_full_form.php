<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('partner_type');
            $table->text('address')->nullable()->after('company_name');
            $table->string('state', 100)->nullable()->after('address');
            $table->string('postcode', 20)->nullable()->after('state');
            $table->string('telephone', 50)->nullable()->after('postcode');
            $table->string('company_website')->nullable()->after('telephone');
            $table->enum('business_type', ['sole_proprietorship', 'partnership', 'corporation'])->nullable()->after('company_website');
            $table->string('industry')->nullable()->after('business_type');
            $table->enum('years_in_business', ['1_3', '4_5', '6_10', 'more_than_10'])->nullable()->after('industry');
            $table->string('password')->nullable()->after('email');
            $table->string('mobile_phone', 50)->nullable()->after('password');
            $table->string('first_name', 100)->nullable()->after('mobile_phone');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('designation', 100)->nullable()->after('last_name');
            $table->boolean('existing_fingertec_reseller')->nullable()->after('designation');
            $table->boolean('consent_setup_permission')->default(false)->after('existing_fingertec_reseller');
            $table->boolean('consent_marketing')->default(false)->after('consent_setup_permission');
        });

        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'phone', 'message']);
        });
    }

    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('message')->nullable();
        });

        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn([
                'categories', 'address', 'state', 'postcode', 'telephone',
                'company_website', 'business_type', 'industry', 'years_in_business',
                'password', 'mobile_phone', 'first_name', 'last_name', 'designation',
                'existing_fingertec_reseller', 'consent_setup_permission', 'consent_marketing',
            ]);
        });
    }
};
