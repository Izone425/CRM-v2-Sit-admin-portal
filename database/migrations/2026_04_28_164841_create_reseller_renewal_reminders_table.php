<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_renewal_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('reseller_id', 50)->index(); // matches reseller_v2.reseller_id
            $table->unsignedBigInteger('f_company_id')->index(); // crm_reseller_link.f_id
            $table->string('f_company_name')->nullable();
            $table->unsignedBigInteger('added_by')->nullable(); // reseller_v2.id
            $table->timestamps();

            $table->unique(['reseller_id', 'f_company_id'], 'reseller_company_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_renewal_reminders');
    }
};
