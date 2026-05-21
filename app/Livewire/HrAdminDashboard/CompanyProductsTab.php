<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrLicense;
use App\Models\Quotation;
use App\Services\HRV2LicenseService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompanyProductsTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];
    public array $productData = [];
    public array $licenseRecords = [];
    public array $groupedLicenseRecords = [];
    public ?string $maxPaidEndDate = null;

    // Edit modal properties
    public bool $showEditModal = false;
    public ?int $editingLicenseNo = null;
    public array $editForm = [
        'total_user' => '',
        'month' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => 'active',
        'update_paid_periods' => false,
    ];
    public string $editingLicenseType = '';

    // Bulk edit modal properties
    public bool $showBulkEditModal = false;
    public array $bulkEditForm = [
        'total_user' => '',
        'start_date' => '',
        'end_date' => '',
        'status' => '',
    ];
    public array $bulkEditEnabled = [
        'total_user' => false,
        'start_date' => false,
        'end_date' => false,
        'status' => false,
    ];

    // Selection mode properties
    public bool $isSelectionMode = false;
    public array $selectedLicenseNos = [];

    // Filter properties
    public string $filterType = 'all';
    public string $filterStatus = 'all';
    public string $filterProduct = 'all';
    public ?string $filterStartDate = null;
    public ?string $filterEndDate = null;

    // PI Modal properties
    public bool $showPiModal = false;
    public ?string $selectedInvoiceNo = null;
    public array $piData = [];
    public array $apiPiData = [];  // Store API-based PI data
    public bool $piLoading = false;
    public ?string $piError = null;

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadLicenseRecords();
        $this->loadProductData();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();

        // Compute max end date from PAID license records for consolidate billing
        $this->maxPaidEndDate = collect($this->licenseRecords)
            ->where('type', 'PAID')
            ->max('end_date');
    }

    protected function loadProductData(): void
    {
        // Derive counts from active license records
        $active = [
            'attendance_user' => 0, 'leave_user' => 0, 'claim_user' => 0, 'payroll_user' => 0,
            'onboarding' => 0, 'recruitment' => 0, 'appraisal' => 0, 'training' => 0,
        ];
        $inactive = [
            'attendance_user' => 0, 'leave_user' => 0, 'claim_user' => 0, 'payroll_user' => 0,
            'onboarding' => 0, 'recruitment' => 0, 'appraisal' => 0, 'training' => 0,
        ];

        $today = now()->startOfDay();

        foreach ($this->licenseRecords as $record) {
            $type = strtolower($record['license_type'] ?? '');
            $users = (int) ($record['user_limit'] ?? $record['total_user'] ?? 0);

            // Determine if license is currently active based on dates
            $start = $record['start_date'] ? \Carbon\Carbon::parse($record['start_date'])->startOfDay() : null;
            $end = $record['end_date'] ? \Carbon\Carbon::parse($record['end_date'])->endOfDay() : null;
            $isActive = $start && $end && $today->between($start, $end);

            $keys = [];
            if (str_contains($type, ' ta') || str_contains($type, 'attendance')) $keys[] = 'attendance_user';
            if (str_contains($type, 'leave')) $keys[] = 'leave_user';
            if (str_contains($type, 'claim')) $keys[] = 'claim_user';
            if (str_contains($type, 'payroll')) $keys[] = 'payroll_user';

            foreach ($keys as $key) {
                if ($isActive) {
                    $active[$key] += $users;
                } else {
                    $inactive[$key] += $users;
                }
            }
        }

        $modules = ['attendance_user', 'leave_user', 'claim_user', 'payroll_user', 'onboarding', 'recruitment', 'appraisal', 'training'];

        $totalActive = max($active['attendance_user'], $active['leave_user'], $active['claim_user'], $active['payroll_user']);
        $totalInactive = max($inactive['attendance_user'], $inactive['leave_user'], $inactive['claim_user'], $inactive['payroll_user']);

        $this->productData = [
            'user_account' => [
                'total' => $totalActive + $totalInactive,
                'active' => $totalActive,
                'inactive' => $totalInactive,
            ],
        ];

        foreach ($modules as $module) {
            $this->productData[$module === 'onboarding' ? 'onboarding_offboarding' : $module] = [
                'total' => $active[$module] + $inactive[$module],
                'active' => $active[$module],
                'inactive' => $inactive[$module],
            ];
        }
    }

    protected function loadLicenseRecords(): void
    {
        $this->licenseRecords = [];
        $allHandoverIds = $this->companyData['all_handover_ids'] ?? [];
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        // Fallback to single handover if no all_handover_ids
        if (empty($allHandoverIds) && $softwareHandover) {
            $allHandoverIds = [$softwareHandover->id];
        }

        if (empty($allHandoverIds)) return;

        // Load all handovers and resolve PI references
        $handovers = \App\Models\SoftwareHandover::whereIn('id', $allHandoverIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->keyBy('id');

        // Build a map of handover_id → sales invoice numbers (multiple per handover possible)
        $salesInvoiceMap = [];
        $allFormattedHandoverIds = $this->companyData['all_formatted_handover_ids'] ?? [];

        $salesInvoices = \App\Models\HrSalesInvoice::where(function ($q) use ($allHandoverIds, $allFormattedHandoverIds) {
                $q->whereIn('software_handover_id', $allHandoverIds);
                if (!empty($allFormattedHandoverIds)) {
                    $q->orWhereIn('handover_id', $allFormattedHandoverIds);
                }
            })
            ->orderBy('id')
            ->get(['software_handover_id', 'handover_id', 'invoice_no']);
        foreach ($salesInvoices as $si) {
            $key = $si->software_handover_id ?? $si->handover_id;
            $salesInvoiceMap[$key][] = $si->invoice_no;
        }

        $no = 1;

        // Load from hr_licenses table across ALL handovers (by SW ID and formatted handover ID)
        $dbLicenses = HrLicense::where(function ($q) use ($allHandoverIds, $allFormattedHandoverIds) {
                $q->whereIn('software_handover_id', $allHandoverIds);
                if (!empty($allFormattedHandoverIds)) {
                    $q->orWhereIn('handover_id', $allFormattedHandoverIds);
                }
            })
            ->orderBy('software_handover_id')
            ->orderBy('type')
            ->orderBy('start_date')
            ->get();

        if ($dbLicenses->isNotEmpty()) {
            // Build a map of (handover_id + license_type) → invoice_no from PAID licenses
            $paidInvoiceByType = [];
            foreach ($dbLicenses as $license) {
                if ($license->type === 'PAID' && $license->invoice_no && $license->invoice_no !== '-') {
                    $paidInvoiceByType[$license->software_handover_id . '|' . $license->license_type] = $license->invoice_no;
                }
            }

            foreach ($dbLicenses as $license) {
                $sw = $handovers[$license->software_handover_id] ?? null;

                // Resolve invoice_no: use own first, then match PAID by license_type, then fallback
                $resolvedInvoiceNo = '-';
                if ($license->invoice_no && $license->invoice_no !== '-') {
                    $resolvedInvoiceNo = $license->invoice_no;
                } else {
                    $typeKey = $license->software_handover_id . '|' . $license->license_type;
                    $resolvedInvoiceNo = $paidInvoiceByType[$typeKey]
                        ?? ($salesInvoiceMap[$license->software_handover_id][0]
                        ?? ($salesInvoiceMap[$license->handover_id][0] ?? '-'));
                }

                $this->licenseRecords[] = [
                    'no' => $no++,
                    'type' => $license->type,
                    'invoice_no' => $license->invoice_no ?? '-',
                    'auto_count_invoice_no' => $license->auto_count_invoice_no ?? '-',
                    'license_type' => $license->license_type,
                    'unit' => $license->unit ?? 0,
                    'user_limit' => $license->user_limit ?? 0,
                    'total_user' => $license->total_user ?? 0,
                    'total_login' => $license->total_login ?? 0,
                    'total_terminal' => 0,
                    'month' => $license->month ?? 0,
                    'start_date' => $license->start_date ? Carbon::parse($license->start_date)->format('Y-m-d') : null,
                    'end_date' => $license->end_date ? Carbon::parse($license->end_date)->format('Y-m-d') : null,
                    'status' => strtolower($license->status ?? 'enabled'),
                    'renewed' => $license->auto_renewal ?? '-',
                    'hr_license_id' => $license->id,
                    'period_id' => $license->period_id,
                    'license_set_id' => $license->license_set_id,
                    'software_handover_id' => $license->software_handover_id,
                    'sales_invoice_no' => $resolvedInvoiceNo,
                ];
            }
        }

        // Fallback: Build TRIAL records from type_1_pi_invoice_data for each handover if no hr_licenses
        if (empty($this->licenseRecords)) {
            foreach ($handovers as $sw) {
                if (!$sw->crm_buffer_license_id || !$sw->type_1_pi_invoice_data) continue;

                $piData = is_string($sw->type_1_pi_invoice_data)
                    ? json_decode($sw->type_1_pi_invoice_data, true)
                    : $sw->type_1_pi_invoice_data;

                $items = $piData['items'] ?? [];
                $bufferMonth = (int) ($piData['buffer_month'] ?? 1);
                $startDate = $sw->db_creation
                    ? Carbon::parse($sw->db_creation)->format('Y-m-d')
                    : null;
                $endDate = $startDate
                    ? Carbon::parse($startDate)->addMonths($bufferMonth)->subDay()->format('Y-m-d')
                    : null;

                $codeToName = [
                    'TCL_TA' => 'TimeTec TA',
                    'TCL_LEAVE' => 'TimeTec Leave',
                    'TCL_CLAIM' => 'TimeTec Claim',
                    'TCL_PAYROLL' => 'TimeTec Payroll',
                ];

                $trialInvoiceNo = 'TR' . Carbon::parse($sw->db_creation)->format('ymd') . $sw->crm_buffer_license_id;

                foreach ($items as $item) {
                    $code = $item['product_code'] ?? '';
                    $licenseName = $item['description'] ?? $code;
                    foreach ($codeToName as $prefix => $name) {
                        if (str_starts_with($code, $prefix)) {
                            $licenseName = $name;
                            break;
                        }
                    }

                    $qty = (int) ($item['qty'] ?? 0);

                    $this->licenseRecords[] = [
                        'no' => $no++,
                        'type' => 'TRIAL',
                        'invoice_no' => $trialInvoiceNo,
                        'license_type' => $licenseName,
                        'unit' => $qty,
                        'user_limit' => $qty,
                        'total_user' => $qty,
                        'total_login' => 0,
                        'total_terminal' => 0,
                        'month' => $bufferMonth,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => 'enabled',
                        'renewed' => '-',
                        'software_handover_id' => $sw->id,
                        'sales_invoice_no' => $salesInvoiceMap[$sw->id][0] ?? '-',
                    ];
                }
            }
        }
    }

    protected function getGroupedLicenseRecords(): array
    {
        return $this->getGroupedLicenseRecordsFrom($this->licenseRecords);
    }

    protected function getGroupedLicenseRecordsFrom(array $records): array
    {
        // Split into two sections:
        //   - TRIAL licenses are flattened to top-level sub-groups (type+year+invoice), rendered on top.
        //   - PAID licenses keep the invoice-level Tier 1 wrapper with type+year sub-groups inside.
        $trialSubGroups = [];
        $invoiceGroups = [];

        foreach ($records as $record) {
            $invoiceNo = $record['sales_invoice_no'] ?? $record['invoice_no'] ?? '-';
            $type = $record['type'];
            $startDate = $record['start_date'] ? \Carbon\Carbon::parse($record['start_date']) : null;
            $year = $startDate ? $startDate->format('Y') : 'Unknown';

            $productPayload = [
                'no' => $record['no'],
                'type' => $record['type'],
                'license_type' => $record['license_type'],
                'user_limit' => $record['user_limit'] ?? $record['total_user'] ?? 0,
                'total_user' => $record['total_user'],
                'total_login' => $record['total_login'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
                'status' => $record['status'] ?? 'enabled',
            ];

            if ($type === 'TRIAL') {
                $key = $year . '|' . $invoiceNo;
                if (!isset($trialSubGroups[$key])) {
                    $trialSubGroups[$key] = [
                        'invoice_no' => $invoiceNo,
                        'software_handover_id' => $record['software_handover_id'] ?? null,
                        'sales_type' => $record['sales_type'] ?? null,
                        'type' => 'TRIAL',
                        'year' => $year,
                        'label' => 'TRIAL - ' . $year,
                        'products' => [],
                    ];
                }
                $trialSubGroups[$key]['products'][] = $productPayload;
            } else {
                if (!isset($invoiceGroups[$invoiceNo])) {
                    $invoiceGroups[$invoiceNo] = [
                        'invoice_no' => $invoiceNo,
                        'software_handover_id' => $record['software_handover_id'] ?? null,
                        'sales_type' => $record['sales_type'] ?? null,
                        'status' => $record['status'] ?? null,
                        'renewed' => $record['renewed'] ?? null,
                        'products' => [],
                        'sub_groups' => [],
                    ];
                }
                $invoiceGroups[$invoiceNo]['products'][] = $productPayload;
            }
        }

        // Dedupe + sort TRIAL sub-groups (top-level flat list)
        foreach ($trialSubGroups as &$sg) {
            $sg['products'] = $this->dedupeProducts($sg['products']);
        }
        unset($sg);

        uasort($trialSubGroups, function ($a, $b) {
            $yearComp = $a['year'] <=> $b['year'];
            if ($yearComp !== 0) return $yearComp;
            return $a['invoice_no'] <=> $b['invoice_no'];
        });

        // Build PAID sub-groups (type+year) inside each invoice group.
        // Multi-year licenses (> 12 months) are split into per-year segments for display,
        // so each yearly section mirrors the quotation's "Nth Year Subscription" breakdown.
        foreach ($invoiceGroups as &$group) {
            $group['products'] = $this->dedupeProducts($group['products']);

            $subGroups = [];
            foreach ($group['products'] as $product) {
                $startDate = $product['start_date'] ? \Carbon\Carbon::parse($product['start_date']) : null;
                $endDate = $product['end_date'] ? \Carbon\Carbon::parse($product['end_date']) : null;
                $totalMonths = (int) ($product['month'] ?? 0);

                if (!$startDate || !$endDate || $totalMonths <= 12) {
                    $year = $startDate ? $startDate->format('Y') : 'Unknown';
                    $subKey = $product['type'] . ' - ' . $year;

                    if (!isset($subGroups[$subKey])) {
                        $subGroups[$subKey] = [
                            'label' => $subKey,
                            'type' => $product['type'],
                            'year' => $year,
                            'products' => [],
                        ];
                    }
                    $subGroups[$subKey]['products'][] = $product;
                    continue;
                }

                $remaining = $totalMonths;
                $segStart = $startDate->copy();
                while ($remaining > 0) {
                    $segMonths = min(12, $remaining);
                    $segEnd = $segStart->copy()->addMonths($segMonths)->subDay();
                    if ($segEnd->greaterThan($endDate)) {
                        $segEnd = $endDate->copy();
                    }

                    $year = $segStart->format('Y');
                    $subKey = $product['type'] . ' - ' . $year;

                    if (!isset($subGroups[$subKey])) {
                        $subGroups[$subKey] = [
                            'label' => $subKey,
                            'type' => $product['type'],
                            'year' => $year,
                            'products' => [],
                        ];
                    }

                    $subGroups[$subKey]['products'][] = array_merge($product, [
                        'start_date' => $segStart->format('Y-m-d'),
                        'end_date' => $segEnd->format('Y-m-d'),
                        'month' => $segMonths,
                    ]);

                    $remaining -= $segMonths;
                    $segStart = $segEnd->copy()->addDay();
                }
            }

            uasort($subGroups, fn ($a, $b) => $a['year'] <=> $b['year']);
            $group['sub_groups'] = $subGroups;
        }
        unset($group);

        return [
            'trials' => array_values($trialSubGroups),
            'invoices' => array_values($invoiceGroups),
        ];
    }

    protected function dedupeProducts(array $products): array
    {
        $seen = [];
        $unique = [];
        foreach ($products as $product) {
            $dedupeKey = $product['type'] . '|' . $product['license_type'] . '|' . $product['start_date'];
            if (isset($seen[$dedupeKey])) continue;
            $seen[$dedupeKey] = true;
            $unique[] = $product;
        }
        return $unique;
    }

    public function applyFilters(): void
    {
        $filtered = collect($this->licenseRecords);

        if ($this->filterType !== 'all') {
            $filtered = $filtered->where('type', $this->filterType);
        }

        if ($this->filterStatus !== 'all') {
            $today = now()->startOfDay();
            $filtered = $filtered->filter(function ($record) use ($today) {
                $start = \Carbon\Carbon::parse($record['start_date'])->startOfDay();
                $end = \Carbon\Carbon::parse($record['end_date'])->endOfDay();
                $isActive = $today->between($start, $end);

                return $this->filterStatus === 'active' ? $isActive : !$isActive;
            });
        }

        if ($this->filterProduct !== 'all') {
            $filtered = $filtered->where('license_type', $this->filterProduct);
        }

        if ($this->filterStartDate) {
            $filtered = $filtered->filter(fn ($record) => $record['start_date'] >= $this->filterStartDate);
        }

        if ($this->filterEndDate) {
            $filtered = $filtered->filter(fn ($record) => $record['end_date'] <= $this->filterEndDate);
        }

        $this->groupedLicenseRecords = $this->getGroupedLicenseRecordsFrom($filtered->values()->toArray());
    }

    public function resetLicenseFilters(): void
    {
        $this->filterType = 'all';
        $this->filterStatus = 'all';
        $this->filterProduct = 'all';
        $this->filterStartDate = null;
        $this->filterEndDate = null;

        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
    }

    // Buffer group edit properties
    public bool $isBufferGroupEdit = false;
    public ?string $editingBufferInvoiceNo = null;

    public function openEditModal(int $licenseNo): void
    {
        // Find the license record by 'no'
        $record = collect($this->licenseRecords)->firstWhere('no', $licenseNo);

        if ($record) {
            $this->isBufferGroupEdit = false;
            $this->editingBufferInvoiceNo = null;
            $this->editingLicenseNo = $licenseNo;
            $this->editingLicenseType = $record['license_type'];
            $this->editForm = [
                'total_user' => $record['user_limit'] ?? $record['total_user'],
                'month' => $record['month'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
                'status' => $this->calculateStatus($record['start_date'], $record['end_date']),
                'update_paid_periods' => false,
            ];
            $this->showEditModal = true;
        }
    }

    /**
     * Open edit modal for entire TRIAL/buffer group (edits all licenses in set)
     */
    public function openEditBufferGroupModal(string $invoiceNo): void
    {
        $groupRecords = collect($this->licenseRecords)
            ->where('sales_invoice_no', $invoiceNo)
            ->where('type', 'TRIAL');

        if ($groupRecords->isEmpty()) {
            Notification::make()->title('No TRIAL records found for this group.')->danger()->send();
            return;
        }

        $first = $groupRecords->first();
        $this->isBufferGroupEdit = true;
        $this->editingBufferInvoiceNo = $invoiceNo;
        $this->editingLicenseNo = null;
        $this->editingLicenseType = 'Buffer License Set (' . $groupRecords->count() . ' modules)';
        $this->editForm = [
            'total_user' => $first['user_limit'] ?? $first['total_user'],
            'month' => $first['month'],
            'start_date' => $first['start_date'],
            'end_date' => $first['end_date'],
            'status' => $this->calculateStatus($first['start_date'], $first['end_date']),
            'update_paid_periods' => false,
        ];
        $this->showEditModal = true;
    }

    public function updatedEditFormMonth($value): void
    {
        if (!empty($value) && !empty($this->editForm['start_date'])) {
            $startDate = \Carbon\Carbon::parse($this->editForm['start_date']);
            $this->editForm['end_date'] = $startDate->copy()->addMonths((int) $value)->subDay()->format('Y-m-d');
        }
    }

    public function updatedEditFormStartDate($value): void
    {
        if (!empty($value) && !empty($this->editForm['month'])) {
            $startDate = \Carbon\Carbon::parse($value);
            $this->editForm['end_date'] = $startDate->copy()->addMonths((int) $this->editForm['month'])->subDay()->format('Y-m-d');
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingLicenseNo = null;
        $this->editingLicenseType = '';
        $this->isBufferGroupEdit = false;
        $this->editingBufferInvoiceNo = null;
        $this->editForm = [
            'total_user' => '',
            'month' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
            'update_paid_periods' => false,
        ];
    }

    public function saveLicense(): void
    {
        // Validate the form
        $this->validate([
            'editForm.total_user' => 'required|integer|min:1',
            'editForm.month' => 'required|integer|min:1|max:36',
            'editForm.start_date' => 'required|date',
            'editForm.end_date' => 'required|date|after_or_equal:editForm.start_date',
            'editForm.status' => 'required|in:active,inactive',
        ]);

        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $apiMessage = '';
        $apiSyncFailed = false;
        $paidPeriodUpdateMessage = '';

        if ($this->isBufferGroupEdit) {
            // ===== BUFFER GROUP EDIT: Update all TRIAL licenses in the set =====
            $groupRecords = collect($this->licenseRecords)
                ->where('sales_invoice_no', $this->editingBufferInvoiceNo)
                ->where('type', 'TRIAL');

            if ($groupRecords->isEmpty()) {
                Notification::make()->title('No TRIAL records found.')->danger()->send();
                return;
            }

            // DB-based overlap check: ensure no other license (PAID or TRIAL) for the same type overlaps
            $bufferHrIds = $groupRecords
                ->pluck('hr_license_id')
                ->filter()
                ->values()
                ->toArray();

            $swId = ($this->companyData['software_handover'] ?? null)?->id;
            $newStart = $this->editForm['start_date'];
            $newEnd   = $this->editForm['end_date'];
            $oldBufferStart = $groupRecords->min('start_date');

            foreach ($groupRecords->groupBy('license_type') as $licType => $typeRecords) {
                $overlapDb = HrLicense::where('software_handover_id', $swId)
                    ->where('license_type', $licType)
                    ->where('start_date', '<=', $newEnd)
                    ->where('end_date', '>=', $newStart)
                    ->when(!empty($bufferHrIds), fn ($q) => $q->whereNotIn('id', $bufferHrIds))
                    ->first();

                // if ($overlapDb) {
                //     $overlapStart = Carbon::parse($overlapDb->start_date)->format('d/m/Y');
                //     $overlapEnd   = Carbon::parse($overlapDb->end_date)->format('d/m/Y');
                //     $overlapType  = strtoupper($overlapDb->type ?? '') === 'TRIAL' ? 'buffer/trial' : 'paid';

                //     Notification::make()
                //         ->title('Unable to update buffer license')
                //         ->body("The selected period overlaps with an existing {$overlapType} license for \"{$licType}\" ({$overlapStart} - {$overlapEnd}).")
                //         ->danger()
                //         ->send();
                //     return;
                // }
            }

            // Try API call for buffer set update - if fails, don't update DB
            if ($softwareHandover && $softwareHandover->hr_account_id && $softwareHandover->hr_company_id) {
                try {
                    $apiSuccess = $this->syncBufferGroupToApi($softwareHandover);
                    if (!$apiSuccess) {
                        Notification::make()->title('API sync failed')->body('Unable to update buffer license via API. No changes were made.')->danger()->send();
                        return;
                    }
                } catch (\Exception $e) {
                    Log::error('Buffer group API sync failed', ['error' => $e->getMessage()]);
                    Notification::make()->title('API sync failed')->body('Error: ' . $e->getMessage() . '. No changes were made.')->danger()->send();
                    return;
                }
            }

            // Persist all TRIAL licenses in group to DB and update in-memory (only reached if API succeeded)
            foreach ($this->licenseRecords as $index => $rec) {
                if (($rec['sales_invoice_no'] ?? $rec['invoice_no']) === $this->editingBufferInvoiceNo && $rec['type'] === 'TRIAL') {
                    // Update in-memory
                    $this->licenseRecords[$index]['total_user'] = (int) $this->editForm['total_user'];
                    $this->licenseRecords[$index]['month'] = (int) $this->editForm['month'];
                    $this->licenseRecords[$index]['start_date'] = $this->editForm['start_date'];
                    $this->licenseRecords[$index]['end_date'] = $this->editForm['end_date'];
                    $this->licenseRecords[$index]['status'] = $this->editForm['status'];

                    // Persist to DB
                    $hrLicenseId = $rec['hr_license_id'] ?? null;
                    if ($hrLicenseId) {
                        $hrLicense = HrLicense::find($hrLicenseId);
                        if ($hrLicense) {
                            $hrLicense->update([
                                'total_user' => (int) $this->editForm['total_user'],
                                'user_limit' => (int) $this->editForm['total_user'],
                                'month' => (int) $this->editForm['month'],
                                'start_date' => $this->editForm['start_date'],
                                'end_date' => $this->editForm['end_date'],
                                'status' => $this->editForm['status'] === 'active' ? 'Enabled' : 'Disabled',
                            ]);
                        }
                    }
                }
            }

            $affectedPaidCount = (int) HrLicense::where('software_handover_id', $swId)
                ->where('type', 'PAID')
                ->count();

            if ($affectedPaidCount > 0 && !empty($oldBufferStart) && $oldBufferStart !== $newStart && !empty($this->editForm['update_paid_periods'])) {
                [$updatedPaidCount, $paidApiFailCount] = $this->updatePaidPeriodsByBufferDate(
                    $softwareHandover,
                    $oldBufferStart,
                    $newStart
                );

                $paidPeriodUpdateMessage = " | Paid periods updated: {$updatedPaidCount}";
                if ($paidApiFailCount > 0) {
                    $paidPeriodUpdateMessage .= " ({$paidApiFailCount} API sync failed)";
                    $apiSyncFailed = true;
                }
            }
        } else {
            // ===== INDIVIDUAL PAID LICENSE EDIT =====
            $record = collect($this->licenseRecords)->firstWhere('no', $this->editingLicenseNo);
            if (!$record) {
                Notification::make()->title('License record not found.')->danger()->send();
                return;
            }

            $hrLicenseId = $record['hr_license_id'] ?? null;

            // DB-based overlap check: any license (PAID or TRIAL) with same type and overlapping dates
            $swId = ($this->companyData['software_handover'] ?? null)?->id;
            if ($swId && !empty($record['license_type'])) {
                $newStart = $this->editForm['start_date'];
                $newEnd   = $this->editForm['end_date'];

                $overlapDb = HrLicense::where('software_handover_id', $swId)
                    ->where('license_type', $record['license_type'])
                    ->where('start_date', '<=', $newEnd)
                    ->where('end_date', '>=', $newStart)
                    ->when($hrLicenseId, fn ($q) => $q->where('id', '!=', $hrLicenseId))
                    ->first();

                if ($overlapDb) {
                    $overlapStart = Carbon::parse($overlapDb->start_date)->format('d/m/Y');
                    $overlapEnd   = Carbon::parse($overlapDb->end_date)->format('d/m/Y');
                    $overlapType  = strtoupper($overlapDb->type ?? '') === 'TRIAL' ? 'buffer/trial' : 'paid';

                    Notification::make()
                        ->title('Unable to update license')
                        ->body("The selected period overlaps with an existing {$overlapType} license ({$overlapStart} - {$overlapEnd}).")
                        ->danger()
                        ->send();
                    return;
                }
            }

            // Try API call for paid license update - if fails, don't update DB
            if ($softwareHandover && $softwareHandover->hr_account_id && $softwareHandover->hr_company_id && $hrLicenseId) {
                try {
                    $apiSuccess = $this->syncLicenseToApi($record, $softwareHandover);
                    if (!$apiSuccess) {
                        Notification::make()->title('API sync failed')->body('Unable to update license via API. No changes were made.')->danger()->send();
                        return;
                    }
                } catch (\Exception $e) {
                    Log::error('License API sync failed', ['error' => $e->getMessage(), 'hr_license_id' => $hrLicenseId]);
                    Notification::make()->title('API sync failed')->body('Error: ' . $e->getMessage() . '. No changes were made.')->danger()->send();
                    return;
                }
            }

            // Persist to HrLicense database (only reached if API succeeded)
            if ($hrLicenseId) {
                $hrLicense = HrLicense::find($hrLicenseId);
                if ($hrLicense) {
                    $hrLicense->update([
                        'total_user' => (int) $this->editForm['total_user'],
                        'user_limit' => (int) $this->editForm['total_user'],
                        'month' => (int) $this->editForm['month'],
                        'start_date' => $this->editForm['start_date'],
                        'end_date' => $this->editForm['end_date'],
                        'status' => $this->editForm['status'] === 'active' ? 'Enabled' : 'Disabled',
                    ]);
                }
            }

            // Update in-memory records
            foreach ($this->licenseRecords as $index => $rec) {
                if ($rec['no'] === $this->editingLicenseNo) {
                    $this->licenseRecords[$index]['total_user'] = (int) $this->editForm['total_user'];
                    $this->licenseRecords[$index]['month'] = (int) $this->editForm['month'];
                    $this->licenseRecords[$index]['start_date'] = $this->editForm['start_date'];
                    $this->licenseRecords[$index]['end_date'] = $this->editForm['end_date'];
                    $this->licenseRecords[$index]['status'] = $this->editForm['status'];
                    break;
                }
            }
        }

        // Reload from DB to ensure latest values are reflected immediately in UI
        $this->loadLicenseRecords();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
        $this->loadProductData();
        $this->maxPaidEndDate = collect($this->licenseRecords)
            ->where('type', 'PAID')
            ->max('end_date');

        // Close the modal
        $this->closeEditModal();

        // Dispatch notification based on API sync result
        if ($apiSyncFailed) {
            Notification::make()->title('License updated locally, but API sync failed.' . $paidPeriodUpdateMessage)->warning()->send();
            return;
        }

        Notification::make()->title('License updated successfully.' . $apiMessage . $paidPeriodUpdateMessage)->success()->send();
    }

    protected function updatePaidPeriodsByBufferDate($softwareHandover, string $oldBufferStart, string $newBufferStart): array
    {
        $swId = $softwareHandover?->id;
        if (!$swId) {
            return [0, 0];
        }

        $oldAnchor = Carbon::parse($oldBufferStart)->startOfDay();
        $newAnchor = Carbon::parse($newBufferStart)->startOfDay();

        $paidLicenses = HrLicense::where('software_handover_id', $swId)
            ->where('type', 'PAID')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->orderBy('start_date')
            ->get();

        if ($paidLicenses->isEmpty()) {
            return [0, 0];
        }

        $licenseService = app(HRV2LicenseService::class);
        $canSyncApi = $softwareHandover && $softwareHandover->hr_account_id && $softwareHandover->hr_company_id;

        $updatedCount = 0;
        $apiFailCount = 0;

        foreach ($paidLicenses as $license) {
            $licenseStart = Carbon::parse($license->start_date)->startOfDay();
            $licenseEnd = Carbon::parse($license->end_date)->endOfDay();

            $startOffsetDays = $oldAnchor->diffInDays($licenseStart, false);
            $durationDays = $licenseStart->diffInDays($licenseEnd) + 1;

            $newStartDate = $newAnchor->copy()->addDays($startOffsetDays)->format('Y-m-d');
            $newEndDate = $newAnchor->copy()->addDays($startOffsetDays + max(0, $durationDays - 1))->format('Y-m-d');

            $license->update([
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
            ]);

            $updatedCount++;

            if ($canSyncApi) {
                try {
                    $periodId = $license->period_id;

                    if (!$periodId) {
                        $periodId = $this->fetchAndStorePeriodId([
                            'hr_license_id' => $license->id,
                            'license_type' => $license->license_type,
                            'start_date' => $newStartDate,
                            'type' => $license->type,
                            'period_id' => $license->period_id,
                        ], (int) $softwareHandover->hr_account_id, (int) $softwareHandover->hr_company_id, $licenseService);
                    }

                    if ($periodId) {
                        $apiResult = $licenseService->updatePaidLicense(
                            (int) $softwareHandover->hr_account_id,
                            (int) $softwareHandover->hr_company_id,
                            (int) $periodId,
                            $newStartDate,
                            $newEndDate,
                            (int) ($license->total_user ?? $license->user_limit ?? 0)
                        );

                        if (!($apiResult['success'] ?? false)) {
                            $apiFailCount++;
                        }
                    } else {
                        $apiFailCount++;
                    }
                } catch (\Exception $e) {
                    $apiFailCount++;
                    Log::error('Failed syncing paid period update to API', [
                        'hr_license_id' => $license->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [$updatedCount, $apiFailCount];
    }

    /**
     * Sync buffer group update to the external CRM API directly
     */
    protected function syncBufferGroupToApi($softwareHandover): bool
    {
        $accountId = $softwareHandover->hr_account_id;
        $companyId = $softwareHandover->hr_company_id;
        $bufferMonths = (int) $this->editForm['month'];
        $seatLimit = (int) $this->editForm['total_user'];

        // Get the TRIAL records being edited from in-memory licenseRecords
        $editingTrialRecords = collect($this->licenseRecords)
            ->where('sales_invoice_no', $this->editingBufferInvoiceNo)
            ->where('type', 'TRIAL');

        // Collect all unique license_set_ids from the records being edited
        $licenseSetIds = $editingTrialRecords
            ->pluck('license_set_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Fallback: resolve from certificate/DB if not in memory
        if (empty($licenseSetIds)) {
            $swIds = $editingTrialRecords->pluck('software_handover_id')->filter()->unique()->toArray();
            foreach ($swIds as $swId) {
                $setId = $this->resolveBufferLicenseSetId((int) $swId);
                if ($setId) {
                    $licenseSetIds[] = $setId;
                }
            }
            $licenseSetIds = array_unique($licenseSetIds);
        }

        if (empty($licenseSetIds)) {
            Log::warning('No buffer license set ID found for update', [
                'editing_invoice_no' => $this->editingBufferInvoiceNo,
            ]);
            return false;
        }

        $crmService = app(\App\Services\HRV2LicenseService::class);
        $allSuccess = true;

        // Update each license set separately
        foreach ($licenseSetIds as $licenseSetId) {
            // Get all TRIAL licenses in this set (includes modules from all handovers)
            $setLicenses = HrLicense::where('license_set_id', $licenseSetId)
                ->where('type', 'TRIAL')
                ->get();

            $seatLimits = [];
            $applications = [];
            foreach ($setLicenses as $license) {
                $appName = $this->extractAppName($license->license_type);
                if ($appName) {
                    $applications[] = $appName;
                    $seatLimits[$appName] = $seatLimit;
                }
            }

            $applications = array_values(array_unique($applications));

            $licenseData = [
                'startDate' => $this->editForm['start_date'],
                'endDate' => $this->editForm['end_date'],
                'notes' => "Buffer License ({$bufferMonths} month(s)) - Updated",
            ];

            if (!empty($applications)) {
                $licenseData['applications'] = $applications;
            }

            if (!empty($seatLimits)) {
                $licenseData['seatLimits'] = $seatLimits;
            }

            Log::info('Updating buffer license set via API', [
                'license_set_id' => $licenseSetId,
                'applications' => $applications,
                'license_data' => $licenseData,
            ]);

            $result = $crmService->updateBufferLicense($accountId, $companyId, (int) $licenseSetId, $licenseData);

            if (!($result['success'] ?? false)) {
                Log::error('Failed to update buffer license set', [
                    'license_set_id' => $licenseSetId,
                    'error' => $result['error'] ?? 'Unknown',
                ]);
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    protected function resolveBufferLicenseSetId(int $softwareHandoverId): ?string
    {
        // 1) Preferred source: certificate
        $certificate = \App\Models\LicenseCertificate::where('software_handover_id', $softwareHandoverId)->first();
        if (!empty($certificate?->buffer_license_set_id)) {
            return (string) $certificate->buffer_license_set_id;
        }

        // 2) Fallback: any persisted TRIAL hr_license with license_set_id
        $trialFromDb = HrLicense::where('software_handover_id', $softwareHandoverId)
            ->where('type', 'TRIAL')
            ->whereNotNull('license_set_id')
            ->where('license_set_id', '!=', '')
            ->value('license_set_id');

        if (!empty($trialFromDb)) {
            Log::info('Resolved buffer license set ID from TRIAL hr_licenses', [
                'software_handover_id' => $softwareHandoverId,
                'license_set_id' => $trialFromDb,
            ]);
            return (string) $trialFromDb;
        }

        // 3) Fallback: in-memory records loaded for this tab
        $trialFromMemory = collect($this->licenseRecords)
            ->first(fn ($r) => ($r['type'] ?? null) === 'TRIAL' && !empty($r['license_set_id']));

        if ($trialFromMemory && !empty($trialFromMemory['license_set_id'])) {
            Log::info('Resolved buffer license set ID from in-memory TRIAL records', [
                'software_handover_id' => $softwareHandoverId,
                'license_set_id' => $trialFromMemory['license_set_id'],
            ]);
            return (string) $trialFromMemory['license_set_id'];
        }

        return null;
    }

    /**
    * Sync a paid license update to the external CRM API via HRV2LicenseService
     */
    protected function syncLicenseToApi(array $record, $softwareHandover): bool
    {
        $accountId = $softwareHandover->hr_account_id;
        $companyId = $softwareHandover->hr_company_id;
        $licenseService = app(HRV2LicenseService::class);

        if ($record['type'] === 'PAID') {
            $periodId = $record['period_id'] ?? null;

            // If no period_id stored, try to fetch from API and backfill
            if (!$periodId) {
                $periodId = $this->fetchAndStorePeriodId($record, $accountId, $companyId, $licenseService);
            }

            if (!$periodId) {
                Log::warning('Cannot update paid license - no period_id available', [
                    'hr_license_id' => $record['hr_license_id'] ?? null,
                ]);
                return false;
            }

            $result = $licenseService->updatePaidLicense(
                $accountId,
                $companyId,
                (int) $periodId,
                $this->editForm['start_date'],
                $this->editForm['end_date'],
                (int) $this->editForm['total_user']
            );

            return $result['success'] ?? false;
        }

        return false;
    }

    /**
     * Fetch period_id from API for a paid license and store it in the DB
     */
    protected function fetchAndStorePeriodId(array $record, int $accountId, int $companyId, HRV2LicenseService $licenseService): ?string
    {
        try {
            $apiResult = $licenseService->getCompanyLicenses($accountId, $companyId);

            if (!($apiResult['success'] ?? false) || empty($apiResult['data'])) {
                Log::warning('Failed to fetch company licenses for period_id lookup', [
                    'hr_license_id' => $record['hr_license_id'] ?? null,
                ]);
                return null;
            }

            $appName = $this->extractAppName($record['license_type']);
            if (!$appName) {
                return null;
            }

            // Search through API license data to find matching paid license
            $licenses = $apiResult['data']['licenses'] ?? $apiResult['data'] ?? [];

            foreach ($licenses as $license) {
                $apiApp = $license['application'] ?? $license['app'] ?? $license['module'] ?? null;
                $apiType = $license['type'] ?? $license['licenseType'] ?? null;
                $apiPeriodId = $license['periodId'] ?? $license['period_id'] ?? null;

                if (!$apiPeriodId) continue;

                // Match by application name (case-insensitive)
                if ($apiApp && stripos($apiApp, $appName) !== false) {
                    // Also try to match by date range if available
                    $apiStart = $license['startDate'] ?? $license['start_date'] ?? null;
                    $apiEnd = $license['endDate'] ?? $license['end_date'] ?? null;

                    $dateMatch = true;
                    if ($apiStart && $record['start_date']) {
                        $dateMatch = Carbon::parse($apiStart)->format('Y-m-d') === $record['start_date'];
                    }

                    if ($dateMatch) {
                        // Store the period_id in DB for future use
                        $hrLicenseId = $record['hr_license_id'] ?? null;
                        if ($hrLicenseId) {
                            HrLicense::where('id', $hrLicenseId)->update(['period_id' => $apiPeriodId]);
                            Log::info('Backfilled period_id from API', [
                                'hr_license_id' => $hrLicenseId,
                                'period_id' => $apiPeriodId,
                                'app' => $appName,
                            ]);
                        }

                        return (string) $apiPeriodId;
                    }
                }
            }

            // Fallback: try matching without date (just app name)
            foreach ($licenses as $license) {
                $apiApp = $license['application'] ?? $license['app'] ?? $license['module'] ?? null;
                $apiPeriodId = $license['periodId'] ?? $license['period_id'] ?? null;

                if ($apiPeriodId && $apiApp && stripos($apiApp, $appName) !== false) {
                    $hrLicenseId = $record['hr_license_id'] ?? null;
                    if ($hrLicenseId) {
                        HrLicense::where('id', $hrLicenseId)->update(['period_id' => $apiPeriodId]);
                        Log::info('Backfilled period_id from API (fallback match)', [
                            'hr_license_id' => $hrLicenseId,
                            'period_id' => $apiPeriodId,
                            'app' => $appName,
                        ]);
                    }
                    return (string) $apiPeriodId;
                }
            }

            Log::warning('Could not find matching period_id from API', [
                'hr_license_id' => $record['hr_license_id'] ?? null,
                'app_name' => $appName,
                'api_licenses_count' => count($licenses),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch period_id from API', [
                'error' => $e->getMessage(),
                'hr_license_id' => $record['hr_license_id'] ?? null,
            ]);
            return null;
        }
    }

    /**
     * Extract application name from license_type string (e.g., "TimeTec Attendance (50 User License)" → "Attendance")
     */
    protected function extractAppName(string $licenseType): ?string
    {
        $appNames = ['Attendance', 'Leave', 'Claim', 'Payroll', 'Appraisal', 'Hire', 'Access', 'PowerBI'];

        foreach ($appNames as $app) {
            if (stripos($licenseType, $app) !== false) {
                return $app;
            }
        }

        // Fallback: check for TA
        if (stripos($licenseType, ' TA') !== false || stripos($licenseType, ' ta ') !== false) {
            return 'Attendance';
        }

        return null;
    }

    protected function calculateStatus(string $startDate, string $endDate): string
    {
        $today = now()->startOfDay();
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        return $today->between($start, $end) ? 'active' : 'inactive';
    }

    protected function findOverlappingPaidLicense(array $targetRecord, string $startDate, string $endDate): ?array
    {
        $targetNo = $targetRecord['no'] ?? null;
        $targetLicenseType = strtolower(trim($targetRecord['license_type'] ?? ''));
        $targetInvoiceNo = $targetRecord['invoice_no'] ?? null;
        $targetType = $targetRecord['type'] ?? null;

        if ($targetLicenseType === '') {
            return null;
        }

        foreach ($this->licenseRecords as $record) {
            $recordType = $record['type'] ?? null;

            // Check against both PAID and TRIAL/buffer licenses
            if (!in_array($recordType, ['PAID', 'TRIAL'])) {
                continue;
            }

            // Skip the record being edited
            if (($record['no'] ?? null) === $targetNo) {
                continue;
            }

            // When editing a TRIAL (buffer) group, skip all other records in the same buffer invoice
            if ($targetType === 'TRIAL' && $recordType === 'TRIAL' && ($record['invoice_no'] ?? null) === $targetInvoiceNo) {
                continue;
            }

            if (strtolower(trim($record['license_type'] ?? '')) !== $targetLicenseType) {
                continue;
            }

            if (empty($record['start_date']) || empty($record['end_date'])) {
                continue;
            }

            if ($this->dateRangesOverlap($startDate, $endDate, $record['start_date'], $record['end_date'])) {
                return $record;
            }
        }

        return null;
    }

    protected function dateRangesOverlap(
        string $firstStartDate,
        string $firstEndDate,
        string $secondStartDate,
        string $secondEndDate
    ): bool {
        $firstStart = Carbon::parse($firstStartDate)->startOfDay();
        $firstEnd = Carbon::parse($firstEndDate)->endOfDay();
        $secondStart = Carbon::parse($secondStartDate)->startOfDay();
        $secondEnd = Carbon::parse($secondEndDate)->endOfDay();

        return $firstStart->lte($secondEnd) && $secondStart->lte($firstEnd);
    }

    // Selection mode methods
    public function enterSelectionMode(): void
    {
        $this->isSelectionMode = true;
        $this->selectedLicenseNos = [];
    }

    public function exitSelectionMode(): void
    {
        $this->isSelectionMode = false;
        $this->selectedLicenseNos = [];
    }

    public function toggleLicenseSelection(int $licenseNo): void
    {
        if (in_array($licenseNo, $this->selectedLicenseNos)) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, [$licenseNo]));
        } else {
            $this->selectedLicenseNos[] = $licenseNo;
        }
    }

    public function toggleSelectAll(): void
    {
        $allNos = collect($this->licenseRecords)
            ->where('type', '!=', 'TRIAL')
            ->pluck('no')
            ->toArray();
        if (count($this->selectedLicenseNos) === count($allNos)) {
            $this->selectedLicenseNos = [];
        } else {
            $this->selectedLicenseNos = $allNos;
        }
    }

    public function toggleGroupSelection(string $invoiceNo): void
    {
        $groupNos = collect($this->licenseRecords)
            ->where('sales_invoice_no', $invoiceNo)
            ->where('type', '!=', 'TRIAL')
            ->pluck('no')
            ->toArray();

        $allSelected = count(array_intersect($this->selectedLicenseNos, $groupNos)) === count($groupNos);

        if ($allSelected) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, $groupNos));
        } else {
            $this->selectedLicenseNos = array_values(array_unique(array_merge($this->selectedLicenseNos, $groupNos)));
        }
    }

    public function toggleSubGroupSelection(string $invoiceNo, string $type, string $year): void
    {
        if ($type === 'TRIAL') {
            return;
        }

        $groupNos = collect($this->licenseRecords)
            ->where('sales_invoice_no', $invoiceNo)
            ->where('type', $type)
            ->filter(function ($r) use ($year) {
                $startDate = $r['start_date'] ? \Carbon\Carbon::parse($r['start_date']) : null;
                $recordYear = $startDate ? $startDate->format('Y') : 'Unknown';
                return $recordYear === $year;
            })
            ->pluck('no')
            ->toArray();

        if (empty($groupNos)) {
            return;
        }

        $allSelected = count(array_intersect($this->selectedLicenseNos, $groupNos)) === count($groupNos);

        if ($allSelected) {
            $this->selectedLicenseNos = array_values(array_diff($this->selectedLicenseNos, $groupNos));
        } else {
            $this->selectedLicenseNos = array_values(array_unique(array_merge($this->selectedLicenseNos, $groupNos)));
        }
    }

    public function getSelectedLicenseDetails(): array
    {
        return collect($this->licenseRecords)
            ->whereIn('no', $this->selectedLicenseNos)
            ->map(function ($record) {
                $name = $record['license_type'];
                if (!empty($record['invoice_no'])) {
                    $name .= ' (' . $record['invoice_no'] . ')';
                }
                return [
                    'name' => $name,
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date'],
                ];
            })
            ->toArray();
    }

    public function openBulkEditModal(): void
    {
        // Validate selection
        if (empty($this->selectedLicenseNos)) {
            Notification::make()->title('Please select at least one license to edit.')->danger()->send();
            return;
        }

        // Reset form and checkboxes
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
        $this->showBulkEditModal = true;
    }

    public function closeBulkEditModal(): void
    {
        $this->showBulkEditModal = false;
        $this->bulkEditForm = [
            'total_user' => '',
            'start_date' => '',
            'end_date' => '',
            'status' => 'active',
        ];
        $this->bulkEditEnabled = [
            'total_user' => false,
            'start_date' => false,
            'end_date' => false,
            'status' => false,
        ];
    }

    public function saveBulkEdit(): void
    {
        // Check if at least one field is enabled
        $hasEnabledField = in_array(true, $this->bulkEditEnabled, true);
        if (!$hasEnabledField) {
            Notification::make()->title('Please select at least one field to update.')->danger()->send();
            return;
        }

        // Build validation rules only for enabled fields
        $rules = [];
        if ($this->bulkEditEnabled['total_user']) {
            $rules['bulkEditForm.total_user'] = 'required|integer|min:1';
        }
        if ($this->bulkEditEnabled['start_date']) {
            $rules['bulkEditForm.start_date'] = 'required|date';
        }
        if ($this->bulkEditEnabled['end_date']) {
            $rules['bulkEditForm.end_date'] = 'required|date';
            if ($this->bulkEditEnabled['start_date']) {
                $rules['bulkEditForm.end_date'] .= '|after_or_equal:bulkEditForm.start_date';
            }
        }
        if ($this->bulkEditEnabled['status']) {
            $rules['bulkEditForm.status'] = 'required|in:active,inactive';
        }

        if (!empty($rules)) {
            $this->validate($rules);
        }

        // Step 1: Call API first for all selected records, collect results
        $softwareHandover = $this->companyData['software_handover'] ?? null;
        $licenseService = app(HRV2LicenseService::class);
        $hasApi = $softwareHandover && $softwareHandover->hr_account_id && $softwareHandover->hr_company_id;

        if ($hasApi) {
            $accountId = $softwareHandover->hr_account_id;
            $companyId = $softwareHandover->hr_company_id;

            foreach ($this->licenseRecords as $index => $record) {
                if (!in_array($record['no'], $this->selectedLicenseNos)) continue;

                $hrLicenseId = $record['hr_license_id'] ?? null;
                if (!$hrLicenseId) continue;

                // Build API update payload
                $startDate = $this->bulkEditEnabled['start_date'] ? $this->bulkEditForm['start_date'] : $record['start_date'];
                $endDate = $this->bulkEditEnabled['end_date'] ? $this->bulkEditForm['end_date'] : $record['end_date'];
                if ($record['type'] === 'PAID') {
                    $periodId = $record['period_id'] ?? null;
                    if (!$periodId) {
                        $periodId = $this->fetchAndStorePeriodId($record, $accountId, $companyId, $licenseService);
                    }

                    if ($periodId) {
                        try {
                            $apiPayload = ['startDate' => $startDate, 'endDate' => $endDate];
                            if ($this->bulkEditEnabled['total_user']) {
                                $apiPayload['seatLimit'] = (int) $this->bulkEditForm['total_user'] ?: null;
                            }

                            $result = $licenseService->updatePaidApplicationLicense(
                                $accountId, $companyId, (int) $periodId,
                                $apiPayload
                            );

                            if (!($result['success'] ?? false)) {
                                Notification::make()->title('API sync failed')->body('Failed to update license (Period ID: ' . $periodId . '). No changes were made.')->danger()->send();
                                return;
                            }
                        } catch (\Exception $e) {
                            Log::error('Bulk edit API sync failed', ['hr_license_id' => $hrLicenseId, 'error' => $e->getMessage()]);
                            Notification::make()->title('API sync failed')->body('Error: ' . $e->getMessage() . '. No changes were made.')->danger()->send();
                            return;
                        }
                    }
                } elseif ($record['type'] === 'TRIAL') {
                    // Buffer licenses use the set-level API, handled separately
                }
            }
        }

        // Step 2: API succeeded (or no API) - now update DB and in-memory
        $updatedCount = 0;

        foreach ($this->licenseRecords as $index => $record) {
            if (!in_array($record['no'], $this->selectedLicenseNos)) continue;

            $dbUpdate = [];

            if ($this->bulkEditEnabled['total_user']) {
                $this->licenseRecords[$index]['total_user'] = (int) $this->bulkEditForm['total_user'];
                $dbUpdate['total_user'] = (int) $this->bulkEditForm['total_user'];
                $dbUpdate['user_limit'] = (int) $this->bulkEditForm['total_user'];
            }
            if ($this->bulkEditEnabled['start_date']) {
                $this->licenseRecords[$index]['start_date'] = $this->bulkEditForm['start_date'];
                $dbUpdate['start_date'] = $this->bulkEditForm['start_date'];
            }
            if ($this->bulkEditEnabled['end_date']) {
                $this->licenseRecords[$index]['end_date'] = $this->bulkEditForm['end_date'];
                $dbUpdate['end_date'] = $this->bulkEditForm['end_date'];
            }
            if ($this->bulkEditEnabled['status']) {
                $this->licenseRecords[$index]['status'] = $this->bulkEditForm['status'];
                $dbUpdate['status'] = $this->bulkEditForm['status'] === 'active' ? 'Enabled' : 'Disabled';
            }

            $hrLicenseId = $record['hr_license_id'] ?? null;
            if ($hrLicenseId && !empty($dbUpdate)) {
                $hrLicense = HrLicense::find($hrLicenseId);
                if ($hrLicense) {
                    $hrLicense->update($dbUpdate);
                }
            }

            $updatedCount++;
        }

        // Reload from DB so table values and totals reflect updates immediately
        $this->loadLicenseRecords();
        $this->groupedLicenseRecords = $this->getGroupedLicenseRecords();
        $this->loadProductData();
        $this->maxPaidEndDate = collect($this->licenseRecords)
            ->where('type', 'PAID')
            ->max('end_date');

        // Close the modal and exit selection mode
        $this->closeBulkEditModal();
        $this->exitSelectionMode();

        Notification::make()
            ->title('Successfully updated ' . $updatedCount . ' license(s).')
            ->success()
            ->send();
    }

    public function showProformaInvoice(string $invoiceNo): void
    {
        $this->selectedInvoiceNo = $invoiceNo;
        $this->piData = [];
        $this->apiPiData = [];
        $this->piLoading = true;
        $this->piError = null;
        $this->showPiModal = true;

        // Get the software handover record
        $softwareHandover = $this->companyData['software_handover'] ?? null;

        if (!$softwareHandover) {
            $this->piLoading = false;
            $this->piError = 'Software handover record not found.';
            return;
        }

        // Get hr_account_id and hr_company_id for API call
        $accountId = $softwareHandover->hr_account_id ?? null;
        $companyId = $softwareHandover->hr_company_id ?? null;

        // Primary: Build PI from license records (includes all years)
        $this->buildPiFromLicenseRecords($invoiceNo);

        // Fallback: If no local license records matched, try API
        if (empty($this->apiPiData)) {
            if ($accountId && $companyId) {
                try {
                    $apiService = app(HRV2LicenseService::class);
                    $response = $apiService->getProformaInvoiceDetails($accountId, $companyId, $invoiceNo);

                    if ($response['success'] && !empty($response['data'])) {
                        $this->apiPiData = $response['data'];

                        // Store PI data in session for the full page view
                        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
                        session()->put($sessionKey, $this->apiPiData);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch PI from API: ' . $e->getMessage());
                }
            }
        }

        // Fallback 2: Local quotations
        if (empty($this->piData) && empty($this->apiPiData)) {
            $this->loadLocalQuotations($softwareHandover, $invoiceNo);
        }

        // Store PI data in session for the full page view
        if (!empty($this->apiPiData)) {
            $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
            session()->put($sessionKey, $this->apiPiData);
        }

        $this->piLoading = false;
    }

    protected function buildPiFromLicenseRecords(string $invoiceNo): void
    {
        // Find license records matching this sales_invoice_no
        $matchingLicenses = collect($this->licenseRecords)
            ->where('sales_invoice_no', $invoiceNo)
            ->values()
            ->toArray();

        if (empty($matchingLicenses)) {
            return;
        }

        // Get company info
        $companyName = $this->companyData['company_name'] ?? '-';
        $companyEmail = $this->companyData['email'] ?? '-';
        $companyAddress = $this->companyData['address'] ?? '-';

        // Build items array
        $items = [];
        $subtotal = 0;

        foreach ($matchingLicenses as $license) {
            $qty = $license['total_user'] ?? $license['unit'] ?? 0;
            $month = $license['month'] ?? 12;
            $startDate = $license['start_date'] ?? '';
            $endDate = $license['end_date'] ?? '';

            // Calculate price per user per month (approximate)
            // Typical pricing: TA=2.00, Leave=1.00, Claim=1.00, Payroll=1.00, Profile=0.50
            $pricePerUser = $this->getLicensePrice($license['license_type'] ?? '');
            $amount = $qty * $pricePerUser * $month;
            $subtotal += $amount;

            $period = '';
            if ($startDate && $endDate) {
                $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
            }

            $items[] = [
                'year' => (int) date('Y', strtotime($startDate)),
                'description' => ($license['license_type'] ?? 'TimeTec License') . ' (1 User License)',
                'period' => $period,
                'qty' => $qty,
                'price' => $pricePerUser,
                'billing_cycle' => $month,
                'discount' => '0%',
                'amount' => $amount,
            ];
        }

        // Calculate totals
        $discount = 0;
        $sstRate = 8;
        $sst = $subtotal * ($sstRate / 100);
        $totalAmount = $subtotal + $sst;

        // Get date from first license
        $invoiceDate = $matchingLicenses[0]['start_date'] ?? date('Y-m-d');

        // Build API-like PI data structure
        $this->apiPiData = [
            'invoice_no' => $invoiceNo,
            'date' => date('d-m-Y', strtotime($invoiceDate)),
            'status' => strtoupper($matchingLicenses[0]['type'] ?? 'PAID') === 'PAID' ? 'PAID' : 'Pending',
            'trx_rate' => '1',
            'currency' => 'MYR',
            'bill_to' => [
                'company_name' => $companyName,
                'email' => $companyEmail,
                'registration_no' => '',
                'address' => $companyAddress,
            ],
            'items' => $items,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'sst_rate' => $sstRate,
            'sst' => $sst,
            'total_amount' => $totalAmount,
            'amount_due' => $totalAmount,
        ];

        // Store PI data in session for the full page view
        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $invoiceNo;
        session()->put($sessionKey, $this->apiPiData);
    }

    protected function getLicensePrice(string $licenseType): float
    {
        // Standard pricing per user per month
        $pricing = [
            'TimeTec TA' => 2.00,
            'TimeTec Attendance' => 2.00,
            'TimeTec Leave' => 1.00,
            'TimeTec Claim' => 1.00,
            'TimeTec Payroll' => 1.00,
            'TimeTec Profile' => 0.50,
            'TimeTec Hire' => 1.00,
        ];

        foreach ($pricing as $key => $price) {
            if (stripos($licenseType, $key) !== false) {
                return $price;
            }
        }

        return 1.00; // Default price
    }

    protected function loadLocalQuotations($softwareHandover, string $invoiceNo): void
    {
        $quotationIds = [];

        // Helper function to extract quotation IDs from JSON data with flexible key names
        $extractQuotationIds = function ($data, $targetInvoiceNo) {
            $ids = [];
            if (!is_array($data)) {
                return $ids;
            }

            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $itemInvoiceNo = $item['invoice_number']
                    ?? $item['invoice_no']
                    ?? $item['invoiceNo']
                    ?? $item['inv_no']
                    ?? $item['tt_invoice_number']
                    ?? null;

                $quotationId = $item['quotation_id']
                    ?? $item['quotationId']
                    ?? $item['pi_id']
                    ?? $item['id']
                    ?? null;

                if ($itemInvoiceNo === $targetInvoiceNo && $quotationId) {
                    $ids[] = $quotationId;
                }
            }

            return $ids;
        };

        // Search through type_1, type_2, type_3 PI invoice data
        $jsonFields = ['type_1_pi_invoice_data', 'type_2_pi_invoice_data', 'type_3_pi_invoice_data'];

        foreach ($jsonFields as $field) {
            $data = $softwareHandover->$field;
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (is_array($data)) {
                $foundIds = $extractQuotationIds($data, $invoiceNo);
                $quotationIds = array_merge($quotationIds, $foundIds);
            }
        }

        // Include quotations from proforma_invoice_product and proforma_invoice_hrdf
        $productPiIds = is_string($softwareHandover->proforma_invoice_product)
            ? json_decode($softwareHandover->proforma_invoice_product, true)
            : $softwareHandover->proforma_invoice_product;

        if (is_array($productPiIds)) {
            $productPiIds = array_filter($productPiIds, fn($id) => is_numeric($id));
            $quotationIds = array_merge($quotationIds, $productPiIds);
        }

        $hrdfPiIds = is_string($softwareHandover->proforma_invoice_hrdf)
            ? json_decode($softwareHandover->proforma_invoice_hrdf, true)
            : $softwareHandover->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPiIds = array_filter($hrdfPiIds, fn($id) => is_numeric($id));
            $quotationIds = array_merge($quotationIds, $hrdfPiIds);
        }

        // If no quotations found, search by lead_id
        if (empty($quotationIds)) {
            $leadId = $softwareHandover->lead_id ?? null;
            if ($leadId) {
                $quotationIds = Quotation::where('lead_id', $leadId)
                    ->pluck('id')
                    ->toArray();
            }
        }

        $quotationIds = array_unique(array_filter($quotationIds));

        if (!empty($quotationIds)) {
            $quotations = Quotation::with(['items', 'lead.companyDetail', 'sales_person'])
                ->whereIn('id', $quotationIds)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($quotations as $quotation) {
                $this->piData[] = [
                    'id' => $quotation->id,
                    'pi_reference_no' => $quotation->pi_reference_no ?? 'PI-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT),
                    'company_name' => $quotation->lead?->companyDetail?->company_name ?? '-',
                    'quotation_date' => $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '-',
                    'currency' => $quotation->currency ?? 'MYR',
                    'salesperson' => $quotation->sales_person?->name ?? '-',
                    'total_amount' => $quotation->items?->sum('amount') ?? 0,
                    'items' => $quotation->items?->map(function ($item) {
                        return [
                            'description' => $item->description ?? '-',
                            'quantity' => $item->quantity ?? 0,
                            'unit_price' => $item->unit_price ?? 0,
                            'amount' => $item->amount ?? 0,
                        ];
                    })->toArray() ?? [],
                ];
            }
        }
    }

    public function closePiModal(): void
    {
        $this->showPiModal = false;
        $this->selectedInvoiceNo = null;
        $this->piData = [];
        $this->apiPiData = [];
        $this->piLoading = false;
        $this->piError = null;
    }

    public function getPiViewUrl(): string
    {
        if (!$this->softwareHandoverId || !$this->selectedInvoiceNo) {
            return '#';
        }

        // Store the PI data in session for the controller to retrieve
        $sessionKey = 'pi_data_' . $this->softwareHandoverId . '_' . $this->selectedInvoiceNo;
        session()->put($sessionKey, $this->apiPiData);

        return route('pdf.license-proforma-invoice', [
            'softwareHandover' => $this->softwareHandoverId,
            'invoiceNo' => $this->selectedInvoiceNo,
        ]);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-products-tab');
    }
}
