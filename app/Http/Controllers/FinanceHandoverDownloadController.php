<?php

namespace App\Http\Controllers;

use App\Models\FinanceHandover;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinanceHandoverDownloadController extends Controller
{
    private function ensureArray($value): array
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                if (count($decoded) === 1 && is_string($decoded[0])) {
                    $inner = json_decode($decoded[0], true);
                    if (is_array($inner)) return $inner;
                }
                return $decoded;
            }
            return [$value];
        }
        return [];
    }

    public function downloadResellerInvoice($id)
    {
        try {
            $record = FinanceHandover::findOrFail($id);
            $files = $this->ensureArray($record->invoice_by_reseller);

            if (empty($files)) {
                return back()->with('error', 'No reseller invoice files found.');
            }

            $filePath = $files[0];
            if (Storage::disk('public')->exists($filePath)) {
                $fullPath = Storage::disk('public')->path($filePath);
                $filename = $record->formatted_handover_id . '_Reseller_Invoice.' . pathinfo($filePath, PATHINFO_EXTENSION);
                return response()->download($fullPath, $filename);
            }

            return back()->with('error', 'File not found.');

        } catch (\Exception $e) {
            Log::error('Reseller invoice download error: ' . $e->getMessage());
            return back()->with('error', 'Error downloading reseller invoice.');
        }
    }

}
