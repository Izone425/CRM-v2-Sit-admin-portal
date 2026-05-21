<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_handover_fgs', function (Blueprint $table) {
            $table->id();
            $table->string('reseller_id')->nullable();
            $table->string('reseller_name')->nullable();
            $table->string('reseller_company_name')->nullable();
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_name')->nullable();
            $table->string('subscriber_status', 10)->nullable()->comment('A = Active, I = Inactive');
            $table->string('category', 50)->nullable();
            $table->integer('attendance_qty')->default(0);
            $table->integer('leave_qty')->default(0);
            $table->integer('claim_qty')->default(0);
            $table->integer('payroll_qty')->default(0);
            $table->integer('qf_master_qty')->default(0);
            $table->text('reseller_remark')->nullable();
            $table->text('admin_reseller_remark')->nullable();
            $table->string('timetec_proforma_invoice')->nullable();
            $table->timestamp('ttpi_submitted_at')->nullable();
            $table->string('purchase_order')->nullable();
            $table->string('autocount_invoice')->nullable();
            $table->string('autocount_invoice_number')->nullable();
            $table->timestamp('aci_submitted_at')->nullable();
            $table->string('reseller_option', 50)->default('cash_term_without_payment');
            $table->string('official_receipt_number')->nullable();
            $table->string('reseller_payment_slip')->nullable();
            $table->timestamp('rni_submitted_at')->nullable();
            $table->string('status', 50)->default('new');
            $table->timestamp('confirmed_proceed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_handover_fgs');
    }
};
