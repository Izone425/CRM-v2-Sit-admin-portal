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
        Schema::create('reseller_inquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reseller_id');
            $table->string('reseller_name');
            $table->enum('subscriber_type', ['active', 'inactive', 'internal'])->default('active');
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_name');
            $table->string('title');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['new', 'in_progress', 'completed'])->default('new');
            $table->timestamps();

            $table->index('reseller_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_inquiries');
    }
};
