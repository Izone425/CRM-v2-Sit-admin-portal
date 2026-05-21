<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_group_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->string('type');
            $table->string('status')->default('New');
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->timestamps();

            $table->foreign('support_group_id')->references('id')->on('support_groups')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('causer_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'date']);
            $table->index(['support_group_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_appointments');
    }
};
