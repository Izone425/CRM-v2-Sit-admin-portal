<?php

namespace App\Http\Controllers;

use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFeController extends Controller
{
    public function getCounts()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'pending_confirmation' => 0,
                'pending_invoice_confirmation' => 0,
                'pending_payment' => 0,
                'pending_timetec' => 0,
                'completed' => 0,
                'all_items' => 0,
            ]);
        }

        $resellerId = $reseller->reseller_id;

        return response()->json([
            'pending_confirmation' => ResellerHandoverFe::where('reseller_id', $resellerId)
                ->where('status', 'pending_quotation_confirmation')
                ->count(),
            'pending_invoice_confirmation' => ResellerHandoverFe::where('reseller_id', $resellerId)
                ->where('status', 'pending_invoice_confirmation')
                ->count(),
            'pending_payment' => ResellerHandoverFe::where('reseller_id', $resellerId)
                ->where('status', 'pending_reseller_payment')
                ->count(),
            'pending_timetec' => ResellerHandoverFe::where('reseller_id', $resellerId)
                ->whereIn('status', ['new', 'pending_timetec_invoice'])
                ->count(),
            'completed' => ResellerHandoverFe::where('reseller_id', $resellerId)
                ->where('status', 'completed')
                ->count(),
            'all_items' => ResellerHandoverFe::where('reseller_id', $resellerId)->count(),
        ]);
    }
}
