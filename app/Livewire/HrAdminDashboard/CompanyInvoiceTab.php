<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoice;
use App\Services\HRV2LicenseService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CompanyInvoiceTab extends Component
{
    public ?int $softwareHandoverId = null;
    public array $companyData = [];

    // State properties
    public bool $isLoading = true;
    public bool $hasError = false;
    public string $errorMessage = '';
    public bool $isLocalData = false;

    // Data properties
    public array $invoices = [];
    public int $totalRecords = 0;

    // Search & Pagination
    public string $search = '';
    public int $perPage = 10;
    public int $currentPage = 1;

    public array $perPageOptions = [10, 25, 50];

    public function mount(?int $softwareHandoverId = null, array $companyData = [])
    {
        $this->softwareHandoverId = $softwareHandoverId;
        $this->companyData = $companyData;
        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';
        $this->isLocalData = false;

        try {
            $accountId = $this->companyData['hr_account_id'] ?? null;
            $companyId = $this->companyData['hr_company_id'] ?? null;

            if (!$accountId || !$companyId) {
                $this->loadInvoicesFromLocalData();
                return;
            }

            $crmService = app(HRV2LicenseService::class);

            $params = [
                'page' => $this->currentPage,
                'limit' => $this->perPage,
            ];

            if (!empty($this->search)) {
                $params['search'] = $this->search;
            }

            $response = $crmService->getCompanyInvoices($accountId, $companyId, $params);

            if ($response['success']) {
                $apiInvoices = $response['data']['invoices'] ?? [];
                // Merge with local hr_sales_invoices
                $localInvoices = $this->getHrSalesInvoices();
                $allInvoices = array_merge($localInvoices, $apiInvoices);
                // Sort by invoice_date descending
                usort($allInvoices, function ($a, $b) {
                    return strtotime($b['invoice_date'] ?? '1970-01-01') - strtotime($a['invoice_date'] ?? '1970-01-01');
                });
                $this->totalRecords = count($allInvoices);
                $offset = ($this->currentPage - 1) * $this->perPage;
                $this->invoices = array_slice($allInvoices, $offset, $this->perPage);
            } else {
                $status = $response['status'] ?? null;
                $context = [
                    'account_id' => $accountId,
                    'company_id' => $companyId,
                    'status' => $status,
                    'error' => $response['error'] ?? 'Unknown error',
                ];

                if ($status === 404) {
                    Log::warning('CRM API invoice endpoint unavailable, using local data', $context);
                } else {
                    Log::error('CRM API: Failed to fetch invoices, falling back to local data', $context);
                }

                $this->loadInvoicesFromLocalData();
                return;
            }
        } catch (\Exception $e) {
            Log::error('CRM API Exception: Failed to fetch invoices, falling back to local data', [
                'message' => $e->getMessage(),
            ]);
            $this->loadInvoicesFromLocalData();
            return;
        } finally {
            $this->isLoading = false;
        }
    }

    public function searchInvoices(): void
    {
        $this->currentPage = 1;
        $this->loadInvoices();
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
        $this->loadInvoices();
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
        $this->loadInvoices();
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadInvoices();
        }
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages()) {
            $this->currentPage++;
            $this->loadInvoices();
        }
    }

    public function totalPages(): int
    {
        return max(1, ceil($this->totalRecords / $this->perPage));
    }

    public function refreshInvoices(): void
    {
        $this->loadInvoices();
    }

    public function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'paid' => 'text-green-600',
            'cancel', 'cancelled' => 'text-red-600',
            'pending' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }

    public function formatCurrency(float $amount, string $currency = 'MYR'): string
    {
        return number_format($amount, 2) . ' ' . $currency;
    }

    protected function loadInvoicesFromLocalData(): void
    {
        $this->isLocalData = true;

        try {
            $allInvoices = $this->getHrSalesInvoices();

            // Apply search filter
            if (!empty($this->search)) {
                $searchLower = strtolower($this->search);
                $allInvoices = array_filter($allInvoices, function ($invoice) use ($searchLower) {
                    return str_contains(strtolower($invoice['invoice_no'] ?? ''), $searchLower)
                        || str_contains(strtolower($invoice['description'] ?? ''), $searchLower);
                });
                $allInvoices = array_values($allInvoices);
            }

            // Sort by invoice_date descending
            usort($allInvoices, function ($a, $b) {
                return strtotime($b['invoice_date'] ?? '1970-01-01') - strtotime($a['invoice_date'] ?? '1970-01-01');
            });

            $this->totalRecords = count($allInvoices);

            // Apply pagination
            $offset = ($this->currentPage - 1) * $this->perPage;
            $this->invoices = array_slice($allInvoices, $offset, $this->perPage);

            if (empty($this->invoices)) {
                $this->hasError = true;
                $this->errorMessage = 'No invoice records found for this company.';
            }
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load local invoice data: ' . $e->getMessage();
            Log::error('Failed to load local invoices', [
                'handover_id' => $this->softwareHandoverId,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getHrSalesInvoices(): array
    {
        // Use all handover IDs for this customer
        $handoverIds = $this->companyData['all_handover_ids'] ?? [];

        if (empty($handoverIds) && $this->softwareHandoverId) {
            $handoverIds = [$this->softwareHandoverId];
        }

        if (empty($handoverIds)) {
            return [];
        }

        $formattedHandoverIds = $this->companyData['all_formatted_handover_ids'] ?? [];

        $records = HrSalesInvoice::with('items')
            ->where(function ($q) use ($handoverIds, $formattedHandoverIds) {
                $q->whereIn('software_handover_id', $handoverIds);
                if (!empty($formattedHandoverIds)) {
                    $q->orWhereIn('handover_id', $formattedHandoverIds);
                }
            })
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Preload official receipts for these invoices
        $invoiceNos = $records->pluck('invoice_no')->filter()->toArray();
        $receiptMap = \App\Models\HrOfficialReceipt::whereIn('invoice_no', $invoiceNos)
            ->get()
            ->keyBy('invoice_no');

        return $records->map(function ($record) use ($receiptMap) {
            $licenseTypes = $record->items
                ->pluck('license_type')
                ->filter()
                ->unique()
                ->implode(', ');

            $receipt = $receiptMap[$record->invoice_no] ?? null;

            return [
                'id' => $record->id,
                'invoice_no' => $record->invoice_no,
                'invoice_date' => $record->invoice_date?->format('Y-m-d'),
                'due_date' => null,
                'description' => $licenseTypes ?: 'TimeTec License',
                'total' => (float) ($record->invoice_amount ?? $record->sales_amount ?? 0),
                'currency' => $record->currency ?? 'MYR',
                'status' => ucfirst(strtolower($record->status ?? $record->payment_status ?? 'pending')),
                'or_no' => $receipt?->or_no,
                'or_id' => $receipt?->id,
            ];
        })->toArray();
    }

    public function viewInvoice(int $invoiceId): void
    {
        $this->redirect(
            url('/admin/view-sales-invoice?invoiceId=' . $invoiceId . '&softwareHandoverId=' . $this->softwareHandoverId . '&from=invoice'),
            navigate: false
        );
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.company-invoice-tab');
    }
}
