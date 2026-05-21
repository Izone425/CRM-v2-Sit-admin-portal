<?php

namespace App\Filament\Pages;

use App\Services\HRV2LicenseSeatApiService;
use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class HRV2LicenseSeatApiTest extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'HRV2 API Test';
    protected static ?string $title = 'HRV2 License Seat API Test';
    protected static string $view = 'filament.pages.hrv2-license-seat-api-test';
    protected static ?string $slug = 'hrv2-api-test';
    protected static ?string $navigationGroup = 'Settings';

    public ?string $responseOutput = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->role_id ?? 0, [3, 10]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Create Account
            Action::make('createAccount')
                ->label('Create Account')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('company_name')->label('Company Name')->required(),
                        TextInput::make('country_id')->label('Country ID')->numeric()->required()->default(136),
                        TextInput::make('name')->label('Contact Name')->required(),
                        TextInput::make('email')->label('Email')->email()->required(),
                        TextInput::make('password')->label('Password')->required()->default('Abc@1234'),
                        TextInput::make('phone_code')->label('Phone Code')->required()->default('60'),
                        TextInput::make('phone')->label('Phone')->required(),
                        Select::make('timezone')->label('Timezone')->required()
                            ->options([
                                'Asia/Kuala_Lumpur' => 'Asia/Kuala_Lumpur',
                                'Asia/Singapore' => 'Asia/Singapore',
                                'Asia/Jakarta' => 'Asia/Jakarta',
                                'Asia/Bangkok' => 'Asia/Bangkok',
                                'Asia/Manila' => 'Asia/Manila',
                                'Asia/Hong_Kong' => 'Asia/Hong_Kong',
                                'Asia/Tokyo' => 'Asia/Tokyo',
                                'Asia/Seoul' => 'Asia/Seoul',
                                'Asia/Kolkata' => 'Asia/Kolkata',
                                'Asia/Dubai' => 'Asia/Dubai',
                                'Europe/London' => 'Europe/London',
                                'America/New_York' => 'America/New_York',
                            ])
                            ->default('Asia/Kuala_Lumpur')
                            ->searchable(),
                    ]),
                ])
                ->modalHeading('Create Account')
                ->modalWidth('xl')
                ->action(function (array $data): void {
                    $this->callApi('createAccount', $data);
                }),

            // Add Buffer License
            Action::make('addBufferLicense')
                ->label('Add Buffer License')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('account_id')->label('Account ID')->numeric()->required(),
                        TextInput::make('company_id')->label('Company ID')->numeric()->required(),
                        DatePicker::make('start_date')->label('Start Date')->required()->default(now()->format('Y-m-d')),
                        DatePicker::make('end_date')->label('End Date')->required()->default(now()->addMonths(2)->format('Y-m-d')),
                        Textarea::make('notes')->label('Notes')->columnSpanFull(),
                    ]),
                    Section::make('Seat Limits (optional)')
                        ->description('Leave empty for unlimited seats on all modules')
                        ->collapsed()
                        ->schema([
                            Repeater::make('seat_limits')
                                ->label('Module Seat Limits')
                                ->schema([
                                    Select::make('module')
                                        ->label('Module')
                                        ->options([
                                            'Attendance' => 'Attendance',
                                            'Leave' => 'Leave',
                                            'Claim' => 'Claim',
                                            'Payroll' => 'Payroll',
                                            'Appraisal' => 'Appraisal',
                                            'Hire' => 'Hire',
                                            'Access' => 'Access',
                                            'PowerBI' => 'PowerBI',
                                        ])
                                        ->required(),
                                    TextInput::make('seats')->label('Seats (empty = unlimited)')->numeric()->nullable(),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel('Add Module'),
                        ]),
                ])
                ->modalHeading('Add Buffer License')
                ->modalWidth('xl')
                ->action(function (array $data): void {
                    $this->callApi('addBufferLicense', $data);
                }),

            // Update Buffer License
            Action::make('updateBufferLicense')
                ->label('Update Buffer License')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('account_id')->label('Account ID')->numeric()->required(),
                        TextInput::make('company_id')->label('Company ID')->numeric()->required(),
                        TextInput::make('license_set_id')->label('License Set ID')->numeric()->required(),
                        DatePicker::make('start_date')->label('Start Date')->required(),
                        DatePicker::make('end_date')->label('End Date')->required(),
                        Textarea::make('notes')->label('Notes')->columnSpanFull(),
                    ]),
                    Section::make('Seat Limits (optional)')
                        ->collapsed()
                        ->schema([
                            Repeater::make('seat_limits')
                                ->label('Module Seat Limits')
                                ->schema([
                                    Select::make('module')
                                        ->label('Module')
                                        ->options([
                                            'Attendance' => 'Attendance',
                                            'Leave' => 'Leave',
                                            'Claim' => 'Claim',
                                            'Payroll' => 'Payroll',
                                            'Appraisal' => 'Appraisal',
                                            'Hire' => 'Hire',
                                            'Access' => 'Access',
                                            'PowerBI' => 'PowerBI',
                                        ])
                                        ->required(),
                                    TextInput::make('seats')->label('Seats (empty = unlimited)')->numeric()->nullable(),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel('Add Module'),
                        ]),
                ])
                ->modalHeading('Update Buffer License')
                ->modalWidth('xl')
                ->action(function (array $data): void {
                    $this->callApi('updateBufferLicense', $data);
                }),

            // Add Paid Application License
            Action::make('addPaidAppLicense')
                ->label('Add Paid App License')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('account_id')->label('Account ID')->numeric()->required(),
                        TextInput::make('company_id')->label('Company ID')->numeric()->required(),
                        Select::make('application')
                            ->label('Application')
                            ->options([
                                'Attendance' => 'Attendance',
                                'Leave' => 'Leave',
                                'Claim' => 'Claim',
                                'Payroll' => 'Payroll',
                                'Appraisal' => 'Appraisal',
                                'Hire' => 'Hire',
                                'Access' => 'Access',
                                'PowerBI' => 'PowerBI',
                            ])
                            ->required(),
                        TextInput::make('seat_limit')->label('Seat Limit (empty = unlimited)')->numeric()->nullable(),
                        DatePicker::make('start_date')->label('Start Date')->required()->default(now()->format('Y-m-d')),
                        DatePicker::make('end_date')->label('End Date')->required()->default(now()->addYear()->format('Y-m-d')),
                        TextInput::make('user_id')->label('User ID (optional)')->numeric()->nullable(),
                    ]),
                ])
                ->modalHeading('Add Paid Application License')
                ->modalWidth('xl')
                ->action(function (array $data): void {
                    $this->callApi('addPaidAppLicense', $data);
                }),

            // Update Paid Application License
            Action::make('updatePaidAppLicense')
                ->label('Update Paid App License')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('account_id')->label('Account ID')->numeric()->required(),
                        TextInput::make('company_id')->label('Company ID')->numeric()->required(),
                        TextInput::make('period_id')->label('Period ID')->numeric()->required(),
                        DatePicker::make('start_date')->label('Start Date')->required(),
                        DatePicker::make('end_date')->label('End Date')->required(),
                        TextInput::make('seat_limit')->label('Seat Limit (empty = unlimited)')->numeric()->nullable(),
                    ]),
                ])
                ->modalHeading('Update Paid Application License')
                ->modalWidth('xl')
                ->action(function (array $data): void {
                    $this->callApi('updatePaidAppLicense', $data);
                }),
        ];
    }

    private function callApi(string $action, array $data): void
    {
        try {
            $service = app(HRV2LicenseSeatApiService::class);

            $result = match ($action) {
                'createAccount' => $service->createAccount($data),

                'addBufferLicense' => $service->addBufferLicense(
                    (int) $data['account_id'],
                    (int) $data['company_id'],
                    [
                        'startDate' => $data['start_date'],
                        'endDate' => $data['end_date'],
                        'notes' => $data['notes'] ?? null,
                        'seatLimits' => $this->formatSeatLimits($data['seat_limits'] ?? []),
                    ]
                ),

                'updateBufferLicense' => $service->updateBufferLicense(
                    (int) $data['account_id'],
                    (int) $data['company_id'],
                    (int) $data['license_set_id'],
                    [
                        'startDate' => $data['start_date'],
                        'endDate' => $data['end_date'],
                        'notes' => $data['notes'] ?? null,
                        'seatLimits' => $this->formatSeatLimits($data['seat_limits'] ?? []),
                    ]
                ),

                'addPaidAppLicense' => $service->addPaidApplicationLicense(
                    (int) $data['account_id'],
                    (int) $data['company_id'],
                    [
                        'application' => $data['application'],
                        'startDate' => $data['start_date'],
                        'endDate' => $data['end_date'],
                        'seatLimit' => $data['seat_limit'] ?? null,
                        'userId' => $data['user_id'] ?? null,
                    ]
                ),

                'updatePaidAppLicense' => $service->updatePaidApplicationLicense(
                    (int) $data['account_id'],
                    (int) $data['company_id'],
                    (int) $data['period_id'],
                    [
                        'startDate' => $data['start_date'],
                        'endDate' => $data['end_date'],
                        'seatLimit' => $data['seat_limit'] ?? null,
                    ]
                ),
            };

            $this->responseOutput = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($result['success'] ?? false) {
                Notification::make()
                    ->title('API Call Successful')
                    ->success()
                    ->body($action . ' completed successfully.')
                    ->send();
            } else {
                Notification::make()
                    ->title('API Call Failed')
                    ->danger()
                    ->body($result['error'] ?? 'Unknown error')
                    ->send();
            }
        } catch (\Exception $e) {
            $this->responseOutput = json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], JSON_PRETTY_PRINT);

            Notification::make()
                ->title('Exception')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    private function formatSeatLimits(array $seatLimits): array
    {
        $formatted = [];
        foreach ($seatLimits as $item) {
            if (!empty($item['module'])) {
                $formatted[$item['module']] = $item['seats'] !== null && $item['seats'] !== '' ? (int) $item['seats'] : null;
            }
        }
        return $formatted;
    }
}
