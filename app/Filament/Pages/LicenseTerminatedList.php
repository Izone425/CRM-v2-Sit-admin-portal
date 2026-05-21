<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LicenseTerminatedList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    protected static ?string $navigationLabel = 'License Terminated';
    protected static ?string $title = 'License Terminated List';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.license-terminated-list';

    public $expandedCompanies = [];

    public function mount(): void
    {
        // Initialize with empty array
        $this->expandedCompanies = [];
    }

    public function toggleCompany($companyId): void
    {
        if (in_array($companyId, $this->expandedCompanies)) {
            // Remove from expanded
            $this->expandedCompanies = array_diff($this->expandedCompanies, [$companyId]);
        } else {
            // Add to expanded
            $this->expandedCompanies[] = $companyId;
        }
    }

    public function getTerminatedCompanies()
    {
        // Cache for 1 hour to improve performance
        return Cache::remember('terminated_companies_2025', 3600, function () {
            // Optimized query using LEFT JOIN instead of NOT EXISTS
            $companies = DB::connection('frontenddb')
                ->table('crm_company_license as ccl')
                ->join('crm_expiring_license as cel', 'ccl.f_company_id', '=', 'cel.f_company_id')
                ->leftJoin('crm_company_license as ccl2', function ($join) {
                    $join->on('ccl.f_company_id', '=', 'ccl2.f_company_id')
                         ->where('ccl2.status', '=', 'Active');
                })
                ->whereBetween('ccl.f_expiry_date', ['2025-01-01', '2025-12-31'])
                ->where('ccl.status', 'Inactive')
                ->whereNull('ccl2.f_company_id') // No active licenses
                ->select('ccl.f_company_id', 'cel.f_company_name')
                ->groupBy('ccl.f_company_id', 'cel.f_company_name')
                ->orderBy('ccl.f_company_id')
                ->get();

            return $companies;
        });
    }

    public function getCompanyModules($companyId)
    {
        // Cache each company's modules for 1 hour
        return Cache::remember("company_modules_{$companyId}_2025", 3600, function () use ($companyId) {
            $modules = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', $companyId)
                ->whereBetween('f_expiry_date', ['2025-01-01', '2025-12-31'])
                ->where('status', 'Inactive')
                ->select('f_module', 'f_head_count', 'f_expiry_date', 'f_license_key')
                ->orderBy('f_module')
                ->get();

            return $modules;
        });
    }
}

