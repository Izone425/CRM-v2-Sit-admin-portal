<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_commission_handovers', function (Blueprint $table) {
            $table->id();

            // Copied from payment update table data
            $table->string('ap_invoice_no')->comment('AP PI No from crm_invoice_details');
            $table->string('tt_invoice_no')->nullable()->comment('TT PI No');
            $table->string('autocount_inv_no')->nullable()->comment('AutoCount Invoice No');
            $table->string('reseller_name')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('MYR');

            // Status workflow: pending_reseller -> pending_finance -> completed
            $table->enum('status', ['pending_reseller', 'pending_finance', 'completed'])->default('pending_reseller');

            // Payment slip uploaded by finance
            $table->string('payment_slip')->nullable();

            // Timestamps for all actions
            $table->timestamp('created_at')->nullable()->comment('When task was completed from payment update');
            $table->timestamp('reseller_proceeded_at')->nullable()->comment('When reseller clicked proceed');
            $table->timestamp('payment_slip_uploaded_at')->nullable()->comment('When finance uploaded payment slip');
            $table->timestamp('completed_at')->nullable()->comment('When finance marked as completed');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_commission_handovers');
    }
};
