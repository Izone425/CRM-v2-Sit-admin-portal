<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class ResellerExpiredLicenseExportController extends Controller
{
    public function export(Request $request)
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            abort(401, 'Unauthorized');
        }

        $activeTab = $request->get('tab', '90days');
        $search = trim((string) $request->get('search', ''));
        $renewalStatusFilter = (array) $request->get('renewal_status_filter', []);

        $canManageAdvanced = ($reseller->advanced_modules ?? 'disable') === 'enable';
        $today = Carbon::now()->startOfDay();
        $ninetyDaysFromNow = Carbon::now()->startOfDay()->addDays(90);

        $resellerLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->get(['f_id', 'f_company_name']);

        $companies = [];

        foreach ($resellerLinks as $link) {
            if ($search !== '' && stripos($link->f_company_name, $search) === false) {
                continue;
            }

            $query = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('crm_company_license.f_company_id', $link->f_id)
                ->where('crm_company_license.f_type', 'PAID')
                ->where(function ($q) use ($canManageAdvanced) {
                    $q->where('crm_company_license.f_name', 'like', '%TA%')
                      ->orWhere('crm_company_license.f_name', 'like', '%leave%')
                      ->orWhere('crm_company_license.f_name', 'like', '%claim%')
                      ->orWhere('crm_company_license.f_name', 'like', '%payroll%')
                      ->orWhere('crm_company_license.f_name', 'like', '%Face & QR Code%');

                    if ($canManageAdvanced) {
                        $q->orWhere('crm_company_license.f_name', 'like', '%VMS%')
                          ->orWhere('crm_company_license.f_name', 'like', '%FCC%')
                          ->orWhere('crm_company_license.f_name', 'like', '%Patrol%');
                    }
                });

            if ($activeTab === '90days') {
                $query->where('crm_company_license.status', 'Active')
                      ->whereBetween('crm_company_license.f_expiry_date', [
                          $today->format('Y-m-d'),
                          $ninetyDaysFromNow->format('Y-m-d'),
                      ]);
            } else {
                $query->where('crm_company_license.status', 'Active')
                      ->whereDate('crm_company_license.f_expiry_date', '>=', $today->format('Y-m-d'));
            }

            $expiringLicense = $query->orderBy('crm_company_license.f_expiry_date', 'asc')
                ->first(['crm_company_license.f_expiry_date']);

            if (!$expiringLicense) {
                continue;
            }

            $expiryDate = Carbon::parse($expiringLicense->f_expiry_date);
            $daysUntilExpiry = $today->diffInDays($expiryDate);

            $newestLicense = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', $link->f_id)
                ->where('f_type', 'PAID')
                ->where('status', 'Active')
                ->where('f_expiry_date', '>', $expiringLicense->f_expiry_date)
                ->where(function ($q) use ($canManageAdvanced) {
                    $q->where('f_name', 'like', '%TA%')
                      ->orWhere('f_name', 'like', '%leave%')
                      ->orWhere('f_name', 'like', '%claim%')
                      ->orWhere('f_name', 'like', '%payroll%')
                      ->orWhere('f_name', 'like', '%Face & QR Code%');

                    if ($canManageAdvanced) {
                        $q->orWhere('f_name', 'like', '%VMS%')
                          ->orWhere('f_name', 'like', '%FCC%')
                          ->orWhere('f_name', 'like', '%Patrol%');
                    }
                })
                ->orderBy('f_expiry_date', 'desc')
                ->first(['f_expiry_date']);

            $renewalStatus = 'pending';
            if ($newestLicense) {
                $newestExpiry = Carbon::parse($newestLicense->f_expiry_date);
                $renewalStatus = $newestExpiry->gt($ninetyDaysFromNow) ? 'done' : 'done_expiring';
            }

            $companies[] = [
                'f_company_name' => $link->f_company_name,
                'f_expiry_date' => $expiringLicense->f_expiry_date,
                'days_until_expiry' => $daysUntilExpiry,
                'renewal_status' => $renewalStatus,
            ];
        }

        if (!empty($renewalStatusFilter)) {
            $companies = array_values(array_filter($companies, function ($c) use ($renewalStatusFilter) {
                return in_array($c['renewal_status'], $renewalStatusFilter, true);
            }));
        }

        usort($companies, function ($a, $b) {
            return $a['days_until_expiry'] - $b['days_until_expiry'];
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(20);

        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'Company Name');
        $sheet->setCellValue('C1', 'Expiry Date');
        $sheet->setCellValue('D1', 'Days Until Expiry');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $row = 2;
        foreach ($companies as $index => $company) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, strtoupper($company['f_company_name']));
            $sheet->setCellValue('C' . $row, Carbon::parse($company['f_expiry_date'])->format('Y-m-d'));
            $sheet->setCellValue('D' . $row, $company['days_until_expiry']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = $activeTab === '90days'
            ? 'expired_licenses_90days_' . date('Y-m-d_His') . '.xlsx'
            : 'all_expired_licenses_' . date('Y-m-d_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
