<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ticketingsystem_live')
            ->table('tickets', function (Blueprint $table) {
                $table->string('pending_party')->nullable()->after('status');
            });
    }

    public function down(): void
    {
        Schema::connection('ticketingsystem_live')
            ->table('tickets', function (Blueprint $table) {
                $table->dropColumn('pending_party');
            });
    }
};
