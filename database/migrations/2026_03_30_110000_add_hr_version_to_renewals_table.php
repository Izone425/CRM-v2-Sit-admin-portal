<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->unsignedTinyInteger('hr_version')->default(1)->after('f_company_id');
            $table->unsignedBigInteger('software_handover_id')->nullable()->after('hr_version');

            $table->index('hr_version');
            $table->index('software_handover_id');
        });
    }

    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->dropIndex(['hr_version']);
            $table->dropIndex(['software_handover_id']);
            $table->dropColumn(['hr_version', 'software_handover_id']);
        });
    }
};
