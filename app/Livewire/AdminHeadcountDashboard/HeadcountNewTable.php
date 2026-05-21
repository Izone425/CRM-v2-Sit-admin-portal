<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHeadcountDashboard/HeadcountNewTable.php

namespace App\Livewire\AdminHeadcountDashboard;

use App\Models\HeadcountHandover;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HeadcountNewTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getNewHeadcountHandovers()
    {
        return HeadcountHandover::with(['lead.companyDetail', 'lead.salespersonUser'])
            ->where('status', 'New')
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewHeadcountHandovers())
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('salesperson_name')
                    ->label('Salesperson')
                    ->getStateUsing(function (HeadcountHandover $record) {
                        if ($record->lead && $record->lead->salesperson) {
                            $user = User::find($record->lead->salesperson);
                            return $user ? $user->name : 'N/A';
                        }
                        return 'N/A';
                    })
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue; font-weight: bold;">New</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->recordClasses(function (HeadcountHandover $record) {
                if (\App\Models\HrSalesInvoice::where('handover_id', $record->formatted_handover_id)->exists()) {
                    return 'success';
                }
                return $record->reseller_id ? 'reseller-row' : null;
            })
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('generate_invoice')
                        ->label('Generate Sales Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(function (HeadcountHandover $record) {
                            // Only show if no sales invoice exists yet
                            return !\App\Models\HrSalesInvoice::where('handover_id', $record->formatted_handover_id)->exists();
                        })
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $companyName = $record->lead->companyDetail->company_name ?? 'Unknown';
                            return "Generate Sales Invoice | {$record->formatted_handover_id} | {$companyName}";
                        })
                        ->modalSubmitActionLabel('Generate Sales Invoice')
                        ->mountUsing(function (\Filament\Forms\Form $form): void {
                            // Reset stale state before loading fresh defaults from the Repeater
                            $form->fill([]);
                        })
                        ->form([
                            Grid::make(1)
                                ->schema(function (Get $get, HeadcountHandover $record) {
                                    $sections = [];

                                    if (!empty($record->proforma_invoice_product)) {
                                        $productPiIds = is_array($record->proforma_invoice_product)
                                            ? $record->proforma_invoice_product
                                            : json_decode($record->proforma_invoice_product, true);

                                        if (is_array($productPiIds) && !empty($productPiIds)) {
                                            $quotations = \App\Models\Quotation::whereIn('id', $productPiIds)
                                                ->with(['lead.companyDetail', 'subsidiary'])->get();

                                            if ($quotations->isNotEmpty()) {
                                                $sections[] = Repeater::make('type_1_entries')
                                                    ->label(false)
                                                    ->schema([
                                                        Grid::make(3)->schema([
                                                            TextInput::make('pi_number')->label('PI Number')->readOnly(),
                                                            TextInput::make('company_name')->label('Company Name')->readOnly(),
                                                            TextInput::make('invoice_number')
                                                                ->label('Invoice Number')->required()->maxLength(13)
                                                                ->regex('/^[A-Z0-9-]+$/')
                                                                ->validationMessages(['regex' => 'Invoice number can only contain letters, numbers, and dashes.'])
                                                                ->live(onBlur: true)
                                                                ->extraAlpineAttributes(['x-on:input' => '$el.value = $el.value.toUpperCase();'])
                                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                                        ])
                                                    ])
                                                    ->default(function () use ($quotations) {
                                                        return $quotations->map(function ($q) {
                                                            return [
                                                                'quotation_id' => $q->id,
                                                                'pi_number' => $q->pi_reference_no ?? 'N/A',
                                                                'company_name' => ($q->subsidiary_id && $q->subsidiary) ? $q->subsidiary->company_name : ($q->lead?->companyDetail?->company_name ?? 'N/A'),
                                                                'invoice_number' => '',
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->addable(false)->deletable(false)->reorderable(false)->collapsible(false);
                                            }
                                        }
                                    }

                                    if (!empty($record->proforma_invoice_hrdf)) {
                                        $hrdfPiIds = is_array($record->proforma_invoice_hrdf)
                                            ? $record->proforma_invoice_hrdf
                                            : json_decode($record->proforma_invoice_hrdf, true);

                                        if (is_array($hrdfPiIds) && !empty($hrdfPiIds)) {
                                            $quotations = \App\Models\Quotation::whereIn('id', $hrdfPiIds)
                                                ->with(['lead.companyDetail', 'subsidiary'])->get();

                                            if ($quotations->isNotEmpty()) {
                                                $sections[] = Repeater::make('type_2_entries')
                                                    ->label(false)
                                                    ->schema([
                                                        Grid::make(3)->schema([
                                                            TextInput::make('pi_number')->label('PI Number')->readOnly(),
                                                            TextInput::make('company_name')->label('Company Name')->readOnly(),
                                                            TextInput::make('invoice_number')
                                                                ->label('Invoice Number')->required()->maxLength(13)
                                                                ->regex('/^[A-Z0-9-]+$/')
                                                                ->validationMessages(['regex' => 'Invoice number can only contain letters, numbers, and dashes.'])
                                                                ->live(onBlur: true)
                                                                ->extraAlpineAttributes(['x-on:input' => '$el.value = $el.value.toUpperCase();'])
                                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                                        ])
                                                    ])
                                                    ->default(function () use ($quotations) {
                                                        return $quotations->map(function ($q) {
                                                            return [
                                                                'quotation_id' => $q->id,
                                                                'pi_number' => $q->pi_reference_no ?? 'N/A',
                                                                'company_name' => ($q->subsidiary_id && $q->subsidiary) ? $q->subsidiary->company_name : ($q->lead?->companyDetail?->company_name ?? 'N/A'),
                                                                'invoice_number' => '',
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->addable(false)->deletable(false)->reorderable(false)->collapsible(false);
                                            }
                                        }
                                    }

                                    if (empty($sections)) {
                                        $sections[] = \Filament\Forms\Components\Placeholder::make('no_pi_data')
                                            ->label(false)
                                            ->content(new HtmlString('<div style="background-color:#FEF3C7; border-left:4px solid #F59E0B; padding:12px; border-radius:4px;"><p style="color:#92400E; font-weight:600; margin:0;">No PI Data Available</p></div>'));
                                    }

                                    return $sections;
                                }),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            try {
                                $handoverId = $record->formatted_handover_id;
                                $companyDetail = $record->lead->companyDetail;
                                $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';
                                $softwareSolutions = ['software', 'software_new_sales', 'software_renewal_sales', 'new_sales_addon', 'renewal_sales_addon'];

                                // Save PI tracking data from form
                                $updateData = [];
                                if (!empty($data['type_1_entries'])) {
                                    $updateData['product_pi_invoice_data'] = json_encode($data['type_1_entries']);
                                }
                                if (!empty($data['type_2_entries'])) {
                                    $updateData['hrdf_pi_invoice_data'] = json_encode($data['type_2_entries']);
                                }
                                if (!empty($updateData)) {
                                    $record->update($updateData);
                                }

                                $allPiIds = [];
                                foreach (['proforma_invoice_product', 'proforma_invoice_hrdf'] as $piField) {
                                    $ids = is_array($record->$piField) ? $record->$piField : json_decode($record->$piField, true);
                                    if (is_array($ids)) $allPiIds = array_merge($allPiIds, $ids);
                                }
                                $allPiIds = array_unique(array_filter($allPiIds));

                                foreach (\App\Models\Quotation::whereIn('id', $allPiIds)->get() as $quotation) {
                                    if (\App\Models\HrSalesInvoice::where('handover_id', $handoverId)->where('quotation_id', $quotation->id)->exists()) continue;

                                    $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                                        ->with('product')->orderBy('sort_order')->get();
                                    $softwareDetails = $details->filter(fn ($d) => $d->product && in_array($d->product->solution, $softwareSolutions));
                                    if ($softwareDetails->isEmpty()) continue;

                                    $invoiceNo = \App\Models\HrSalesInvoice::generateInvoiceNo();
                                    $salesInvoice = \App\Models\HrSalesInvoice::create([
                                        'software_handover_id' => null,
                                        'handover_id' => $handoverId,
                                        'quotation_id' => $quotation->id,
                                        'lead_id' => $record->lead_id,
                                        'invoice_no' => $invoiceNo,
                                        'invoice_date' => now(),
                                        'company_name' => $companyName,
                                        'country' => $companyDetail->country ?? null,
                                        'pi_no' => $quotation->pi_reference_no ?? null,
                                        'quotation_reference_no' => $quotation->quotation_reference_no ?? null,
                                        'headcount' => $quotation->headcount ?? null,
                                        'currency' => $quotation->currency ?? 'MYR',
                                        'sales_type' => $quotation->sales_type ?? null,
                                        'subscription_period' => $quotation->subscription_period ?? null,
                                        'tax_rate' => $quotation->tax_rate ?? 0,
                                        'sales_amount' => $softwareDetails->sum('total_before_tax'),
                                        'invoice_amount' => $softwareDetails->sum('total_after_tax'),
                                        'payment_method' => null,
                                        'payment_status' => 'unpaid',
                                        'auto_renewal' => 'Disabled',
                                        'created_by_name' => auth()->user()->name ?? 'System',
                                        'status' => 'pending',
                                    ]);

                                    foreach ($softwareDetails as $detail) {
                                        $productCode = $detail->product->code ?? '';
                                        $licenseType = match (true) {
                                            str_contains(strtoupper($productCode), 'TCL_TA') => 'TimeTec TA',
                                            str_contains(strtoupper($productCode), 'TCL_LEAVE') => 'TimeTec Leave',
                                            str_contains(strtoupper($productCode), 'TCL_CLAIM') => 'TimeTec Claim',
                                            str_contains(strtoupper($productCode), 'TCL_PAYROLL') => 'TimeTec Payroll',
                                            default => $productCode,
                                        };
                                        \App\Models\HrSalesInvoiceItem::create([
                                            'hr_sales_invoice_id' => $salesInvoice->id,
                                            'product_id' => $detail->product_id,
                                            'product_code' => $productCode,
                                            'description' => $detail->description,
                                            'license_type' => $licenseType,
                                            'quantity' => $detail->quantity ?? 0,
                                            'subscription_period' => $detail->subscription_period ?? 12,
                                            'license_start_date' => $detail->license_start_date,
                                            'license_end_date' => $detail->license_end_date,
                                            'unit_price' => $detail->unit_price ?? 0,
                                            'discount' => $detail->discount ?? 0,
                                            'taxation' => $detail->taxation ?? 0,
                                            'tax_code' => $detail->tax_code ?? null,
                                            'year' => $detail->year ?? null,
                                            'tariff_code' => $detail->tariff_code ?? null,
                                            'total_before_tax' => $detail->total_before_tax ?? 0,
                                            'total_after_tax' => $detail->total_after_tax ?? 0,
                                            'sort_order' => $detail->sort_order ?? 0,
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('Sales Invoice Generated')
                                    ->success()
                                    ->body("Sales invoice created for {$handoverId}. Payment must be completed before marking as completed.")
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to generate sales invoice: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color(function (HeadcountHandover $record) {
                            return self::checkAllInvoicesPaid($record) ? 'success' : 'danger';
                        })
                        ->visible(function (HeadcountHandover $record) {
                            // Only show if sales invoice exists
                            return \App\Models\HrSalesInvoice::where('handover_id', $record->formatted_handover_id)->exists();
                        })
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = $record->formatted_handover_id;
                            $companyName = $record->lead->companyDetail->company_name ?? 'Unknown Company';
                            return "Headcount Handover | {$formattedId} | {$companyName}";
                        })
                        ->modalSubmitActionLabel('Mark as Completed')
                        ->modalWidth(MaxWidth::ThreeExtraLarge)
                        ->mountUsing(function (\Filament\Forms\Form $form): void {
                            // Clear any stale state leaked from the generate_invoice action's Repeater
                            $form->fill([]);
                        })
                        ->form(function (HeadcountHandover $record) {
                            $allPaid = self::checkAllInvoicesPaid($record);
                            $invoiceNumbers = self::getAutoCountInvoiceNumbers($record);

                            $html = '';
                            if (!$allPaid) {
                                $unpaidItems = '';
                                foreach ($invoiceNumbers as $invoiceNo) {
                                    $debtorAging = \Illuminate\Support\Facades\DB::table('debtor_agings')
                                        ->where('invoice_number', $invoiceNo)->first();
                                    if (!$debtorAging) {
                                        $unpaidItems .= '<li>' . e($invoiceNo) . ' — <strong style="color:#6B7280;">NOT FOUND</strong></li>';
                                    } elseif ((float) $debtorAging->outstanding !== 0.0) {
                                        $unpaidItems .= '<li>' . e($invoiceNo) . ' — <strong style="color:#DC2626;">OUTSTANDING: ' . number_format($debtorAging->outstanding, 2) . '</strong></li>';
                                    } else {
                                        $unpaidItems .= '<li>' . e($invoiceNo) . ' — <strong style="color:#059669;">PAID</strong></li>';
                                    }
                                }
                                $html .= '<div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:8px; padding:12px 16px; margin-bottom:16px;">
                                    <p style="color:#991B1B; font-weight:600; margin:0 0 8px 0;">Payment Required</p>
                                    <p style="color:#991B1B; margin:0 0 4px 0; font-size:13px;">AutoCount invoice payment status:</p>
                                    <ul style="margin:0; padding-left:20px; font-size:13px;">' . $unpaidItems . '</ul>
                                </div>';
                            }
                            $html .= self::buildLicenseSummaryHtml($record) ?? '';

                            return [
                                \Filament\Forms\Components\Placeholder::make('license_info')
                                    ->label(false)
                                    ->content(new HtmlString($html)),
                            ];
                        })
                        ->modalSubmitAction(function (HeadcountHandover $record, \Filament\Actions\StaticAction $action) {
                            if (!self::checkAllInvoicesPaid($record)) {
                                return $action->disabled();
                            }
                            return $action;
                        })
                        ->action(function (HeadcountHandover $record): void {
                            try {
                                // Update status to Completed
                                $record->update([
                                    'status' => 'Completed',
                                    'completed_by' => auth()->id(),
                                    'completed_at' => now(),
                                ]);

                                // === HR License Activation (addon headcount) ===
                                $handoverId = $record->formatted_handover_id;
                                $companyDetail = $record->lead->companyDetail;
                                $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                                // Get CRM account IDs from customer table
                                $customer = \App\Models\Customer::where('lead_id', $record->lead_id)
                                    ->whereNotNull('hr_account_id')
                                    ->whereNotNull('hr_company_id')
                                    ->whereNotNull('hr_user_id')
                                    ->first();

                                if ($customer) {
                                    $accountId = (int) $customer->hr_account_id;
                                    $companyId = (int) $customer->hr_company_id;

                                    // Extract autocount invoice numbers from PI data
                                    $autocountInvoiceNumbers = [];
                                    foreach (['product_pi_invoice_data', 'hrdf_pi_invoice_data'] as $piField) {
                                        $piData = $updateData[$piField] ?? null;
                                        if (empty($piData)) continue;
                                        $decoded = is_string($piData) ? json_decode($piData, true) : $piData;
                                        if (is_string($decoded)) $decoded = json_decode($decoded, true);
                                        if (!is_array($decoded)) continue;
                                        foreach ($decoded as $entry) {
                                            if (!empty($entry['invoice_number'])) {
                                                $autocountInvoiceNumbers[] = $entry['invoice_number'];
                                            }
                                        }
                                    }

                                    // Detect modules from quotation products
                                    $allPiIds = [];
                                    foreach (['proforma_invoice_product', 'proforma_invoice_hrdf'] as $piField) {
                                        $ids = is_array($record->$piField) ? $record->$piField : json_decode($record->$piField, true);
                                        if (is_array($ids)) $allPiIds = array_merge($allPiIds, $ids);
                                    }
                                    $allPiIds = array_unique(array_filter($allPiIds));

                                    $moduleProductCodes = [
                                        'ta' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                        'tl' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                        'tc' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                        'tp' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
                                    ];

                                    $moduleSelections = ['ta' => false, 'tl' => false, 'tc' => false, 'tp' => false];
                                    $moduleSeatLimits = [];

                                    if (!empty($allPiIds)) {
                                        $quotationDetails = \App\Models\QuotationDetail::whereIn('quotation_id', $allPiIds)
                                            ->join('products', 'quotation_details.product_id', '=', 'products.id')
                                            ->select('quotation_details.*', 'products.code as product_code')
                                            ->get();

                                        foreach ($moduleProductCodes as $modKey => $codes) {
                                            $matched = $quotationDetails->filter(fn ($d) => in_array($d->product_code, $codes));
                                            if ($matched->isNotEmpty()) {
                                                $moduleSelections[$modKey] = true;
                                                $year1 = $matched->filter(fn ($d) => ($d->year ?? '') === 'Year 1');
                                                $moduleSeatLimits[$modKey] = max(
                                                    $year1->isNotEmpty() ? $year1->max('quantity') : 0,
                                                    $matched->max('quantity'),
                                                    1
                                                );
                                            }
                                        }
                                    }

                                    if (array_filter($moduleSelections) && !empty($allPiIds)) {
                                        $crmService = app(\App\Services\HRV2LicenseService::class);
                                        $moduleMapping = ['ta' => 'Attendance', 'tl' => 'Leave', 'tc' => 'Claim', 'tp' => 'Payroll'];

                                        // Group quotation details by year to handle multi-year licenses
                                        $detailsByYear = $quotationDetails->groupBy(fn ($d) => $d->year ?? 'Year 1');
                                        $yearKeys = $detailsByYear->keys()->sort()->values();

                                        // Call CRM API once per year with correct dates
                                        foreach ($yearKeys as $yearKey) {
                                            $yearDetails = $detailsByYear[$yearKey];
                                            $firstDetail = $yearDetails->sortBy('sort_order')->first();
                                            $paidStartDate = null;
                                            $paidEndDate = null;
                                            $totalPaidMonths = (int) ($firstDetail->subscription_period ?? 12);

                                            // Get dates from license_start_date / license_end_date
                                            if ($firstDetail->license_start_date) {
                                                $paidStartDate = \Illuminate\Support\Carbon::parse($firstDetail->license_start_date);
                                            }
                                            if ($firstDetail->license_end_date) {
                                                $paidEndDate = \Illuminate\Support\Carbon::parse($firstDetail->license_end_date);
                                            }

                                            // Fallback: parse from description
                                            if ((!$paidStartDate || !$paidEndDate) && $firstDetail->description) {
                                                if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})/', strip_tags($firstDetail->description), $matches)) {
                                                    try {
                                                        if (!$paidStartDate) $paidStartDate = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', trim($matches[1]));
                                                        if (!$paidEndDate) $paidEndDate = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', trim($matches[2]));
                                                    } catch (\Exception $e) {}
                                                }
                                            }

                                            if (!$paidEndDate) $paidEndDate = \Illuminate\Support\Carbon::now()->addMonths($totalPaidMonths)->subDay();
                                            if (!$paidStartDate) $paidStartDate = $paidEndDate->copy()->subMonths($totalPaidMonths)->addDay();

                                            // Determine which modules are in this year
                                            $yearModuleSelections = ['ta' => false, 'tl' => false, 'tc' => false, 'tp' => false];
                                            foreach ($moduleProductCodes as $modKey => $codes) {
                                                if ($yearDetails->filter(fn ($d) => in_array($d->product_code, $codes))->isNotEmpty()) {
                                                    $yearModuleSelections[$modKey] = true;
                                                }
                                            }
                                            if (!array_filter($yearModuleSelections)) continue;

                                            // Call CRM API for this year
                                            $paidResult = $crmService->addPaidApplicationLicenses(
                                                new \App\Models\SoftwareHandover(['proforma_invoice_product' => json_encode($allPiIds)]),
                                                $accountId, $companyId, $yearModuleSelections, $handoverId,
                                                $paidStartDate, $paidEndDate
                                            );

                                            \Illuminate\Support\Facades\Log::info("Headcount addon paid license ({$yearKey})", [
                                                'handover_id' => $handoverId, 'year' => $yearKey,
                                                'start' => $paidStartDate->format('Y-m-d'), 'end' => $paidEndDate->format('Y-m-d'),
                                                'modules' => array_keys(array_filter($yearModuleSelections)),
                                            ]);

                                            // Create HrLicense records — use CRM API result dates, not quotation dates
                                            // to avoid duplicates (API already handles period splitting)
                                            foreach ($moduleMapping as $modKey => $appName) {
                                                if (!$yearModuleSelections[$modKey]) continue;
                                                $seatLimit = $moduleSeatLimits[$modKey] ?? 1;

                                                // Only create one HrLicense per module per year using quotation dates directly
                                                \App\Models\HrLicense::create([
                                                    'software_handover_id' => null,
                                                    'handover_id' => $handoverId,
                                                    'type' => 'PAID',
                                                    'invoice_no' => $autocountInvoiceNumbers[0] ?? '-',
                                                    'auto_count_invoice_no' => $autocountInvoiceNumbers[0] ?? '-',
                                                    'company_name' => $companyName,
                                                    'license_category' => 'Subscriber',
                                                    'license_type' => 'TimeTec ' . $appName,
                                                    'unit' => $seatLimit,
                                                    'user_limit' => $seatLimit,
                                                    'total_user' => 0,
                                                    'total_login' => 0,
                                                    'month' => $totalPaidMonths,
                                                    'start_date' => $paidStartDate,
                                                    'end_date' => $paidEndDate,
                                                    'status' => 'Enabled',
                                                    'auto_renewal' => 'Enabled',
                                                    'period_id' => $paidResult['results'][$appName]['data']['periodId'] ?? null,
                                                ]);
                                            }
                                        }

                                        Notification::make()
                                            ->title('HR License Created')
                                            ->success()
                                            ->body('Paid licenses activated via CRM API for ' . $yearKeys->count() . ' year(s).')
                                            ->send();
                                    }

                                    // === Generate Official Receipt (invoice already paid at this point) ===
                                    try {
                                        $salesInvoices = \App\Models\HrSalesInvoice::where('handover_id', $handoverId)->get();
                                        foreach ($salesInvoices as $index => $salesInvoice) {
                                            if (\App\Models\HrOfficialReceipt::where('invoice_no', $salesInvoice->invoice_no)->exists()) continue;

                                            $prefix = 'OR' . now()->format('ym');
                                            $lastOr = \App\Models\HrOfficialReceipt::where('or_no', 'like', $prefix . '%')
                                                ->orderBy('or_no', 'desc')->value('or_no');
                                            $nextSeq = $lastOr ? ((int) substr($lastOr, strlen($prefix))) + 1 : 1;
                                            $orNo = $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);

                                            \App\Models\HrOfficialReceipt::create([
                                                'or_no' => $orNo,
                                                'receipt_date' => now()->toDateString(),
                                                'company_name' => $companyName,
                                                'subscriber_name' => $record->lead->companyDetail->name ?? null,
                                                'description' => 'Official Receipt for ' . $salesInvoice->invoice_no,
                                                'currency' => $salesInvoice->currency ?? 'MYR',
                                                'amount' => $salesInvoice->invoice_amount ?? 0,
                                                'status' => 'paid',
                                                'created_by' => auth()->user()->name ?? 'System',
                                                'invoice_no' => $salesInvoice->invoice_no,
                                                'payment_method' => $salesInvoice->payment_method,
                                                'software_handover_id' => null,
                                                'handover_id' => $handoverId,
                                                'autocount_invoice_no' => $autocountInvoiceNumbers[$index] ?? null,
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to generate official receipts for headcount", [
                                            'handover_id' => $handoverId, 'error' => $e->getMessage(),
                                        ]);
                                    }

                                } else {
                                    \Illuminate\Support\Facades\Log::warning("No CRM account found for headcount handover", [
                                        'handover_id' => $handoverId, 'lead_id' => $record->lead_id,
                                    ]);
                                }

                                // === Email Notification ===
                                // Get salesperson from lead->salesperson (user ID)
                                $salesperson = null;
                                if ($record->lead && $record->lead->salesperson) {
                                    $salesperson = User::find($record->lead->salesperson);
                                }

                                $completedBy = auth()->user();

                                // Send email notification to salesperson
                                if ($salesperson && $salesperson->email) {
                                    try {
                                        Mail::send('emails.headcount-handover-completed', [
                                            'handoverId' => $handoverId,
                                            'companyName' => $companyName,
                                            'salesperson' => $salesperson,
                                            'completedBy' => $completedBy,
                                            'completedAt' => now(),
                                            'record' => $record
                                        ], function ($mail) use ($salesperson, $completedBy, $handoverId) {
                                            $mail->to($salesperson->email, $salesperson->name)
                                                ->subject("HEADCOUNT HANDOVER | {$handoverId} | COMPLETED");
                                        });

                                        \Illuminate\Support\Facades\Log::info("Headcount handover completion email sent", [
                                            'handover_id' => $handoverId,
                                            'salesperson_email' => $salesperson->email,
                                            'completed_by' => $completedBy->email
                                        ]);

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("Failed to send headcount handover completion email", [
                                            'error' => $e->getMessage(),
                                            'handover_id' => $handoverId
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->title('Headcount Handover Completed')
                                    ->body("Headcount handover {$handoverId} has been marked as completed and notification sent to salesperson.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to complete headcount handover: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = $record->formatted_handover_id;
                            return "Reject Headcount Handover {$formattedId}";
                        })
                        ->modalSubmitActionLabel('Reject Handover')
                        ->form([
                            Textarea::make('reject_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Enter the reason for rejection...')
                                ->rows(4)
                                ->maxLength(1000)
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        ])
                        ->action(function (HeadcountHandover $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'rejected_by' => auth()->id(),
                                'rejected_at' => now(),
                                'reject_reason' => $data['reject_reason'],
                            ]);

                            $handoverId = $record->formatted_handover_id;

                            Notification::make()
                                ->title('Headcount Handover Rejected')
                                ->body("Headcount handover {$handoverId} has been rejected.")
                                ->warning()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('300s');
    }

    public static function buildLicenseSummaryHtml(HeadcountHandover $record): ?string
    {
        // Gather PI IDs
        $allPiIds = [];
        foreach (['proforma_invoice_product', 'proforma_invoice_hrdf'] as $piField) {
            $ids = is_array($record->$piField) ? $record->$piField : json_decode($record->$piField, true);
            if (is_array($ids)) $allPiIds = array_merge($allPiIds, $ids);
        }
        $allPiIds = array_unique(array_filter($allPiIds));
        if (empty($allPiIds)) return null;

        $moduleProductCodes = [
            'ta' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'tl' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'tc' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'tp' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
        ];
        $moduleNames = ['ta' => 'TimeTec Attendance', 'tl' => 'TimeTec Leave', 'tc' => 'TimeTec Claim', 'tp' => 'TimeTec Payroll'];

        $quotationDetails = \App\Models\QuotationDetail::whereIn('quotation_id', $allPiIds)
            ->join('products', 'quotation_details.product_id', '=', 'products.id')
            ->select('quotation_details.*', 'products.code as product_code')
            ->orderBy('quotation_details.sort_order')
            ->get();

        if ($quotationDetails->isEmpty()) return null;

        // Check CRM account
        $customer = \App\Models\Customer::where('lead_id', $record->lead_id)
            ->whereNotNull('hr_account_id')
            ->first();
        $hasAccount = $customer && $customer->hr_account_id;

        $rows = '';
        foreach ($moduleProductCodes as $modKey => $codes) {
            $matched = $quotationDetails->filter(fn ($d) => in_array($d->product_code, $codes));
            if ($matched->isEmpty()) continue;

            // Group by year
            $years = $matched->groupBy(fn ($d) => $d->year ?? 'Year 1');
            foreach ($years as $year => $items) {
                $item = $items->first();
                $seats = $items->max('quantity');

                // Get dates
                $startDate = '-';
                $endDate = '-';
                if ($item->license_start_date) {
                    $startDate = \Illuminate\Support\Carbon::parse($item->license_start_date)->format('d/m/Y');
                    $endDate = $item->license_end_date ? \Illuminate\Support\Carbon::parse($item->license_end_date)->format('d/m/Y') : '-';
                } elseif ($item->description && preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})/', strip_tags($item->description), $m)) {
                    $startDate = $m[1];
                    $endDate = $m[2];
                }

                $period = $item->subscription_period ?? 12;
                $rows .= '<tr>
                    <td style="padding:6px 10px; border-bottom:1px solid #e5e7eb;">' . e($moduleNames[$modKey]) . '</td>
                    <td style="padding:6px 10px; border-bottom:1px solid #e5e7eb; text-align:center;">' . e($seats) . '</td>
                    <td style="padding:6px 10px; border-bottom:1px solid #e5e7eb; text-align:center;">' . e($period) . ' months</td>
                    <td style="padding:6px 10px; border-bottom:1px solid #e5e7eb; text-align:center; white-space:nowrap;">' . e($startDate) . ' - ' . e($endDate) . '</td>
                    <td style="padding:6px 10px; border-bottom:1px solid #e5e7eb; text-align:center;">' . e($year) . '</td>
                </tr>';
            }
        }

        if (empty($rows)) return null;

        $accountStatus = $hasAccount
            ? '<span style="color:#059669; font-weight:600;">✓ CRM Account Found (ID: ' . e($customer->hr_account_id) . ')</span>'
            : '<span style="color:#DC2626; font-weight:600;">✗ No CRM Account — complete Software Handover first</span>';

        return '<div style="margin-top:16px; border:1px solid #d1d5db; border-radius:8px; overflow:hidden;">
            <div style="background:#1e40af; color:#fff; padding:10px 16px; font-weight:600; font-size:14px;">
                Licenses to be Created
            </div>
            <div style="padding:12px 16px; background:#f9fafb; font-size:13px;">
                ' . $accountStatus . '
            </div>
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:8px 10px; text-align:left; font-weight:600; border-bottom:2px solid #d1d5db; width:25%;">Module</th>
                        <th style="padding:8px 10px; text-align:center; font-weight:600; border-bottom:2px solid #d1d5db; width:10%;">Seats</th>
                        <th style="padding:8px 10px; text-align:center; font-weight:600; border-bottom:2px solid #d1d5db; width:15%;">Period</th>
                        <th style="padding:8px 10px; text-align:center; font-weight:600; border-bottom:2px solid #d1d5db; width:35%; white-space:nowrap;">Date Range</th>
                        <th style="padding:8px 10px; text-align:center; font-weight:600; border-bottom:2px solid #d1d5db; width:15%;">Year</th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
        </div>';
    }

    public static function getAutoCountInvoiceNumbers(HeadcountHandover $record): array
    {
        $invoiceNumbers = [];
        foreach (['product_pi_invoice_data', 'hrdf_pi_invoice_data'] as $field) {
            $piData = $record->getRawOriginal($field) ?? $record->$field;
            if (empty($piData)) continue;
            $decoded = is_string($piData) ? json_decode($piData, true) : $piData;
            if (is_string($decoded)) $decoded = json_decode($decoded, true);
            if (!is_array($decoded)) continue;
            foreach ($decoded as $entry) {
                if (!empty($entry['invoice_number'])) {
                    $invoiceNumbers[] = $entry['invoice_number'];
                }
            }
        }
        return $invoiceNumbers;
    }

    public static function checkAllInvoicesPaid(HeadcountHandover $record): bool
    {
        $invoiceNumbers = self::getAutoCountInvoiceNumbers($record);
        if (empty($invoiceNumbers)) return false;

        foreach ($invoiceNumbers as $invoiceNo) {
            $debtorAging = \Illuminate\Support\Facades\DB::table('debtor_agings')
                ->where('invoice_number', $invoiceNo)
                ->first();
            if (!$debtorAging || (float) $debtorAging->outstanding !== 0.0) {
                return false;
            }
        }
        return true;
    }

    protected function getPaymentStatusForInvoice(string $invoiceNo): array
    {
        try {
            $debtorAging = \Illuminate\Support\Facades\DB::table('debtor_agings')
                ->where('invoice_number', $invoiceNo)
                ->first();

            if (!$debtorAging) {
                return ['status' => 'Not Found', 'outstanding' => null, 'color' => 'gray'];
            }

            $outstanding = (float) $debtorAging->outstanding;

            if ($outstanding === 0.0) {
                return ['status' => 'Full Payment', 'outstanding' => 0, 'color' => 'green'];
            }

            return ['status' => 'Unpaid', 'outstanding' => $outstanding, 'color' => 'red'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error checking payment status: " . $e->getMessage());
            return ['status' => 'Error', 'outstanding' => null, 'color' => 'gray'];
        }
    }

    public function render()
    {
        return view('livewire.admin-headcount-dashboard.headcount-new-table');
    }
}
