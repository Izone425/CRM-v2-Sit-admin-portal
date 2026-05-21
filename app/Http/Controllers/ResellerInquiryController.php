<?php

namespace App\Http\Controllers;

use App\Models\ResellerInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerInquiryController extends Controller
{
    public function getCounts(Request $request)
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'new_inquiries' => 0,
                'draft_inquiries' => 0,
                'rejected_inquiries' => 0,
                'completed_inquiries' => 0,
            ]);
        }

        $newCount = ResellerInquiry::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new')
            ->count();

        $draftCount = ResellerInquiry::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'draft')
            ->count();

        $rejectedCount = ResellerInquiry::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'rejected')
            ->count();

        $completedCount = ResellerInquiry::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'new_inquiries' => $newCount,
            'draft_inquiries' => $draftCount,
            'rejected_inquiries' => $rejectedCount,
            'completed_inquiries' => $completedCount,
        ]);
    }
}
