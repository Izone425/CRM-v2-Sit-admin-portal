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
        Schema::table('reseller_inquiries', function (Blueprint $table) {
            $table->text('admin_remark')->nullable()->after('attachment_path');
            $table->string('admin_attachment_path')->nullable()->after('admin_remark');
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->timestamp('rejected_at')->nullable()->after('completed_at');
            $table->text('reject_reason')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_inquiries', function (Blueprint $table) {
            $table->dropColumn(['admin_remark', 'admin_attachment_path', 'completed_at', 'rejected_at', 'reject_reason']);
        });
    }
};
