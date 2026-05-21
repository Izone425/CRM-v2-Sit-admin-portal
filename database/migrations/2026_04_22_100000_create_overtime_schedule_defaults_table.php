<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('overtime_schedule_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday .. 5 = Friday
            $table->string('type', 20);                  // 'main' | 'backup'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['day_of_week', 'type']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_schedule_defaults');
    }
};
