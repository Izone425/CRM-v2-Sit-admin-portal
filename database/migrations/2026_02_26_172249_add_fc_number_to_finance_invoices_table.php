<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->string('fc_number')->nullable()->after('id');
        });

        // Backfill existing records using the legacy sequential format: FC{YY}{MM}-{XXXX}
        $records = DB::table('finance_invoices')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $monthCounts = [];
        foreach ($records as $record) {
            $createdAt = \Carbon\Carbon::parse($record->created_at);
            $yearMonth = $createdAt->format('ym');

            if (!isset($monthCounts[$yearMonth])) {
                $monthCounts[$yearMonth] = 0;
            }
            $monthCounts[$yearMonth]++;

            $fcNumber = 'FC' . $yearMonth . '-' . str_pad($monthCounts[$yearMonth], 4, '0', STR_PAD_LEFT);

            DB::table('finance_invoices')
                ->where('id', $record->id)
                ->update(['fc_number' => $fcNumber]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropColumn('fc_number');
        });
    }
};
