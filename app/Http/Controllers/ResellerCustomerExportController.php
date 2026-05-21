<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerCustomerExportController extends Controller
{
    public function export()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            abort(401, 'Unauthorized');
        }

        // Get active customers
        $activeCustomers = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->select('crm_customer.f_company_name')
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
            ->where('crm_customer.f_status', 'A')
            ->orderBy('crm_customer.f_reg_date', 'desc')
            ->pluck('f_company_name')
            ->toArray();

        // Get inactive customers
        $inactiveCustomers = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->select('crm_customer.f_company_name')
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
            ->whereIn('crm_customer.f_status', ['D', 'I', 'T'])
            ->orderBy('crm_customer.f_reg_date', 'desc')
            ->pluck('f_company_name')
            ->toArray();

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set column widths (80 characters)
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(50);

        // Add headers
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'Active Customers');
        $sheet->setCellValue('C1', 'InActive Customer');

        // Style headers
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        // Add data rows
        $maxRows = max(count($activeCustomers), count($inactiveCustomers));
        $row = 2;

        for ($i = 0; $i < $maxRows; $i++) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, strtoupper($activeCustomers[$i] ?? ''));
            $sheet->setCellValue('C' . $row, strtoupper($inactiveCustomers[$i] ?? ''));
            $row++;
        }

        // Create writer and stream response
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="customer_list_' . date('Y-m-d_His') . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
