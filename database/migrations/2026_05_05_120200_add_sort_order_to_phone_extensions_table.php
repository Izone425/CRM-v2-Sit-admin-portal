<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_extensions', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // Backfill to match the previous hardcoded order.
        $hardcoded = [
            'Ahmad Syazwan' => 1,
            'Siti Nadia'    => 2,
            'Fairos'        => 3,
            'Noor Syazana'  => 4,
            'Siti Shahilah' => 5,
            'Ahmad Syamim'  => 6,
        ];

        $rows = DB::table('phone_extensions')
            ->leftJoin('users', 'users.id', '=', 'phone_extensions.user_id')
            ->select('phone_extensions.id', 'phone_extensions.extension', 'users.name as user_name')
            ->get();

        $tail = count($hardcoded);
        foreach ($rows as $row) {
            $effectiveName = $row->user_name ?: '';
            $order = null;
            foreach ($hardcoded as $needle => $rank) {
                if (stripos($effectiveName, $needle) !== false) {
                    $order = $rank;
                    break;
                }
            }
            if ($order === null) {
                $order = ++$tail;
            }
            DB::table('phone_extensions')->where('id', $row->id)->update(['sort_order' => $order]);
        }
    }

    public function down(): void
    {
        Schema::table('phone_extensions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
