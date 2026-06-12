<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->id();
            $table->enum('partner_type', ['reseller', 'distributor']);
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_remark')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['partner_type', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
