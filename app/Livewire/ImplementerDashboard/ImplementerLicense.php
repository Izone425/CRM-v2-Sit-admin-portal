<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class ImplementerLicense extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getOverdueSoftwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        // Show handovers that still need kick-off confirmation. Regardless of whether
        // the drawer has already provisioned trial/paid licenses, implementer needs
        // to set the real kick-off date so license dates can be recomputed from it.
        $query = SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            ->whereNull('kick_off_meeting')
            ->where('license_activated', 0)
            ->whereHas('implementerAppointments', function ($q) {
                $q->where('type', 'KICK OFF MEETING SESSION')
                    ->where('status', '!=', 'Cancelled');
            })
            ->where('id', '>=', 561)
            ->orderBy('created_at', 'asc')
            ->with(['lead', 'lead.companyDetail', 'creator']);

        if ($this->selectedUser === 'all-implementer') {

        }
        elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer', $user->name);
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser->role_id === 4) {
                $query->where('implementer', $currentUser->name);
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueSoftwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4,5])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }


                        return $record->formatted_handover_id;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 4),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('status_handover')
                    ->label('Status'),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('create_license_Duration')
                        ->label('Create License Duration')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool =>
                            $record->hr_version == '1' &&
                            $record->status === 'Completed' &&
                            is_null($record->kick_off_meeting)
                        )
                        ->form([
                            \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('confirmed_kickoff_date')
                                    ->label('Confirmed Kick-off Date')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->displayFormat('d M Y')
                                    ->default(function (SoftwareHandover $record = null) {
                                        return $record ? ($record->kick_off_meeting ?? now()) : now();
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('buffer_months')
                                    ->label('Buffer License Duration')
                                    ->options([
                                        '1' => '1 month',
                                        '2' => '2 months',
                                        '3' => '3 months',
                                        '4' => '4 months',
                                        '5' => '5 months',
                                        '6' => '6 months',
                                        '7' => '7 months',
                                        '8' => '8 months',
                                        '9' => '9 months',
                                        '10' => '10 months',
                                        '11' => '11 months',
                                        '12' => '12 months',
                                    ])
                                    ->required()
                                    ->default(function (SoftwareHandover $record = null) {
                                        if ($record && !empty($record->type_1_pi_invoice_data)) {
                                            $data = $record->type_1_pi_invoice_data;
                                            if (isset($data['buffer_month'])) {
                                                return (string) $data['buffer_month'];
                                            }
                                        }
                                        return '1';
                                    })
                                    ->dehydrated(true)
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('paid_license_years')
                                    ->label('Paid License Years')
                                    ->options([
                                        '0' => '0 years',
                                        '1' => '1 year',
                                        '2' => '2 years',
                                        '3' => '3 years',
                                        '4' => '4 years',
                                        '5' => '5 years',
                                        '6' => '6 years',
                                        '7' => '7 years',
                                        '8' => '8 years',
                                        '9' => '9 years',
                                        '10' => '10 years',
                                    ])
                                    ->required()
                                    ->default(function (SoftwareHandover $record = null) {
                                        if ($record && !empty($record->type_2_pi_invoice_data)) {
                                            $data = $record->type_2_pi_invoice_data;
                                            if (isset($data['billing_cycle'])) {
                                                return (string) floor((int) $data['billing_cycle'] / 12);
                                            }
                                        }
                                        return '1';
                                    })
                                    ->dehydrated(true)
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('paid_license_months')
                                    ->label('Paid License Months')
                                    ->options([
                                        '0' => '0 months',
                                        '1' => '1 month',
                                        '2' => '2 months',
                                        '3' => '3 months',
                                        '4' => '4 months',
                                        '5' => '5 months',
                                        '6' => '6 months',
                                        '7' => '7 months',
                                        '8' => '8 months',
                                        '9' => '9 months',
                                        '10' => '10 months',
                                        '11' => '11 months',
                                    ])
                                    ->required()
                                    ->default(function (SoftwareHandover $record = null) {
                                        if ($record && !empty($record->type_2_pi_invoice_data)) {
                                            $data = $record->type_2_pi_invoice_data;
                                            if (isset($data['billing_cycle'])) {
                                                return (string) ((int) $data['billing_cycle'] % 12);
                                            }
                                        }
                                        return '0';
                                    })
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                            ]),
                            \Filament\Forms\Components\Section::make('Implementer Reference')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('subscription_periods_table')
                                    ->hiddenLabel()
                                    ->content(function (SoftwareHandover $record = null) {
                                        if (!$record) {
                                            return 'No record available.';
                                        }

                                        $subscriptionPeriods = $this->getSubscriptionPeriodsForHandover($record);

                                        if (empty($subscriptionPeriods)) {
                                            return 'No subscription periods found.';
                                        }

                                        $html = '<table class="w-full text-sm border border-collapse border-gray-300">';
                                        $html .= '<thead class="bg-gray-50">';
                                        $html .= '<tr>';
                                        $html .= '<th class="px-4 py-2 font-semibold text-left border border-gray-300">Product</th>';
                                        $html .= '<th class="px-4 py-2 font-semibold text-center border border-gray-300">Year</th>';
                                        $html .= '<th class="px-4 py-2 font-semibold text-center border border-gray-300">Month</th>';
                                        $html .= '</tr>';
                                        $html .= '</thead>';
                                        $html .= '<tbody>';

                                        foreach ($subscriptionPeriods as $period) {
                                            $html .= '<tr>';
                                            $html .= '<td class="px-4 py-2 border border-gray-300">' . htmlspecialchars($period['product']) . '</td>';
                                            $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $period['years'] . '</td>';
                                            $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $period['months'] . '</td>';
                                            $html .= '</tr>';
                                        }

                                        $html .= '</tbody>';
                                        $html .= '</table>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    }),
                            ]),
                            \Filament\Forms\Components\Section::make('Amendment License Summary')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('amendment_summary')
                                    ->hiddenLabel()
                                    ->content(function (\Filament\Forms\Get $get, SoftwareHandover $record): \Illuminate\Support\HtmlString {
                                        $kickoffDate = $get('confirmed_kickoff_date');
                                        $bufferMonths = (int) ($get('buffer_months') ?? 1);
                                        $paidYears = (int) ($get('paid_license_years') ?? 0);
                                        $paidMonths = (int) ($get('paid_license_months') ?? 0);

                                        if (!$kickoffDate) {
                                            return new \Illuminate\Support\HtmlString('<p style="color: #6b7280; text-align: center;">Please select a Confirmed Kick-off Date</p>');
                                        }

                                        $kickoff = Carbon::parse($kickoffDate);
                                        $trialStart = $kickoff->copy();
                                        $trialEnd = $kickoff->copy()->addMonths($bufferMonths)->subDay();
                                        $totalPaidMonths = ($paidYears * 12) + $paidMonths;
                                        $numberOfYears = max((int) ($totalPaidMonths / 12), 1);

                                        $html = '<table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">';
                                        $html .= '<thead><tr style="background-color: #f3f4f6;">';
                                        $html .= '<th class="px-4 py-2 font-semibold text-left border border-gray-300">License Type</th>';
                                        $html .= '<th class="px-4 py-2 font-semibold text-center border border-gray-300">Start Date</th>';
                                        $html .= '<th class="px-4 py-2 font-semibold text-center border border-gray-300">End Date</th>';
                                        $html .= '</tr></thead><tbody>';

                                        // Trial row
                                        $html .= '<tr>';
                                        $html .= '<td class="px-4 py-2 font-medium border border-gray-300">Trial (Buffer)</td>';
                                        $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $trialStart->format('d/m/Y') . '</td>';
                                        $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $trialEnd->format('d/m/Y') . '</td>';
                                        $html .= '</tr>';

                                        // Paid year rows
                                        $pendingStart = $trialEnd->copy()->addDay();
                                        for ($y = 1; $y <= $numberOfYears; $y++) {
                                            $yearStart = $pendingStart->copy()->addMonths(($y - 1) * 12);
                                            $yearEnd = $pendingStart->copy()->addMonths($y * 12)->subDay();
                                            $ordinal = match($y) { 1 => '1st', 2 => '2nd', 3 => '3rd', default => $y . 'th' };
                                            $html .= '<tr>';
                                            $html .= '<td class="px-4 py-2 border border-gray-300">' . $ordinal . ' Year Subscription</td>';
                                            $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $yearStart->format('d/m/Y') . '</td>';
                                            $html .= '<td class="px-4 py-2 text-center border border-gray-300">' . $yearEnd->format('d/m/Y') . '</td>';
                                            $html .= '</tr>';
                                        }

                                        $html .= '</tbody></table>';

                                        return new \Illuminate\Support\HtmlString($html);
                                    }),
                            ]),
                            \Filament\Forms\Components\Section::make('Email Recipients')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('additional_recipients')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->default(fn (SoftwareHandover $record = null) => $this->getDefaultRecipientEmails($record))
                                    ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),
                            ]),
                        ])
                        ->modalHeading("Create License Duration")
                        ->modalSubmitActionLabel('Submit')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function (array $data, SoftwareHandover $record): void {
                            // Get the implementer info
                            $implementer = \App\Models\User::where('name', $record->implementer)->first();
                            $implementerEmail = $implementer?->email ?? null;
                            $implementerName = $implementer?->name ?? $record->implementer ?? 'Unknown';

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Calculate license dates
                            $kickOffDate = $data['confirmed_kickoff_date'] ?? now();

                            // Ensure kickOffDate is a Carbon object before cloning
                            if (!$kickOffDate instanceof Carbon) {
                                $kickOffDate = Carbon::parse($kickOffDate);
                            }

                            // Handle buffer license duration
                            $bufferMonths = (int) $data['buffer_months'];
                            $bufferYears = 0;

                            // Handle paid license duration - now supporting both years and months
                            $paidLicenseYears = (int) ($data['paid_license_years'] ?? 0);
                            $paidLicenseMonths = (int) ($data['paid_license_months'] ?? 0);

                            // Validate that at least some paid license duration is specified
                            if ($paidLicenseYears === 0 && $paidLicenseMonths === 0) {
                                throw new \Exception('Please specify at least some paid license duration (years or months).');
                            }

                            // Calculate buffer duration in months for display
                            $totalBufferMonths = ($bufferYears * 12) + $bufferMonths;

                            // Calculate total paid duration in months
                            $totalPaidMonths = ($paidLicenseYears * 12) + $paidLicenseMonths;

                            // Calculate dates
                            $bufferEndDate = (clone $kickOffDate)->addMonths($totalBufferMonths);
                            $paidStartDate = (clone $bufferEndDate)->addDay();
                            $paidEndDate = (clone $paidStartDate)
                                ->addYears($paidLicenseYears)
                                ->addMonths($paidLicenseMonths)
                                ->subDay();
                            $nextRenewalDate = (clone $paidEndDate)->addDay();

                            // Format durations for display
                            $bufferDuration = $this->formatDuration($bufferYears, $bufferMonths);
                            $paidDuration = $this->formatDuration($paidLicenseYears, $paidLicenseMonths);

                            // Upsert the license certificate so an existing one (e.g. created by
                            // the Create DB + Trial License drawer) is updated in-place instead of duplicated.
                            $certificate = \App\Models\LicenseCertificate::updateOrCreate(
                                ['software_handover_id' => $record->id],
                                [
                                    'company_name' => $companyName,
                                    'kick_off_date' => $kickOffDate ?? $record->kick_off_meeting ?? now(),
                                    'buffer_license_start' => $kickOffDate,
                                    'buffer_license_end' => $bufferEndDate,
                                    'buffer_months' => $totalBufferMonths,
                                    'paid_license_start' => $paidStartDate,
                                    'paid_license_end' => $paidEndDate,
                                    'paid_months' => $totalPaidMonths,
                                    'next_renewal_date' => $nextRenewalDate,
                                    'license_years' => $paidLicenseYears + ($paidLicenseMonths / 12),
                                    'updated_by' => auth()->id(),
                                    'created_by' => $record->license_certification_id ? null : auth()->id(),
                                ]
                            );

                            // Update the software handover record with license information
                            $record->update([
                                'license_certification_id' => $certificate->id,
                                'kick_off_meeting' => $data['confirmed_kickoff_date'] ?? $record->kick_off_meeting,
                                'db_creation' => $kickOffDate, // Amend trial license start to kick-off date
                            ]);

                            // Amend pending license dates based on new kick-off date
                            $existingType1 = $record->type_1_pi_invoice_data;
                            $existingType2 = $record->type_2_pi_invoice_data;
                            $trialBufferMonth = (int) ($existingType1['buffer_month'] ?? $totalBufferMonths);

                            if (is_array($existingType2) && isset($existingType2['items'])) {
                                $newPendingDate = (clone $kickOffDate)->addMonths($trialBufferMonth)->format('Y-m-d');
                                $existingType2['pending_date'] = $newPendingDate;
                                $record->update(['type_2_pi_invoice_data' => $existingType2]);

                                // Also update HrSalesInvoice + HrSalesInvoiceItem dates
                                $salesInvoice = \App\Models\HrSalesInvoice::where('software_handover_id', $record->id)->first();
                                if ($salesInvoice) {
                                    $billingCycle = (int) ($existingType2['billing_cycle'] ?? 12);
                                    $numberOfYears = max((int) ($billingCycle / 12), 1);
                                    $pendingStart = Carbon::parse($newPendingDate);

                                    // Build new year date ranges
                                    $yearDateRanges = [];
                                    for ($y = 1; $y <= $numberOfYears; $y++) {
                                        $yearDateRanges["Year {$y}"] = [
                                            'start_date' => $pendingStart->copy()->addMonths(($y - 1) * 12)->format('Y-m-d'),
                                            'end_date'   => $pendingStart->copy()->addMonths($y * 12)->subDay()->format('Y-m-d'),
                                        ];
                                    }

                                    // Update line_items JSON and recalculate totals
                                    $lineItems = $salesInvoice->line_items ?? [];
                                    $yearCounter = [];
                                    foreach ($lineItems as &$li) {
                                        // Determine which year this item belongs to based on position
                                        $itemYear = null;
                                        foreach ($yearDateRanges as $yLabel => $yRange) {
                                            if (!isset($yearCounter[$yLabel])) $yearCounter[$yLabel] = 0;
                                        }
                                    }
                                    // Re-assign dates sequentially by year groups
                                    $itemIndex = 0;
                                    $productsPerYear = count($lineItems) / $numberOfYears;
                                    foreach ($yearDateRanges as $yLabel => $yRange) {
                                        for ($p = 0; $p < $productsPerYear && $itemIndex < count($lineItems); $p++) {
                                            $lineItems[$itemIndex]['start_date'] = $yRange['start_date'];
                                            $lineItems[$itemIndex]['end_date'] = $yRange['end_date'];
                                            $itemIndex++;
                                        }
                                    }

                                    $salesInvoice->update([
                                        'invoice_date' => $newPendingDate,
                                        'line_items' => $lineItems,
                                    ]);

                                    // Update individual HrSalesInvoiceItem records
                                    $invoiceItems = \App\Models\HrSalesInvoiceItem::where('hr_sales_invoice_id', $salesInvoice->id)
                                        ->orderBy('id')
                                        ->get();

                                    $itemIdx = 0;
                                    foreach ($yearDateRanges as $yLabel => $yRange) {
                                        for ($p = 0; $p < $productsPerYear && $itemIdx < $invoiceItems->count(); $p++) {
                                            $invoiceItems[$itemIdx]->update([
                                                'invoice_date' => $newPendingDate,
                                                'start_date'   => $yRange['start_date'],
                                                'end_date'     => $yRange['end_date'],
                                            ]);
                                            $itemIdx++;
                                        }
                                    }
                                }
                            }

                            // HR Sales Invoice is created in SoftwareHandoverV2PendingLicense
                            // when paid licenses are activated (Activate License V2 action)

                            // Format the handover ID properly
                            $handoverId = $record->formatted_handover_id;
                            $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            // Send email notification
                            try {
                                $viewName = 'emails.implementer_license_notification';

                                // Create email content structure
                                $emailContent = [
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'implementer' => [
                                        'name' => $implementerName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'licenses' => [
                                        'kickOffDate' => $record->kick_off_meeting ? $record->kick_off_meeting->format('d M Y') : now()->format('d M Y'),
                                        'bufferLicense' => [
                                            'start' => $kickOffDate->format('d M Y'),
                                            'end' => $bufferEndDate->format('d M Y'),
                                            'duration' => $bufferDuration
                                        ],
                                        'paidLicense' => [
                                            'start' => $paidStartDate->format('d M Y'),
                                            'end' => $paidEndDate->format('d M Y'),
                                            'duration' => $paidDuration
                                        ],
                                        'nextRenewal' => $nextRenewalDate->format('d M Y')
                                    ],
                                ];

                                // Initialize recipients array
                                $recipients = [];

                                // Process additional recipients from the form data
                                if (!empty($data['additional_recipients']) && is_string($data['additional_recipients'])) {
                                    foreach (array_filter(array_map('trim', explode(';', $data['additional_recipients']))) as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients, true)) {
                                            $recipients[] = $email;
                                        }
                                    }
                                }

                                // Always add implementer email if valid
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Always add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $certificateId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("LICENSE CERTIFICATE | TIMETEC HR | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("Data migration completion & license certification email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for software handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('License Duration Created')
                                ->success()
                                ->body("License certificate duration generated successfully and email has been sent.")
                                ->send();
                        }),
                    Action::make('activate_license_v2')
                        ->label('Activate License (V2)')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool =>
                            $record->hr_version == 2 &&
                            $record->status === 'Completed' &&
                            is_null($record->kick_off_meeting)
                        )
                        ->form([
                            \Filament\Forms\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('confirmed_kickoff_date')
                                    ->label('Confirmed Kick-off Date')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->displayFormat('d M Y')
                                    ->default(function (SoftwareHandover $record = null) {
                                        if ($record) {
                                            $kickoff = \App\Models\ImplementerAppointment::where('type', 'KICK OFF MEETING SESSION')
                                                ->where('status', '!=', 'Cancelled')
                                                ->where(function ($q) use ($record) {
                                                    $q->where('software_handover_id', $record->id)
                                                      ->orWhere('lead_id', $record->lead_id);
                                                })
                                                ->orderBy('id')
                                                ->value('date');
                                            if ($kickoff) return $kickoff;
                                        }
                                        return $record ? ($record->kick_off_meeting ?? now()) : now();
                                    })
                                    ->disabled(function (SoftwareHandover $record = null): bool {
                                        if (!$record) return false;
                                        return \App\Models\ImplementerAppointment::where('type', 'KICK OFF MEETING SESSION')
                                            ->where('status', '!=', 'Cancelled')
                                            ->where(function ($q) use ($record) {
                                                $q->where('software_handover_id', $record->id)
                                                  ->orWhere('lead_id', $record->lead_id);
                                            })
                                            ->exists();
                                    })
                                    ->dehydrated(true)
                                    ->helperText(function (SoftwareHandover $record = null): ?string {
                                        if (!$record) return null;
                                        $exists = \App\Models\ImplementerAppointment::where('type', 'KICK OFF MEETING SESSION')
                                            ->where('status', '!=', 'Cancelled')
                                            ->where(function ($q) use ($record) {
                                                $q->where('software_handover_id', $record->id)
                                                  ->orWhere('lead_id', $record->lead_id);
                                            })
                                            ->exists();
                                        return $exists ? 'Auto-set from the first scheduled Kick Off Meeting Session.' : null;
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('buffer_months')
                                    ->label('Buffer License Duration')
                                    ->options([
                                        '1' => '1 month',
                                        '2' => '2 months',
                                        '3' => '3 months',
                                        '4' => '4 months',
                                        '5' => '5 months',
                                        '6' => '6 months',
                                        '7' => '7 months',
                                        '8' => '8 months',
                                        '9' => '9 months',
                                        '10' => '10 months',
                                        '11' => '11 months',
                                        '12' => '12 months',
                                    ])
                                    ->required()
                                    ->live()
                                    ->default(fn (SoftwareHandover $record = null) => $record && (int) ($record->headcount ?? 0) > 50 ? '2' : '1')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Auto-set to 2 months when company size is more than 50; otherwise 1 month.')
                                    ->columnSpan(1),
                            ]),
                            \Filament\Forms\Components\Placeholder::make('amendment_summary')
                                ->hiddenLabel()
                                ->content(function (\Filament\Forms\Get $get, SoftwareHandover $record): \Illuminate\Support\HtmlString {
                                    $kickoffDate = $get('confirmed_kickoff_date');
                                    $bufferMonths = (int) ($get('buffer_months') ?? 1);

                                    if (!$kickoffDate) {
                                        return new \Illuminate\Support\HtmlString('<p style="color: #6b7280; text-align: center;">Please select a Confirmed Kick-off Date</p>');
                                    }

                                    $kickoff = Carbon::parse($kickoffDate);
                                    $trialStart = $kickoff->copy();
                                    $trialEnd = $kickoff->copy()->addMonths($bufferMonths)->subDay();
                                    $segments = $this->getLicenseSetSegmentsForHandover($record);

                                    // Get modules
                                    $moduleLabels = [];
                                    foreach (['ta' => 'TA', 'tl' => 'TL', 'tc' => 'TC', 'tp' => 'TP', 'tapp' => 'TApp', 'thire' => 'THire', 'tacc' => 'TAcc', 'tpbi' => 'TPBI'] as $key => $label) {
                                        if ($record->$key) $moduleLabels[] = $label;
                                    }
                                    $modulesStr = implode(', ', $moduleLabels) ?: 'None';

                                    $html = '<div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px;">';

                                    // License Summary Card
                                    $html .= '<div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">';
                                    $html .= '<div style="background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 12px 16px; color: white; font-weight: 700; font-size: 14px;">License Summary</div>';
                                    $html .= '<div style="padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';

                                    $html .= '<div><div style="font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Kick-off Date</div><div style="font-weight: 600; color: #111827;">' . $kickoff->format('d M Y') . '</div></div>';
                                    $html .= '<div><div style="font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Modules</div><div style="font-weight: 600; color: #111827;">' . $modulesStr . '</div></div>';

                                    // Buffer License
                                    $html .= '<div style="grid-column: span 2; padding: 10px; border-radius: 8px; background: #fef3c7; border: 1px solid #fcd34d;">';
                                    $html .= '<div style="font-size: 11px; color: #92400e; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Buffer License (' . $bufferMonths . ' month' . ($bufferMonths > 1 ? 's' : '') . ')</div>';
                                    $html .= '<div style="font-weight: 600; color: #78350f;">' . $trialStart->format('d M Y') . ' — ' . $trialEnd->format('d M Y') . '</div>';
                                    $html .= '</div>';

                                    // Paid License Timeline
                                    $paidBaseStart = $trialEnd->copy()->addDay();

                                    $monthsByYearOrder = [];
                                    foreach ($segments as $moduleSegments) {
                                        foreach ($moduleSegments as $segment) {
                                            $yearOrder = (int) ($segment['year_order'] ?? 1);
                                            $months = max(1, (int) ($segment['subscription_period'] ?? 1));
                                            if (!isset($monthsByYearOrder[$yearOrder])) {
                                                $monthsByYearOrder[$yearOrder] = $months;
                                            } else {
                                                $monthsByYearOrder[$yearOrder] = max($monthsByYearOrder[$yearOrder], $months);
                                            }
                                        }
                                    }

                                    ksort($monthsByYearOrder);

                                    $yearOffsets = [];
                                    $runningOffset = 0;
                                    foreach ($monthsByYearOrder as $yearOrder => $months) {
                                        $yearOffsets[(int) $yearOrder] = $runningOffset;
                                        $runningOffset += (int) $months;
                                    }

                                    $totalPaidMonths = $runningOffset;
                                    $paidYears = (int) floor($totalPaidMonths / 12);
                                    $paidMonthsRem = (int) ($totalPaidMonths % 12);
                                    $paidDuration = $paidYears > 0 ? "{$paidYears} year" . ($paidYears > 1 ? 's' : '') : '';
                                    if ($paidMonthsRem > 0) $paidDuration .= ($paidDuration ? ' ' : '') . "{$paidMonthsRem} month" . ($paidMonthsRem > 1 ? 's' : '');

                                    $paidRows = [];
                                    foreach ($segments as $moduleName => $moduleSegments) {
                                        foreach ($moduleSegments as $segment) {
                                            $months = max(1, (int) ($segment['subscription_period'] ?? 1));
                                            $yearOrder = (int) ($segment['year_order'] ?? 1);
                                            $offsetMonths = (int) ($yearOffsets[$yearOrder] ?? 0);

                                            $segmentStart = $paidBaseStart->copy()->addMonths($offsetMonths);
                                            $segmentEnd = $segmentStart->copy()->addMonths($months)->subDay();

                                            $paidRows[] = [
                                                'year_label' => $segment['year_label'] ?? 'Year 1',
                                                'year_order' => (int) ($segment['year_order'] ?? 1),
                                                'module_name' => $moduleName,
                                                'seat_limit' => (int) ($segment['seat_limit'] ?? 0),
                                                'months' => $months,
                                                'start_date' => $segmentStart,
                                                'end_date' => $segmentEnd,
                                            ];
                                        }
                                    }

                                    usort($paidRows, static function (array $a, array $b): int {
                                        if ($a['year_order'] === $b['year_order']) {
                                            return strcmp($a['module_name'], $b['module_name']);
                                        }
                                        return $a['year_order'] <=> $b['year_order'];
                                    });

                                    $html .= '<div style="grid-column: span 2; padding: 10px; border-radius: 8px; background: #f0fdf4; border: 1px solid #86efac;">';
                                    $html .= '<div style="font-size: 11px; color: #166534; text-transform: uppercase; font-weight: 700; margin-bottom: 8px;">Paid License' . ($paidDuration ? ' (' . $paidDuration . ')' : '') . '</div>';

                                    if (!empty($paidRows)) {
                                        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                                        $html .= '<thead><tr style="background: #dcfce7;">';
                                        $html .= '<th style="padding: 6px 8px; text-align: left; border: 1px solid #bbf7d0;">Year</th>';
                                        $html .= '<th style="padding: 6px 8px; text-align: left; border: 1px solid #bbf7d0;">Module</th>';
                                        $html .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #bbf7d0;">Seat</th>';
                                        $html .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #bbf7d0;">Months</th>';
                                        $html .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #bbf7d0;">Start</th>';
                                        $html .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #bbf7d0;">End</th>';
                                        $html .= '</tr></thead><tbody>';

                                        foreach ($paidRows as $row) {
                                            $html .= '<tr>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0;">' . htmlspecialchars($row['year_label']) . '</td>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0; font-weight: 600;">' . htmlspecialchars($row['module_name']) . '</td>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0; text-align: center; font-weight: 600; color: #0f172a;">' . $row['seat_limit'] . '</td>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0; text-align: center;">' . $row['months'] . '</td>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0; text-align: center;">' . $row['start_date']->format('d/m/Y') . '</td>';
                                            $html .= '<td style="padding: 6px 8px; border: 1px solid #bbf7d0; text-align: center;">' . $row['end_date']->format('d/m/Y') . '</td>';
                                            $html .= '</tr>';
                                        }

                                        $html .= '</tbody></table>';

                                        $overallEnd = $paidBaseStart->copy()->addMonths($totalPaidMonths)->subDay();
                                        $html .= '<div style="margin-top: 8px; font-size: 12px; color: #166534;">Next Renewal: <strong>' . $overallEnd->copy()->addDay()->format('d M Y') . '</strong></div>';
                                    } else {
                                        $html .= '<div style="color: #6b7280; font-style: italic;">No license set segments found.</div>';
                                    }

                                    $html .= '</div>';
                                    $html .= '</div></div>';
                                    $html .= '</div>';

                                    return new \Illuminate\Support\HtmlString($html);
                                }),

                            \Filament\Forms\Components\Section::make('Email Recipients')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('additional_recipients')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->default(fn (SoftwareHandover $record = null) => $this->getDefaultRecipientEmails($record))
                                    ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),
                            ]),
                        ])
                        ->modalHeading('Activate License (V2)')
                        ->modalWidth('4xl')
                        ->action(function (SoftwareHandover $record, array $data): void {
                            $handoverId = $record->formatted_handover_id;
                            $accountId = $record->hr_account_id;
                            $companyId = $record->hr_company_id;

                            // If CRM account doesn't exist, create it
                            if (!$accountId || !$companyId) {
                                $crmResult = $this->createCRMAccount($record, $handoverId);

                                if (!$crmResult['success']) {
                                    Notification::make()
                                        ->title('CRM Account Creation Failed')
                                        ->danger()
                                        ->body($crmResult['error'] ?? 'Failed to create CRM account. Please try again.')
                                        ->send();
                                    return;
                                }

                                $accountId = $crmResult['data']['accountId'] ?? $record->fresh()->hr_account_id;
                                $companyId = $crmResult['data']['companyId'] ?? $record->fresh()->hr_company_id;

                                if (!$accountId || !$companyId) {
                                    Notification::make()
                                        ->title('License Activation Failed')
                                        ->danger()
                                        ->body('CRM account was created but IDs could not be retrieved.')
                                        ->send();
                                    return;
                                }

                                Notification::make()
                                    ->title('CRM Account Created')
                                    ->success()
                                    ->body("Account ID: {$accountId} | Company ID: {$companyId}")
                                    ->send();
                            }

                            // Detect modules directly from quotations (disabled checkboxes send false, not null)
                            $moduleSelections = [
                                'ta' => !empty($data['ta']) ?: $this->shouldModuleBeChecked($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tl' => !empty($data['tl']) ?: $this->shouldModuleBeChecked($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tc' => !empty($data['tc']) ?: $this->shouldModuleBeChecked($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tp' => !empty($data['tp']) ?: $this->shouldModuleBeChecked($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']),
                                'tapp' => !empty($data['tapp']) ?: $this->shouldModuleBeChecked($record, ['TCL_APPRAISAL USER-NEW']),
                                'thire' => !empty($data['thire']) ?: $this->shouldModuleBeChecked($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']),
                                'tacc' => !empty($data['tacc']) ?: $this->shouldModuleBeChecked($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']),
                                'tpbi' => !empty($data['tpbi']) ?: $this->shouldModuleBeChecked($record, ['TCL_POWER BI']),
                            ];

                            \Illuminate\Support\Facades\Log::info("Starting V2 license activation", [
                                'handover_id' => $handoverId,
                                'account_id' => $accountId,
                                'company_id' => $companyId,
                                'modules' => $moduleSelections
                            ]);

                            // Get company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Calculate license dates for V2 using form data
                            $kickOffDate = $data['confirmed_kickoff_date'] ?? now();
                            if (!$kickOffDate instanceof Carbon) {
                                $kickOffDate = Carbon::parse($kickOffDate);
                            }

                            $bufferMonths = (int) ($data['buffer_months'] ?? 1);
                            $bufferStartDate = (clone $kickOffDate);
                            $bufferEndDate = (clone $kickOffDate)->addMonths($bufferMonths)->subDay();

                            // Trial/buffer license MUST already exist (provisioned by the Create DB drawer).
                            // This action only updates the existing CRM buffer license with confirmed-kick-off dates.
                            $existingBufferSetId = $record->crm_buffer_license_id
                                ?? \App\Models\LicenseCertificate::where('software_handover_id', $record->id)->value('buffer_license_set_id');

                            if (!$existingBufferSetId) {
                                Notification::make()
                                    ->title('Trial License Not Found')
                                    ->danger()
                                    ->body('Please create the trial license first via "Create DB + Trial License" on the handover details.')
                                    ->send();
                                return;
                            }

                            $crmService = app(\App\Services\HRV2LicenseService::class);
                            $bufferApps = [];
                            $bufferSeats = [];
                            $moduleMapping = ['ta' => 'Attendance', 'tl' => 'Leave', 'tc' => 'Claim', 'tp' => 'Payroll'];
                            $moduleProductCodes = [
                                'ta' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                'tl' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                'tc' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                'tp' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
                            ];
                            foreach ($moduleMapping as $mk => $app) {
                                if (!empty($moduleSelections[$mk])) {
                                    $bufferApps[] = $app;
                                    $bufferSeats[$app] = $this->getSeatLimitForModule($record, $moduleProductCodes[$mk]);
                                }
                            }

                            $bufferResult = $crmService->updateBufferLicense((int) $accountId, (int) $companyId, (int) $existingBufferSetId, [
                                'startDate' => $bufferStartDate->format('Y-m-d'),
                                'endDate' => $bufferEndDate->format('Y-m-d'),
                                'applications' => $bufferApps,
                                'seatLimits' => $bufferSeats,
                                'notes' => 'Buffer license updated — kick-off confirmed',
                            ]);
                            if (!isset($bufferResult['data']['licenseSetId'])) {
                                $bufferResult['data']['licenseSetId'] = $existingBufferSetId;
                            }

                            if (!($bufferResult['success'] ?? false)) {
                                $bufferError = $bufferResult['error'] ?? 'Unknown error';
                                \Illuminate\Support\Facades\Log::error("Buffer license update failed, aborting activation", [
                                    'handover_id' => $handoverId,
                                    'error' => $bufferError,
                                ]);
                                Notification::make()
                                    ->title('Buffer License Update Failed')
                                    ->danger()
                                    ->body("Failed to update buffer license: {$bufferError}")
                                    ->send();
                                return;
                            }

                            // Notify if some modules were skipped due to overlap
                            if (!empty($bufferResult['partial'])) {
                                $skippedList = implode(', ', $bufferResult['skipped_apps'] ?? []);
                                $successList = implode(', ', $bufferResult['success_apps'] ?? []);
                                Notification::make()
                                    ->title('Buffer License Partial')
                                    ->warning()
                                    ->body("Created: {$successList}\nSkipped (already exists): {$skippedList}")
                                    ->persistent()
                                    ->send();
                            }

                            // If paid licenses were already created (by the drawer), recompute their
                            // dates from the new buffer end and push updates to CRM + local HrLicense.
                            $existingPaidLicenses = \App\Models\HrLicense::where('software_handover_id', $record->id)
                                ->where('type', 'PAID')
                                ->whereNotNull('period_id')
                                ->get();
                            $paidExists = $existingPaidLicenses->isNotEmpty();

                            $newPaidStart = null; $newPaidEnd = null; $newBillingCycle = null;
                            if ($paidExists) {
                                $pendingData = $record->type_2_pi_invoice_data;
                                if (is_string($pendingData)) $pendingData = json_decode($pendingData, true);
                                $newBillingCycle = is_array($pendingData) && !empty($pendingData['billing_cycle'])
                                    ? (int) $pendingData['billing_cycle'] : 12;
                                $newPaidStart = (clone $bufferEndDate)->addDay();
                                $newPaidEnd = (clone $newPaidStart)->addMonths($newBillingCycle)->subDay();
                            }

                            // Upsert the license certificate so re-running or following the drawer flow
                            // updates the same row instead of inserting a duplicate. Preserve paid dates
                            // if paid license was already provisioned.
                            $certificate = \App\Models\LicenseCertificate::updateOrCreate(
                                ['software_handover_id' => $record->id],
                                [
                                    'company_name' => $companyName,
                                    'kick_off_date' => $kickOffDate,
                                    'buffer_license_start' => $bufferStartDate,
                                    'buffer_license_end' => $bufferEndDate,
                                    'buffer_months' => $bufferMonths,
                                    'buffer_license_set_id' => $bufferResult['data']['licenseSetId'] ?? null,
                                    'paid_license_start' => $newPaidStart,
                                    'paid_license_end' => $newPaidEnd,
                                    'paid_months' => $newBillingCycle,
                                    'next_renewal_date' => $newPaidEnd ? (clone $newPaidEnd)->addDay() : null,
                                    'license_years' => $newBillingCycle ? $newBillingCycle / 12 : null,
                                    'updated_by' => auth()->id(),
                                    'created_by' => $record->license_certification_id ? null : auth()->id(),
                                ]
                            );

                            // Push paid-license updates to CRM and the local HrLicense rows.
                            if ($paidExists && $newPaidStart && $newPaidEnd) {
                                $crmService = app(\App\Services\HRV2LicenseService::class);
                                foreach ($existingPaidLicenses as $paidLicense) {
                                    try {
                                        $crmService->updatePaidApplicationLicense(
                                            (int) $accountId,
                                            (int) $companyId,
                                            (int) $paidLicense->period_id,
                                            [
                                                'startDate' => $newPaidStart->format('Y-m-d'),
                                                'endDate' => $newPaidEnd->format('Y-m-d'),
                                                'seatLimit' => (int) ($paidLicense->user_limit ?? $paidLicense->unit ?? 0),
                                            ]
                                        );
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error('Failed to push paid license update to CRM', [
                                            'handover_id' => $handoverId,
                                            'period_id' => $paidLicense->period_id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                    $paidLicense->update([
                                        'start_date' => $newPaidStart,
                                        'end_date' => $newPaidEnd,
                                        'month' => $newBillingCycle,
                                        'status' => 'Enabled',
                                    ]);
                                }
                            }

                            // Prevent duplicate TRIAL rows when the drawer already created them.
                            \App\Models\HrLicense::where('software_handover_id', $record->id)
                                ->where('type', 'TRIAL')
                                ->delete();

                            $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                            // Update software handover
                            $record->update([
                                'license_certification_id' => $certificate->id,
                                'license_activated' => true,
                                'license_activated_at' => now(),
                                'kick_off_meeting' => $kickOffDate,
                                'ta' => $moduleSelections['ta'],
                                'tl' => $moduleSelections['tl'],
                                'tc' => $moduleSelections['tc'],
                                'tp' => $moduleSelections['tp'],
                                'tapp' => $moduleSelections['tapp'],
                                'thire' => $moduleSelections['thire'],
                                'tacc' => $moduleSelections['tacc'],
                                'tpbi' => $moduleSelections['tpbi'],
                            ]);

                            \App\Models\HrSalesInvoice::where('software_handover_id', $record->id)
                                ->update(['payment_status' => 'paid']);

                            // Create local HrLicense records for each active module
                            $moduleMapping = [
                                'ta' => 'Attendance',
                                'tl' => 'Leave',
                                'tc' => 'Claim',
                                'tp' => 'Payroll',
                                'tapp' => 'Appraisal',
                                'thire' => 'Hire',
                                'tacc' => 'Access',
                                'tpbi' => 'PowerBI',
                            ];

                            $bufferLicenseSetId = $bufferResult['data']['licenseSetId'] ?? null;

                            // Update existing TRIAL HrLicense rows (drawer is the creator) with new buffer dates.
                            $updatedTrialCount = \App\Models\HrLicense::where('software_handover_id', $record->id)
                                ->where('type', 'TRIAL')
                                ->update([
                                    'start_date' => $bufferStartDate,
                                    'end_date' => $bufferEndDate,
                                    'month' => $bufferMonths,
                                    'license_set_id' => $bufferLicenseSetId,
                                    'status' => 'Enabled',
                                ]);

                            // Safety net: if TRIAL rows are missing (e.g. purged), rebuild from PAID rows.
                            if ($updatedTrialCount === 0) {
                                foreach (\App\Models\HrLicense::where('software_handover_id', $record->id)->where('type', 'PAID')->get() as $paid) {
                                    \App\Models\HrLicense::create([
                                        'software_handover_id' => $record->id,
                                        'handover_id' => $paid->handover_id,
                                        'type' => 'TRIAL',
                                        'invoice_no' => $paid->invoice_no,
                                        'auto_count_invoice_no' => '-',
                                        'company_name' => $companyName,
                                        'license_category' => 'Subscriber',
                                        'license_type' => $paid->license_type . ' (Trial)',
                                        'unit' => $paid->unit,
                                        'user_limit' => $paid->user_limit,
                                        'total_user' => 0,
                                        'total_login' => 0,
                                        'month' => $bufferMonths,
                                        'start_date' => $bufferStartDate,
                                        'end_date' => $bufferEndDate,
                                        'status' => 'Enabled',
                                        'auto_renewal' => 'Disabled',
                                        'license_set_id' => $bufferLicenseSetId,
                                    ]);
                                }
                            }

                            if ($bufferResult['success']) {
                                \Illuminate\Support\Facades\Log::info("V2 Buffer License updated (kick-off confirmed)", [
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'buffer_period' => "{$bufferStartDate->format('d M Y')} to {$bufferEndDate->format('d M Y')}",
                                ]);

                                // Update HrSalesInvoiceItem per-year dates relative to the new paid start.
                                // HrSalesInvoice rows themselves are owned by the drawer flow.
                                if ($newPaidStart) {
                                    try {
                                        foreach (\App\Models\HrSalesInvoice::where('software_handover_id', $record->id)->get() as $si) {
                                            foreach (\App\Models\HrSalesInvoiceItem::where('hr_sales_invoice_id', $si->id)->orderBy('sort_order')->orderBy('id')->get() as $item) {
                                                $yearNum = 1;
                                                if ($item->year && preg_match('/Year\s*(\d+)/i', $item->year, $m)) {
                                                    $yearNum = (int) $m[1];
                                                }
                                                $subMonths = $item->subscription_period ?? 12;
                                                $itemStart = (clone $newPaidStart)->addMonths(($yearNum - 1) * $subMonths);
                                                $itemEnd = (clone $itemStart)->addMonths($subMonths)->subDay();
                                                $item->update([
                                                    'license_start_date' => $itemStart->format('Y-m-d'),
                                                    'license_end_date' => $itemEnd->format('Y-m-d'),
                                                ]);
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error('Failed to update HrSalesInvoiceItem dates on kick-off confirm', [
                                            'handover_id' => $handoverId,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('Buffer License Updated')
                                    ->success()
                                    ->body("Kick-off confirmed — buffer period updated to {$bufferStartDate->format('d M Y')} → {$bufferEndDate->format('d M Y')}.")
                                    ->send();
                            } else {
                                $bufferError = $bufferResult['error'] ?? json_encode($bufferResult) ?? 'Unknown error';

                                \Illuminate\Support\Facades\Log::error("V2 Buffer License activation failed", [
                                    'handover_id' => $handoverId,
                                    'buffer_result' => $bufferResult,
                                ]);

                                Notification::make()
                                    ->title('Buffer License Activation Failed')
                                    ->danger()
                                    ->body("Failed to create buffer license: " . $bufferError)
                                    ->send();
                            }

                            // Send email notification (same as V1)
                            $implementer = \App\Models\User::where('name', $record->implementer)->first();
                            $implementerEmail = $implementer?->email ?? null;
                            $implementerName = $implementer?->name ?? $record->implementer ?? 'Unknown';

                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            $bufferDuration = $this->formatDuration(0, $bufferMonths);

                            try {
                                $viewName = 'emails.implementer_license_notification_v2';

                                // Build per-module breakdown from hr_licenses (V2 source of truth).
                                $hrLicenses = \App\Models\HrLicense::where('software_handover_id', $record->id)->get();
                                $moduleMap = [];
                                foreach ($hrLicenses as $lic) {
                                    $baseName = trim(preg_replace('/\s*\(Trial\)\s*$/i', '', (string) $lic->license_type));
                                    if ($baseName === '') continue;
                                    if (!isset($moduleMap[$baseName])) {
                                        $moduleMap[$baseName] = [
                                            'name' => $baseName,
                                            'seats' => $lic->user_limit ?? $lic->unit ?? 0,
                                            'trial' => null,
                                            'paid' => null,
                                        ];
                                    }
                                    $bucket = strtoupper((string) $lic->type) === 'TRIAL' ? 'trial' : 'paid';
                                    $moduleMap[$baseName][$bucket] = [
                                        'start' => $lic->start_date ? Carbon::parse($lic->start_date)->format('d M Y') : '-',
                                        'end' => $lic->end_date ? Carbon::parse($lic->end_date)->format('d M Y') : '-',
                                        'months' => (int) ($lic->month ?? 0),
                                    ];
                                    $moduleMap[$baseName]['seats'] = max(
                                        (int) $moduleMap[$baseName]['seats'],
                                        (int) ($lic->user_limit ?? $lic->unit ?? 0),
                                    );
                                }
                                ksort($moduleMap);

                                // Derive paid summary from PAID rows (earliest start, latest end).
                                $paidRows = $hrLicenses->where('type', 'PAID');
                                $paidStart = $paidRows->pluck('start_date')->filter()->min();
                                $paidEnd = $paidRows->pluck('end_date')->filter()->max();
                                $paidMonths = (int) ($paidRows->max('month') ?? 0);

                                $emailContent = [
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'implementer' => [
                                        'name' => $implementerName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'licenses' => [
                                        'kickOffDate' => $record->kick_off_meeting ? Carbon::parse($record->kick_off_meeting)->format('d M Y') : now()->format('d M Y'),
                                        'bufferLicense' => [
                                            'start' => $bufferStartDate->format('d M Y'),
                                            'end' => $bufferEndDate->format('d M Y'),
                                            'duration' => $bufferDuration,
                                        ],
                                        'paidLicense' => [
                                            'start' => $paidStart ? Carbon::parse($paidStart)->format('d M Y') : 'Pending payment confirmation',
                                            'end' => $paidEnd ? Carbon::parse($paidEnd)->format('d M Y') : 'Pending payment confirmation',
                                            'duration' => $paidMonths > 0 ? $this->formatDuration(intdiv($paidMonths, 12), $paidMonths % 12) : 'TBD',
                                        ],
                                        'nextRenewal' => $paidEnd ? Carbon::parse($paidEnd)->copy()->addDay()->format('d M Y') : 'TBD',
                                    ],
                                    'modules' => array_values($moduleMap),
                                ];

                                $recipients = [];

                                if (!empty($data['additional_recipients']) && is_string($data['additional_recipients'])) {
                                    foreach (array_filter(array_map('trim', explode(';', $data['additional_recipients']))) as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients, true)) {
                                            $recipients[] = $email;
                                        }
                                    }
                                }

                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $certificateId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("LICENSE CERTIFICATE | TIMETEC HR | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("V2 License notification email sent from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("V2 License email sending failed for handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('License Activated & Email Sent')
                                ->success()
                                ->body("License certificate {$certificateId} generated successfully and email has been sent.")
                                ->send();
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    /**
     * Get module period info for display
     */
    protected function getDefaultRecipientEmails(?SoftwareHandover $record): ?string
    {
        if (!$record) return null;

        $emails = [];

        $lead = $record->lead;
        if ($lead) {
            if ($lead->companyDetail && !empty($lead->companyDetail->email)) {
                $emails[] = $lead->companyDetail->email;
            }

            if ($lead->companyDetail && !empty($lead->companyDetail->additional_pic)) {
                try {
                    $additionalPics = is_array($lead->companyDetail->additional_pic)
                        ? $lead->companyDetail->additional_pic
                        : json_decode($lead->companyDetail->additional_pic, true);

                    if (is_array($additionalPics)) {
                        foreach ($additionalPics as $pic) {
                            if (
                                !empty($pic['email']) &&
                                isset($pic['status']) &&
                                $pic['status'] === 'Available'
                            ) {
                                $emails[] = $pic['email'];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error parsing additional_pic JSON: ' . $e->getMessage());
                }
            }
        }

        $uniqueEmails = array_values(array_unique($emails));
        return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
    }

    protected function getModulePeriodInfo(SoftwareHandover $record, array $productCodes): ?string
    {
        // Get all PI IDs
        $allPiIds = [];
        if (!empty($record->software_hardware_pi)) {
            $productPis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }
        if (!empty($record->proforma_invoice_product)) {
            $hrdfPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return null;
        }

        // Get license periods
        $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $record->project_code);

        // Find matching period for this module
        foreach ($licensePeriods as $period) {
            $intersection = array_intersect($productCodes, $period['product_codes']);
            if (!empty($intersection)) {
                $totalMonths = $period['subscription_period'];
                $years = floor($totalMonths / 12);
                $months = $totalMonths % 12;
                $duration = $this->formatDuration($years, $months);

                $startDate = now()->addMonth()->format('d M Y');
                $endDate = $period['end_date'];

                return "📅 {$startDate} to {$endDate} ({$duration})";
            }
        }

        return null;
    }

    /**
     * Check if module should be checked based on quotation products
     */
    protected function shouldModuleBeChecked(SoftwareHandover $record, array $productCodes): bool
    {
        // Get all PI IDs from software_hardware_pi and proforma_invoice_product
        $allPiIds = [];

        if (!empty($record->software_hardware_pi)) {
            $productPis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_product)) {
            $hrdfPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return false;
        }

        // Get quotation details for these PIs
        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                ->with('product')
                ->get();

            foreach ($details as $detail) {
                if (!$detail->product) {
                    continue;
                }

                // Check if this product code matches any of the module's product codes
                if (in_array($detail->product->code, $productCodes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add buffer licenses for all selected modules (1 month)
     */
    protected function addBufferLicenses(SoftwareHandover $record, int $accountId, int $companyId, array $modules, string $handoverId, int $bufferMonths = 1, ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $crmService = app(\App\Services\HRV2LicenseService::class);

            $bufferStartDate = $startDate ?? now()->format('Y-m-d');
            $bufferEndDate = $endDate ?? now()->addMonths($bufferMonths)->subDay()->format('Y-m-d');

            // CRM buffer endpoint only accepts: Attendance, Payroll, Leave, Claim, Profile.
            // Map unsupported products to Profile for buffer creation.
            $moduleMapping = [
                'ta' => 'Attendance',
                'tl' => 'Leave',
                'tc' => 'Claim',
                'tp' => 'Payroll',
            ];

            // Collect all selected applications and their seat limits
            $applications = [];
            $seatLimits = [];
            $moduleProductCodes = [
                'ta' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
                'tl' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
                'tc' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
                'tp' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
                'tapp' => ['TCL_APPRAISAL USER-NEW'],
                'thire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
                'tacc' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
                'tpbi' => ['TCL_POWER BI'],
            ];

            foreach ($moduleMapping as $moduleKey => $appName) {
                if (!empty($modules[$moduleKey])) {
                    $applications[] = $appName;
                    $moduleSeat = $this->getSeatLimitForModule($record, $moduleProductCodes[$moduleKey] ?? []);

                    // Multiple modules may map to Profile. Keep the highest seat limit.
                    if (!isset($seatLimits[$appName])) {
                        $seatLimits[$appName] = $moduleSeat;
                    } else {
                        $seatLimits[$appName] = max($seatLimits[$appName], $moduleSeat);
                    }
                }
            }

            $applications = array_values(array_unique($applications));

            if (empty($applications)) {
                return ['success' => false, 'error' => 'No modules selected'];
            }

            $licenseData = [
                'applications' => $applications,
                'startDate' => $bufferStartDate,
                'endDate' => $bufferEndDate,
                'seatLimits' => $seatLimits,
                'notes' => 'Buffer license created from CRM Implementer License',
            ];

            \Illuminate\Support\Facades\Log::info("Adding buffer licenses (single call)", [
                'handover_id' => $handoverId,
                'account_id' => $accountId,
                'company_id' => $companyId,
                'applications' => $applications,
                'buffer_start' => $bufferStartDate,
                'buffer_end' => $bufferEndDate,
            ]);

            $result = $crmService->addBufferLicense($accountId, $companyId, $licenseData);

            // If overlapping buffer exists, try adding modules individually (non-duplicated ones can still succeed)
            if (!($result['success'] ?? false) && str_contains($result['error'] ?? '', 'Overlapping Buffer license')) {
                \Illuminate\Support\Facades\Log::info("Overlapping buffer detected, retrying modules individually", [
                    'handover_id' => $handoverId,
                    'applications' => $applications,
                ]);

                // Try to find existing buffer_license_set_id from LicenseCertificate
                $existingSetId = null;

                // From same handover
                $existingCert = \App\Models\LicenseCertificate::whereNotNull('buffer_license_set_id')
                    ->where('software_handover_id', $record->id)
                    ->latest()
                    ->first();

                // From same account (different handover)
                if (!$existingCert) {
                    $existingCert = \App\Models\LicenseCertificate::whereNotNull('buffer_license_set_id')
                        ->whereHas('softwareHandover', function ($q) use ($record) {
                            $q->where('hr_account_id', $record->hr_account_id)
                              ->where('hr_company_id', $record->hr_company_id);
                        })
                        ->latest()
                        ->first();
                }

                if ($existingCert) {
                    $existingSetId = (int) $existingCert->buffer_license_set_id;
                }

                // Fallback: check HrLicense table
                if (!$existingSetId) {
                    $existingBufferLicense = \App\Models\HrLicense::where('license_type', 'TRIAL')
                        ->whereNotNull('license_set_id')
                        ->where('software_handover_id', $record->id)
                        ->latest()
                        ->first();

                    if (!$existingBufferLicense) {
                        $existingBufferLicense = \App\Models\HrLicense::where('license_type', 'TRIAL')
                            ->whereNotNull('license_set_id')
                            ->whereHas('softwareHandover', function ($q) use ($record) {
                                $q->where('hr_account_id', $record->hr_account_id)
                                  ->where('hr_company_id', $record->hr_company_id);
                            })
                            ->latest()
                            ->first();
                    }

                    if ($existingBufferLicense) {
                        $existingSetId = (int) $existingBufferLicense->license_set_id;
                    }
                }

                if ($existingSetId) {
                    // Merge existing modules with new modules (add, not replace)
                    $existingApps = [];
                    $existingSeatLimits = [];

                    // Get existing modules from the other handover's certificate
                    if ($existingCert && $existingCert->softwareHandover) {
                        $existingHandover = $existingCert->softwareHandover;
                        $existingModuleMapping = [
                            'ta' => 'Attendance',
                            'tl' => 'Leave',
                            'tc' => 'Claim',
                            'tp' => 'Payroll',
                        ];
                        foreach ($existingModuleMapping as $mk => $appName) {
                            if ($existingHandover->$mk) {
                                $existingApps[] = $appName;
                                $existingProductCodes = $moduleProductCodes[$mk] ?? [];
                                $existingSeatLimits[$appName] = $this->getSeatLimitForModule($existingHandover, $existingProductCodes);
                            }
                        }
                    }

                    // Also check HrLicense records for the existing set
                    $existingLicenses = \App\Models\HrLicense::where('license_set_id', $existingSetId)->get();
                    foreach ($existingLicenses as $el) {
                        $appName = str_replace('TimeTec ', '', $el->license_type);
                        if (!in_array($appName, $existingApps)) {
                            $existingApps[] = $appName;
                            if ($el->user_limit) {
                                $existingSeatLimits[$appName] = (int) $el->user_limit;
                            }
                        }
                    }

                    // Merge: existing + new (new takes priority for seat limits)
                    $mergedApps = array_values(array_unique(array_merge($existingApps, $applications)));
                    $mergedSeatLimits = array_merge($existingSeatLimits, $seatLimits);

                    $mergedLicenseData = [
                        'applications' => $mergedApps,
                        'startDate' => $licenseData['startDate'],
                        'endDate' => $licenseData['endDate'],
                        'seatLimits' => $mergedSeatLimits,
                        'notes' => $licenseData['notes'] ?? null,
                    ];

                    \Illuminate\Support\Facades\Log::info("Overlapping buffer detected, updating existing buffer with merged modules", [
                        'handover_id' => $handoverId,
                        'existing_license_set_id' => $existingSetId,
                        'existing_apps' => $existingApps,
                        'new_apps' => $applications,
                        'merged_apps' => $mergedApps,
                    ]);

                    $result = $crmService->updateBufferLicense($accountId, $companyId, $existingSetId, $mergedLicenseData);

                    if ($result['success'] ?? false) {
                        $result['data']['licenseSetId'] = $existingSetId;
                    }
                } else {
                    // No existing set ID found locally — the overlap exists on CRM side only
                    // Try to get the existing buffer info from CRM API and update it
                    \Illuminate\Support\Facades\Log::warning("Overlapping buffer on CRM but no local set ID found, attempting to create with new applications", [
                        'handover_id' => $handoverId,
                        'account_id' => $accountId,
                        'company_id' => $companyId,
                    ]);

                    $result = ['success' => false, 'error' => 'Overlapping buffer license exists on this account. No existing buffer set ID found locally to update. Please check the CRM account for existing buffer licenses.'];
                }
            }

            if ($result['success'] ?? false) {
                \Illuminate\Support\Facades\Log::info("Buffer licenses processed successfully", [
                    'handover_id' => $handoverId,
                    'applications' => $applications,
                    'license_set_id' => $result['data']['licenseSetId'] ?? null,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error("Failed to process buffer licenses", [
                    'handover_id' => $handoverId,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to add buffer licenses", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add paid application licenses based on selected modules and quotation details
     */
    protected function addPaidApplicationLicenses(SoftwareHandover $record, int $accountId, int $companyId, array $modules, string $handoverId, int $bufferMonths = 1, int $totalPaidMonths = 12): array
    {
        try {
            $crmService = app(\App\Services\HRV2LicenseService::class);
            $paidStart = now()->addMonths($bufferMonths);

            return $crmService->addPaidApplicationLicenses(
                $record,
                $accountId,
                $companyId,
                $modules,
                $handoverId,
                $paidStart,
                null
            );

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to add paid application licenses", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get license periods from quotations - groups by module type and sums periods
     */
    protected function getLicensePeriodsFromQuotations(array $piIds, string $handoverId): array
    {
        $licensePeriods = [];
        $quotations = \App\Models\Quotation::whereIn('id', $piIds)->get();

        $moduleGroups = [
            'Attendance' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Leave' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Claim' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Payroll' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Appraisal' => ['TCL_APPRAISAL USER-NEW'],
            'Hire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
            'Access' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
            'PowerBI' => ['TCL_POWER BI'],
        ];

        $periodsByModule = [];

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)->with('product')->get();

            foreach ($details as $detail) {
                if (!$detail->product) continue;

                $productCode = $detail->product->code;
                $subscriptionPeriod = $detail->subscription_period ?? $detail->product->subscription_period ?? 12;

                $moduleName = null;
                foreach ($moduleGroups as $module => $codes) {
                    if (in_array($productCode, $codes)) {
                        $moduleName = $module;
                        break;
                    }
                }

                if (!$moduleName) continue;

                if (!isset($periodsByModule[$moduleName])) {
                    $periodsByModule[$moduleName] = [
                        'module_name' => $moduleName,
                        'total_months' => 0,
                        'product_codes' => [],
                    ];
                }

                $periodsByModule[$moduleName]['total_months'] += (int)$subscriptionPeriod;
                $periodsByModule[$moduleName]['product_codes'][] = $productCode;
            }
        }

        $paidStartDate = now()->addMonth();

        foreach ($periodsByModule as $moduleName => $data) {
            $totalMonths = $data['total_months'];
            $endDate = $paidStartDate->copy()->addMonths($totalMonths)->subDay()->format('Y-m-d');

            $licensePeriods[] = [
                'module_name' => $moduleName,
                'product_codes' => array_unique($data['product_codes']),
                'subscription_period' => $totalMonths,
                'end_date' => $endDate,
            ];
        }

        return $licensePeriods;
    }

    /**
     * Find end date for a specific module based on product codes
     */
    protected function findEndDateForModule(array $productCodes, array $licensePeriods, string $startDate, string $handoverId): ?string
    {
        foreach ($licensePeriods as $period) {
            $intersection = array_intersect($productCodes, $period['product_codes']);

            if (!empty($intersection)) {
                return $period['end_date'];
            }
        }

        return null;
    }

    /**
     * Get seat limit for a specific module from quotation details
     */
    private function getSeatLimitForModule(SoftwareHandover $record, array $productCodes): int
    {
        $allPiIds = [];
        if (!empty($record->software_hardware_pi)) {
            $pis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($pis)) {
                $allPiIds = array_merge($allPiIds, $pis);
            }
        }
        if (!empty($record->proforma_invoice_product)) {
            $pis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($pis)) {
                $allPiIds = array_merge($allPiIds, $pis);
            }
        }

        if (empty($allPiIds)) {
            return 0;
        }

        $yearOneSeats = null;
        $fallbackSeats = null;
        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                ->with('product')
                ->orderBy('sort_order')
                ->get();

            foreach ($details as $detail) {
                if ($detail->product && in_array($detail->product->code, $productCodes)) {
                    $qty = (int) ($detail->quantity ?? 0);
                    $year = strtolower(trim((string) ($detail->year ?? '')));

                    if ($fallbackSeats === null) {
                        $fallbackSeats = $qty;
                    }

                    if ($year === '' || str_starts_with($year, 'year 1')) {
                        $yearOneSeats = max((int) ($yearOneSeats ?? 0), $qty);
                    }

                    \Illuminate\Support\Facades\Log::info("Found seat for module", [
                        'product_code' => $detail->product->code,
                        'quantity' => $qty,
                        'quotation_id' => $quotation->id,
                        'year' => $detail->year,
                    ]);
                }
            }
        }

        $seat = $yearOneSeats ?? $fallbackSeats ?? 0;
        return max($seat, 1);
    }

    private function getTotalPaidMonthsFromRecord(SoftwareHandover $record): int
    {
        $allPiIds = [];
        if (!empty($record->software_hardware_pi)) {
            $productPis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }
        if (!empty($record->proforma_invoice_product)) {
            $hrdfPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        $totalPaidMonths = 0;
        if (!empty($allPiIds)) {
            $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $record->project_code);
            foreach ($licensePeriods as $period) {
                if ($period['subscription_period'] > $totalPaidMonths) {
                    $totalPaidMonths = $period['subscription_period'];
                }
            }
        }

        return $totalPaidMonths ?: 12;
    }

    private function formatDuration(int $years, int $months): string
    {
        $parts = [];

        if ($years > 0) {
            $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        }

        if ($months > 0) {
            $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        }

        if (empty($parts)) {
            return '0 months';
        }

        return implode(' and ', $parts);
    }

    /**
     * Get module subscription periods for implementer reference table
     */
    public function getModuleSubscriptionPeriods(): array
    {
        // This will be populated when a record is selected for action
        // For now, return empty array - will be updated when implementing the action modal
        return [];
    }

    /**
     * Get subscription periods from quotations for a specific software handover
     */
    protected function getSubscriptionPeriodsForHandover(SoftwareHandover $record): array
    {
        // Get all PI IDs from software_hardware_pi and proforma_invoice_product
        $allPiIds = [];

        if (!empty($record->software_hardware_pi)) {
            $productPis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_product)) {
            $hrdfPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return [];
        }

        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        $moduleGroups = [
            'Attendance' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Leave' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Claim' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Payroll' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Appraisal' => ['TCL_APPRAISAL USER-NEW'],
            'Hire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
            'Access' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
            'PowerBI' => ['TCL_POWER BI'],
        ];

        $periodsByModule = [];

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)->with('product')->get();

            foreach ($details as $detail) {
                if (!$detail->product) {
                    continue;
                }

                foreach ($moduleGroups as $moduleName => $productCodes) {
                    if (in_array($detail->product->code, $productCodes)) {
                        if (!isset($periodsByModule[$moduleName])) {
                            $periodsByModule[$moduleName] = ['total_months' => 0];
                        }
                        $periodsByModule[$moduleName]['total_months'] += (int) ($detail->subscription_period ?? 0);
                    }
                }
            }
        }

        // Convert to years and months format
        $formattedPeriods = [];
        foreach ($periodsByModule as $moduleName => $data) {
            $totalMonths = $data['total_months'];
            $years = floor($totalMonths / 12);
            $months = $totalMonths % 12;

            $formattedPeriods[] = [
                'product' => $moduleName,
                'years' => $years,
                'months' => $months > 0 ? $months : '-'
            ];
        }

        return $formattedPeriods;
    }

    protected function getLicenseSetSegmentsForHandover(SoftwareHandover $record): array
    {
        $allPiIds = [];

        if (!empty($record->software_hardware_pi)) {
            $productPis = is_string($record->software_hardware_pi)
                ? json_decode($record->software_hardware_pi, true)
                : $record->software_hardware_pi;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_product)) {
            $productPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (empty($allPiIds)) {
            return [];
        }

        $moduleGroups = [
            'Attendance' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Leave' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Claim' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Payroll' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Appraisal' => ['TCL_APPRAISAL USER-NEW'],
            'Hire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
            'Access' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
            'PowerBI' => ['TCL_POWER BI'],
        ];

        $details = \App\Models\QuotationDetail::query()
            ->whereIn('quotation_id', $allPiIds)
            ->with('product')
            ->orderBy('quotation_id')
            ->orderBy('sort_order')
            ->get();

        $grouped = [];

        foreach ($details as $detail) {
            if (!$detail->product || !$detail->product->code) {
                continue;
            }

            $moduleName = null;
            foreach ($moduleGroups as $module => $codes) {
                if (in_array($detail->product->code, $codes)) {
                    $moduleName = $module;
                    break;
                }
            }

            if (!$moduleName) {
                continue;
            }

            $yearLabel = $this->normalizeYearLabel((string) ($detail->year ?? 'Year 1'));
            $yearOrder = $this->extractYearOrder($yearLabel);
            $groupKey = $moduleName . '|' . $yearLabel;

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'module_name' => $moduleName,
                    'year_label' => $yearLabel,
                    'year_order' => $yearOrder,
                    'seat_limit' => (int) ($detail->quantity ?? 0),
                    'subscription_period' => (int) ($detail->subscription_period ?? $detail->product->subscription_period ?? 12),
                ];
            } else {
                $grouped[$groupKey]['seat_limit'] = max($grouped[$groupKey]['seat_limit'], (int) ($detail->quantity ?? 0));
                $grouped[$groupKey]['subscription_period'] = max(
                    $grouped[$groupKey]['subscription_period'],
                    (int) ($detail->subscription_period ?? $detail->product->subscription_period ?? 12)
                );
            }
        }

        $segmentsByModule = [];
        foreach ($grouped as $segment) {
            $segmentsByModule[$segment['module_name']][] = [
                'year_label' => $segment['year_label'],
                'year_order' => $segment['year_order'],
                'seat_limit' => $segment['seat_limit'],
                'subscription_period' => $segment['subscription_period'],
            ];
        }

        foreach ($segmentsByModule as &$moduleSegments) {
            usort($moduleSegments, static fn (array $a, array $b): int => $a['year_order'] <=> $b['year_order']);
        }
        unset($moduleSegments);

        return $segmentsByModule;
    }

    private function normalizeYearLabel(string $year): string
    {
        $trimmed = trim($year);
        if ($trimmed === '') {
            return 'Year 1';
        }

        if (preg_match('/year\s*(\d+)/i', $trimmed, $matches)) {
            return 'Year ' . $matches[1];
        }

        return $trimmed;
    }

    private function extractYearOrder(string $yearLabel): int
    {
        if (preg_match('/year\s*(\d+)/i', $yearLabel, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    /**
     * Create CRM account for the handover
     */
    protected function createCRMAccount(SoftwareHandover $record, string $handoverId, string $accountOption = 'new')
    {
        try {
            // Check for existing customer with CRM account
            $existingCustomer = \App\Models\Customer::where('lead_id', $record->lead_id)
                ->whereNotNull('hr_account_id')
                ->whereNotNull('hr_company_id')
                ->whereNotNull('hr_user_id')
                ->first();

            if ($existingCustomer) {
                Log::info("Reusing existing HRV2 account from customer table", [
                    'handover_id' => $handoverId,
                    'customer_id' => $existingCustomer->id,
                    'hr_account_id' => $existingCustomer->hr_account_id,
                ]);

                $record->update([
                    'hr_account_id' => $existingCustomer->hr_account_id,
                    'hr_company_id' => $existingCustomer->hr_company_id,
                    'hr_user_id' => $existingCustomer->hr_user_id,
                ]);

                return [
                    'success' => true,
                    'reused' => true,
                    'data' => [
                        'accountId' => $existingCustomer->hr_account_id,
                        'companyId' => $existingCustomer->hr_company_id,
                        'userId' => $existingCustomer->hr_user_id,
                    ]
                ];
            }

            // Check for existing software handover with CRM account
            $existingHandover = SoftwareHandover::where('lead_id', $record->lead_id)
                ->whereNotNull('hr_company_id')
                ->whereNotNull('hr_account_id')
                ->whereNotNull('hr_user_id')
                ->where('id', '!=', $record->id)
                ->first();

            if ($accountOption === 'existing' && $existingHandover) {
                $record->update([
                    'hr_account_id' => $existingHandover->hr_account_id,
                    'hr_company_id' => $existingHandover->hr_company_id,
                    'hr_user_id' => $existingHandover->hr_user_id,
                ]);

                return [
                    'success' => true,
                    'reused' => true,
                    'data' => [
                        'accountId' => $existingHandover->hr_account_id,
                        'companyId' => $existingHandover->hr_company_id,
                        'userId' => $existingHandover->hr_user_id,
                    ]
                ];
            }

            // No existing account - create new one
            $lead = $record->lead;

            $countryService = app(\App\Services\CountryService::class);
            $countries = $countryService->getCountries();
            $leadCountry = $lead->country ?? 'Malaysia';
            $countryData = collect($countries)->firstWhere('name', $leadCountry);

            if (!$countryData) {
                $countryData = collect($countries)->firstWhere('id', 132);
            }

            $credentials = $this->getOrCreateCustomerCredentials($record, $handoverId);
            $phoneData = $this->processPhoneNumber($record, $countryData, $handoverId);

            $crmAccountData = [
                'company_name' => $record->company_name,
                'country_id' => (int)$countryData['id'],
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'phone_code' => $phoneData['phone_code'],
                'phone' => $phoneData['clean_phone'],
                'timezone' => $countryData['timezone'] ?? 'Asia/Kuala_Lumpur',
            ];

            $crmService = app(\App\Services\HRV2LicenseService::class);
            $crmResult = $crmService->createAccount($crmAccountData);

            if ($crmResult['success']) {
                $this->saveCRMAccountData($record, $crmResult['data'], $credentials, $phoneData['raw_phone']);
            } else {
                Log::error("HRV2 Account creation failed", [
                    'handover_id' => $handoverId,
                    'error' => $crmResult['error'],
                ]);
            }

            return $crmResult;

        } catch (\Exception $e) {
            Log::error("HRV2 Account creation exception", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get existing customer credentials or generate new ones
     */
    protected function getOrCreateCustomerCredentials(SoftwareHandover $record, string $handoverId): array
    {
        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();
        $activationController = app(\App\Http\Controllers\CustomerActivationController::class);

        if ($customer) {
            return [
                'email' => $customer->email,
                'password' => $customer->plain_password,
                'name' => $customer->name,
                'customer' => $customer,
            ];
        }

        $credentials = $activationController->generateCRMAccountCredentials(
            $record->lead_id,
            $handoverId
        );

        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();

        return [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'name' => $credentials['name'],
            'customer' => $customer,
        ];
    }

    /**
     * Process phone number from implementation PICs
     */
    protected function processPhoneNumber(SoftwareHandover $record, array $countryData, string $handoverId): array
    {
        $implementationPics = json_decode($record->implementation_pics, true);

        if (!is_array($implementationPics) || empty($implementationPics)) {
            throw new \Exception("No implementation PICs found for handover {$handoverId}");
        }

        $rawPhone = $implementationPics[0]['pic_phone_impl'] ?? null;
        $firstPicName = $implementationPics[0]['pic_name_impl'] ?? null;

        if (!$rawPhone) {
            throw new \Exception("No phone number found in implementation PICs for handover {$handoverId}");
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $phoneCode = $countryData['phone_code'];
        $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);

        if (substr($cleanPhone, 0, strlen($phoneCodeDigits)) === $phoneCodeDigits) {
            $cleanPhone = substr($cleanPhone, strlen($phoneCodeDigits));
        }

        $cleanPhone = ltrim($cleanPhone, '0');

        return [
            'raw_phone' => $rawPhone,
            'clean_phone' => $cleanPhone,
            'phone_code' => $phoneCode,
            'pic_name' => $firstPicName,
        ];
    }

    /**
     * Save CRM account data to database
     */
    protected function saveCRMAccountData(SoftwareHandover $record, array $crmData, array $credentials, string $rawPhone): void
    {
        $lead = $record->lead;

        $record->update([
            'hr_account_id' => $crmData['accountId'] ?? null,
            'hr_company_id' => $crmData['companyId'] ?? null,
            'hr_user_id' => $crmData['userId'] ?? null,
        ]);

        $customer = $credentials['customer'];

        if (!$customer) {
            $customer = \App\Models\Customer::where('lead_id', $record->lead_id)->first();
        }

        if ($customer) {
            $customer->update([
                'hr_account_id' => $crmData['accountId'] ?? null,
                'hr_company_id' => $crmData['companyId'] ?? null,
                'hr_user_id' => $crmData['userId'] ?? null,
                'sw_id' => $record->id,
                'phone' => $rawPhone,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            \App\Models\Customer::create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'original_email' => $lead->companyDetail->email ?? $lead->email ?? $credentials['email'],
                'lead_id' => $lead->id,
                'sw_id' => $record->id,
                'company_name' => $record->company_name,
                'phone' => $rawPhone,
                'password' => \Illuminate\Support\Facades\Hash::make($credentials['password']),
                'plain_password' => $credentials['password'],
                'status' => 'active',
                'email_verified_at' => now(),
                'hr_account_id' => $crmData['accountId'] ?? null,
                'hr_company_id' => $crmData['companyId'] ?? null,
                'hr_user_id' => $crmData['userId'] ?? null,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-license');
    }
}
