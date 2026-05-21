<?php

namespace App\Filament\Pages;

use App\Models\ResellerV2;
use App\Models\Reseller;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Services\IrbmService;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\Action as TableAction;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class ResellerAccount extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Reseller Accounts';
    protected static ?string $title = 'Reseller Accounts';
    protected static string $view = 'filament.pages.reseller-account';
    protected static ?int $navigationSort = 70;

    public array $customerCounts = [];
    public array $licenseCounts = [];

    public function mount(): void
    {
        $this->preloadCounts();
    }

    protected function preloadCounts(): void
    {
        $resellerIds = ResellerV2::whereNotNull('reseller_id')->pluck('reseller_id')->toArray();

        if (empty($resellerIds)) {
            return;
        }

        $this->customerCounts = \Illuminate\Support\Facades\DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->whereIn('crm_reseller_link.reseller_id', $resellerIds)
            ->where('crm_customer.f_status', 'A')
            ->groupBy('crm_reseller_link.reseller_id')
            ->selectRaw('crm_reseller_link.reseller_id, COUNT(crm_customer.f_backend_companyid) as cnt')
            ->pluck('cnt', 'reseller_id')
            ->toArray();

        $today = \Carbon\Carbon::now()->format('Y-m-d');

        $this->licenseCounts = \Illuminate\Support\Facades\DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_company_license', 'crm_reseller_link.f_id', '=', 'crm_company_license.f_company_id')
            ->whereIn('crm_reseller_link.reseller_id', $resellerIds)
            ->where('crm_company_license.f_type', 'PAID')
            ->where('crm_company_license.status', 'Active')
            ->where(function ($q) {
                $q->where('crm_company_license.f_name', 'like', '%TA%')
                  ->orWhere('crm_company_license.f_name', 'like', '%leave%')
                  ->orWhere('crm_company_license.f_name', 'like', '%claim%')
                  ->orWhere('crm_company_license.f_name', 'like', '%payroll%')
                  ->orWhere('crm_company_license.f_name', 'like', '%Face & QR Code%');
            })
            ->whereDate('crm_company_license.f_expiry_date', '>=', $today)
            ->groupBy('crm_reseller_link.reseller_id')
            ->selectRaw('crm_reseller_link.reseller_id, COUNT(DISTINCT crm_company_license.f_company_id) as cnt')
            ->pluck('cnt', 'reseller_id')
            ->toArray();
    }

    protected static function getResellerFormSchema(string $mode = 'create'): array
    {
        $isDisabled = $mode === 'view';
        $isCreate = $mode === 'create';

        $uppercaseAlpine = [
            'x-on:input' => '
                const start = $el.selectionStart;
                const end = $el.selectionEnd;
                const value = $el.value;
                $el.value = value.toUpperCase();
                $el.setSelectionRange(start, end);
            '
        ];

        $schema = [];

        // Row 1: Company Name, Payment Type, SST Category
        $companyNameField = $isCreate
            ? Select::make('company_name')
                ->label('Company Name')
                ->searchable()
                ->required()
                ->options(function () {
                    $resellerNames = \Illuminate\Support\Facades\DB::connection('frontenddb')
                        ->table('crm_reseller_link')
                        ->whereNotNull('reseller_name')
                        ->where('reseller_name', '!=', '')
                        ->orderBy('reseller_name')
                        ->pluck('reseller_name')
                        ->map(fn($name) => strtoupper($name));

                    $dealerNames = \Illuminate\Support\Facades\DB::connection('frontenddb')
                        ->table('crm_customer')
                        ->whereRaw('UPPER(f_company_type) = ?', ['DEALER'])
                        ->whereNotNull('f_company_name')
                        ->where('f_company_name', '!=', '')
                        ->orderBy('f_company_name')
                        ->pluck('f_company_name')
                        ->map(fn($name) => strtoupper($name));

                    return $resellerNames->merge($dealerNames)
                        ->unique()
                        ->sort()
                        ->mapWithKeys(fn($name) => [$name => $name])
                        ->toArray();
                })
            : TextInput::make('company_name')
                ->label('Company Name')
                ->disabled()
                ->extraInputAttributes(['style' => 'font-weight: bold;']);

        $schema[] = Grid::make(3)->schema([
            $companyNameField,

            Select::make('payment_type')
                ->label('Payment Type')
                ->options([
                    'cash_term' => 'Cash Term',
                    'credit_term' => 'Credit Term',
                ])
                ->disabled($isDisabled)
                ->required(!$isDisabled),

            Select::make('sst_category')
                ->label('SST Category')
                ->options([
                    'With SST' => 'With SST',
                    'Without SST' => 'Without SST',
                ])
                ->disabled($isDisabled)
                ->required(!$isDisabled),
        ]);

        // Row 2: PIC Name, PIC No HP, PIC Email + Hidden reseller_id
        $schema[] = Grid::make(3)->schema(array_filter([
            TextInput::make('name')
                ->label('PIC Name')
                ->disabled($isDisabled)
                ->extraAlpineAttributes($isDisabled ? [] : array_merge($uppercaseAlpine, [
                    'x-on:input' => "\$el.value = \$el.value.replace(/[^A-Za-z\\s]/g, '').toUpperCase()",
                ]))
                ->dehydrateStateUsing(fn ($state) => strtoupper(preg_replace('/[^A-Za-z\\s]/', '', $state)))
                ->required(!$isDisabled)
                ->maxLength(255),

            TextInput::make('phone')
                ->label('PIC No HP')
                ->tel()
                ->disabled($isDisabled)
                ->required(!$isDisabled)
                ->maxLength(50),

            TextInput::make('pic_email')
                ->label('PIC Email Address')
                ->email()
                ->disabled($isDisabled)
                ->required(!$isDisabled)
                ->maxLength(255),

            !$isDisabled ? Hidden::make('reseller_id')->dehydrated() : null,
        ]));


        // Row 3: Debtor Code, Creditor Code
        $schema[] = Grid::make(3)->schema([
            TextInput::make('debtor_code')
                ->label('Debtor Code')
                ->disabled($isDisabled)
                ->extraAlpineAttributes($isDisabled ? [] : $uppercaseAlpine)
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                ->required(!$isDisabled)
                ->maxLength(50),

            TextInput::make('creditor_code')
                ->label('Creditor Code')
                ->disabled($isDisabled)
                ->extraAlpineAttributes($isDisabled ? [] : $uppercaseAlpine)
                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                ->required(!$isDisabled)
                ->maxLength(50),
        ]);

        // Row 4: Email Notification, Trial Account, Installation Payment
        $schema[] = Grid::make(3)->schema([
            Select::make('email_notification')
                ->label('Email Notification')
                ->options(['yes' => 'Yes'])
                ->disabled()
                ->dehydrated()
                ->default('yes'),

            Select::make('trial_account_feature')
                ->label('Trial Account Feature')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->required(!$isDisabled),

            Select::make('installation_payment_feature')
                ->label('Installation Payment Feature')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->required(!$isDisabled),
        ]);

        // Row 5: Renewal Quotation, Bill as Reseller, Bill as End User
        $schema[] = Grid::make(3)->schema([
            Select::make('renewal_quotation')
                ->label('Renewal Quotation')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->default($isCreate ? 'enable' : null),

            Select::make('bill_as_reseller')
                ->label('Bill as Reseller')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled),

            Select::make('bill_as_end_user')
                ->label('Bill as End User')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled),
        ]);

        // Row 6: Block Payment Gateway, Bypass Invoice, Advanced Modules
        $schema[] = Grid::make(3)->schema([
            Select::make('block_payment_gateway')
                ->label('Block Payment Razor/Paypal?')
                ->options(['confirmed' => 'Yes, Confirm', 'pending' => 'No, Still Pending'])
                ->disabled($isDisabled)
                ->required(!$isDisabled),

            Select::make('bypass_invoice')
                ->label('Bypass Invoice')
                ->options(['yes' => 'Yes', 'no' => 'No'])
                ->disabled($isDisabled)
                ->required(!$isDisabled),

            Select::make('advanced_modules')
                ->label('Advanced Modules')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->default($isCreate ? 'disable' : null),
        ]);

        // Row 7: USD With Quotation, USD With Invoice
        $schema[] = Grid::make(3)->schema([
            Select::make('usd_with_quotation')
                ->label('USD With Quotation')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->default($isCreate ? 'disable' : null),

            Select::make('usd_with_invoice')
                ->label('USD With Invoice')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->default($isCreate ? 'disable' : null),

            Select::make('reseller_commission')
                ->label('Reseller Commission')
                ->options(['enable' => 'Enable', 'disable' => 'Disable'])
                ->disabled($isDisabled)
                ->default($isCreate ? 'disable' : null),
        ]);

        return $schema;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createReseller')
                ->label('Create Reseller')
                ->icon('heroicon-o-plus')
                ->modalWidth('3xl')
                ->slideOver()
                ->modalHeading('Create Reseller Account')
                ->form(static::getResellerFormSchema('create'))
                ->action(function (array $data) {
                    $password = Str::random(12);

                    // Lookup reseller_id from company_name
                    $resellerId = $data['reseller_id'] ?? null;
                    if (!$resellerId && !empty($data['company_name'])) {
                        $resellerLink = \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->whereRaw('UPPER(reseller_name) = ?', [strtoupper($data['company_name'])])
                            ->first();

                        if ($resellerLink) {
                            $resellerId = $resellerLink->reseller_id;
                        } else {
                            $dealer = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                ->table('crm_customer')
                                ->whereRaw('UPPER(f_company_name) = ?', [strtoupper($data['company_name'])])
                                ->whereRaw('UPPER(f_company_type) = ?', ['DEALER'])
                                ->first();

                            if ($dealer) {
                                $resellerId = $dealer->company_id;
                            }
                        }
                    }

                    $reseller = ResellerV2::create([
                        'company_name' => $data['company_name'],
                        'name' => $data['name'],
                        'phone' => $data['phone'],
                        'email' => $data['pic_email'],
                        'password' => Hash::make($password),
                        'plain_password' => $password,
                        'sst_category' => $data['sst_category'],
                        'reseller_id' => $resellerId,
                        'debtor_code' => $data['debtor_code'] ?? null,
                        'creditor_code' => $data['creditor_code'] ?? null,
                        'payment_type' => $data['payment_type'] ?? null,
                        'email_notification' => $data['email_notification'] ?? 'no',
                        'trial_account_feature' => $data['trial_account_feature'] ?? 'disable',
                        'installation_payment_feature' => $data['installation_payment_feature'] ?? 'disable',
                        'renewal_quotation' => $data['renewal_quotation'] ?? 'disable',
                        'bill_as_reseller' => $data['bill_as_reseller'] ?? 'disable',
                        'bill_as_end_user' => $data['bill_as_end_user'] ?? 'disable',
                        'usd_with_quotation' => $data['usd_with_quotation'] ?? 'disable',
                        'usd_with_invoice' => $data['usd_with_invoice'] ?? 'disable',
                        'reseller_commission' => $data['reseller_commission'] ?? 'disable',
                        'block_payment_gateway' => $data['block_payment_gateway'] ?? 'pending',
                        'bypass_invoice' => $data['bypass_invoice'] ?? 'no',
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]);

                    if (isset($data['email_notification']) && $data['email_notification'] === 'yes') {
                        try {
                            Mail::send('emails.reseller-credentials', [
                                'name' => $data['name'],
                                'company_name' => $data['company_name'],
                                'email' => $data['pic_email'],
                                'password' => $password,
                                'login_url' => route('reseller.login'),
                            ], function ($message) use ($data) {
                                $message->to($data['pic_email'])
                                    ->subject('TimeTec CRM - Reseller Account Created');
                            });

                            Notification::make()
                                ->title('Reseller Created')
                                ->body("Reseller created successfully. Login credentials sent to {$data['pic_email']}")
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Reseller Created')
                                ->body("Reseller created successfully, but email failed to send. Login email: {$data['pic_email']}, Password: {$password}")
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Reseller Created')
                            ->body("Reseller created successfully. Login email: {$data['pic_email']}, Password: {$password}")
                            ->success()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerV2::query())
            ->columns([
                TextColumn::make('no')
                    ->label('NO')
                    ->rowIndex(),

                TextColumn::make('company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('active_customer_count')
                    ->label('Customer')
                    ->getStateUsing(function (ResellerV2 $record) {
                        return $this->customerCounts[$record->reseller_id] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('all_license_count')
                    ->label('License')
                    ->getStateUsing(function (ResellerV2 $record) {
                        return $this->licenseCounts[$record->reseller_id] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('email')
                    ->label('Login Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('plain_password')
                    ->label('Login Password')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('sst_category')
                //     ->label('SST Category')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'EXEMPTED' => 'success',
                //         'NON-EXEMPTED' => 'warning',
                //         default => 'gray',
                //     })
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('commission_rate')
                //     ->label('Commission %')
                //     ->sortable()
                //     ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                //     ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bypass_invoice')
                    ->label('BI')
                    ->formatStateUsing(function ($state) {
                        return $state === 'yes'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('Bypass Invoice')
                    ->alignCenter(),

                TextColumn::make('renewal_quotation')
                    ->label('FB')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('Renewal Quotation')
                    ->alignCenter(),

                TextColumn::make('bill_as_reseller')
                    ->label('FD')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('Bill as Reseller')
                    ->alignCenter(),

                TextColumn::make('bill_as_end_user')
                    ->label('FE')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('Bill as End User')
                    ->alignCenter(),

                TextColumn::make('usd_with_quotation')
                    ->label('FF')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('USD With Quotation')
                    ->alignCenter(),

                TextColumn::make('usd_with_invoice')
                    ->label('FG')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('USD With Invoice')
                    ->alignCenter(),

                TextColumn::make('reseller_commission')
                    ->label('FH')
                    ->formatStateUsing(function ($state) {
                        return $state === 'enable'
                            ? new \Illuminate\Support\HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new \Illuminate\Support\HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    })
                    ->tooltip('Reseller Commission')
                    ->alignCenter(),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('name')
                //     ->label('PIC Name')
                //     ->searchable()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('phone')
                //     ->label('PIC No HP')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('ssm_number')
                //     ->label('SSM Number')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('tax_identification_number')
                //     ->label('Tax ID Number')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('debtor_code')
                //     ->label('Debtor Code')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('creditor_code')
                //     ->label('Creditor Code')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('reseller.company_name')
                //     ->label('Bound Reseller')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('status')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'active' => 'success',
                //         'inactive' => 'danger',
                //         default => 'gray',
                //     })
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                TableAction::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $resellers = ResellerV2::orderBy('created_at', 'desc')->get();

                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Reseller Accounts');

                        // Headers
                        $headers = ['No', 'Company Name', 'Email', 'Password', 'Last Login', 'Created At'];
                        foreach ($headers as $col => $header) {
                            $cell = chr(65 + $col) . '1';
                            $sheet->setCellValue($cell, $header);
                        }

                        // Header styling
                        $headerRange = 'A1:F1';
                        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1a56db');
                        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        // Data rows
                        $row = 2;
                        foreach ($resellers as $index => $reseller) {
                            $sheet->setCellValue("A{$row}", $index + 1);
                            $sheet->setCellValue("B{$row}", $reseller->company_name);
                            $sheet->setCellValue("C{$row}", $reseller->email);
                            $sheet->setCellValue("D{$row}", $reseller->plain_password);
                            $sheet->setCellValue("E{$row}", $reseller->last_login_at ? $reseller->last_login_at->format('d M Y, H:i') : 'Never');
                            $sheet->setCellValue("F{$row}", $reseller->created_at->format('d M Y, H:i'));

                            $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                            $row++;
                        }

                        // Auto-size columns
                        foreach (range('A', 'F') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        $fileName = 'reseller_accounts_' . now()->format('Ymd_His') . '.xlsx';
                        $filePath = storage_path('app/' . $fileName);

                        $writer = new Xlsx($spreadsheet);
                        $writer->save($filePath);

                        return response()->download($filePath)->deleteFileAfterSend(true);
                    }),
            ])
            ->filters([
                Filter::make('payment_sst')
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('payment_type')
                                ->label('Payment Type')
                                ->options([
                                    'cash_term' => 'Cash Term',
                                    'credit_term' => 'Credit Term',
                                ]),
                            Select::make('sst_category')
                                ->label('SST Category')
                                ->options([
                                    'With SST' => 'With SST',
                                    'Without SST' => 'Without SST',
                                ]),
                        ]),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['payment_type'])) {
                            $query->where('payment_type', $data['payment_type']);
                        }
                        if (!empty($data['sst_category'])) {
                            $query->where('sst_category', $data['sst_category']);
                        }
                    }),

                Filter::make('ticket_category')
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'enable' => 'Enable',
                                    'disable' => 'Disable',
                                ]),
                            Select::make('categories')
                                ->label('ID Category')
                                ->multiple()
                                ->options([
                                    'bi' => 'BI - Bypass Invoice',
                                    'fb' => 'FB - Renewal Quotation',
                                    'fd' => 'FD - Bill as Reseller',
                                    'fe' => 'FE - Bill as End User',
                                    'ff' => 'FF - USD with Quotation',
                                    'fg' => 'FG - USD with Invoice',
                                ]),
                        ]),
                    ])
                    ->query(function ($query, array $data) {
                        $status = $data['status'] ?? null;
                        $categories = $data['categories'] ?? [];

                        if (!$status || empty($categories)) {
                            return $query;
                        }

                        $columnMap = [
                            'bi' => ['column' => 'bypass_invoice', 'enable' => 'yes', 'disable' => 'no'],
                            'fb' => ['column' => 'renewal_quotation', 'enable' => 'enable', 'disable' => 'disable'],
                            'fd' => ['column' => 'bill_as_reseller', 'enable' => 'enable', 'disable' => 'disable'],
                            'fe' => ['column' => 'bill_as_end_user', 'enable' => 'enable', 'disable' => 'disable'],
                            'ff' => ['column' => 'usd_with_quotation', 'enable' => 'enable', 'disable' => 'disable'],
                            'fg' => ['column' => 'usd_with_invoice', 'enable' => 'enable', 'disable' => 'disable'],
                            'rc' => ['column' => 'reseller_commission', 'enable' => 'enable', 'disable' => 'disable'],
                        ];

                        foreach ($categories as $category) {
                            if (isset($columnMap[$category])) {
                                $map = $columnMap[$category];
                                $value = $status === 'enable' ? $map['enable'] : $map['disable'];
                                $query->where($map['column'], $value);
                            }
                        }

                        return $query;
                    }),
            ])
            ->filtersFormWidth('lg')
            ->actions([
                TableAction::make('edit')
                    ->label(false)
                    ->icon('heroicon-o-pencil-square')
                    ->modalWidth('3xl')
                    ->slideOver()
                    ->modalHeading('Edit Reseller Account')
                    ->fillForm(fn (ResellerV2 $record) => [
                        'company_name' => $record->company_name,
                        'payment_type' => $record->payment_type,
                        'sst_category' => $record->sst_category,
                        'name' => $record->name,
                        'phone' => $record->phone,
                        'pic_email' => $record->email,
                        'reseller_id' => $record->reseller_id,
                        'debtor_code' => $record->debtor_code,
                        'creditor_code' => $record->creditor_code,
                        'email_notification' => $record->email_notification ?? 'no',
                        'trial_account_feature' => $record->trial_account_feature ?? 'disable',
                        'installation_payment_feature' => $record->installation_payment_feature ?? 'disable',
                        'renewal_quotation' => $record->renewal_quotation ?? 'disable',
                        'bill_as_reseller' => $record->bill_as_reseller ?? 'disable',
                        'bill_as_end_user' => $record->bill_as_end_user ?? 'disable',
                        'usd_with_quotation' => $record->usd_with_quotation ?? 'disable',
                        'usd_with_invoice' => $record->usd_with_invoice ?? 'disable',
                        'reseller_commission' => $record->reseller_commission ?? 'disable',
                        'block_payment_gateway' => $record->block_payment_gateway ?? 'pending',
                        'bypass_invoice' => $record->bypass_invoice ?? 'no',
                        'advanced_modules' => $record->advanced_modules ?? 'disable',
                    ])
                    ->form(static::getResellerFormSchema('edit'))
                    ->action(function (ResellerV2 $record, array $data) {
                        $record->update([
                            'name' => $data['name'],
                            'phone' => $data['phone'],
                            'email' => $data['pic_email'],
                            'sst_category' => $data['sst_category'],
                            'reseller_id' => $data['reseller_id'] ?? $record->reseller_id,
                            'debtor_code' => $data['debtor_code'],
                            'creditor_code' => $data['creditor_code'],
                            'payment_type' => $data['payment_type'],
                            'email_notification' => $data['email_notification'],
                            'trial_account_feature' => $data['trial_account_feature'],
                            'installation_payment_feature' => $data['installation_payment_feature'],
                            'renewal_quotation' => $data['renewal_quotation'] ?? 'disable',
                            'bill_as_reseller' => $data['bill_as_reseller'] ?? 'disable',
                            'bill_as_end_user' => $data['bill_as_end_user'] ?? 'disable',
                            'usd_with_quotation' => $data['usd_with_quotation'] ?? 'disable',
                            'usd_with_invoice' => $data['usd_with_invoice'] ?? 'disable',
                            'reseller_commission' => $data['reseller_commission'] ?? 'disable',
                            'block_payment_gateway' => $data['block_payment_gateway'],
                            'bypass_invoice' => $data['bypass_invoice'],
                            'advanced_modules' => $data['advanced_modules'] ?? 'disable',
                        ]);

                        Notification::make()
                            ->title('Reseller Updated')
                            ->body("Reseller '{$record->company_name}' has been updated successfully.")
                            ->success()
                            ->send();
                    }),
                TableAction::make('view_details')
                    ->label(false)
                    ->icon('heroicon-o-eye')
                    ->modalWidth('3xl')
                    ->slideOver()
                    ->modalHeading('View Reseller Account')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->fillForm(fn (ResellerV2 $record) => [
                        'company_name' => $record->company_name,
                        'reseller_id' => $record->reseller_id ?? 'N/A',
                        'payment_type' => $record->payment_type,
                        'sst_category' => $record->sst_category,
                        'name' => $record->name,
                        'phone' => $record->phone,
                        'pic_email' => $record->email,
                        'login_password' => $record->plain_password,
                        'debtor_code' => $record->debtor_code,
                        'creditor_code' => $record->creditor_code,
                        'email_notification' => $record->email_notification ?? 'no',
                        'trial_account_feature' => $record->trial_account_feature ?? 'disable',
                        'installation_payment_feature' => $record->installation_payment_feature ?? 'disable',
                        'renewal_quotation' => $record->renewal_quotation ?? 'disable',
                        'bill_as_reseller' => $record->bill_as_reseller ?? 'disable',
                        'bill_as_end_user' => $record->bill_as_end_user ?? 'disable',
                        'usd_with_quotation' => $record->usd_with_quotation ?? 'disable',
                        'usd_with_invoice' => $record->usd_with_invoice ?? 'disable',
                        'reseller_commission' => $record->reseller_commission ?? 'disable',
                        'block_payment_gateway' => $record->block_payment_gateway ?? 'pending',
                        'bypass_invoice' => $record->bypass_invoice ?? 'no',
                        'advanced_modules' => $record->advanced_modules ?? 'disable',
                        'created_at' => $record->created_at?->format('d M Y, H:i'),
                    ])
                    ->form(static::getResellerFormSchema('view')),
                TableAction::make('exportCustomerLicense')
                    ->label(false)
                    ->tooltip('Export Active Customers Without License')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->hidden(fn (ResellerV2 $record) => !$record->reseller_id)
                    ->action(function (ResellerV2 $record) {
                        $today = \Carbon\Carbon::now()->format('Y-m-d');
                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('No License');

                        // Title row
                        $sheet->setCellValue('A1', $record->company_name . ' - Active Customers Without License');
                        $sheet->mergeCells('A1:B1');
                        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                        // Headers
                        $headers = ['No', 'Customer Company Name'];
                        foreach ($headers as $col => $header) {
                            $cell = chr(65 + $col) . '3';
                            $sheet->setCellValue($cell, $header);
                        }

                        $headerRange = 'A3:B3';
                        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
                        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1a56db');
                        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        // Get all active customers with their f_id
                        $activeCustomers = \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
                            ->where('crm_reseller_link.reseller_id', $record->reseller_id)
                            ->where('crm_customer.f_status', 'A')
                            ->select('crm_customer.f_company_name', 'crm_reseller_link.f_id')
                            ->orderBy('crm_customer.f_company_name')
                            ->get();

                        // Get f_ids that have active paid license
                        $fIdsWithLicense = collect();
                        if ($activeCustomers->isNotEmpty()) {
                            $fIdsWithLicense = \Illuminate\Support\Facades\DB::connection('frontenddb')
                                ->table('crm_company_license')
                                ->whereIn('f_company_id', $activeCustomers->pluck('f_id'))
                                ->where('f_type', 'PAID')
                                ->where('status', 'Active')
                                ->where(function($q) {
                                    $q->where('f_name', 'like', '%TA%')
                                      ->orWhere('f_name', 'like', '%leave%')
                                      ->orWhere('f_name', 'like', '%claim%')
                                      ->orWhere('f_name', 'like', '%payroll%')
                                      ->orWhere('f_name', 'like', '%Face & QR Code%');
                                })
                                ->whereDate('f_expiry_date', '>=', $today)
                                ->distinct()
                                ->pluck('f_company_id');
                        }

                        // Filter: only customers WITHOUT active license
                        $customersWithoutLicense = $activeCustomers->filter(function ($customer) use ($fIdsWithLicense) {
                            return !$fIdsWithLicense->contains($customer->f_id);
                        })->values();

                        $row = 4;
                        foreach ($customersWithoutLicense as $index => $customer) {
                            $sheet->setCellValue("A{$row}", $index + 1);
                            $sheet->setCellValue("B{$row}", $customer->f_company_name);
                            $sheet->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                            $row++;
                        }

                        if ($customersWithoutLicense->isEmpty()) {
                            $sheet->setCellValue('A4', 'All active customers have licenses.');
                            $sheet->mergeCells('A4:B4');
                        }

                        // Auto-size columns
                        $sheet->getColumnDimension('A')->setAutoSize(true);
                        $sheet->getColumnDimension('B')->setAutoSize(true);

                        $safeFileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $record->company_name);
                        $fileName = $safeFileName . '_no_license_' . now()->format('Ymd_His') . '.xlsx';
                        $filePath = storage_path('app/' . $fileName);

                        $writer = new Xlsx($spreadsheet);
                        $writer->save($filePath);

                        return response()->download($filePath)->deleteFileAfterSend(true);
                    }),
                TableAction::make('loginAsReseller')
                    ->label(false)
                    ->tooltip('Backend Login as Reseller')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Login as Reseller')
                    ->modalDescription(fn (ResellerV2 $record) => "Are you sure you want to login as {$record->company_name}?")
                    ->modalSubmitActionLabel('Login')
                    ->action(function (ResellerV2 $record) {
                        $url = route('admin.reseller.login', $record);
                        $this->js("window.open('{$url}', '_blank')");
                    }),
            ])
            ->recordAction('view_details')
            ->recordUrl(null)
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('batchUpdate')
                    ->label('Batch Update Features')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->modalWidth('lg')
                    ->modalHeading('Batch Update Features')
                    ->modalDescription(fn (\Illuminate\Database\Eloquent\Collection $records) => "Update features for {$records->count()} selected reseller(s).")
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('bypass_invoice')
                                    ->label('Bypass Invoice')
                                    ->options(['yes' => 'Yes', 'no' => 'No', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('renewal_quotation')
                                    ->label('Renewal Quotation')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('trial_account_feature')
                                    ->label('Trial Account Feature')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('installation_payment_feature')
                                    ->label('Installation Payment Feature')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('bill_as_reseller')
                                    ->label('Bill as Reseller')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('bill_as_end_user')
                                    ->label('Bill as End User')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('usd_with_quotation')
                                    ->label('USD With Quotation')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('usd_with_invoice')
                                    ->label('USD With Invoice')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                                Select::make('reseller_commission')
                                    ->label('Reseller Commission')
                                    ->options(['enable' => 'Enable', 'disable' => 'Disable', '' => '-- No Change --'])
                                    ->default(''),
                            ]),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $updateData = collect($data)
                            ->filter(fn ($value) => $value !== '' && $value !== null)
                            ->toArray();

                        if (empty($updateData)) {
                            Notification::make()
                                ->title('No changes selected')
                                ->warning()
                                ->send();
                            return;
                        }

                        $count = $records->count();
                        foreach ($records as $record) {
                            $record->update($updateData);
                        }

                        $fields = collect($updateData)->keys()->map(fn ($key) => ucwords(str_replace('_', ' ', $key)))->implode(', ');

                        Notification::make()
                            ->title('Batch Update Completed')
                            ->body("Updated {$fields} for {$count} reseller(s).")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('company_name', 'asc')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}

