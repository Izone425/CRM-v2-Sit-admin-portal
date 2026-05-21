<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandoverFd;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFdController extends Controller
{
    public function getCounts()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'pending_confirmation' => 0,
                'pending_timetec' => 0,
                'completed' => 0,
                'all_items' => 0,
            ]);
        }

        $resellerId = $reseller->reseller_id;

        return response()->json([
            'pending_confirmation' => ResellerHandoverFd::where('reseller_id', $resellerId)
                ->where('status', 'pending_quotation_confirmation')
                ->count(),
            'pending_invoice_confirmation' => ResellerHandoverFd::where('reseller_id', $resellerId)
                ->where('status', 'pending_invoice_confirmation')
                ->count(),
            'pending_payment' => ResellerHandoverFd::where('reseller_id', $resellerId)
                ->where('status', 'pending_reseller_payment')
                ->count(),
            'pending_timetec' => ResellerHandoverFd::where('reseller_id', $resellerId)
                ->whereIn('status', ['new', 'pending_timetec_invoice'])
                ->count(),
            'completed' => ResellerHandoverFd::where('reseller_id', $resellerId)
                ->where('status', 'completed')
                ->count(),
            'all_items' => ResellerHandoverFd::where('reseller_id', $resellerId)->count(),
        ]);
    }
}
