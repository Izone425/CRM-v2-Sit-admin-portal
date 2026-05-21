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
        Schema::create('reseller_database_creations', function (Blueprint $table) {
            $table->id();
            $table->integer('reseller_id');
            $table->string('reseller_name');
            $table->string('company_name');
            $table->string('ssm_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('master_login_email');
            $table->json('modules'); // Store as JSON array
            $table->integer('headcount');
            $table->text('reseller_remark')->nullable();
            $table->string('status')->default('new'); // new, completed, rejected
            $table->text('admin_remark')->nullable();
            $table->string('admin_attachment_path')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_database_creations');
    }
};
