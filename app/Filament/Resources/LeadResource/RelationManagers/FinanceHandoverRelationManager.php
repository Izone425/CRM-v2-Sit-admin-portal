<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\FinanceHandover;
use App\Models\HardwareHandoverV2;
use App\Models\Reseller;
use App\Models\ResellerInstallationPayment;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Mail;

class FinanceHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'financeHandover';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        return [
            Section::make('Step 1: Reseller Details')
                ->schema([
                    Grid::make(3)
                        ->schema([
                        Select::make('related_hardware_handovers')
                            ->label('Hardware Handover')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $leadId = $this->getOwnerRecord()->id;

                                return HardwareHandoverV2::where('lead_id', $leadId)
                                    ->get()
                                    ->mapWithKeys(function ($handover) {
                                        // Format the display name with ID and any relevant info
                                        $formattedId = $handover->formatted_handover_id;
                                        $displayName = $formattedId;

                                        // Add additional info if available (e.g., status, date)
                                        if ($handover->status) {
                                            $displayName .= ' - ' . $handover->status;
                                        }

                                        if ($handover->created_at) {
                                            $displayName .= ' (' . $handover->created_at->format('d M Y') . ')';
                                        }

                                        return [$handover->id => $displayName];
                                    })
                                    ->toArray();
                            }),

                        Select::make('reseller_id')
                            ->label('Reseller')
                            ->required()
                            ->options(function () {
                                return Reseller::pluck('company_name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live(),

                        TextInput::make('reseller_invoice_number')
                            ->label('Reseller Invoice Number')
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->required(),
                    ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('pic_name')
                                ->label('Name')
                                ->required()
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

                            TextInput::make('pic_phone')
                                ->label('HP Number')
                                ->required()
                                ->tel()
                                ->numeric(),

                            TextInput::make('pic_email')
                                ->label('Email Address')
                                ->required()
                                ->email(),
                        ]),
                ]),

            Section::make('Step 2: Payment Method')
                ->schema([
                    Radio::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'bank_transfer' => 'Via Bank Transfer',
                            'hrdf' => 'Via HRDF',
                        ])
                        ->inline()
                        ->inlineLabel(false)
                        ->required()
                        ->live()
                        ->default(fn (?FinanceHandover $record) => $record?->payment_method),
                ]),

            Section::make('Step 3: Upload Documents')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            FileUpload::make('quotation_by_reseller')
                                ->label('Quotation by Reseller')
                                ->disk('public')
                                ->directory('finance_handovers/quotation_reseller')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-QUO-RESELLER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('invoice_by_reseller')
                                ->label('Invoice by Reseller')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_reseller')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-RESELLER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('invoice_by_customer')
                                ->label('Invoice by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/invoice_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-INV-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('payment_by_customer')
                                ->label('Payment by Customer')
                                ->disk('public')
                                ->directory('finance_handovers/payment_customer')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-PAY-CUSTOMER-{$timestamp}-{$random}.{$extension}";
                                }),

                            FileUpload::make('product_quotation')
                                ->label('Product Quotation')
                                ->disk('public')
                                ->directory('finance_handovers/product_quotation')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->visible(fn (Forms\Get $get) => $get('payment_method') === 'hrdf')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                    $leadId = $this->getOwnerRecord()->id;
                                    $year = now()->format('y');
                                    $formattedId = sprintf('FN_%02d%04d', $year, $leadId);
                                    $extension = $file->getClientOriginalExtension();
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);
                                    return "{$formattedId}-PROD-QUOTATION-{$timestamp}-{$random}.{$extension}";
                                }),
                        ]),
                ]),

            Section::make('Step 4: Bind Installation Payment')
                ->schema([
                    Select::make('installation_payment_id')
                        ->label('Pending Installation Payment')
                        ->searchable()
                        ->options(function () {
                            return ResellerInstallationPayment::where('status', 'new')
                                ->where('attention_to', auth()->id())
                                ->whereNull('finance_handover_id')
                                ->orderBy('created_at', 'desc')
                                ->get()
                                ->mapWithKeys(function ($payment) {
                                    $label = $payment->formatted_id . ' | ' . $payment->customer_name;
                                    return [$payment->id => $label];
                                })
                                ->toArray();
                        }),
                ]),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';

        return [
            Tables\Actions\Action::make('AddFinanceHandover')
                ->label('Add Finance Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->slideOver()
                ->modalSubmitActionLabel('Submit')
                ->modalHeading('Finance Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    $installationPaymentId = $data['installation_payment_id'] ?? null;

                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

                    // Generate next available ID
                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new FinanceHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    // Bind installation payment if selected
                    if ($installationPaymentId) {
                        $payment = ResellerInstallationPayment::find($installationPaymentId);
                        if ($payment) {
                            $payment->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                                'finance_handover_id' => $handover->formatted_id,
                            ]);
                        }
                    }

                    Notification::make()
                        ->title('Finance Handover Created Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y'),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller Company')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: gray;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: green;">New</span>'),
                        'Pending Payment' => new HtmlString('<span style="color: orange;">Pending Payment</span>'),
                        'Completed' => new HtmlString('<span style="color: blue;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (FinanceHandover $record): View {
                            return view('components.finance-handover-details', [
                                'record' => $record
                            ]);
                        }),

                    Action::make('convertToDraft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn(FinanceHandover $record): bool => $record->status === 'Rejected')
                        ->requiresConfirmation()
                        ->modalHeading('Convert to Draft')
                        ->modalDescription('Are you sure you want to convert this finance handover back to draft? This will allow editing.')
                        ->action(function (FinanceHandover $record): void {
                            $record->update(['status' => 'Draft']);

                            Notification::make()
                                ->title('Finance handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('viewReason')
                        ->label('View Rejected Reason')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(FinanceHandover $record): bool => $record->status === 'Rejected')
                        ->modalHeading('Rejected Reason')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(function (FinanceHandover $record): HtmlString {
                            $reason = $record->remarks ?? 'No reason provided';
                            return new HtmlString('
                                <div style="padding: 1rem; border-radius: 0.5rem; background-color: #fef2f2; border-left: 4px solid #dc2626;">
                                    <p style="color: #991b1b; font-weight: 500; margin: 0;">' . e($reason) . '</p>
                                </div>
                            ');
                        }),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->visible(fn(FinanceHandover $record): bool => $record->status === 'Draft')
                        ->slideOver()
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->fillForm(fn(FinanceHandover $record) => [
                            'related_hardware_handovers' => $this->ensureArray($record->related_hardware_handovers),
                            'reseller_id' => $record->reseller_id,
                            'reseller_invoice_number' => $record->reseller_invoice_number,
                            'pic_name' => $record->pic_name,
                            'pic_phone' => $record->pic_phone,
                            'pic_email' => $record->pic_email,
                            'payment_method' => $record->payment_method,
                            'quotation_by_reseller' => $this->ensureArray($record->quotation_by_reseller),
                            'invoice_by_reseller' => $this->ensureArray($record->invoice_by_reseller),
                            'invoice_by_customer' => $this->ensureArray($record->invoice_by_customer),
                            'payment_by_customer' => $this->ensureArray($record->payment_by_customer),
                            'product_quotation' => $this->ensureArray($record->product_quotation),
                        ])
                        ->form($this->defaultForm())
                        ->action(function (FinanceHandover $record, array $data): void {
                            $installationPaymentId = $data['installation_payment_id'] ?? null;

                            $data['status'] = 'New';

                            if (($data['payment_method'] ?? null) !== 'hrdf') {
                                $data['product_quotation'] = null;
                            }

                            $record->update($data);

                            // Bind installation payment if selected
                            if ($installationPaymentId) {
                                $payment = ResellerInstallationPayment::find($installationPaymentId);
                                if ($payment) {
                                    $payment->update([
                                        'status' => 'completed',
                                        'completed_at' => now(),
                                        'finance_handover_id' => $record->formatted_id,
                                    ]);
                                }
                            }

                            Notification::make()
                                ->title('Finance Handover Updated & Submitted')
                                ->success()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([]);
    }

    private function getNextAvailableId()
    {
        $existingIds = FinanceHandover::pluck('id')->toArray();

        if (empty($existingIds)) {
            return 1;
        }

        $maxId = max($existingIds);

        for ($i = 1; $i <= $maxId; $i++) {
            if (!in_array($i, $existingIds)) {
                return $i;
            }
        }

        return $maxId + 1;
    }

    private function ensureArray($value): array
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function sendFinanceHandoverEmail(FinanceHandover $handover)
    {
        try {
            $lead = $handover->lead;
            $reseller = $handover->reseller;

            // Get salesperson from Lead model properly
            $salesperson = null;
            if ($lead->salesperson) {
                // If salesperson is stored as user ID
                if (is_numeric($lead->salesperson)) {
                    $salesperson = User::find($lead->salesperson);
                } else {
                    // If salesperson is stored as name, search by name
                    $salesperson = User::where('name', $lead->salesperson)->first();
                }
            }

            // Fallback to auth user if no salesperson found
            if (!$salesperson) {
                $salesperson = auth()->user();
            }

            $formattedId = $handover->formatted_handover_id;
            $companyName = $lead->companyDetail->company_name ?? $lead->name ?? 'Unknown Company';

            // Prepare attachment details
            $attachmentDetails = $this->formatAttachmentDetails($handover);

            // Prepare related hardware handovers details
            $relatedHandovers = $this->formatRelatedHandoverDetails($handover);

            $emailData = [
                'fn_id' => $formattedId,
                'submitted_date' => $handover->submitted_at->format('d M Y'),
                'salesperson' => $salesperson->name ?? 'Unknown',
                'customer' => $companyName,
                'reseller_company' => $reseller->company_name ?? 'Unknown',
                'pic_name' => $handover->pic_name,
                'pic_phone' => $handover->pic_phone,
                'pic_email' => $handover->pic_email,
                'reseller_invoice_number' => $handover->reseller_invoice_number ?? 'N/A',
                'attachment_details' => $attachmentDetails,
                'related_handovers' => $relatedHandovers,
            ];

            // Build recipients array
            $recipients = [];

            // Add salesperson email if available
            if ($salesperson && $salesperson->email) {
                $recipients[] = $salesperson->email;
            }

            // Always add finance email
            $recipients[] = 'putri.shafiqah@timeteccloud.com';
            $recipients[] = 'faiz@timeteccloud.com';

            // Remove duplicates and ensure valid emails
            $recipients = array_unique(array_filter($recipients, function($email) {
                return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
            }));

            Log::info('Finance Handover Email Recipients: ', $recipients);
            Log::info('Salesperson found: ', [
                'id' => $salesperson->id ?? 'None',
                'name' => $salesperson->name ?? 'None',
                'email' => $salesperson->email ?? 'None'
            ]);

            // FIXED: Send to all recipients together, not one by one
            if (!empty($recipients)) {
                Mail::send('emails.finance-handover-notification', $emailData, function ($message) use ($recipients, $formattedId, $companyName) {
                    $message->to($recipients)
                        ->subject("FINANCE HANDOVER | {$formattedId} | {$companyName}");
                });

                Log::info("Finance handover email sent to all recipients: " . implode(', ', $recipients));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send finance handover email: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function formatAttachmentDetails(FinanceHandover $handover): array
    {
        $details = [
            'invoice_by_customer' => [],
            'payment_by_customer' => [],
            'invoice_by_reseller' => [],
        ];

        // Process invoice by customer files
        if ($handover->invoice_by_customer) {
            $files = is_string($handover->invoice_by_customer)
                ? json_decode($handover->invoice_by_customer, true)
                : $handover->invoice_by_customer;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['invoice_by_customer'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        // Process payment by customer files
        if ($handover->payment_by_customer) {
            $files = is_string($handover->payment_by_customer)
                ? json_decode($handover->payment_by_customer, true)
                : $handover->payment_by_customer;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['payment_by_customer'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        // Process invoice by reseller files
        if ($handover->invoice_by_reseller) {
            $files = is_string($handover->invoice_by_reseller)
                ? json_decode($handover->invoice_by_reseller, true)
                : $handover->invoice_by_reseller;

            if (is_array($files)) {
                foreach ($files as $index => $file) {
                    $details['invoice_by_reseller'][] = [
                        'name' => "File " . ($index + 1),
                        'url' => asset('storage/' . $file)
                    ];
                }
            }
        }

        return $details;
    }

    private function formatRelatedHandoverDetails(FinanceHandover $handover): array
    {
        $details = [];

        if ($handover->related_hardware_handovers) {
            $handoverIds = is_string($handover->related_hardware_handovers)
                ? json_decode($handover->related_hardware_handovers, true)
                : $handover->related_hardware_handovers;

            if (is_array($handoverIds) && !empty($handoverIds)) {
                $hardwareHandovers = HardwareHandoverV2::whereIn('id', $handoverIds)->get();

                foreach ($hardwareHandovers as $hw) {
                    $details[] = $hw->formatted_handover_id;
                }
            }
        }

        return $details;
    }
}
