<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hr_sales_invoice_items');
        Schema::dropIfExists('hr_sales_invoices');

        // Mirror quotations table structure
        Schema::create('hr_sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('software_handover_id')->nullable();
            $table->string('handover_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('company_name')->nullable();
            $table->string('country')->nullable();
            $table->string('pi_no')->nullable();
            $table->string('quotation_reference_no')->nullable();
            $table->integer('headcount')->nullable();
            $table->string('currency', 10)->default('MYR');
            $table->string('sales_type')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('sales_amount', 15, 2)->default(0);
            $table->decimal('invoice_amount', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('auto_renewal')->nullable();
            $table->string('created_by_name')->nullable();
            $table->string('status')->default('Active');
            // Reseller fields
            $table->string('reseller')->nullable();
            $table->unsignedBigInteger('reseller_software_handover_id')->nullable();
            $table->string('reseller_handover_id')->nullable();
            $table->decimal('commission', 15, 2)->nullable();
            $table->timestamps();
        });

        // Mirror quotation_details table structure
        Schema::create('hr_sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_sales_invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_code')->nullable();
            $table->text('description')->nullable();
            $table->string('license_type')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('subscription_period')->nullable();
            $table->date('license_start_date')->nullable();
            $table->date('license_end_date')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('taxation', 15, 2)->default(0);
            $table->string('tax_code')->nullable();
            $table->string('year')->nullable();
            $table->string('tariff_code')->nullable();
            $table->decimal('total_before_tax', 15, 2)->default(0);
            $table->decimal('total_after_tax', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('hr_sales_invoice_id')
                ->references('id')
                ->on('hr_sales_invoices')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_sales_invoice_items');
        Schema::dropIfExists('hr_sales_invoices');
    }
};
