<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termination_analysis_notes', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->boolean('is_excluded')->default(false);
            $table->text('exclude_reason')->nullable();
            $table->text('termination_reason')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('company_id');
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_analysis_notes');
    }
};
