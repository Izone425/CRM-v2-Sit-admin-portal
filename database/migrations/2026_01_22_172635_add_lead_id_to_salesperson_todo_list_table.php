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
        Schema::table('salesperson_todo_list', function (Blueprint $table) {
            $table->unsignedInteger('lead_id')->nullable()->after('salesperson_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salesperson_todo_list', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
