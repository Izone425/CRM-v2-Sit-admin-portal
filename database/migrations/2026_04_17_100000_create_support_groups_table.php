<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('support_group_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('support_group_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('support_group_id')->references('id')->on('support_groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['support_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_group_user');
        Schema::dropIfExists('support_groups');
    }
};
