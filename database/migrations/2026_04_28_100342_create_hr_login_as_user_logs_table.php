<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_login_as_user_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('causer_id')->nullable()->index();
            $table->string('causer_name')->nullable();
            $table->string('target_email');
            $table->unsignedBigInteger('hr_user_id');
            $table->unsignedBigInteger('hr_company_id')->index();
            $table->unsignedBigInteger('software_handover_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status', 20)->default('initiated');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_login_as_user_logs');
    }
};
