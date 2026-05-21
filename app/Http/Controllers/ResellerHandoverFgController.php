<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandoverFg;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFgController extends Controller
{
    public function getCounts()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'pending_confirmation' => 0,
                'pending_payment' => 0,
                'completed' => 0,
                'pending_timetec' => 0,
                'all_items' => 0,
            ]);
        }

        $resellerId = $reseller->reseller_id;

        return response()->json([
            'pending_confirmation' => ResellerHandoverFg::where('reseller_id', $resellerId)
                ->where('status', 'pending_quotation_confirmation')
                ->count(),
            'pending_invoice_confirmation' => ResellerHandoverFg::where('reseller_id', $resellerId)
                ->where('status', 'pending_invoice_confirmation')
                ->count(),
            'pending_payment' => ResellerHandoverFg::where('reseller_id', $resellerId)
                ->where('status', 'pending_reseller_payment')
                ->count(),
            'completed' => ResellerHandoverFg::where('reseller_id', $resellerId)
                ->where('status', 'completed')
                ->count(),
            'pending_timetec' => ResellerHandoverFg::where('reseller_id', $resellerId)
                ->whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license', 'pending_timetec_finance'])
                ->count(),
            'all_items' => ResellerHandoverFg::where('reseller_id', $resellerId)->count(),
        ]);
    }
}
