<?php

namespace App\Filament\Pages;

use App\Models\AdminRenewalLogs;
use App\Models\HrLicense;
use App\Models\HrOfficialReceipt;
use App\Models\HrSalesInvoice;
use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class HrV2RenewalDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'HR V2 Renewal';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.hr-v2-renewal-dashboard';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'HR License';

    public string $activeTab = 'dashboard';

    public $newStats;
    public $pendingConfirmationStats;
    public $pendingPaymentStats;
    public $completedRenewalStats;
    public $renewalForecastStats;

    // Dashboard tab data
    public $followUpTotalPC = 0;
    public $followUpTodayPC = 0;
    public $followUpOverduePC = 0;
    public $followUpFuturePC = 0;
    public $followUpAllPC = 0;
    public $followUpTotalPP = 0;
    public $followUpTodayPP = 0;
    public $followUpOverduePP = 0;
    public $followUpFuturePP = 0;
    public $followUpAllPP = 0;

    // Cache for license data keyed by software_handover_id
    protected array $licenseCache = [];

    public function mount(): void
    {
        $this->loadData();
        $this->loadDashboardStats();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function loadDashboardStats(): void
    {
        $base = fn ($q) => $q->where('hr_version', 2)->where('follow_up_counter', true)->where('mapping_status', 'completed_mapping');

        $this->followUpTodayPC = $this->getDashboardCount('pending_confirmation', 'today', $base);
        $this->followUpOverduePC = $this->getDashboardCount('pending_confirmation', 'overdue', $base);
        $this->followUpFuturePC = $this->getDashboardCount('pending_confirmation', 'upcoming', $base);
        $this->followUpAllPC = $this->getDashboardCount('pending_confirmation', 'all', $base);
        $this->followUpTotalPC = $this->followUpTodayPC + $this->followUpOverduePC;

        $this->followUpTodayPP = $this->getDashboardCount('pending_payment', 'today', $base);
        $this->followUpOverduePP = $this->getDashboardCount('pending_payment', 'overdue', $base);
        $this->followUpFuturePP = $this->getDashboardCount('pending_payment', 'upcoming', $base);
        $this->followUpAllPP = $this->getDashboardCount('pending_payment', 'all', $base);
        $this->followUpTotalPP = $this->followUpTodayPP + $this->followUpOverduePP;
    }

    private function getDashboardCount(string $progress, string $period, \Closure $base): int
    {
        $query = Renewal::where('renewal_progress', $progress);
        $base($query);

        match ($period) {
            'today' => $query->whereDate('follow_up_date', today()),
            'overdue' => $query->whereDate('follow_up_date', '<', today()),
            'upcoming' => $query->whereDate('follow_up_date', '>', today()),
            'all' => null,
        };

        return $query->count();
    }

    /**
     * Get aggregated license data for a renewal's software_handover_id (cached).
     */
    protected function getLicenseData($softwareHandoverId): ?object
    {
        if (!$softwareHandoverId) return null;

        if (array_key_exists($softwareHandoverId, $this->licenseCache)) {
            return $this->licenseCache[$softwareHandoverId];
        }

        $data = HrLicense::where('software_handover_id', $softwareHandoverId)
            ->where('type', 'PAID')
            ->where('status', 'Enabled')
            ->select([
                DB::raw('MIN(end_date) as earliest_expiry'),
                DB::raw('SUM(unit) as total_headcount'),
                DB::raw('COUNT(*) as license_count'),
                DB::raw("GROUP_CONCAT(DISTINCT license_type ORDER BY license_type SEPARATOR ', ') as modules"),
                DB::raw('ANY_VALUE(auto_renewal) as auto_renewal'),
                DB::raw('ANY_VALUE(invoice_no) as invoice_no'),
            ])
            ->first();

        $this->licenseCache[$softwareHandoverId] = $data;
        return $data;
    }

    // ─── Stats Methods ──────────────────────────────────────────────────

    protected function loadData(): void
    {
        $this->newStats = $this->getStatsByProgress('new');
        $this->pendingConfirmationStats = $this->getStatsByProgress('pending_confirmation');
        $this->pendingPaymentStats = $this->getStatsByProgress('pending_payment');
        $this->completedRenewalStats = $this->getStatsByProgress('completed_renewal');

        $this->renewalForecastStats = [
            'total_companies' => ($this->newStats['total_companies'] ?? 0) + ($this->pendingConfirmationStats['total_companies'] ?? 0),
            'total_amount' => ($this->newStats['total_amount'] ?? 0) + ($this->pendingConfirmationStats['total_amount'] ?? 0),
        ];
    }

    protected function getStatsByProgress(string $progress): array
    {
        try {
            $today = now()->format('Y-m-d');
            $threeMonths = now()->addMonths(3)->format('Y-m-d');

            $expiringSwIds = HrLicense::where('type', 'PAID')
                ->where('status', 'Enabled')
                ->whereBetween('end_date', [$today, $threeMonths])
                ->pluck('software_handover_id')
                ->unique()
                ->toArray();

            $renewals = Renewal::where('hr_version', 2)
                ->where('renewal_progress', $progress)
                ->whereNotNull('software_handover_id')
                ->whereIn('software_handover_id', $expiringSwIds)
                ->get();

            $totalCompanies = $renewals->count();

            $swIds = $renewals->pluck('software_handover_id')->filter()->toArray();
            $totalAmount = HrSalesInvoice::whereIn('software_handover_id', $swIds)
                ->sum('invoice_amount');

            return [
                'total_companies' => $totalCompanies,
                'total_amount' => $totalAmount,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching {$progress} stats (V2): " . $e->getMessage());
            return ['total_companies' => 0, 'total_amount' => 0];
        }
    }

    // ─── Table ──────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        $today = now()->format('Y-m-d');
        $threeMonths = now()->addMonths(3)->format('Y-m-d');

        // Get software_handover_ids with licenses expiring within next 3 months
        $expiringSwIds = HrLicense::where('type', 'PAID')
            ->where('status', 'Enabled')
            ->whereBetween('end_date', [$today, $threeMonths])
            ->pluck('software_handover_id')
            ->unique()
            ->toArray();

        return $table
            ->query(
                Renewal::query()
                    ->where('hr_version', 2)
                    ->whereNotIn('renewal_progress', ['terminated', 'completed_renewal'])
                    ->whereIn('software_handover_id', $expiringSwIds)
            )
            ->defaultSort('created_at', 'desc')
            ->poll('300s')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => strtoupper($state))
                    ->weight('bold')
                    ->wrap()
                    ->color('primary')
                    ->url(function (Renewal $record) {
                        if ($record->lead_id) {
                            return route('filament.admin.resources.leads.view', [
                                'record' => \App\Classes\Encryptor::encrypt($record->lead_id),
                            ]) . '?view=admin_renewal_v2';
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),

                TextColumn::make('earliest_expiry')
                    ->label('Expired License')
                    ->alignCenter()
                    ->getStateUsing(function (Renewal $record) {
                        $data = $this->getLicenseData($record->software_handover_id);
                        return $data?->earliest_expiry;
                    })
                    ->date('d M Y')
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $expiryDate = Carbon::parse($state);
                        $daysDiff = $expiryDate->diffInDays(now());
                        if ($expiryDate->isToday()) return 'danger';
                        if ($daysDiff <= 7) return 'warning';
                        if ($daysDiff <= 30) return 'info';
                        return 'gray';
                    }),

                TextColumn::make('renewal_progress')
                    ->label('Renewal Status')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'new' => 'New',
                        'pending_confirmation' => 'Pending Confirmation',
                        'pending_payment' => 'Pending Payment',
                        'completed_renewal' => 'Completed Payment',
                        default => ucfirst(str_replace('_', ' ', $state ?? '')),
                    })
                    ->badge()
                    ->alignLeft()
                    ->color(fn($state) => match ($state) {
                        'new' => 'info',
                        'pending_confirmation' => 'warning',
                        'pending_payment' => 'danger',
                        'completed_renewal' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('task_status')
                    ->label('Task')
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->html(),

                TextColumn::make('total_amount_display')
                    ->label('Amount')
                    ->alignRight()
                    ->getStateUsing(function (Renewal $record) {
                        if (!$record->software_handover_id) return '0.00';

                        $cacheKey = "hrv2_invoice_amount_{$record->software_handover_id}";
                        $totalAmount = Cache::remember($cacheKey, 300, function () use ($record) {
                            return HrSalesInvoice::where('software_handover_id', $record->software_handover_id)
                                ->sum('invoice_amount');
                        });

                        return number_format($totalAmount, 2);
                    }),

                TextColumn::make('follow_up_date')
                    ->label('Next Follow Up Date')
                    ->alignLeft()
                    ->formatStateUsing(function ($state, Renewal $record) {
                        if ($record->renewal_progress === 'new') return 'N/A';
                        if (!$state) return 'N/A';
                        return Carbon::parse($state)->format('d M Y');
                    }),

                TextColumn::make('days_status')
                    ->label('Days')
                    ->alignLeft()
                    ->getStateUsing(function (Renewal $record) {
                        if ($record->renewal_progress === 'new') return null;
                        if (!$record->follow_up_date) return null;
                        return $record->follow_up_date;
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $today = Carbon::now()->startOfDay();
                        $followUpDate = Carbon::parse($state)->startOfDay();
                        $days = (int) $today->diffInDays($followUpDate, false);
                        if ($days < 0) return abs($days) . ' day(s) overdue';
                        if ($days === 0) return 'Today';
                        return $days . ' day(s) left';
                    })
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        $today = Carbon::now()->startOfDay();
                        $followUpDate = Carbon::parse($state)->startOfDay();
                        $days = (int) $today->diffInDays($followUpDate, false);
                        return $days <= 0 ? 'danger' : 'success';
                    })
                    ->badge(),
            ])
            ->filters([
                Filter::make('expiry_range')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Expiry Date Range')
                            ->placeholder('Select expiry date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d');
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d');

                            $swIds = HrLicense::where('type', 'PAID')
                                ->where('status', 'Enabled')
                                ->whereBetween('end_date', [$startDate, $endDate])
                                ->pluck('software_handover_id')
                                ->unique()
                                ->toArray();

                            $query->whereIn('software_handover_id', $swIds);
                        }
                    }),

                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'TimeTec Attendance' => 'Attendance',
                        'TimeTec Leave' => 'Leave',
                        'TimeTec Claim' => 'Claim',
                        'TimeTec Payroll' => 'Payroll',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $swIds = HrLicense::where('type', 'PAID')
                                ->where('status', 'Enabled')
                                ->where('license_type', $data['value'])
                                ->pluck('software_handover_id')
                                ->unique()
                                ->toArray();

                            $query->whereIn('software_handover_id', $swIds);
                        }
                    }),

                SelectFilter::make('renewal_progress')
                    ->label('Renewal Status')
                    ->options(Renewal::getRenewalProgressOptions()),

                SelectFilter::make('auto_renewal')
                    ->label('Auto Renewal')
                    ->options([
                        'Enabled' => 'Enabled',
                        'Disabled' => 'Disabled',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $swIds = HrLicense::where('auto_renewal', $data['value'])
                                ->pluck('software_handover_id')
                                ->unique()
                                ->toArray();

                            $query->whereIn('software_handover_id', $swIds);
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_lead_details')
                        ->label('View Lead Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(function (Renewal $record) {
                            if ($record->lead_id) {
                                return route('filament.admin.resources.leads.view', [
                                    'record' => \App\Classes\Encryptor::encrypt($record->lead_id),
                                ]) . '?view=admin_renewal_v2';
                            }
                            return null;
                        })
                        ->openUrlInNewTab(),

                    Action::make('assign_to_me')
                        ->label('Assign to Me')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Renewal to Me')
                        ->modalDescription(fn (Renewal $record) => "Assign renewal for {$record->company_name} to yourself?")
                        ->modalSubmitActionLabel('Yes, Assign to Me')
                        ->visible(fn (Renewal $record) => $record->admin_renewal === null)
                        ->action(function (Renewal $record) {
                            $record->update(['admin_renewal' => auth()->user()->name]);
                            Notification::make()->success()->title('Assigned to you.')->send();
                        }),

                    Action::make('assign_to_admin')
                        ->label('Assign to Admin Renewal')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->form([
                            Select::make('admin_renewal')
                                ->label('Select Admin Renewal')
                                ->options(['Fatimah Nurnabilah' => 'Fatimah Nurnabilah'])
                                ->required(),
                        ])
                        ->modalHeading('Assign to Admin Renewal')
                        ->visible(fn (Renewal $record) => $record->admin_renewal === null)
                        ->action(function (Renewal $record, array $data) {
                            $record->update(['admin_renewal' => $data['admin_renewal']]);
                            Notification::make()->success()->title("Assigned to {$data['admin_renewal']}.")->send();
                        }),

                    Action::make('complete_task')
                        ->label('Complete Task')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Task')
                        ->visible(fn (Renewal $record) => $record->admin_renewal !== null && $record->renewal_progress === 'new' && !$record->task_status)
                        ->action(function (Renewal $record) {
                            $record->update(['task_status' => true]);
                            Notification::make()->success()->title('Task Completed')->send();
                        }),

                    Action::make('completed_follow_up')
                        ->label('Completed Follow Up')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Follow Up as Completed')
                        ->modalDescription('This will change the status to "Pending Confirmation".')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->visible(fn (Renewal $record) => $record->admin_renewal !== null && $record->renewal_progress === 'new' && $record->task_status)
                        ->action(function (Renewal $record) {
                            $history = is_array($record->progress_history) ? $record->progress_history : [];
                            $history[] = [
                                'timestamp' => now(), 'action' => 'follow_up_completed',
                                'previous_status' => $record->renewal_progress, 'new_status' => 'pending_confirmation',
                                'performed_by' => auth()->user()->name, 'performed_by_id' => auth()->user()->id,
                            ];
                            $record->update(['renewal_progress' => 'pending_confirmation', 'progress_history' => $history]);
                            Notification::make()->success()->title('Status updated to Pending Confirmation.')->send();
                        }),

                    Action::make('request_invoice')
                        ->label('Request Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->modalHeading('Request Invoice')
                        ->modalDescription('Select the quotation to create an HR Sales Invoice.')
                        ->modalSubmitActionLabel('Yes, Request Invoice')
                        ->visible(fn (Renewal $record) => $record->renewal_progress === 'pending_confirmation')
                        ->form(function (Renewal $record) {
                            $lead = $record->lead_id ? \App\Models\Lead::find($record->lead_id) : null;
                            $options = [];

                            if ($lead) {
                                $quotations = $lead->quotations()
                                    ->where('sales_type', 'RENEWAL SALES')
                                    ->orderByDesc('id')
                                    ->get();

                                foreach ($quotations as $q) {
                                    $total = $q->items->sum('total_before_tax');
                                    $options[$q->id] = $q->quotation_reference_no . ' - RM ' . number_format($total, 2)
                                        . ' (' . ($q->status?->value ?? $q->status ?? 'N/A') . ')';
                                }
                            }

                            return [
                                Select::make('quotation_id')
                                    ->label('Select Renewal Quotation')
                                    ->options($options)
                                    ->required()
                                    ->placeholder('Choose a quotation'),
                            ];
                        })
                        ->action(function (Renewal $record, array $data) {
                            try {
                                $swId = $record->software_handover_id;
                                if (!$swId) {
                                    Notification::make()->danger()->title('No software handover linked.')->send();
                                    return;
                                }

                                $handover = \App\Models\SoftwareHandover::find($swId);
                                if (!$handover) {
                                    Notification::make()->danger()->title('Software handover not found.')->send();
                                    return;
                                }

                                // Check if invoice already exists for this quotation
                                $exists = HrSalesInvoice::where('software_handover_id', $swId)
                                    ->where('quotation_id', $data['quotation_id'])
                                    ->exists();

                                if ($exists) {
                                    Notification::make()->warning()->title('Invoice Already Exists')->body('An HR Sales Invoice already exists for this quotation.')->send();
                                    return;
                                }

                                // Create HR Sales Invoice using existing method
                                HrSalesInvoice::createHrSalesInvoiceFromQuotationsforSH(
                                    $handover,
                                    [$data['quotation_id']],
                                    $handover->project_code ?? "SW_{$swId}",
                                    $record->company_name,
                                    null,
                                    null,
                                );

                                // Update renewal status
                                $history = is_array($record->progress_history) ? $record->progress_history : [];
                                $history[] = [
                                    'timestamp' => now(), 'action' => 'invoice_requested',
                                    'previous_status' => $record->renewal_progress, 'new_status' => 'pending_payment',
                                    'performed_by' => auth()->user()->name, 'performed_by_id' => auth()->user()->id,
                                    'quotation_id' => $data['quotation_id'],
                                ];
                                $record->update(['renewal_progress' => 'pending_payment', 'progress_history' => $history]);

                                Notification::make()->success()->title('Invoice Created')->body('HR Sales Invoice created and status updated to Pending Payment.')->send();

                            } catch (\Exception $e) {
                                Log::error('Error requesting invoice: ' . $e->getMessage());
                                Notification::make()->danger()->title('Error')->body('Failed to create invoice: ' . $e->getMessage())->send();
                            }
                        }),

                    Action::make('completed_payment')
                        ->label('Completed Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->modalHeading('Mark Payment as Completed')
                        ->modalDescription('Select the renewal quotation to preview and update the licenses.')
                        ->modalSubmitActionLabel('Yes, Complete Payment')
                        ->modalWidth('4xl')
                        ->visible(fn (Renewal $record) => $record->renewal_progress === 'pending_payment')
                        ->form(function (Renewal $record) {
                            $lead = $record->lead_id ? \App\Models\Lead::find($record->lead_id) : null;
                            $options = [];

                            if ($lead) {
                                $quotations = $lead->quotations()
                                    ->where('sales_type', 'RENEWAL SALES')
                                    ->orderByDesc('id')
                                    ->get();

                                foreach ($quotations as $q) {
                                    $total = $q->items->sum('total_before_tax');
                                    $options[$q->id] = $q->quotation_reference_no . ' - RM ' . number_format($total, 2)
                                        . ' (' . ($q->status?->value ?? $q->status ?? 'N/A') . ')';
                                }
                            }

                            return [
                                Select::make('quotation_id')
                                    ->label('Select Renewal Quotation')
                                    ->options($options)
                                    ->required()
                                    ->live()
                                    ->placeholder('Choose a quotation'),

                                Placeholder::make('license_preview')
                                    ->label('')
                                    ->visible(fn (\Filament\Forms\Get $get) => !empty($get('quotation_id')))
                                    ->content(function (\Filament\Forms\Get $get) use ($record) {
                                        $quotationId = $get('quotation_id');
                                        if (!$quotationId) return '';

                                        return self::buildLicensePreview($quotationId, $record->software_handover_id);
                                    }),
                            ];
                        })
                        ->action(function (Renewal $record, array $data) {
                            $quotation = \App\Models\Quotation::with('items.product')->find($data['quotation_id']);
                            if (!$quotation) {
                                Notification::make()->danger()->title('Quotation not found.')->send();
                                return;
                            }

                            $swId = $record->software_handover_id;
                            if (!$swId) {
                                Notification::make()->danger()->title('No software handover linked.')->send();
                                return;
                            }

                            try {
                                DB::beginTransaction();

                                $handover = \App\Models\SoftwareHandover::find($swId);
                                $hrAccountId = $handover?->hr_account_id;
                                $hrCompanyId = $handover?->hr_company_id;

                                if (!$hrAccountId || !$hrCompanyId) {
                                    DB::rollBack();
                                    Notification::make()->danger()->title('Missing HR Account')->body('Software handover does not have hr_account_id or hr_company_id.')->send();
                                    return;
                                }

                                $licenseService = new \App\Services\HRV2LicenseService();

                                // Get hr_sales_invoice number for this quotation
                                $hrInvoiceNo = HrSalesInvoice::where('software_handover_id', $swId)
                                    ->where('quotation_id', $quotation->id)
                                    ->value('invoice_no')
                                    ?? HrSalesInvoice::where('software_handover_id', $swId)
                                        ->latest()
                                        ->value('invoice_no')
                                    ?? '-';

                                $codeToModule = self::getCodeToModuleMapping();
                                // API uses short names: Attendance, Leave, Claim, Payroll
                                $codeToApiApp = [
                                    'TCL_TA USER-NEW' => 'Attendance', 'TCL_TA USER-ADDON' => 'Attendance',
                                    'TCL_TA USER-ADDON(R)' => 'Attendance', 'TCL_TA USER-RENEWAL' => 'Attendance',
                                    'TCL_LEAVE USER-NEW' => 'Leave', 'TCL_LEAVE USER-ADDON' => 'Leave',
                                    'TCL_LEAVE USER-ADDON(R)' => 'Leave', 'TCL_LEAVE USER-RENEWAL' => 'Leave',
                                    'TCL_CLAIM USER-NEW' => 'Claim', 'TCL_CLAIM USER-ADDON' => 'Claim',
                                    'TCL_CLAIM USER-ADDON(R)' => 'Claim', 'TCL_CLAIM USER-RENEWAL' => 'Claim',
                                    'TCL_PAYROLL USER-NEW' => 'Payroll', 'TCL_PAYROLL USER-ADDON' => 'Payroll',
                                    'TCL_PAYROLL USER-ADDON(R)' => 'Payroll', 'TCL_PAYROLL USER-RENEWAL' => 'Payroll',
                                ];

                                $softwareSolutions = ['software_new_sales', 'software_renewal_sales', 'software_addon_new_sales'];
                                $updatedCount = 0;

                                foreach ($quotation->items as $item) {
                                    if (!$item->product || !in_array($item->product->solution, $softwareSolutions)) {
                                        continue;
                                    }

                                    // Handle TCL_RENEWAL: expand into individual modules from description
                                    if ($item->product->code === 'TCL_RENEWAL') {
                                        $parsed = self::parseTclRenewalDescription($item->description);

                                        if (empty($parsed['modules'])) {
                                            Log::warning('TCL_RENEWAL: No modules found in description', ['quotation_detail_id' => $item->id]);
                                            continue;
                                        }

                                        foreach ($parsed['modules'] as $moduleInfo) {
                                            $licenseType = $moduleInfo['license_type'];
                                            $apiApp = $moduleInfo['api_app'];
                                            $subscriptionMonths = $item->subscription_period ?? 12;

                                            $license = HrLicense::where('software_handover_id', $swId)
                                                ->where('type', 'PAID')
                                                ->where('license_type', $licenseType)
                                                ->orderByDesc('end_date')
                                                ->first();

                                            // Use dates from description if available, otherwise calculate
                                            if ($parsed['start_date'] && $parsed['end_date']) {
                                                $newStartDate = $parsed['start_date']->copy();
                                                $newEndDate = $parsed['end_date']->copy();
                                            } elseif ($license) {
                                                $existingEnd = Carbon::parse($license->end_date);
                                                $newStartDate = $existingEnd->copy()->addDay();
                                                $newEndDate = $newStartDate->copy()->addMonths($subscriptionMonths)->subDay();
                                            } else {
                                                $newStartDate = $item->license_start_date ? Carbon::parse($item->license_start_date) : now();
                                                $newEndDate = $item->license_end_date ? Carbon::parse($item->license_end_date) : $newStartDate->copy()->addMonths($subscriptionMonths)->subDay();
                                            }

                                            $seatLimit = $item->quantity ?? ($license?->unit ?? 1);

                                            // Check if this is an extend (continuous from existing end)
                                            $isExtend = $license && $license->period_id
                                                && $newStartDate->format('Y-m-d') === Carbon::parse($license->end_date)->addDay()->format('Y-m-d');

                                            if ($isExtend) {
                                                // Extend: keep original start, extend end date
                                                $apiResult = $licenseService->updatePaidApplicationLicense(
                                                    $hrAccountId, $hrCompanyId, (int) $license->period_id,
                                                    [
                                                        'startDate' => Carbon::parse($license->start_date)->format('Y-m-d'),
                                                        'endDate' => $newEndDate->format('Y-m-d'),
                                                        'seatLimit' => $seatLimit,
                                                    ]
                                                );

                                                if (!($apiResult['success'] ?? false)) {
                                                    throw new \Exception("API failed to extend {$licenseType} (TCL_RENEWAL): " . ($apiResult['error'] ?? json_encode($apiResult)));
                                                }

                                                $periodId = $apiResult['data']['periodId'] ?? $apiResult['periodId'] ?? $license->period_id;
                                                $totalMonths = (int) $license->month + $subscriptionMonths;

                                                $license->update([
                                                    'end_date' => $newEndDate,
                                                    'unit' => $seatLimit,
                                                    'user_limit' => $seatLimit,
                                                    'month' => $totalMonths,
                                                    'invoice_no' => $hrInvoiceNo,
                                                    'period_id' => $periodId,
                                                ]);
                                            } else {
                                                // New period — create new
                                                $apiResult = $licenseService->addPaidApplicationLicense(
                                                    $hrAccountId, $hrCompanyId,
                                                    [
                                                        'application' => $apiApp,
                                                        'startDate' => $newStartDate->format('Y-m-d'),
                                                        'endDate' => $newEndDate->format('Y-m-d'),
                                                        'seatLimit' => $seatLimit,
                                                    ]
                                                );

                                                if (!($apiResult['success'] ?? false)) {
                                                    throw new \Exception("API failed to create renewal {$licenseType} (TCL_RENEWAL): " . ($apiResult['error'] ?? json_encode($apiResult)));
                                                }

                                                $periodId = $apiResult['data']['periodId'] ?? $apiResult['periodId'] ?? null;

                                                HrLicense::create([
                                                    'software_handover_id' => $swId,
                                                    'handover_id' => $handover->project_code ?? "SW_{$swId}",
                                                    'type' => 'PAID',
                                                    'invoice_no' => $hrInvoiceNo,
                                                    'auto_count_invoice_no' => '-',
                                                    'company_name' => $record->company_name,
                                                    'license_category' => 'Subscriber',
                                                    'license_type' => $licenseType,
                                                    'unit' => $seatLimit,
                                                    'user_limit' => $seatLimit,
                                                    'total_user' => 0,
                                                    'total_login' => 0,
                                                    'month' => $subscriptionMonths,
                                                    'start_date' => $newStartDate,
                                                    'end_date' => $newEndDate,
                                                    'status' => 'Enabled',
                                                    'auto_renewal' => 'Enabled',
                                                    'period_id' => $periodId,
                                                ]);
                                            }
                                            $updatedCount++;
                                        }
                                        continue;
                                    }

                                    $licenseType = $codeToModule[$item->product->code] ?? null;
                                    $apiApp = $codeToApiApp[$item->product->code] ?? null;
                                    if (!$licenseType || !$apiApp) continue;

                                    $subscriptionMonths = $item->subscription_period ?? 12;

                                    $license = HrLicense::where('software_handover_id', $swId)
                                        ->where('type', 'PAID')
                                        ->where('license_type', $licenseType)
                                        ->orderByDesc('end_date')
                                        ->first();

                                    // Calculate dates
                                    if ($license) {
                                        $existingEnd = Carbon::parse($license->end_date);
                                        $extendStart = $existingEnd->copy()->addDay();
                                        $quotationStart = $item->license_start_date ? Carbon::parse($item->license_start_date) : null;
                                        $quotationEnd = $item->license_end_date ? Carbon::parse($item->license_end_date) : null;

                                        if ($quotationStart && $quotationStart->gt($extendStart)) {
                                            $newStartDate = $quotationStart;
                                            $newEndDate = $quotationEnd ?? $newStartDate->copy()->addMonths($subscriptionMonths)->subDay();
                                        } else {
                                            $newStartDate = $extendStart;
                                            $newEndDate = $newStartDate->copy()->addMonths($subscriptionMonths)->subDay();
                                        }
                                    } else {
                                        $newStartDate = $item->license_start_date ? Carbon::parse($item->license_start_date) : now();
                                        $newEndDate = $item->license_end_date ? Carbon::parse($item->license_end_date) : $newStartDate->copy()->addMonths($subscriptionMonths)->subDay();
                                    }

                                    $seatLimit = $item->quantity ?? ($license?->unit ?? 1);

                                    // Determine if this is an extend (existing license, continuous dates)
                                    $isExtend = $license && $license->period_id
                                        && $newStartDate->format('Y-m-d') === Carbon::parse($license->end_date)->addDay()->format('Y-m-d');

                                    // Step 1: Call API
                                    if ($isExtend) {
                                        // Extend: keep original start, extend end date on existing period
                                        $apiResult = $licenseService->updatePaidApplicationLicense(
                                            $hrAccountId, $hrCompanyId, (int) $license->period_id,
                                            [
                                                'startDate' => Carbon::parse($license->start_date)->format('Y-m-d'),
                                                'endDate' => $newEndDate->format('Y-m-d'),
                                                'seatLimit' => $seatLimit,
                                            ]
                                        );

                                        if (!($apiResult['success'] ?? false)) {
                                            throw new \Exception("API failed to extend {$licenseType}: " . ($apiResult['error'] ?? json_encode($apiResult)));
                                        }
                                    } elseif ($license && $license->period_id) {
                                        // New period (gap exists) — create new, don't touch existing
                                        $apiResult = $licenseService->addPaidApplicationLicense(
                                            $hrAccountId, $hrCompanyId,
                                            [
                                                'application' => $apiApp,
                                                'startDate' => $newStartDate->format('Y-m-d'),
                                                'endDate' => $newEndDate->format('Y-m-d'),
                                                'seatLimit' => $seatLimit,
                                            ]
                                        );

                                        if (!($apiResult['success'] ?? false)) {
                                            throw new \Exception("API failed to create new period for {$licenseType}: " . ($apiResult['error'] ?? json_encode($apiResult)));
                                        }
                                    } else {
                                        // No existing license — create new
                                        $apiResult = $licenseService->addPaidApplicationLicense(
                                            $hrAccountId, $hrCompanyId,
                                            [
                                                'application' => $apiApp,
                                                'startDate' => $newStartDate->format('Y-m-d'),
                                                'endDate' => $newEndDate->format('Y-m-d'),
                                                'seatLimit' => $seatLimit,
                                            ]
                                        );

                                        if (!($apiResult['success'] ?? false)) {
                                            throw new \Exception("API failed to create {$licenseType}: " . ($apiResult['error'] ?? json_encode($apiResult)));
                                        }
                                    }

                                    // Step 2: Only update local DB after API success
                                    $periodId = $apiResult['data']['periodId'] ?? $apiResult['periodId'] ?? ($license?->period_id);

                                    if ($isExtend) {
                                        // Extend: update end date only, keep original start
                                        $totalMonths = (int) $license->month + $subscriptionMonths;
                                        $license->update([
                                            'end_date' => $newEndDate,
                                            'unit' => $seatLimit,
                                            'user_limit' => $seatLimit,
                                            'month' => $totalMonths,
                                            'invoice_no' => $hrInvoiceNo,
                                            'period_id' => $periodId,
                                        ]);
                                    } elseif ($license && $license->period_id) {
                                        // New period (gap) — create new record, keep existing untouched
                                        HrLicense::create([
                                            'software_handover_id' => $swId,
                                            'handover_id' => $handover->project_code ?? "SW_{$swId}",
                                            'type' => 'PAID',
                                            'invoice_no' => $hrInvoiceNo,
                                            'auto_count_invoice_no' => '-',
                                            'company_name' => $record->company_name,
                                            'license_category' => 'Subscriber',
                                            'license_type' => $licenseType,
                                            'unit' => $seatLimit,
                                            'user_limit' => $seatLimit,
                                            'total_user' => 0,
                                            'total_login' => 0,
                                            'month' => $subscriptionMonths,
                                            'start_date' => $newStartDate,
                                            'end_date' => $newEndDate,
                                            'status' => 'Enabled',
                                            'auto_renewal' => 'Enabled',
                                            'period_id' => $periodId,
                                        ]);
                                    } else {
                                        // No existing — create new
                                        HrLicense::create([
                                            'software_handover_id' => $swId,
                                            'handover_id' => $handover->project_code ?? "SW_{$swId}",
                                            'type' => 'PAID',
                                            'invoice_no' => $hrInvoiceNo,
                                            'auto_count_invoice_no' => '-',
                                            'company_name' => $record->company_name,
                                            'license_category' => 'Subscriber',
                                            'license_type' => $licenseType,
                                            'unit' => $seatLimit,
                                            'user_limit' => $seatLimit,
                                            'total_user' => 0,
                                            'total_login' => 0,
                                            'month' => $subscriptionMonths,
                                            'start_date' => $newStartDate,
                                            'end_date' => $newEndDate,
                                            'status' => 'Enabled',
                                            'auto_renewal' => 'Enabled',
                                            'period_id' => $periodId,
                                        ]);
                                    }
                                    $updatedCount++;
                                }

                                if ($updatedCount === 0) {
                                    DB::rollBack();
                                    Notification::make()->warning()->title('No Licenses Updated')->body('No software license items found in this quotation.')->send();
                                    return;
                                }

                                // Create official receipts for all sales invoices linked to this handover
                                $salesInvoices = HrSalesInvoice::where('software_handover_id', $swId)->get();
                                $receiptCount = 0;

                                foreach ($salesInvoices as $salesInvoice) {
                                    // Skip if receipt already exists for this invoice
                                    if (HrOfficialReceipt::where('invoice_no', $salesInvoice->invoice_no)->exists()) {
                                        continue;
                                    }

                                    // Generate OR number: OR2603000001 format
                                    $prefix = 'OR' . now()->format('ym');
                                    $lastOr = HrOfficialReceipt::where('or_no', 'like', $prefix . '%')
                                        ->orderBy('or_no', 'desc')
                                        ->value('or_no');
                                    $nextSeq = $lastOr ? ((int) substr($lastOr, strlen($prefix))) + 1 : 1;
                                    $orNo = $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);

                                    HrOfficialReceipt::create([
                                        'or_no' => $orNo,
                                        'receipt_date' => now()->toDateString(),
                                        'company_name' => $record->company_name,
                                        'subscriber_name' => $handover?->pic_name,
                                        'description' => 'Official Receipt for ' . $salesInvoice->invoice_no,
                                        'currency' => $salesInvoice->currency ?? 'MYR',
                                        'amount' => $salesInvoice->invoice_amount ?? 0,
                                        'status' => 'paid',
                                        'created_by' => auth()->user()->name ?? 'System',
                                        'invoice_no' => $salesInvoice->invoice_no,
                                        'payment_method' => $salesInvoice->payment_method,
                                        'software_handover_id' => $swId,
                                        'handover_id' => $handover?->project_code ?? "SW_{$swId}",
                                    ]);

                                    // Mark sales invoice as paid
                                    $salesInvoice->update([
                                        'payment_status' => 'paid',
                                        'status' => 'paid',
                                    ]);

                                    $receiptCount++;
                                }

                                // Only update renewal status if licenses were successfully created/updated
                                $history = is_array($record->progress_history) ? $record->progress_history : [];
                                $history[] = [
                                    'timestamp' => now(), 'action' => 'payment_completed',
                                    'previous_status' => $record->renewal_progress, 'new_status' => 'completed_renewal',
                                    'performed_by' => auth()->user()->name, 'performed_by_id' => auth()->user()->id,
                                    'quotation_id' => $quotation->id,
                                    'quotation_ref' => $quotation->quotation_reference_no,
                                    'licenses_updated' => $updatedCount,
                                ];

                                $record->update([
                                    'renewal_progress' => 'completed_renewal',
                                    'progress_history' => $history,
                                    'payment_completed_at' => now(),
                                    'payment_completed_by' => auth()->user()->id,
                                ]);

                                DB::commit();

                                Notification::make()
                                    ->success()
                                    ->title('Renewal Completed')
                                    ->body("Payment completed. {$updatedCount} license(s) updated, {$receiptCount} receipt(s) created.")
                                    ->send();

                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error('Error completing payment: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                                Notification::make()->danger()->title('Failed - No changes made')->body($e->getMessage())->send();
                            }
                        }),

                    Action::make('follow_up')
                        ->label('Follow Up')
                        ->icon('heroicon-o-calendar-days')
                        ->color('primary')
                        ->form([
                            DatePicker::make('follow_up_date')
                                ->label('Next Follow-up Date')
                                ->default(function () {
                                    $d = now()->copy();
                                    $added = 0;
                                    while ($added < 2) { $d->addDay(); if ($d->isWeekday()) $added++; }
                                    return $d;
                                })
                                ->minDate(now()->subDay())
                                ->required(),
                            RichEditor::make('notes')
                                ->label('Remarks')
                                ->disableToolbarButtons(['attachFiles', 'blockquote', 'codeBlock', 'h2', 'h3', 'link', 'redo', 'strike', 'undo'])
                                ->placeholder('Add your follow-up details here...')
                                ->required(),
                        ])
                        ->action(function (Renewal $record, array $data) {
                            $record->update(['follow_up_date' => $data['follow_up_date'], 'follow_up_counter' => true]);

                            AdminRenewalLogs::create([
                                'lead_id' => $record->lead_id,
                                'description' => 'Admin Renewal Follow Up By ' . auth()->user()->name,
                                'causer_id' => auth()->id(),
                                'remark' => $data['notes'],
                                'subject_id' => $record->id,
                                'follow_up_date' => $data['follow_up_date'],
                                'follow_up_counter' => true,
                            ]);

                            Notification::make()->success()->title('Follow Up Added')->body("Follow-up set for {$record->company_name}.")->send();
                        })
                        ->modalHeading('Follow Up')
                        ->modalSubmitActionLabel('Submit Follow Up'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary'),
            ])
            ->emptyStateHeading('No renewal records')
            ->emptyStateDescription('No HR V2 renewal records found. Run the auto-mapping scheduler to populate.');
    }

    public static function getCodeToModuleMapping(): array
    {
        return [
            'TCL_TA USER-NEW' => 'TimeTec Attendance',
            'TCL_TA USER-ADDON' => 'TimeTec Attendance',
            'TCL_TA USER-ADDON(R)' => 'TimeTec Attendance',
            'TCL_TA USER-RENEWAL' => 'TimeTec Attendance',
            'TCL_LEAVE USER-NEW' => 'TimeTec Leave',
            'TCL_LEAVE USER-ADDON' => 'TimeTec Leave',
            'TCL_LEAVE USER-ADDON(R)' => 'TimeTec Leave',
            'TCL_LEAVE USER-RENEWAL' => 'TimeTec Leave',
            'TCL_CLAIM USER-NEW' => 'TimeTec Claim',
            'TCL_CLAIM USER-ADDON' => 'TimeTec Claim',
            'TCL_CLAIM USER-ADDON(R)' => 'TimeTec Claim',
            'TCL_CLAIM USER-RENEWAL' => 'TimeTec Claim',
            'TCL_PAYROLL USER-NEW' => 'TimeTec Payroll',
            'TCL_PAYROLL USER-ADDON' => 'TimeTec Payroll',
            'TCL_PAYROLL USER-ADDON(R)' => 'TimeTec Payroll',
            'TCL_PAYROLL USER-RENEWAL' => 'TimeTec Payroll',
        ];
    }

    /**
     * Parse TCL_RENEWAL description to extract modules and date range.
     * Description format example:
     *   TIMETEC - HR SOLUTIONS
     *   TIMETEC - TIME ATTENDANCE
     *   TIMETEC - PAYROLL
     *   RENEWAL LICENSE SUBSCRIPTION:
     *   16 APRIL 2026 TO 15 APRIL 2027
     */
    public static function parseTclRenewalDescription(?string $description): array
    {
        $result = ['modules' => [], 'start_date' => null, 'end_date' => null];

        if (!$description) return $result;

        $text = strip_tags($description);
        $text = html_entity_decode($text);
        $text = preg_replace('/\xc2\xa0|\&nbsp;/', ' ', $text); // non-breaking spaces
        $text = trim($text);

        // Module mapping from description keywords to license types and API app names
        $moduleKeywords = [
            'TIME ATTENDANCE' => ['license_type' => 'TimeTec Attendance', 'api_app' => 'Attendance'],
            'ATTENDANCE' => ['license_type' => 'TimeTec Attendance', 'api_app' => 'Attendance'],
            'LEAVE' => ['license_type' => 'TimeTec Leave', 'api_app' => 'Leave'],
            'CLAIM' => ['license_type' => 'TimeTec Claim', 'api_app' => 'Claim'],
            'PAYROLL' => ['license_type' => 'TimeTec Payroll', 'api_app' => 'Payroll'],
        ];

        $upperText = strtoupper($text);
        foreach ($moduleKeywords as $keyword => $mapping) {
            if (str_contains($upperText, $keyword) && !str_contains($keyword, 'HR SOLUTIONS')) {
                // Avoid duplicate: TIME ATTENDANCE already matches, don't also match ATTENDANCE
                if ($keyword === 'ATTENDANCE' && in_array($mapping, $result['modules'])) continue;
                if (!in_array($mapping, $result['modules'])) {
                    $result['modules'][] = $mapping;
                }
            }
        }

        // Parse date range: "16 APRIL 2026 TO 15 APRIL 2027" or "16/04/2026 TO 15/04/2027"
        if (preg_match('/(\d{1,2})\s+(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+(\d{4})\s+TO\s+(\d{1,2})\s+(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+(\d{4})/i', $text, $matches)) {
            $result['start_date'] = Carbon::parse("{$matches[1]} {$matches[2]} {$matches[3]}");
            $result['end_date'] = Carbon::parse("{$matches[4]} {$matches[5]} {$matches[6]}");
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\s+TO\s+(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i', $text, $matches)) {
            $result['start_date'] = Carbon::parse("{$matches[3]}-{$matches[2]}-{$matches[1]}");
            $result['end_date'] = Carbon::parse("{$matches[6]}-{$matches[5]}-{$matches[4]}");
        }

        return $result;
    }

    public static function buildLicensePreview(int $quotationId, ?int $swId): HtmlString
    {
        $quotation = \App\Models\Quotation::with('items.product')->find($quotationId);
        if (!$quotation) return new HtmlString('<p style="color:#6b7280;">Quotation not found.</p>');

        $codeToModule = self::getCodeToModuleMapping();
        $softwareSolutions = ['software_new_sales', 'software_renewal_sales', 'software_addon_new_sales'];

        $rows = [];
        foreach ($quotation->items as $item) {
            if (!$item->product || !in_array($item->product->solution, $softwareSolutions)) continue;

            // Handle TCL_RENEWAL: expand into individual module preview rows
            if ($item->product->code === 'TCL_RENEWAL') {
                $parsed = self::parseTclRenewalDescription($item->description);

                if (empty($parsed['modules'])) continue;

                foreach ($parsed['modules'] as $moduleInfo) {
                    $licenseType = $moduleInfo['license_type'];
                    $subscriptionMonths = $item->subscription_period ?? 12;

                    $existing = $swId ? HrLicense::where('software_handover_id', $swId)
                        ->where('type', 'PAID')
                        ->where('license_type', $licenseType)
                        ->orderByDesc('end_date')
                        ->first() : null;

                    if ($parsed['start_date'] && $parsed['end_date']) {
                        $renewalStart = $parsed['start_date']->copy();
                        $renewalEnd = $parsed['end_date']->copy();

                        if ($existing) {
                            $existingEnd = Carbon::parse($existing->end_date);
                            $extendStart = $existingEnd->copy()->addDay();
                            $currentDates = Carbon::parse($existing->start_date)->format('d/m/Y') . ' - ' . $existingEnd->format('d/m/Y');

                            if ($renewalStart->lte($extendStart)) {
                                // Extend: show combined date (existing start to renewal end)
                                $action = 'Extend';
                                $newDatesDisplay = Carbon::parse($existing->start_date)->format('d/m/Y') . ' - ' . $renewalEnd->format('d/m/Y');
                            } else {
                                $action = 'New Period';
                                $newDatesDisplay = $renewalStart->format('d/m/Y') . ' - ' . $renewalEnd->format('d/m/Y');
                            }
                        } else {
                            $action = 'Create';
                            $currentDates = '-';
                            $newDatesDisplay = $renewalStart->format('d/m/Y') . ' - ' . $renewalEnd->format('d/m/Y');
                        }
                    } elseif ($existing) {
                        $existingEnd = Carbon::parse($existing->end_date);
                        $extendStart = $existingEnd->copy()->addDay();
                        $newEnd = $extendStart->copy()->addMonths($subscriptionMonths)->subDay();
                        $action = 'Extend';
                        $currentDates = Carbon::parse($existing->start_date)->format('d/m/Y') . ' - ' . $existingEnd->format('d/m/Y');
                        $newDatesDisplay = Carbon::parse($existing->start_date)->format('d/m/Y') . ' - ' . $newEnd->format('d/m/Y');
                    } else {
                        $newStart = now();
                        $newEnd = $newStart->copy()->addMonths($subscriptionMonths)->subDay();
                        $action = 'Create';
                        $currentDates = '-';
                        $newDatesDisplay = $newStart->format('d/m/Y') . ' - ' . $newEnd->format('d/m/Y');
                    }

                    $moduleColors = [
                        'TimeTec Attendance' => '#3b82f6',
                        'TimeTec Leave' => '#8b5cf6',
                        'TimeTec Claim' => '#f59e0b',
                        'TimeTec Payroll' => '#10b981',
                    ];
                    $color = $moduleColors[$licenseType] ?? '#6b7280';
                    $actionColor = match ($action) {
                        'Create' => '#16a34a',
                        'Extend' => '#2563eb',
                        'New Period' => '#f59e0b',
                        default => '#6b7280',
                    };

                    $rows[] = '<tr>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">'
                        . '<span style="background:' . $color . ';color:#fff;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;">' . str_replace('TimeTec ', '', $licenseType) . '</span>'
                        . ' <span style="font-size:0.65rem;color:#9ca3af;">(TCL_RENEWAL)</span>'
                        . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">' . ($item->quantity ?? 1) . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">' . $subscriptionMonths . ' months</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:0.8rem;color:#6b7280;">' . $currentDates . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;">' . $newDatesDisplay . '</td>'
                        . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">'
                        . '<span style="background:' . $actionColor . ';color:#fff;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;">' . $action . '</span>'
                        . '</td>'
                        . '</tr>';
                }
                continue;
            }

            $licenseType = $codeToModule[$item->product->code] ?? null;
            if (!$licenseType) continue;

            $subscriptionMonths = $item->subscription_period ?? 12;

            // Check existing license
            $existing = $swId ? HrLicense::where('software_handover_id', $swId)
                ->where('type', 'PAID')
                ->where('license_type', $licenseType)
                ->orderByDesc('end_date')
                ->first() : null;

            $quotationStart = $item->license_start_date ? Carbon::parse($item->license_start_date) : null;
            $quotationEnd = $item->license_end_date ? Carbon::parse($item->license_end_date) : null;

            if ($existing) {
                $existingStart = Carbon::parse($existing->start_date);
                $existingEnd = Carbon::parse($existing->end_date);
                $extendStart = $existingEnd->copy()->addDay();
                $currentDates = $existingStart->format('d/m/Y') . ' - ' . $existingEnd->format('d/m/Y');

                if ($quotationStart && $quotationStart->gt($extendStart)) {
                    $newStart = $quotationStart;
                    $newEnd = $quotationEnd ?? $newStart->copy()->addMonths($subscriptionMonths)->subDay();
                    $action = 'New Period';
                    $newDatesDisplay = $newStart->format('d/m/Y') . ' - ' . $newEnd->format('d/m/Y');
                } else {
                    $newEnd = $extendStart->copy()->addMonths($subscriptionMonths)->subDay();
                    $action = 'Extend';
                    // Show combined: existing start to new end
                    $newDatesDisplay = $existingStart->format('d/m/Y') . ' - ' . $newEnd->format('d/m/Y');
                }
            } else {
                $newStart = $quotationStart ?? now();
                $newEnd = $quotationEnd ?? $newStart->copy()->addMonths($subscriptionMonths)->subDay();
                $action = 'Create';
                $currentDates = '-';
                $newDatesDisplay = $newStart->format('d/m/Y') . ' - ' . $newEnd->format('d/m/Y');
            }

            $moduleColors = [
                'TimeTec Attendance' => '#3b82f6',
                'TimeTec Leave' => '#8b5cf6',
                'TimeTec Claim' => '#f59e0b',
                'TimeTec Payroll' => '#10b981',
            ];
            $color = $moduleColors[$licenseType] ?? '#6b7280';
            $actionColor = match ($action) {
                'Create' => '#16a34a',
                'Extend' => '#2563eb',
                'New Period' => '#f59e0b',
                default => '#6b7280',
            };

            $rows[] = '<tr>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">'
                . '<span style="background:' . $color . ';color:#fff;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;">' . str_replace('TimeTec ', '', $licenseType) . '</span>'
                . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">' . ($item->quantity ?? 1) . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">' . $subscriptionMonths . ' months</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:0.8rem;color:#6b7280;">' . $currentDates . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;">' . $newDatesDisplay . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">'
                . '<span style="background:' . $actionColor . ';color:#fff;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;">' . $action . '</span>'
                . '</td>'
                . '</tr>';
        }

        if (empty($rows)) {
            return new HtmlString('<p style="color:#6b7280;padding:12px;">No software license items found in this quotation.</p>');
        }

        $html = '<div style="margin-top:16px;">'
            . '<h4 style="font-size:0.9rem;font-weight:700;color:#374151;margin-bottom:12px;">License Preview</h4>'
            . '<table style="width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">'
            . '<thead><tr style="background:#f9fafb;">'
            . '<th style="padding:10px 12px;text-align:left;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Module</th>'
            . '<th style="padding:10px 12px;text-align:center;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">HC</th>'
            . '<th style="padding:10px 12px;text-align:center;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Period</th>'
            . '<th style="padding:10px 12px;text-align:left;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Current Dates</th>'
            . '<th style="padding:10px 12px;text-align:left;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">New Dates</th>'
            . '<th style="padding:10px 12px;text-align:center;font-size:0.8rem;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;">Action</th>'
            . '</tr></thead>'
            . '<tbody>' . implode('', $rows) . '</tbody>'
            . '</table>'
            . '</div>';

        return new HtmlString($html);
    }
}
