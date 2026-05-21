<?php

namespace App\Http\Controllers;

use App\Models\ResellerDatabaseCreation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerDatabaseCreationController extends Controller
{
    public function getCounts(Request $request)
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'new_database' => 0,
                'draft_database' => 0,
                'rejected_database' => 0,
                'completed_database' => 0,
            ]);
        }

        $newCount = ResellerDatabaseCreation::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new')
            ->count();

        $draftCount = ResellerDatabaseCreation::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'draft')
            ->count();

        $rejectedCount = ResellerDatabaseCreation::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'rejected')
            ->count();

        $completedCount = ResellerDatabaseCreation::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'new_database' => $newCount,
            'draft_database' => $draftCount,
            'rejected_database' => $rejectedCount,
            'completed_database' => $completedCount,
        ]);
    }
}
