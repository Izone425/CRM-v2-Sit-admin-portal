<?php

namespace App\Http\Controllers;

use App\Models\ResellerInstallationPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerInstallationPaymentController extends Controller
{
    public function getCounts(Request $request)
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return response()->json([
                'new_payments' => 0,
                'completed_payments' => 0,
            ]);
        }

        $newCount = ResellerInstallationPayment::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new')
            ->count();

        $completedCount = ResellerInstallationPayment::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'new_payments' => $newCount,
            'completed_payments' => $completedCount,
        ]);
    }
}
