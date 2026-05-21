<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandoverFf;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFfController extends Controller
{
    public function getCounts()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'pending_confirmation' => 0,
                'completed' => 0,
                'all_items' => 0,
            ]);
        }

        $resellerId = $reseller->reseller_id;

        return response()->json([
            'pending_confirmation' => ResellerHandoverFf::where('reseller_id', $resellerId)
                ->where('status', 'pending_quotation_confirmation')
                ->count(),
            'completed' => ResellerHandoverFf::where('reseller_id', $resellerId)
                ->where('status', 'completed')
                ->count(),
            'all_items' => ResellerHandoverFf::where('reseller_id', $resellerId)->count(),
        ]);
    }
}
