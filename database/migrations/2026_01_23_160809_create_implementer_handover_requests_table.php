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
        Schema::create('implementer_handover_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sw_id')->comment('Software Handover ID');
            $table->string('implementer_name')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamp('date_request')->nullable();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('sw_id')
                ->references('id')
                ->on('software_handovers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementer_handover_requests');
    }
};
