<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\AdminRenewalLogs;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

// Create a temporary model for the renewal data
class RenewalDataMyr extends Model
{
    protected $connection = 'frontenddb';

    protected $table = 'crm_expiring_license';

    protected $primaryKey = 'f_company_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    // Define excluded products in one place
    public static $excludedProducts = [
        'TimeTec VMS Corporate (1 Floor License)',
        'TimeTec VMS SME (1 Location License)',
        'TimeTec Patrol (1 Checkpoint License)',
        'TimeTec Patrol (10 Checkpoint License)',
        'Other',
        'TimeTec Profile (10 User License)',
    ];

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    // Helper method to apply product exclusions to query
    public static function applyProductExclusions($query)
    {
        foreach (self::$excludedProducts as $excludedProduct) {
            $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
        }

        return $query;
    }

    // Get reseller information for a company
    public static function getResellerForCompany($companyId)
    {
        try {
            return DB::connection('frontenddb')->table('crm_reseller_link')
                ->select('reseller_name', 'f_rate')
                ->where('f_id', $companyId)
                ->first();
        } catch (\Exception $e) {
            Log::error("Error fetching reseller for company $companyId: ".$e->getMessage());

            return null;
        }
    }

    // Get invoices for a specific company
    public static function getInvoicesForCompany($companyId, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            if (! $startDate || ! $endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(90)->format('Y-m-d');
            }

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_invoice_no',
                    'f_currency',
                    DB::raw('MAX(f_total_amount) AS invoice_total_amount'),
                    DB::raw('SUM(f_unit) AS invoice_total_units'),
                    DB::raw('COUNT(*) AS invoice_product_count'),
                    DB::raw('MIN(f_expiry_date) AS invoice_earliest_expiry'),
                    DB::raw('MAX(f_expiry_date) AS invoice_latest_expiry'),
                    DB::raw('ANY_VALUE(f_company_name) AS f_company_name'),
                    DB::raw('ANY_VALUE(f_company_id) AS f_company_id'),
                ])
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate)
                ->where('f_currency', 'MYR');

            // Apply product exclusions
            foreach (self::$excludedProducts as $excludedProduct) {
                $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
            }

            return $query->groupBy('f_invoice_no', 'f_currency')
                ->having(DB::raw('COUNT(*)'), '>', 0)
                ->orderBy('f_invoice_no', 'ASC')
                ->get(); // Remove ->toArray() to keep as objects
        } catch (\Exception $e) {
            Log::error("Error fetching invoices for company $companyId: ".$e->getMessage());

            return collect(); // Return empty collection instead of empty array
        }
    }

    public static function getProductsForInvoice($companyId, $invoiceNo, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            if (! $startDate || ! $endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(90)->format('Y-m-d');
            }

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_currency',
                    'f_id',
                    'f_company_name',
                    'f_company_id',
                    'f_name',
                    'f_invoice_no',
                    'f_total_amount',
                    'f_unit',
                    'f_start_date',
                    'f_expiry_date',
                    'Created',
                    'payer',
                    'payer_id',
                    'f_created_time',
                ])
                ->where('f_company_id', $companyId)
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate)
                ->where('f_currency', 'MYR');

            // Apply product exclusions
            foreach (self::$excludedProducts as $excludedProduct) {
                $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
            }

            return $query->orderBy('f_expiry_date', 'ASC')
                ->get(); // Remove ->toArray() to keep as objects
        } catch (\Exception $e) {
            Log::error("Error fetching products for company $companyId and invoice $invoiceNo: ".$e->getMessage());

            return collect(); // Return empty collection instead of empty array
        }
    }
}

class AdminRenewalProcessDataMyr extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Renewal Process Data (MYR)';

    protected static ?string $navigationLabel = 'Renewal Process Data (MYR)';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 51;

    protected static string $view = 'filament.pages.admin-renewal-process-data-myr';

    protected $renewalCache = [];
    protected $resellerCache = [];

    public function mount(): void
    {
        $today = Carbon::now()->format('d/m/Y');
        $next90Days = Carbon::now()->addDays(90)->format('d/m/Y');
        $this->tableFilters['earliest_expiry']['date_range'] = $today.' - '.$next90Days;
    }

    public function getFilteredCompanyIds(): array
    {
        try {
            return $this->getFilteredTableQuery()
                ->distinct()
                ->pluck('f_company_id')
                ->filter()
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting filtered company IDs: '.$e->getMessage());
            return [];
        }
    }


    protected function getCachedRenewal($companyId)
    {
        if (!isset($this->renewalCache[$companyId])) {
            $this->renewalCache[$companyId] = Renewal::whereCompanyId($companyId)->first();
        }
        return $this->renewalCache[$companyId];
    }

    // ✅ Cache reseller data method
    protected function getCachedReseller($companyId)
    {
        if (!isset($this->resellerCache[$companyId])) {
            $this->resellerCache[$companyId] = RenewalDataMyr::getResellerForCompany($companyId);
        }
        return $this->resellerCache[$companyId];
    }

    protected function getProductGroupMapping(): array
    {
        return [
            // TimeTec HR Group
            'timetec_hr' => [
                'TimeTec TA (1 User License)',
                'TimeTec TA (10 User License)',
                'TimeTec Leave (1 User License)',
                'TimeTec Leave (10 User License)',
                'TimeTec Claim (1 User License)',
                'TimeTec Claim (10 User License)',
                'TimeTec Payroll (1 Payroll License)',
                'TimeTec Payroll (10 Payroll License)',
            ],
            // Non-TimeTec HR Group
            'non_timetec_hr' => [
                'Face & QR Code (1 Device License)',
                'FCC Terminal License',
                'TimeTec Access (1 Door License)',
                'TimeTec Hire Business (Unlimited Job Posts)',
                'TimeTec Hire Startup (10 Job Posts)',
            ],
            // Other Division Group
            'other_division' => [
                'TimeTec VMS Corporate (1 Floor License)',
                'TimeTec VMS SME (1 Location License)',
                'TimeTec Patrol (1 Checkpoint License)',
                'TimeTec Patrol (10 Checkpoint License)',
                'Other',
                'TimeTec Profile (10 User License)',
            ],
        ];
    }

    protected function getProductGroup(string $productName): ?string
    {
        $mapping = $this->getProductGroupMapping();

        foreach ($mapping as $group => $products) {
            foreach ($products as $product) {
                if (stripos($productName, $product) !== false || $productName === $product) {
                    return $group;
                }
            }
        }

        return 'other_division'; // Default to Other Division for unmapped products
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $baseQuery = RenewalDataMyr::query();

                // ✅ Use indexed columns and reduce function calls
                $today = Carbon::now()->format('Y-m-d');

                $baseQuery
                    ->where('f_expiry_date', '>=', $today)
                    ->where('f_currency', 'MYR');

                // ✅ Apply product exclusions more efficiently using NOT IN
                $excludedProducts = RenewalDataMyr::$excludedProducts;
                foreach ($excludedProducts as $excludedProduct) {
                    $baseQuery->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
                }

                // Get excluded company IDs without caching to ensure real-time data accuracy
                $excludedCompanyIds = Renewal::where('hr_version', 1)
                    ->whereIn('renewal_progress', ['terminated', 'completed_renewal', 'completed_reseller_portal'])
                    ->pluck('f_company_id')
                    ->toArray();

                if (!empty($excludedCompanyIds)) {
                    $baseQuery->whereNotIn('f_company_id', $excludedCompanyIds);
                }

                // Exclude companies that already have any relevant MYR license expiring beyond current year
                // (multi-year subscribers / pre-renewed customers — no current renewal action needed)
                $endOfCurrentYear = Carbon::now()->endOfYear()->format('Y-m-d');

                $preRenewedQuery = RenewalDataMyr::query()
                    ->where('f_currency', 'MYR')
                    ->where('f_expiry_date', '>', $endOfCurrentYear);
                RenewalDataMyr::applyProductExclusions($preRenewedQuery);

                $preRenewedCompanyIds = $preRenewedQuery
                    ->distinct()
                    ->pluck('f_company_id')
                    ->toArray();

                if (!empty($preRenewedCompanyIds)) {
                    $baseQuery->whereNotIn('f_company_id', $preRenewedCompanyIds);
                }

                // ✅ Optimized aggregation - avoid ANY_VALUE and complex calculations
                $baseQuery->selectRaw('
                    f_company_id,
                    f_company_name,
                    f_currency,
                    SUM(f_total_amount) as total_amount,
                    SUM(f_unit) as total_units,
                    COUNT(*) as total_products,
                    COUNT(DISTINCT f_invoice_no) as total_invoices,
                    MIN(f_expiry_date) as earliest_expiry,
                    MAX(f_created_time) as f_created_time
                ')
                ->groupBy('f_company_id', 'f_company_name', 'f_currency')
                ->havingRaw('COUNT(*) > 0');

                return $baseQuery;
            })
            ->filters([
                SelectFilter::make('f_name')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        $today = Carbon::now()->format('Y-m-d');
                        $query = RenewalDataMyr::query()
                            ->whereRaw('f_expiry_date >= ?', [$today])
                            ->where('f_currency', '=', 'MYR');

                        // Apply product exclusions
                        RenewalDataMyr::applyProductExclusions($query);

                        return $query->distinct()
                            ->orderBy('f_name')
                            ->pluck('f_name', 'f_name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['values'])) {
                            $subQuery = RenewalDataMyr::query()
                                ->select('f_company_id')
                                ->whereIn('f_name', $data['values'])
                                ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                ->where('f_currency', '=', 'MYR');

                            // Apply product exclusions
                            RenewalDataMyr::applyProductExclusions($subQuery);

                            $subQuery->distinct();

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Products'),

                SelectFilter::make('product_group')
                    ->label('Product Group')
                    ->options([
                        'timetec_hr' => 'TimeTec HR',
                        'non_timetec_hr' => 'Non-TimeTec HR',
                        'other_division' => 'Other Division',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $mapping = $this->getProductGroupMapping();
                            $selectedProducts = $mapping[$data['value']] ?? [];

                            if (! empty($selectedProducts)) {
                                // Get company IDs that have products in the selected group
                                $subQuery = RenewalDataMyr::query()
                                    ->select('f_company_id')
                                    ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                    ->where('f_currency', '=', 'MYR')
                                    ->where(function ($q) use ($selectedProducts) {
                                        foreach ($selectedProducts as $product) {
                                            $q->orWhere('f_name', 'LIKE', '%'.$product.'%');
                                        }
                                    });

                                // Apply product exclusions to subquery
                                RenewalDataMyr::applyProductExclusions($subQuery);

                                $subQuery->distinct();

                                $query->whereIn('f_company_id', $subQuery);
                            }
                        }
                    })
                    ->indicator('Product Group'),

                SelectFilter::make('reseller_name')
                    ->label('Filter by Reseller')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        // Get all unique resellers from the reseller link table
                        return DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->select('reseller_name')
                            ->whereNotNull('reseller_name')
                            ->where('reseller_name', '!=', '')
                            ->distinct()
                            ->orderBy('reseller_name')
                            ->pluck('reseller_name', 'reseller_name')
                            ->mapWithKeys(function ($name, $key) {
                                return [$key => strtoupper($name)];
                            })
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            // Get company IDs that belong to the selected reseller
                            $resellerCompanyIds = DB::connection('frontenddb')
                                ->table('crm_reseller_link')
                                ->where('reseller_name', $data['value'])
                                ->pluck('f_id')
                                ->toArray();

                            if (! empty($resellerCompanyIds)) {
                                $query->whereIn('f_company_id', $resellerCompanyIds);
                            } else {
                                // If no companies found for this reseller, return empty result
                                $query->where('f_company_id', -1);
                            }
                        }
                    })
                    ->indicator('Reseller'),

                Filter::make('earliest_expiry')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Expiry Date Range')
                            ->placeholder('Select expiry date range')
                            ->default(function () {
                                $today = Carbon::now()->format('d/m/Y');
                                $next90Days = Carbon::now()->addDays(90)->format('d/m/Y');

                                return $today.' - '.$next90Days;
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['date_range'])) {
                            return;
                        }

                        try {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay()->format('Y-m-d');
                            $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay()->format('Y-m-d');

                            $today = Carbon::now()->format('Y-m-d');
                            if ($startDate < $today) {
                                $startDate = $today;
                            }

                            $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
                        } catch (\Exception $e) {
                            Log::error('Date filter error: '.$e->getMessage());
                            $today = Carbon::now()->format('Y-m-d');
                            $next90Days = Carbon::now()->addDays(90)->format('Y-m-d');
                            $query->whereBetween('f_expiry_date', [$today, $next90Days]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (empty($data['date_range'])) {
                            return null;
                        }

                        [$start, $end] = explode(' - ', $data['date_range']);

                        return 'Expiry: '.
                            Carbon::createFromFormat('d/m/Y', trim($start))->format('j M Y').
                            ' → '.
                            Carbon::createFromFormat('d/m/Y', trim($end))->format('j M Y');
                    }),

                SelectFilter::make('renewal_progress')
                    ->multiple()
                    ->label('Renewal Progress')
                    ->options([
                        'new' => 'New',
                        'pending_confirmation' => 'Pending Confirmation',
                        'pending_payment' => 'Pending Payment',
                        'completed_renewal' => 'Completed Payment',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['values'])) {
                            // Get company IDs with the selected renewal progress
                            $companyIds = Renewal::whereIn('renewal_progress', $data['values'])
                                ->pluck('f_company_id')
                                ->toArray();

                            if (! empty($companyIds)) {
                                $query->whereIn('f_company_id', $companyIds);
                            } else {
                                // If no companies found with this progress, return empty result
                                $query->where('f_company_id', -1);
                            }
                        }
                    })
                    ->indicator('Renewal Progress'),

                SelectFilter::make('admin_renewal')
                    ->label('Admin Renewal')
                    ->options(function () {
                        // Get all unique admin_renewal values from the database
                        $adminRenewals = Renewal::whereNotNull('admin_renewal')
                            ->distinct()
                            ->pluck('admin_renewal')
                            ->sort()
                            ->mapWithKeys(function ($name) {
                                return [$name => $name];
                            })
                            ->toArray();

                        // Add the "Unassigned" option
                        return ['unassigned' => 'Unassigned'] + $adminRenewals;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'unassigned') {
                                // For unassigned, include companies that either:
                                // 1. Don't have a renewal record, OR
                                // 2. Have admin_renewal as NULL
                                $assignedCompanyIds = Renewal::whereNotNull('admin_renewal')
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($assignedCompanyIds)) {
                                    $query->whereNotIn('f_company_id', $assignedCompanyIds);
                                }
                            } else {
                                // For specific admin assignments
                                $companyIds = Renewal::where('admin_renewal', $data['value'])
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($companyIds)) {
                                    $query->whereIn('f_company_id', $companyIds);
                                } else {
                                    // If no companies found with this admin, return empty result
                                    $query->where('f_company_id', -1);
                                }
                            }
                        }
                    })
                    ->indicator('Admin Renewal'),

                SelectFilter::make('reseller_status')
                    ->label('Reseller Status')
                    ->options([
                        'with_reseller' => 'With Reseller',
                        'without_reseller' => 'Without Reseller',
                        'direct_end_user_part_1' => 'Direct End User Part 1',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'with_reseller') {
                                // Get company IDs that have resellers
                                $resellerCompanyIds = DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->pluck('f_id')
                                    ->toArray();

                                if (! empty($resellerCompanyIds)) {
                                    $query->whereIn('f_company_id', $resellerCompanyIds);
                                } else {
                                    // If no resellers found, return empty result
                                    $query->where('f_company_id', -1);
                                }
                            } elseif ($data['value'] === 'without_reseller') {
                                // Get company IDs that don't have resellers
                                $resellerCompanyIds = DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->pluck('f_id')
                                    ->toArray();

                                if (! empty($resellerCompanyIds)) {
                                    $query->whereNotIn('f_company_id', $resellerCompanyIds);
                                }
                                // If no resellers exist at all, all companies are without resellers (no additional filter needed)
                            } elseif ($data['value'] === 'direct_end_user_part_1') {
                                // Show only end users under these specific resellers
                                $includeResellers = [
                                    'PC MART SDN BHD',
                                    'TALENT EARTH MANAGEMENT',
                                    'AMTECH SYSTEM SOLUTIONS',
                                    'MY RADIONS TECHNOLOGIES SDN BHD',
                                    'HR FORTE SYSTEMS PTE. LTD.',
                                    'ACCLIME MALAYSIA',
                                    'FLEX MANAGEMENT SOLUTIONS',
                                    'EPITRONIC SDN BHD',
                                ];

                                $companyIds = DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->whereIn('reseller_name', $includeResellers)
                                    ->pluck('f_id')
                                    ->toArray();

                                if (! empty($companyIds)) {
                                    $query->whereIn('f_company_id', $companyIds);
                                } else {
                                    $query->where('f_company_id', -1);
                                }
                            }
                        }
                    })
                    ->indicator('Reseller Status'),

                SelectFilter::make('exclude_resellers')
                    ->label('Exclude Resellers')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        // Get all unique resellers from the reseller link table
                        return DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->select('reseller_name')
                            ->whereNotNull('reseller_name')
                            ->where('reseller_name', '!=', '')
                            ->distinct()
                            ->orderBy('reseller_name')
                            ->pluck('reseller_name', 'reseller_name')
                            ->mapWithKeys(function ($name, $key) {
                                return [$key => strtoupper($name)];
                            })
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['values'])) {
                            // Get company IDs that belong to the excluded resellers
                            $excludedResellerCompanyIds = DB::connection('frontenddb')
                                ->table('crm_reseller_link')
                                ->whereIn('reseller_name', $data['values'])
                                ->pluck('f_id')
                                ->toArray();

                            if (!empty($excludedResellerCompanyIds)) {
                                // Exclude companies that belong to these resellers
                                $query->whereNotIn('f_company_id', $excludedResellerCompanyIds);
                            }
                        }
                    })
                    ->indicator('Exclude Resellers'),

                SelectFilter::make('has_quotation')
                    ->label('Quotation Status')
                    ->options([
                        'has_quotation' => 'Has Quotation',
                        'no_quotation' => 'No Quotation',
                    ])
                    ->placeholder('All')
                    ->default(null)
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query; // Return all results if no filter is selected
                        }

                        if ($data['value'] === 'has_quotation') {
                            // Get company IDs that have renewal quotations
                            $companyIdsWithQuotations = \App\Models\Renewal::query()
                                ->whereNotNull('lead_id')
                                ->whereHas('lead.quotations', function ($q) {
                                    $q->where('mark_as_final', true)
                                    ->where('sales_type', 'RENEWAL SALES');
                                })
                                ->pluck('f_company_id')
                                ->toArray();

                            if (!empty($companyIdsWithQuotations)) {
                                return $query->whereIn('f_company_id', $companyIdsWithQuotations);
                            } else {
                                // No companies with quotations found, return empty result
                                return $query->where('f_company_id', -1);
                            }

                        } elseif ($data['value'] === 'no_quotation') {
                            // Get company IDs that DON'T have renewal quotations
                            $companyIdsWithQuotations = \App\Models\Renewal::query()
                                ->whereNotNull('lead_id')
                                ->whereHas('lead.quotations', function ($q) {
                                    $q->where('mark_as_final', true)
                                    ->where('sales_type', 'RENEWAL SALES');
                                })
                                ->pluck('f_company_id')
                                ->toArray();

                            if (!empty($companyIdsWithQuotations)) {
                                return $query->whereNotIn('f_company_id', $companyIdsWithQuotations);
                            } else {
                                // No companies with quotations exist, so all companies are "no quotation"
                                return $query;
                            }
                        }

                        return $query;
                    })
                    ->indicator('Quotation Status'),

                SelectFilter::make('task_status')
                    ->label('Task Status')
                    ->options([
                        'completed' => 'Completed',
                        'not_completed' => 'Not Completed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'completed') {
                                $companyIds = Renewal::where('task_status', true)
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($companyIds)) {
                                    $query->whereIn('f_company_id', $companyIds);
                                } else {
                                    $query->where('f_company_id', -1);
                                }
                            } elseif ($data['value'] === 'not_completed') {
                                $completedCompanyIds = Renewal::where('task_status', true)
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($completedCompanyIds)) {
                                    $query->whereNotIn('f_company_id', $completedCompanyIds);
                                }
                            }
                        }
                    })
                    ->indicator('Task Status'),
            ])
            ->filtersFormColumns(3)
            ->columns([
                TextColumn::make('f_company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->weight('bold')
                    ->alignLeft()
                    ->wrap()
                    ->color('primary')
                    ->url(function ($record) {
                        if (!$record) return null;
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return null;

                        $renewal = Renewal::whereCompanyId($companyId)->first();

                        if ($renewal && $renewal->lead_id) {
                            return route('filament.admin.resources.leads.view', [
                                'record' => \App\Classes\Encryptor::encrypt($renewal->lead_id),
                            ]);
                        }

                        return null;
                    })
                    ->openUrlInNewTab(),

                TextColumn::make('earliest_expiry')
                    ->alignCenter()
                    ->label('Expired License')
                    ->date('d M Y')
                    ->color(function ($state) {
                        if (!$state) return 'gray';

                        $today = Carbon::now();
                        $expiryDate = Carbon::parse($state);
                        $daysDiff = $expiryDate->diffInDays($today);

                        if ($expiryDate->isToday()) return 'danger';
                        if ($daysDiff <= 7) return 'warning';
                        if ($daysDiff <= 30) return 'info';
                        return 'gray';
                    }),

                TextColumn::make('renewal_progress_display')
                    ->label('Renewal Status')
                    ->state(function ($record) {
                        if (!$record) return null;
                        return $record->f_company_id ?? null;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return '';
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return '';

                        $renewal = $this->getCachedRenewal($companyId);
                        if (!$renewal || !$renewal->renewal_progress) return '';

                        return match ($renewal->renewal_progress) {
                            'new' => 'New',
                            'pending_confirmation' => 'Pending Confirmation',
                            'pending_payment' => 'Pending Payment',
                            'completed_renewal' => 'Completed Payment',
                            'completed_reseller_portal' => 'Completed(Reseller Portal)',
                            'not_completed' => 'Not Completed',
                            default => ucfirst(str_replace('_', ' ', $renewal->renewal_progress))
                        };
                    })
                    ->badge()
                    ->alignLeft()
                    ->color(function ($state, $record) {
                        if (!$record) return 'gray';
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return 'gray';

                        $renewal = $this->getCachedRenewal($companyId);
                        if (!$renewal || !$renewal->renewal_progress) return 'gray';

                        return match ($renewal->renewal_progress) {
                            'new' => 'info',
                            'pending_confirmation' => 'warning',
                            'pending_payment' => 'danger',
                            'completed_renewal' => 'success',
                            'completed_reseller_portal' => 'success',
                            'not_completed' => 'danger',
                            default => 'gray'
                        };
                    }),

                TextColumn::make('task_status_display')
                    ->label('Task')
                    ->alignCenter()
                    ->state(function ($record) {
                        if (!$record) return false;
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return false;
                        $renewal = $this->getCachedRenewal($companyId);
                        return $renewal ? (bool) $renewal->task_status : false;
                    })
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->html(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->alignRight()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return '0.00';
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return '0.00';

                        $renewal = $this->getCachedRenewal($companyId);
                        if (!$renewal || !$renewal->lead_id) return '0.00';

                        $cacheKey = "renewal_quotation_amount_{$renewal->lead_id}";
                        $totalAmount = Cache::remember($cacheKey, 300, function () use ($renewal) {
                            $lead = Lead::find($renewal->lead_id);
                            if (!$lead) return 0;

                            return $lead->quotations()
                                ->where('mark_as_final', true)
                                ->where('sales_type', 'RENEWAL SALES')
                                ->with('items')
                                ->get()
                                ->sum(function ($quotation) {
                                    return $quotation->items->sum('total_before_tax');
                                });
                        });

                        return number_format($totalAmount, 2);
                    }),

                TextColumn::make('next_follow_up_date')
                    ->label('Next Follow Up Date')
                    ->alignLeft()
                    ->state(function ($record) {
                        if (!$record) return null;
                        return $record->f_company_id ?? null;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return 'N/A';
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return 'N/A';

                        $renewal = $this->getCachedRenewal($companyId);
                        if (!$renewal) return 'N/A';

                        if ($renewal->renewal_progress === 'new') return 'N/A';

                        if (!$renewal->follow_up_date) return 'N/A';

                        return Carbon::parse($renewal->follow_up_date)->format('d M Y');
                    }),

                TextColumn::make('days_status')
                    ->label('Days')
                    ->alignLeft()
                    ->state(function ($record) {
                        if (!$record) return null;
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return null;

                        $renewal = $this->getCachedRenewal($companyId);
                        if (!$renewal) return null;
                        if ($renewal->renewal_progress === 'new') return null;
                        if (!$renewal->follow_up_date) return null;

                        return $renewal->follow_up_date;
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $today = Carbon::now()->startOfDay();
                        $followUpDate = Carbon::parse($state)->startOfDay();
                        $days = (int) $today->diffInDays($followUpDate, false);

                        if ($days < 0) {
                            return abs($days) . ' day(s) overdue';
                        } elseif ($days === 0) {
                            return 'Today';
                        } else {
                            return $days . ' day(s) left';
                        }
                    })
                    ->color(function ($state) {
                        if (!$state) return 'gray';

                        $today = Carbon::now()->startOfDay();
                        $followUpDate = Carbon::parse($state)->startOfDay();
                        $days = (int) $today->diffInDays($followUpDate, false);

                        if ($days <= 0) return 'danger';
                        return 'success';
                    })
                    ->badge(),

                TextColumn::make('reseller_display')
                    ->label('Category')
                    ->state(function ($record) {
                        if (!$record) return null;
                        return $record->f_company_id ?? null;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return '';
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return '';

                        $reseller = $this->getCachedReseller($companyId);
                        return $reseller ? 'Reseller' : '';
                    })
                    ->badge()
                    ->alignRight()
                    ->color('danger')
                    ->tooltip(function ($state, $record) {
                        if (!$record) return null;
                        $companyId = $record->f_company_id ?? null;
                        if (!$companyId) return null;

                        $reseller = $this->getCachedReseller($companyId);
                        if (!$reseller) return null;

                        return new HtmlString(strtoupper($reseller->reseller_name));
                    }),
            ])
            ->headerActions([
                Action::make('export_to_excel')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $query = $this->getFilteredTableQuery();

                        // Apply current table sorting (fallback to default sort)
                        $sortColumn = $this->tableSortColumn ?? 'earliest_expiry';
                        $sortDirection = $this->tableSortDirection ?? 'asc';
                        $query->reorder()->orderBy($sortColumn, $sortDirection);

                        $records = $query->get();

                        $data = [];
                        foreach ($records as $record) {
                            $companyId = $record->f_company_id ?? null;

                            // Renewal Status
                            $renewal = $companyId ? $this->getCachedRenewal($companyId) : null;
                            $renewalStatus = '';
                            if ($renewal && $renewal->renewal_progress) {
                                $renewalStatus = match ($renewal->renewal_progress) {
                                    'new' => 'New',
                                    'pending_confirmation' => 'Pending Confirmation',
                                    'pending_payment' => 'Pending Payment',
                                    'completed_renewal' => 'Completed Payment',
                                    'completed_reseller_portal' => 'Completed(Reseller Portal)',
                                    'not_completed' => 'Not Completed',
                                    default => ucfirst(str_replace('_', ' ', $renewal->renewal_progress))
                                };
                            }

                            // Amount
                            $totalAmount = '0.00';
                            if ($renewal && $renewal->lead_id) {
                                $cacheKey = "renewal_quotation_amount_{$renewal->lead_id}";
                                $amount = Cache::remember($cacheKey, 300, function () use ($renewal) {
                                    $lead = Lead::find($renewal->lead_id);
                                    if (!$lead) return 0;
                                    return $lead->quotations()
                                        ->where('mark_as_final', true)
                                        ->where('sales_type', 'RENEWAL SALES')
                                        ->with('items')
                                        ->get()
                                        ->sum(fn ($q) => $q->items->sum('total_before_tax'));
                                });
                                $totalAmount = number_format($amount, 2);
                            }

                            // Next Follow Up Date
                            $followUpDate = 'N/A';
                            if ($renewal && $renewal->renewal_progress !== 'new' && $renewal->follow_up_date) {
                                $followUpDate = Carbon::parse($renewal->follow_up_date)->format('d M Y');
                            }

                            // Category
                            $reseller = $companyId ? $this->getCachedReseller($companyId) : null;
                            $category = $reseller ? 'Reseller' : '';

                            $data[] = [
                                'company_name' => strtoupper($record->f_company_name),
                                'expired_license' => $record->earliest_expiry ? Carbon::parse($record->earliest_expiry)->format('d M Y') : '',
                                'renewal_status' => $renewalStatus,
                                'amount' => $totalAmount,
                                'next_follow_up_date' => $followUpDate,
                                'category' => $category,
                            ];
                        }

                        $timestamp = now()->format('Y-m-d_H-i-s');

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\RenewalProcessDataExport($data, 'MYR'),
                            "renewal_process_data_MYR_{$timestamp}.xlsx"
                        );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('view_lead_details')
                    //     ->label('View Lead Details')
                    //     ->icon('heroicon-o-eye')
                    //     ->color('info')
                    //     ->url(function ($record) {
                    //         $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                    //         if ($renewal && $renewal->lead_id) {
                    //             return route('filament.admin.resources.leads.view', [
                    //                 'record' => \App\Classes\Encryptor::encrypt($renewal->lead_id),
                    //             ]);
                    //         }

                    //         return null;
                    //     })
                    //     ->openUrlInNewTab(),

                    Action::make('assign_to_me')
                        ->label('Assign to Me')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Renewal to Me')
                        ->modalDescription(fn ($record) => "Are you sure you want to assign the renewal for {$record->f_company_name} to yourself?")
                        ->modalSubmitActionLabel('Yes, Assign to Me')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            // Only show after mapping is completed AND no one is assigned yet
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            return $renewal &&
                                $renewal->mapping_status === 'completed_mapping' &&
                                $renewal->admin_renewal === null;
                        })
                        ->action(function ($record) {
                            try {
                                // Update or create renewal record with current user
                                Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                                        'admin_renewal' => auth()->user()->name,
                                        'company_name' => $record->f_company_name,
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Assignment Successful')
                                    ->body("Renewal for {$record->f_company_name} has been assigned to you.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error assigning renewal: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Assignment Failed')
                                    ->body('There was an error assigning the renewal. Please try again.')
                                    ->send();
                            }
                        }),

                    Action::make('assign_to_admin')
                        ->label('Assign to Admin Renewal')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->form([
                            Select::make('admin_renewal')
                                ->label('Select Admin Renewal')
                                ->options([
                                    'Fatimah Nurnabilah' => 'Fatimah Nurnabilah',
                                ])
                                ->required()
                                ->placeholder('Select an admin'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                // Update or create renewal record with selected admin
                                Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                                        'admin_renewal' => $data['admin_renewal'],
                                        'company_name' => $record->f_company_name,
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Assignment Successful')
                                    ->body("Renewal for {$record->f_company_name} has been assigned to {$data['admin_renewal']}.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error assigning renewal: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Assignment Failed')
                                    ->body('There was an error assigning the renewal. Please try again.')
                                    ->send();
                            }
                        })
                        ->modalHeading('Assign to Admin Renewal')
                        ->modalDescription(fn ($record) => "Select an admin to assign the renewal for {$record->f_company_name}.")
                        ->modalSubmitActionLabel('Assign')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            // Only show after mapping is completed AND no one is assigned yet
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            return $renewal &&
                                $renewal->mapping_status === 'completed_mapping' &&
                                $renewal->admin_renewal === null;
                        }),

                    Action::make('mapping_action')
                        ->label('Mapping')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->fillForm(function ($record) {
                            return [
                                'company_name' => $record->f_company_name,
                                'name' => '-',  // Default dash
                                'email' => 'fatimah.tarmizi@timeteccloud.com',  // Default email
                                'phone' => '0',  // Default dash for phone
                                'company_size' => '1-24',  // Default to SMALL
                                'country' => 'MYS',  // Default to MALAYSIA
                                'lead_source' => 'Existing Customer (Migration)',
                                'products' => ['hr'], // This matches the 'hr' key from CreateLead
                            ];
                        })
                        ->form([
                            Select::make('mapping_type')
                                ->label('Mapping Type')
                                ->options([
                                    'before_handover' => 'Before Software Handover',
                                    'after_handover' => 'After Software Handover',
                                    'onhold' => 'OnHold Mapping',
                                ])
                                ->required()
                                ->reactive(),

                            // Show Lead ID field for after handover
                            Select::make('lead_id')
                                ->label('Select Lead')
                                ->searchable()
                                ->preload()
                                ->options(function () {
                                    return Lead::with('companyDetail')
                                        ->get()
                                        ->mapWithKeys(function ($lead) {
                                            $companyName = $lead->companyDetail
                                                ? $lead->companyDetail->company_name
                                                : 'Unknown Company';

                                            $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                            return [
                                                $lead->id => "Lead {$leadIdFormatted} - {$companyName}",
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->placeholder('Select a closed lead to map')
                                ->visible(fn ($get) => $get('mapping_type') === 'after_handover')
                                ->required(fn ($get) => $get('mapping_type') === 'after_handover')
                                ->getSearchResultsUsing(function (string $search) {
                                    return Lead::with('companyDetail')
                                        ->where(function ($query) use ($search) {
                                            $query->where('id', 'like', "%{$search}%")
                                                ->orWhereHas('companyDetail', function ($q) use ($search) {
                                                    $q->where('company_name', 'like', "%{$search}%");
                                                });
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($lead) {
                                            $companyName = $lead->companyDetail
                                                ? $lead->companyDetail->company_name
                                                : 'Unknown Company';

                                            $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                            return [
                                                $lead->id => "Lead {$leadIdFormatted} - {$companyName}",
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $lead = Lead::with('companyDetail')->find($value);

                                    if (! $lead) {
                                        return 'Lead not found';
                                    }

                                    $companyName = $lead->companyDetail
                                        ? $lead->companyDetail->company_name
                                        : 'Unknown Company';

                                    $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                    return "Lead {$leadIdFormatted} - {$companyName}";
                                }),

                            // Show Create Lead form for before handover - following CreateLead.php exactly
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    TextInput::make('email')
                                        ->label('Work Email Address')
                                        ->email()
                                        ->required()
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    PhoneInput::make('phone')
                                        ->label('Phone Number')
                                        ->required()
                                        ->suffixAction(
                                            \Filament\Forms\Components\Actions\Action::make('searchPhone')
                                                ->label('Verify')
                                                ->icon('heroicon-o-magnifying-glass')
                                                ->color('primary')
                                                ->action(function ($state, $set, $livewire) {
                                                    if (empty($state)) {
                                                        $set('phone_helper_text', 'Please enter a phone number to verify');

                                                        return;
                                                    }

                                                    // Show loading state
                                                    $set('phone_search_loading', true);

                                                    // Use sleep for visual effect
                                                    usleep(800000); // 0.8 second delay

                                                    // Remove the "+" symbol from the phone number for searching
                                                    $searchPhone = ltrim($state, '+');

                                                    // Check if phone already exists in the Lead table
                                                    $existingLeadsWithPhone = \App\Models\Lead::where('phone', $searchPhone)->get();

                                                    // If exists, set helper text with found lead details
                                                    if ($existingLeadsWithPhone->isNotEmpty()) {
                                                        $duplicateInfo = $existingLeadsWithPhone->map(function ($lead) {
                                                            $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : 'Unknown Company';

                                                            return "• {$companyName} (Lead ID: ".str_pad($lead->id, 5, '0', STR_PAD_LEFT).')';
                                                        })->implode("\n");

                                                        // Store as plain string with HTML markup
                                                        $set('phone_helper_text', '<span style="color:red;">⚠️ This phone number is already in use:</span><br>'.nl2br(htmlspecialchars($duplicateInfo)));
                                                    } else {
                                                        // Store as plain string with HTML markup
                                                        $set('phone_helper_text', '<span style="color:green;">✓ Phone number is unique</span>');
                                                    }

                                                    // Reset loading state
                                                    $set('phone_search_loading', false);
                                                })
                                        )
                                        ->helperText(function (callable $get) {
                                            if ($get('phone_search_loading')) {
                                                return 'Verifying phone number...';
                                            }

                                            // Get the helper text which is now stored as a string with HTML markup
                                            $helperText = $get('phone_helper_text');

                                            // Convert it to HtmlString only when rendering, not when storing
                                            return $helperText ? new HtmlString($helperText) : null;
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            // Remove the "+" symbol from the phone number
                                            return ltrim($state, '+');
                                        })
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    Select::make('company_size')
                                        ->label('Company Size')
                                        ->options([
                                            '1-24' => '1 - 24',
                                            '25-99' => '25 - 99',
                                            '100-500' => '100 - 500',
                                            '501 and Above' => '501 and Above',
                                        ])
                                        ->required()
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    Select::make('country')
                                        ->label('Country')
                                        ->searchable()
                                        ->required()
                                        ->default('MYS')
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/CountryCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                return collect($countries)->mapWithKeys(function ($country) {
                                                    return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                })->toArray();
                                            }

                                            return [];
                                        })
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),
                                ])
                                ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),
                        ])
                        ->action(function ($record, array $data) {
                            return $this->handleMappingAction($record, $data);
                        })
                        ->visible(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            if (! $renewal) {
                                return true;
                            }

                            return $renewal->mapping_status !== 'completed_mapping';
                        })
                        ->modalWidth('5xl')
                        ->modalHeading(fn ($record) => 'Mapping Action - '.$record->f_company_name),
                    Action::make('complete_task')
                        ->label('Complete Task')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Task')
                        ->modalDescription('Are you sure you want to mark this task as completed?')
                        ->visible(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();
                            return $renewal &&
                                $renewal->admin_renewal !== null &&
                                $renewal->renewal_progress === 'new' &&
                                !$renewal->task_status;
                        })
                        ->action(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();
                            if ($renewal) {
                                $renewal->update(['task_status' => true]);
                                $this->renewalCache = [];

                                Notification::make()
                                    ->success()
                                    ->title('Task Completed')
                                    ->send();
                            }
                        }),
                    Action::make('completed_follow_up')
                        ->label('Completed Follow Up')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Follow Up as Completed')
                        ->modalDescription('Are you sure you want to mark this follow up as completed? This will change the renewal progress to "Pending Confirmation".')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            return $renewal &&
                                $renewal->admin_renewal !== null &&
                                $renewal->renewal_progress === 'new' &&
                                $renewal->task_status;
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::whereCompanyId($record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? (is_array($existingRenewal->progress_history) ? $existingRenewal->progress_history : json_decode($existingRenewal->progress_history, true))
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now(),
                                    'action' => 'follow_up_completed',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'pending_confirmation',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Follow up marked as completed - Status changed to Pending Confirmation',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update or create renewal record with pending_confirmation status and updated progress_history
                                $renewal = Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                                        'renewal_progress' => 'pending_confirmation',
                                        'progress_history' => $progressHistory,
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Follow Up Completed')
                                    ->body("Follow up has been marked as completed. Renewal progress updated to 'Pending Confirmation'.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error updating follow up status: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error updating the follow up status. Please try again.')
                                    ->send();
                            }
                        }),
                    Action::make('completed_payment')
                        ->label('Completed Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Payment as Completed')
                        ->modalDescription('Are you sure you want to mark payment as completed? This will change the renewal progress to "Completed Renewal".')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            return $renewal && ($renewal->renewal_progress === 'pending_confirmation' || $renewal->renewal_progress === 'pending_payment');
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::whereCompanyId($record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? (is_array($existingRenewal->progress_history) ? $existingRenewal->progress_history : json_decode($existingRenewal->progress_history, true))
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now(),
                                    'action' => 'payment_completed',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'completed_renewal',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Payment marked as completed - Renewal process completed',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update renewal record
                                $renewal = Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                                        'renewal_progress' => 'completed_renewal',
                                        'progress_history' => $progressHistory,
                                        'payment_completed_at' => now(),
                                        'payment_completed_by' => auth()->user()->id,
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Payment Completed')
                                    ->body('Payment has been marked as completed. Renewal process is now complete.')
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error updating payment status: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error updating the payment status. Please try again.')
                                    ->send();
                            }
                        }),

                    Action::make('request_invoice')
                        ->label('Request Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Request Invoice')
                        ->modalDescription('Are you sure you want to request an invoice? This will change the renewal progress to "Pending Payment".')
                        ->modalSubmitActionLabel('Yes, Request Invoice')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                            return $renewal && $renewal->renewal_progress === 'pending_confirmation';
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::whereCompanyId($record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? (is_array($existingRenewal->progress_history) ? $existingRenewal->progress_history : json_decode($existingRenewal->progress_history, true))
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now(),
                                    'action' => 'invoice_requested',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'pending_payment',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Invoice requested - Status changed to Pending Payment',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update renewal record
                                $renewal = Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                                        'renewal_progress' => 'pending_payment',
                                        'progress_history' => $progressHistory,
                                        'invoice_requested_at' => now(),
                                        'invoice_requested_by' => auth()->user()->id,
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Invoice Requested')
                                    ->body("Invoice has been requested. Renewal progress updated to 'Pending Payment'.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error requesting invoice: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error requesting the invoice. Please try again.')
                                    ->send();
                            }
                        }),
                    // Action::make('follow_up')
                    //     ->label('Follow Up')
                    //     ->icon('heroicon-o-calendar-days')
                    //     ->color('primary')
                    //     ->form([
                    //         DatePicker::make('follow_up_date')
                    //             ->label('Next Follow-up Date')
                    //             ->default(function () {
                    //                 $today = now();
                    //                 $workingDaysAdded = 0;
                    //                 $currentDate = $today->copy();

                    //                 while ($workingDaysAdded < 2) {
                    //                     $currentDate->addDay();
                    //                     if ($currentDate->dayOfWeek >= 1 && $currentDate->dayOfWeek <= 5) {
                    //                         $workingDaysAdded++;
                    //                     }
                    //                 }

                    //                 return $currentDate;
                    //             })
                    //             ->minDate(now()->subDay())
                    //             ->required(),

                    //         RichEditor::make('notes')
                    //             ->label('Remarks')
                    //             ->disableToolbarButtons([
                    //                 'attachFiles',
                    //                 'blockquote',
                    //                 'codeBlock',
                    //                 'h2',
                    //                 'h3',
                    //                 'link',
                    //                 'redo',
                    //                 'strike',
                    //                 'undo',
                    //             ])
                    //             ->placeholder('Add your follow-up details here...')
                    //             ->required(),
                    //     ])
                    //     ->action(function ($record, array $data) {
                    //         try {
                    //             $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                    //             if (!$renewal) {
                    //                 Notification::make()
                    //                     ->warning()
                    //                     ->title('No Renewal Record')
                    //                     ->body("No renewal record found for {$record->f_company_name}.")
                    //                     ->send();
                    //                 return;
                    //             }

                    //             $renewal->update([
                    //                 'follow_up_date' => $data['follow_up_date'],
                    //                 'follow_up_counter' => true,
                    //             ]);

                    //             AdminRenewalLogs::create([
                    //                 'lead_id' => $renewal->lead_id,
                    //                 'description' => 'Admin Renewal Follow Up By ' . auth()->user()->name,
                    //                 'causer_id' => auth()->id(),
                    //                 'remark' => $data['notes'],
                    //                 'subject_id' => $renewal->id,
                    //                 'follow_up_date' => $data['follow_up_date'],
                    //                 'follow_up_counter' => true,
                    //             ]);

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Follow Up Added')
                    //                 ->body("Follow-up set for {$record->f_company_name}.")
                    //                 ->send();
                    //         } catch (\Exception $e) {
                    //             Log::error("Error follow-up for company {$record->f_company_id}: " . $e->getMessage());

                    //             Notification::make()
                    //                 ->danger()
                    //                 ->title('Error')
                    //                 ->body('Failed to add follow-up. Please try again.')
                    //                 ->send();
                    //         }
                    //     })
                    //     ->modalHeading('Follow Up')
                    //     ->modalSubmitActionLabel('Submit Follow Up')
                    //     ->modalCancelActionLabel('Cancel'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    // \Filament\Tables\Actions\BulkAction::make('batch_complete_renewal')
                    //     ->label('Batch Complete Renewal')
                    //     ->icon('heroicon-o-check-circle')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Batch Complete Renewal')
                    //     ->modalDescription('Are you sure you want to mark all selected renewals as "Completed Renewal"? This will update their renewal progress status.')
                    //     ->modalSubmitActionLabel('Yes, Complete Renewals')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->action(function ($records) {
                    //         $successCount = 0;
                    //         $errorCount = 0;
                    //         $skippedCount = 0;
                    //         $updatedCompanies = [];

                    //         foreach ($records as $record) {
                    //             try {
                    //                 // Get existing renewal record
                    //                 $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                    //                 if ($renewal) {
                    //                     // Skip if already completed
                    //                     if ($renewal->renewal_progress === 'completed_renewal') {
                    //                         $skippedCount++;
                    //                         continue;
                    //                     }

                    //                     // Get current progress_history or initialize as empty array
                    //                     $progressHistory = $renewal->progress_history
                    //                         ? (is_array($renewal->progress_history) ? $renewal->progress_history : json_decode($renewal->progress_history, true))
                    //                         : [];

                    //                     // Add new log entry
                    //                     $newLogEntry = [
                    //                         'timestamp' => now(),
                    //                         'action' => 'batch_completion',
                    //                         'previous_status' => $renewal->renewal_progress,
                    //                         'new_status' => 'completed_renewal',
                    //                         'performed_by' => auth()->user()->name,
                    //                         'performed_by_id' => auth()->user()->id,
                    //                         'description' => 'Renewal marked as completed via batch action',
                    //                         'company_name' => $record->f_company_name,
                    //                         'f_company_id' => $record->f_company_id,
                    //                     ];

                    //                     // Add the new entry to progress history
                    //                     $progressHistory[] = $newLogEntry;

                    //                     // Update renewal record
                    //                     $renewal->update([
                    //                         'renewal_progress' => 'completed_renewal',
                    //                         'progress_history' => $progressHistory,
                    //                         'payment_completed_at' => now(),
                    //                         'payment_completed_by' => auth()->user()->id,
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                     $updatedCompanies[] = $record->f_company_name;
                    //                 } else {
                    //                     // Create new renewal record with completed status
                    //                     Renewal::create([
                    //                         'f_company_id' => $record->f_company_id,
                    //                         'company_name' => $record->f_company_name,
                    //                         'renewal_progress' => 'completed_renewal',
                    //                         'progress_history' => [[
                    //                             'timestamp' => now(),
                    //                             'action' => 'batch_completion',
                    //                             'previous_status' => null,
                    //                             'new_status' => 'completed_renewal',
                    //                             'performed_by' => auth()->user()->name,
                    //                             'performed_by_id' => auth()->user()->id,
                    //                             'description' => 'Renewal created and marked as completed via batch action',
                    //                             'company_name' => $record->f_company_name,
                    //                             'f_company_id' => $record->f_company_id,
                    //                         ]],
                    //                         'payment_completed_at' => now(),
                    //                         'payment_completed_by' => auth()->user()->id,
                    //                         'mapping_status' => 'completed_mapping',
                    //                         'created_at' => now(),
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                     $updatedCompanies[] = $record->f_company_name;
                    //                 }
                    //             } catch (\Exception $e) {
                    //                 Log::error("Error batch completing renewal for company {$record->f_company_id}: " . $e->getMessage());
                    //                 $errorCount++;
                    //             }
                    //         }

                    //         // Send notification with results
                    //         if ($successCount > 0) {
                    //             $message = "Successfully completed {$successCount} renewal(s).";
                    //             if ($skippedCount > 0) {
                    //                 $message .= " {$skippedCount} were already completed.";
                    //             }
                    //             if ($errorCount > 0) {
                    //                 $message .= " {$errorCount} failed due to errors.";
                    //             }

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Batch Completion Successful')
                    //                 ->body($message)
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->warning()
                    //                 ->title('No Renewals Completed')
                    //                 ->body("No renewals were updated. {$skippedCount} were already completed and {$errorCount} had errors.")
                    //                 ->send();
                    //         }
                    //     })
                    //     ->deselectRecordsAfterCompletion()
                    //     ->visible(function () {
                    //         // Only show if user has appropriate permissions
                    //         return auth()->user()->role_id === 1 || auth()->user()->role_id === 3;
                    //     }),
                    \Filament\Tables\Actions\BulkAction::make('batch_completed_reseller_portal')
                        ->label('Completed (Reseller Portal)')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Batch Completed(Reseller Portal)')
                        ->modalDescription('Are you sure you want to mark all selected renewals as "Completed(Reseller Portal)"? This will update their renewal progress status.')
                        ->modalSubmitActionLabel('Yes, Mark as Completed(Reseller Portal)')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function ($records) {
                            $successCount = 0;
                            $errorCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                try {
                                    $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                                    if ($renewal) {
                                        if (in_array($renewal->renewal_progress, ['completed_renewal', 'completed_reseller_portal'])) {
                                            $skippedCount++;
                                            continue;
                                        }

                                        $progressHistory = $renewal->progress_history
                                            ? (is_array($renewal->progress_history) ? $renewal->progress_history : json_decode($renewal->progress_history, true))
                                            : [];

                                        $progressHistory[] = [
                                            'timestamp' => now(),
                                            'action' => 'batch_completed_reseller_portal',
                                            'previous_status' => $renewal->renewal_progress,
                                            'new_status' => 'completed_reseller_portal',
                                            'performed_by' => auth()->user()->name,
                                            'performed_by_id' => auth()->user()->id,
                                            'description' => 'Renewal marked as Completed(Reseller Portal) via batch action',
                                            'company_name' => $record->f_company_name,
                                            'f_company_id' => $record->f_company_id,
                                        ];

                                        $renewal->update([
                                            'renewal_progress' => 'completed_reseller_portal',
                                            'progress_history' => $progressHistory,
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    } else {
                                        Renewal::create([
                                            'f_company_id' => $record->f_company_id,
                                            'company_name' => $record->f_company_name,
                                            'renewal_progress' => 'completed_reseller_portal',
                                            'progress_history' => [[
                                                'timestamp' => now(),
                                                'action' => 'batch_completed_reseller_portal',
                                                'previous_status' => null,
                                                'new_status' => 'completed_reseller_portal',
                                                'performed_by' => auth()->user()->name,
                                                'performed_by_id' => auth()->user()->id,
                                                'description' => 'Renewal created and marked as Completed(Reseller Portal) via batch action',
                                                'company_name' => $record->f_company_name,
                                                'f_company_id' => $record->f_company_id,
                                            ]],
                                            'mapping_status' => 'completed_mapping',
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Error batch completing reseller portal for company {$record->f_company_id}: " . $e->getMessage());
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                $message = "Successfully marked {$successCount} renewal(s) as Completed(Reseller Portal).";
                                if ($skippedCount > 0) {
                                    $message .= " {$skippedCount} were already completed.";
                                }
                                if ($errorCount > 0) {
                                    $message .= " {$errorCount} failed due to errors.";
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Batch Completed(Reseller Portal) Successful')
                                    ->body($message)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Renewals Updated')
                                    ->body("No renewals were updated. {$skippedCount} were already completed and {$errorCount} had errors.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(function () {
                            return auth()->user()->role_id === 1 || auth()->user()->role_id === 3;
                        }),
                    // \Filament\Tables\Actions\BulkAction::make('batch_onhold_mapping')
                    //     ->label('Batch Update OnHold Mapping')
                    //     ->icon('heroicon-o-pause-circle')
                    //     ->color('warning')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Batch Update OnHold Mapping')
                    //     ->modalDescription('Are you sure you want to set the selected renewals to OnHold Mapping status? This will also assign them to "Auto Renewal".')
                    //     ->modalSubmitActionLabel('Yes, Update to OnHold')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->action(function ($records) {
                    //         $successCount = 0;
                    //         $errorCount = 0;
                    //         $updatedCompanies = [];

                    //         foreach ($records as $record) {
                    //             try {
                    //                 // Update or create renewal record with onhold mapping status
                    //                 Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                    //                         'company_name' => $record->f_company_name,
                    //                         'mapping_status' => 'onhold_mapping',
                    //                         'admin_renewal' => 'Auto Renewal',
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                 $successCount++;
                    //                 $updatedCompanies[] = $record->f_company_name;
                    //             } catch (\Exception $e) {
                    //                 Log::error("Error updating OnHold mapping for company {$record->f_company_id}: ".$e->getMessage());
                    //                 $errorCount++;
                    //             }
                    //         }

                    //         if ($successCount > 0) {
                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Batch Update Successful')
                    //                 ->body("Successfully updated {$successCount} renewal(s) to OnHold Mapping status.".
                    //                     ($errorCount > 0 ? " {$errorCount} failed to update." : ''))
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->danger()
                    //                 ->title('Batch Update Failed')
                    //                 ->body('No renewals were updated. Please try again.')
                    //                 ->send();
                    //         }
                    //     })
                    //     ->deselectRecordsAfterCompletion(),

                    // \Filament\Tables\Actions\BulkAction::make('batch_assign_admin')
                    //     ->label('Batch Assign Admin Renewal')
                    //     ->icon('heroicon-o-user-group')
                    //     ->color('info')
                    //     ->form([
                    //         Select::make('admin_renewal')
                    //             ->label('Select Admin Renewal')
                    //             ->options([
                    //                 'Fatimah Nurnabilah' => 'Fatimah Nurnabilah',
                    //             ])
                    //             ->required()
                    //             ->placeholder('Select an admin to assign')
                    //             ->helperText('All selected renewals will be assigned to the chosen admin.'),
                    //     ])
                    //     ->action(function ($records, array $data) {
                    //         $successCount = 0;
                    //         $errorCount = 0;
                    //         $skippedCount = 0;
                    //         $selectedAdmin = $data['admin_renewal'];

                    //         foreach ($records as $record) {
                    //             try {
                    //                 // Check if renewal exists and mapping status
                    //                 $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                    //                 if ($renewal && $renewal->mapping_status === 'completed_mapping') {
                    //                     // Update existing renewal record
                    //                     $renewal->update([
                    //                         'admin_renewal' => $selectedAdmin,
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                 } elseif (! $renewal) {
                    //                     // Create new renewal record with completed mapping (for assignment)
                    //                     Renewal::create([
                    //                         'f_company_id' => $record->f_company_id,
                    //                         'company_name' => $record->f_company_name,
                    //                         'mapping_status' => 'completed_mapping',
                    //                         'follow_up_date' => now(),
                    //                         'follow_up_counter' => true,
                    //                         'admin_renewal' => $selectedAdmin,
                    //                         'created_at' => now(),
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                 } else {
                    //                     // Skip records with incomplete mapping
                    //                     $skippedCount++;
                    //                 }
                    //             } catch (\Exception $e) {
                    //                 Log::error("Error batch assigning admin for company {$record->f_company_id}: ".$e->getMessage());
                    //                 $errorCount++;
                    //             }
                    //         }

                    //         if ($successCount > 0) {
                    //             $message = "Successfully assigned {$successCount} renewal(s) to {$selectedAdmin}.";
                    //             if ($skippedCount > 0) {
                    //                 $message .= " {$skippedCount} were skipped (mapping not completed).";
                    //             }
                    //             if ($errorCount > 0) {
                    //                 $message .= " {$errorCount} failed due to errors.";
                    //             }

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Batch Assignment Successful')
                    //                 ->body($message)
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->warning()
                    //                 ->title('No Assignments Made')
                    //                 ->body("No renewals were assigned. {$skippedCount} were skipped and {$errorCount} had errors.")
                    //                 ->send();
                    //         }
                    //     })
                    //     ->modalHeading('Batch Assign Admin Renewal')
                    //     ->modalDescription('Select an admin to assign to all selected renewal records.')
                    //     ->modalSubmitActionLabel('Assign Selected')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->deselectRecordsAfterCompletion(),

                    // \Filament\Tables\Actions\BulkAction::make('batch_assign_to_me')
                    //     ->label('Batch Assign to Me')
                    //     ->icon('heroicon-o-user')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Batch Assign to Me')
                    //     ->modalDescription('Are you sure you want to assign all selected renewals to yourself?')
                    //     ->modalSubmitActionLabel('Yes, Assign to Me')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->action(function ($records) {
                    //         $successCount = 0;
                    //         $errorCount = 0;
                    //         $skippedCount = 0;
                    //         $currentUserName = auth()->user()->name;

                    //         foreach ($records as $record) {
                    //             try {
                    //                 // Check if renewal exists
                    //                 $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                    //                 if ($renewal && $renewal->mapping_status === 'completed_mapping' && $renewal->admin_renewal === null) {
                    //                     // Update existing renewal record
                    //                     $renewal->update([
                    //                         'admin_renewal' => $currentUserName,
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                 } elseif (! $renewal) {
                    //                     // Create new renewal record
                    //                     Renewal::create([
                    //                         'f_company_id' => $record->f_company_id,
                    //                         'company_name' => $record->f_company_name,
                    //                         'mapping_status' => 'completed_mapping',
                    //                         'follow_up_date' => now(),
                    //                         'follow_up_counter' => true,
                    //                         'admin_renewal' => $currentUserName,
                    //                         'created_at' => now(),
                    //                         'updated_at' => now(),
                    //                     ]);

                    //                     $successCount++;
                    //                 } else {
                    //                     // Skip records that don't meet criteria
                    //                     $skippedCount++;
                    //                 }
                    //             } catch (\Exception $e) {
                    //                 Log::error("Error batch assigning to self for company {$record->f_company_id}: ".$e->getMessage());
                    //                 $errorCount++;
                    //             }
                    //         }

                    //         if ($successCount > 0) {
                    //             $message = "Successfully assigned {$successCount} renewal(s) to yourself.";
                    //             if ($skippedCount > 0) {
                    //                 $message .= " {$skippedCount} were skipped (already assigned or other conditions).";
                    //             }
                    //             if ($errorCount > 0) {
                    //                 $message .= " {$errorCount} failed due to errors.";
                    //             }

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Batch Assignment Successful')
                    //                 ->body($message)
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->warning()
                    //                 ->title('No Assignments Made')
                    //                 ->body("No renewals were assigned. {$skippedCount} were skipped and {$errorCount} had errors.")
                    //                 ->send();
                    //         }
                    //     })
                    //     ->deselectRecordsAfterCompletion(),

                    \Filament\Tables\Actions\BulkAction::make('batch_follow_up')
                        ->label('Batch Update Follow Up')
                        ->icon('heroicon-o-calendar-days')
                        ->color('primary')
                        ->form([
                            DatePicker::make('follow_up_date')
                                ->label('Next Follow-up Date')
                                ->default(function () {
                                    $today = now();
                                    $workingDaysAdded = 0;
                                    $currentDate = $today->copy();

                                    while ($workingDaysAdded < 2) {
                                        $currentDate->addDay();
                                        if ($currentDate->dayOfWeek >= 1 && $currentDate->dayOfWeek <= 5) {
                                            $workingDaysAdded++;
                                        }
                                    }

                                    return $currentDate;
                                })
                                ->minDate(now()->subDay())
                                ->required(),

                            RichEditor::make('notes')
                                ->label('Remarks')
                                ->disableToolbarButtons([
                                    'attachFiles',
                                    'blockquote',
                                    'codeBlock',
                                    'h2',
                                    'h3',
                                    'link',
                                    'redo',
                                    'strike',
                                    'undo',
                                ])
                                ->placeholder('Add your follow-up details here...')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $successCount = 0;
                            $errorCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                try {
                                    $renewal = Renewal::whereCompanyId($record->f_company_id)->first();

                                    if (!$renewal) {
                                        $skippedCount++;
                                        continue;
                                    }

                                    // Update the Renewal record with follow-up information
                                    $renewal->update([
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                    ]);

                                    // Create a new admin_renewal_logs entry
                                    AdminRenewalLogs::create([
                                        'lead_id' => $renewal->lead_id,
                                        'description' => 'Admin Renewal Batch Follow Up By ' . auth()->user()->name,
                                        'causer_id' => auth()->id(),
                                        'remark' => $data['notes'],
                                        'subject_id' => $renewal->id,
                                        'follow_up_date' => $data['follow_up_date'],
                                        'follow_up_counter' => true,
                                    ]);

                                    $successCount++;
                                } catch (\Exception $e) {
                                    Log::error("Error batch follow-up for company {$record->f_company_id}: " . $e->getMessage());
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                $message = "Successfully added follow-up for {$successCount} renewal(s).";
                                if ($skippedCount > 0) {
                                    $message .= " {$skippedCount} were skipped (no renewal record).";
                                }
                                if ($errorCount > 0) {
                                    $message .= " {$errorCount} failed due to errors.";
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Batch Follow Up Successful')
                                    ->body($message)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Follow Ups Added')
                                    ->body("No follow-ups were added. {$skippedCount} were skipped and {$errorCount} had errors.")
                                    ->send();
                            }
                        })
                        ->modalHeading('Batch Update Follow Up')
                        ->modalDescription('Set the next follow-up date and remarks for all selected renewals.')
                        ->modalSubmitActionLabel('Submit Follow Up')
                        ->modalCancelActionLabel('Cancel')
                        ->deselectRecordsAfterCompletion(),
                ])->label('Batch Update'),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_expiry', 'asc');
    }

    protected function handleMappingAction($record, array $data)
    {
        $mappingType = $data['mapping_type'];

        switch ($mappingType) {
            case 'before_handover':
                try {
                    // Follow the exact same pattern as CreateLead.php

                    // Get the latest lead ID to determine the next one
                    $latestLeadId = Lead::max('id') ?? 0;
                    $nextLeadId = $latestLeadId + 1;

                    // Create CompanyDetail first (like in CreateLead)
                    $companyDetail = CompanyDetail::create([
                        'company_name' => strtoupper(trim($data['company_name'])),
                        'lead_id' => $nextLeadId,
                    ]);

                    // Convert country code to country name (like in CreateLead)
                    $countryName = $this->convertCountryCodeToName($data['country']);

                    // Remove + from phone number (like in CreateLead)
                    $phoneNumber = ltrim($data['phone'], '+');

                    // Create Lead
                    $lead = Lead::create([
                        'company_name' => $companyDetail->id, // Store CompanyDetail ID
                        'name' => strtoupper($data['name']),
                        'email' => $data['email'],
                        'phone' => $phoneNumber,
                        'company_size' => $data['company_size'],
                        'country' => $countryName,
                        'admin_renewal' => 'Fatimah Nurnabilah',
                        'lead_code' => 'Existing Customer (Migration)',
                        'products' => 'hr', // This will be stored as JSON
                        'status' => 'new',
                        'f_company_id' => $record->f_company_id, // Link to renewal data
                    ]);

                    // First ActivityLog update - for renewal mapping
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    // Update the activity log description
                    if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'New lead created for renewal mapping',
                            'causer_id' => auth()->user()->id,
                        ]);
                    }

                    if (auth()->user()->role_id === 1 || auth()->user()->role_id === 3) {
                        sleep(1);
                        $lead->update([
                            'lead_owner' => auth()->user()->name,
                            'categories' => 'Inactive',
                            'stage' => null,
                            'lead_status' => 'Closed',
                            'pickup_date' => now(),
                            'closing_date' => now(),
                        ]);

                        // Second ActivityLog update - for assignment and closure
                        $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            ->orderByDesc('id')
                            ->first();

                        if ($latestActivityLog) {
                            $latestActivityLog->update([
                                'subject_id' => $lead->id,
                                'description' => 'Lead assigned to '.auth()->user()->name.' and Mark as Closed',
                            ]);
                        }
                    }

                    // Create or update renewal record
                    Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                            'lead_id' => $lead->id,
                            'company_name' => $data['company_name'],
                            'mapping_status' => 'completed_mapping',
                            'follow_up_date' => now(),
                            'follow_up_counter' => true,
                        ]);

                    Notification::make()
                        ->success()
                        ->title('Lead Created Successfully')
                        ->body("New lead created with ID: {$lead->lead_code} and mapped to renewal.")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view_lead')
                                ->label('View Lead')
                                ->url(route('filament.admin.resources.leads.view', ['record' => \App\Classes\Encryptor::encrypt($lead->id)]))
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                } catch (\Exception $e) {
                    Log::error('Error creating lead: '.$e->getMessage());

                    Notification::make()
                        ->danger()
                        ->title('Error Creating Lead')
                        ->body('There was an error creating the lead. Please try again.')
                        ->send();
                }
                break;

            case 'after_handover':
                $leadId = $data['lead_id'];

                Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                        'lead_id' => $leadId,
                        'company_name' => $record->f_company_name,
                        'mapping_status' => 'completed_mapping',
                        'follow_up_date' => now(),
                        'follow_up_counter' => true,
                    ]);

                Notification::make()
                    ->success()
                    ->title('Mapping Completed')
                    ->body("Successfully mapped to Lead ID: {$leadId}")
                    ->send();
                break;

            case 'onhold':
                Renewal::updateOrCreateByCompanyId($record->f_company_id, [
                        'company_name' => $record->f_company_name,
                        'mapping_status' => 'onhold_mapping',
                        'renewal_progress' => 'completed_renewal',
                        'admin_renewal' => 'Auto Renewal',
                    ]);

                Notification::make()
                    ->info()
                    ->title('Mapping On Hold')
                    ->body('Renewal mapping has been placed on hold.')
                    ->send();
                break;
        }
    }

    // Helper method to convert country code to name (like in CreateLead)
    protected function convertCountryCodeToName($countryCode)
    {
        $filePath = storage_path('app/public/json/CountryCodes.json');

        if (file_exists($filePath)) {
            $countriesContent = file_get_contents($filePath);
            $countries = json_decode($countriesContent, true);

            foreach ($countries as $country) {
                if ($country['Code'] === $countryCode) {
                    return ucfirst(strtolower($country['Country']));
                }
            }
        }

        return $countryCode; // Fallback
    }
}
