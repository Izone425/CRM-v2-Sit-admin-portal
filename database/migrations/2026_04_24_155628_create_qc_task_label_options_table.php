<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_task_label_options', function (Blueprint $table) {
            $table->id();
            $table->enum('tier', ['tier1', 'tier2', 'tier3']);
            $table->string('value');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tier', 'value']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_task_label_options');
    }
};
