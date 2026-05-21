<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offset_payment_handovers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requestor_id')->nullable();
            $table->string('company_name');
            $table->string('invoice_no')->nullable();
            $table->json('payment_slip')->nullable();
            $table->string('status')->default('new');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('requestor_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offset_payment_handovers');
    }
};
