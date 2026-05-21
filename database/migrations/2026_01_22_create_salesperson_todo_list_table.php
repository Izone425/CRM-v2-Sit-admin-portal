<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salesperson_todo_list', function (Blueprint $table) {
            $table->id();
            $table->string('todo_id')->unique(); // TL_260001
            $table->unsignedBigInteger('salesperson_id');
            $table->string('company_name');
            $table->date('reminder_date');
            $table->text('remark');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('salesperson_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['salesperson_id', 'status']);
            $table->index('reminder_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesperson_todo_list');
    }
};
