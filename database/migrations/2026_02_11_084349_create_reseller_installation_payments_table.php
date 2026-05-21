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
        Schema::create('reseller_installation_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reseller_id')->nullable();
            $table->string('reseller_name')->nullable();
            $table->unsignedBigInteger('attention_to')->nullable();
            $table->string('customer_name')->nullable();
            $table->date('installation_date')->nullable();
            $table->text('installation_address')->nullable();
            $table->text('quotation_path')->nullable();
            $table->text('invoice_path')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('completed_at')->nullable();
            $table->text('admin_remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_installation_payments');
    }
};
