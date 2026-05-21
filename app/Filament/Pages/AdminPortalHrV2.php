<?php
namespace App\Filament\Pages;

use App\Models\SoftwareHandover;
use App\Models\Customer;
use App\Models\LicenseCertificate;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class AdminPortalHrV2 extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin-portal-hr-v2';

    protected static ?string $navigationLabel = 'HR V2 Database Portal';

    protected static ?string $title = 'Admin Portal HR V2';

    protected static ?string $navigationGroup = 'Admin Portal';

    protected static ?int $navigationSort = 1;

    /**
     * Get all handovers for the same customer (hr_account_id + hr_company_id)
     */
    private function getCustomerHandovers(SoftwareHandover $record): \Illuminate\Database\Eloquent\Collection
    {
        return SoftwareHandover::query()
            ->where('hr_account_id', $record->hr_account_id)
            ->where('hr_company_id', $record->hr_company_id)
            ->where('hr_version', 2)
            ->with(['licenseCertificateById'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get combined modules across all handovers for a customer
     */
    private function getCombinedModules(\Illuminate\Database\Eloquent\Collection $handovers): array
    {
        $modules = ['ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0, 'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0];

        foreach ($handovers as $handover) {
            foreach ($modules as $key => $val) {
                if ($handover->$key) {
                    $modules[$key] = 1;
                }
            }
        }

        return $modules;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SoftwareHandover::query()
                    ->where('hr_version', 2)
                    ->whereNotNull('hr_company_id')
                    ->with(['licenseCertificateById'])
                    ->whereIn('id', function ($query) {
                        $query->selectRaw('MAX(id)')
                            ->from('software_handovers')
                            ->where('hr_version', 2)
                            ->whereNotNull('hr_company_id')
                            ->groupBy('hr_account_id', 'hr_company_id');
                    })
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('hr_company_id')
                    ->label(new HtmlString('Database<br>Backend ID'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Backend ID copied!')
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->url(fn (SoftwareHandover $record) => url('/admin/hr-company-license-details?' . http_build_query([
                        'hrAccountId' => $record->hr_account_id,
                        'hrCompanyId' => $record->hr_company_id,
                    ])))
                    ->color('primary'),

                TextColumn::make('handovers_count')
                    ->label('Handovers')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $count = SoftwareHandover::where('hr_account_id', $record->hr_account_id)
                            ->where('hr_company_id', $record->hr_company_id)
                            ->where('hr_version', 2)
                            ->count();

                        $color = $count > 1 ? '#2563eb' : '#6b7280';
                        $bg = $count > 1 ? '#eff6ff' : '#f3f4f6';
                        $border = $count > 1 ? '#bfdbfe' : '#d1d5db';

                        return new HtmlString("
                            <div style='display: inline-flex; align-items: center; padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 20px; background-color: {$bg}; color: {$color}; border: 1px solid {$border};'>
                                {$count}
                            </div>
                        ");
                    }),

                TextColumn::make('combined_modules')
                    ->label('Modules')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $handovers = $this->getCustomerHandovers($record);
                        $modules = $this->getCombinedModules($handovers);

                        $moduleLabels = [
                            'ta' => ['TA', '#059669'],
                            'tl' => ['TL', '#7c3aed'],
                            'tc' => ['TC', '#ea580c'],
                            'tp' => ['TP', '#2563eb'],
                            'tapp' => ['TApp', '#db2777'],
                            'thire' => ['THire', '#0891b2'],
                            'tacc' => ['TAcc', '#4f46e5'],
                            'tpbi' => ['TPBI', '#b45309'],
                        ];

                        $badges = '';
                        foreach ($modules as $key => $active) {
                            if ($active && isset($moduleLabels[$key])) {
                                [$label, $color] = $moduleLabels[$key];
                                $badges .= "<span style='display: inline-block; padding: 2px 8px; margin: 2px; font-size: 11px; font-weight: 600; border-radius: 12px; background-color: {$color}15; color: {$color}; border: 1px solid {$color}40;'>{$label}</span>";
                            }
                        }

                        return new HtmlString($badges ?: '<span style="color: #9ca3af; font-size: 12px;">None</span>');
                    }),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('master_email')
                    ->label('Master Email')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->email ?? "sw{$record->id}@timeteccloud.com";
                    })
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->searchable(),

                TextColumn::make('plain_password')
                    ->label('Master Password')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $customer = Customer::where('sw_id', $record->id)->first();
                        return $customer?->plain_password ?? 'N/A';
                    })
                    ->copyable()
                    ->copyMessage('Password copied!'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Completed' => 'Completed',
                        'Rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // View all handovers and their licenses for this customer
                    Action::make('view_handovers')
                        ->label('View Handovers & Licenses')
                        ->icon('heroicon-o-queue-list')
                        ->color('info')
                        ->modalHeading(fn (SoftwareHandover $record) => "Handovers — {$record->company_name}")
                        ->modalContent(function (SoftwareHandover $record) {
                            $handovers = $this->getCustomerHandovers($record);

                            $html = '<div style="display: flex; flex-direction: column; gap: 16px;">';

                            foreach ($handovers as $handover) {
                                $certificate = $handover->licenseCertificateById;

                                // Module badges
                                $moduleLabels = ['ta' => 'TA', 'tl' => 'TL', 'tc' => 'TC', 'tp' => 'TP', 'tapp' => 'TApp', 'thire' => 'THire', 'tacc' => 'TAcc', 'tpbi' => 'TPBI'];
                                $moduleBadges = '';
                                foreach ($moduleLabels as $key => $label) {
                                    if ($handover->$key) {
                                        $moduleBadges .= "<span style='display: inline-block; padding: 2px 8px; margin: 1px; font-size: 11px; font-weight: 600; border-radius: 12px; background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;'>{$label}</span>";
                                    }
                                }

                                // License info
                                $licenseHtml = '<div style="color: #9ca3af; font-size: 12px; margin-top: 8px;">No license certificate</div>';
                                if ($certificate) {
                                    $bufferStart = $certificate->buffer_license_start ? Carbon::parse($certificate->buffer_license_start)->format('d M Y') : 'N/A';
                                    $bufferEnd = $certificate->buffer_license_end ? Carbon::parse($certificate->buffer_license_end)->format('d M Y') : 'N/A';
                                    $paidStart = $certificate->paid_license_start ? Carbon::parse($certificate->paid_license_start)->format('d M Y') : '—';
                                    $paidEnd = $certificate->paid_license_end ? Carbon::parse($certificate->paid_license_end)->format('d M Y') : '—';

                                    $licenseHtml = "
                                        <div style='margin-top: 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;'>
                                            <div style='padding: 8px; border-radius: 8px; background-color: #f0fdf4; border: 1px solid #bbf7d0;'>
                                                <div style='font-size: 11px; font-weight: 600; color: #16a34a; margin-bottom: 4px;'>Buffer License</div>
                                                <div style='font-size: 12px; color: #111827;'>{$bufferStart} — {$bufferEnd}</div>
                                            </div>
                                            <div style='padding: 8px; border-radius: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe;'>
                                                <div style='font-size: 11px; font-weight: 600; color: #2563eb; margin-bottom: 4px;'>Paid License</div>
                                                <div style='font-size: 12px; color: #111827;'>{$paidStart} — {$paidEnd}</div>
                                            </div>
                                        </div>
                                    ";
                                }

                                $createdAt = $handover->created_at ? Carbon::parse($handover->created_at)->format('d M Y h:i A') : 'N/A';
                                $statusColor = match ($handover->status) {
                                    'Completed' => '#16a34a',
                                    'Approved' => '#2563eb',
                                    'Rejected' => '#dc2626',
                                    default => '#d97706',
                                };

                                $html .= "
                                    <div style='padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; background-color: #fafafa;'>
                                        <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;'>
                                            <div style='font-weight: 700; font-size: 14px; color: #111827;'>{$handover->project_code}</div>
                                            <span style='padding: 3px 10px; font-size: 11px; font-weight: 600; border-radius: 20px; color: {$statusColor}; background-color: {$statusColor}15; border: 1px solid {$statusColor}40;'>{$handover->status}</span>
                                        </div>
                                        <div style='font-size: 12px; color: #6b7280; margin-bottom: 8px;'>
                                            {$handover->company_name} · Created {$createdAt}
                                        </div>
                                        <div style='margin-bottom: 4px;'>{$moduleBadges}</div>
                                        {$licenseHtml}
                                    </div>
                                ";
                            }

                            $html .= '</div>';

                            return new HtmlString($html);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('2xl'),

                    // Create paid license — pick which handover
                    Action::make('create_paid_license')
                        ->label('Create Paid License')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form(function (SoftwareHandover $record) {
                            $handovers = $this->getCustomerHandovers($record);
                            $handoverOptions = $handovers->mapWithKeys(function ($h) {
                                $modules = collect(['ta' => 'TA', 'tl' => 'TL', 'tc' => 'TC', 'tp' => 'TP', 'tapp' => 'TApp', 'thire' => 'THire', 'tacc' => 'TAcc', 'tpbi' => 'TPBI'])
                                    ->filter(fn ($label, $key) => $h->$key)
                                    ->values()
                                    ->implode(', ');
                                $hasLicense = $h->licenseCertificateById?->paid_license_start ? ' [HAS PAID LICENSE]' : '';
                                return [$h->id => "{$h->project_code} — {$modules}{$hasLicense}"];
                            })->toArray();

                            return [
                                Select::make('handover_id')
                                    ->label('Select Handover')
                                    ->options($handoverOptions)
                                    ->required()
                                    ->helperText('Choose which handover to create a paid license for'),

                                DatePicker::make('paid_license_start')
                                    ->label('Paid License Start Date')
                                    ->required()
                                    ->default(now()->addMonth())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $years = $get('license_years') ?? 1;
                                        if ($state && $years) {
                                            $endDate = \Carbon\Carbon::parse($state)->addYears($years)->subDay();
                                            $set('paid_license_end', $endDate->format('Y-m-d'));
                                            $set('next_renewal_date', $endDate->format('Y-m-d'));
                                        }
                                    }),

                                Select::make('license_years')
                                    ->label('License Duration')
                                    ->options([
                                        1 => '1 Year',
                                        2 => '2 Years',
                                        3 => '3 Years',
                                    ])
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $startDate = $get('paid_license_start');
                                        if ($startDate && $state) {
                                            $endDate = \Carbon\Carbon::parse($startDate)->addYears($state)->subDay();
                                            $set('paid_license_end', $endDate->format('Y-m-d'));
                                            $set('next_renewal_date', $endDate->format('Y-m-d'));
                                        }
                                    }),

                                DatePicker::make('paid_license_end')
                                    ->label('Paid License End Date')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true),

                                DatePicker::make('next_renewal_date')
                                    ->label('Next Renewal Date')
                                    ->helperText('Optional: When should this license be renewed?'),
                            ];
                        })
                        ->fillForm(function (SoftwareHandover $record) {
                            $startDate = now()->addMonth();
                            $endDate = $startDate->copy()->addYear()->subDay();

                            return [
                                'paid_license_start' => $startDate->format('Y-m-d'),
                                'paid_license_end' => $endDate->format('Y-m-d'),
                                'license_years' => 1,
                                'next_renewal_date' => $endDate->format('Y-m-d'),
                            ];
                        })
                        ->action(function (SoftwareHandover $record, array $data) {
                            try {
                                $handover = SoftwareHandover::with('licenseCertificateById')->findOrFail($data['handover_id']);

                                \Illuminate\Support\Facades\Log::info("Form data received", [
                                    'handover_id' => $handover->id,
                                    'form_data' => $data
                                ]);

                                if (!isset($data['paid_license_end']) || empty($data['paid_license_end'])) {
                                    $startDate = \Carbon\Carbon::parse($data['paid_license_start']);
                                    $years = intval($data['license_years'] ?? 1);
                                    $data['paid_license_end'] = $startDate->copy()->addYears($years)->subDay()->format('Y-m-d');
                                }

                                $requiredFields = ['paid_license_start', 'paid_license_end', 'license_years'];
                                foreach ($requiredFields as $field) {
                                    if (!isset($data[$field]) || empty($data[$field])) {
                                        throw new \Exception("Required field '{$field}' is missing or empty");
                                    }
                                }

                                $certificate = $handover->licenseCertificateById ?? new LicenseCertificate();

                                $certificateData = [
                                    'software_handover_id' => $handover->id,
                                    'company_name' => $handover->company_name,
                                    'paid_license_start' => $data['paid_license_start'],
                                    'paid_license_end' => $data['paid_license_end'],
                                    'license_years' => $data['license_years'],
                                    'next_renewal_date' => $data['next_renewal_date'] ?? null,
                                    'updated_by' => auth()->id(),
                                ];

                                if (!$certificate->exists) {
                                    $certificateData['created_by'] = auth()->id();
                                }

                                $certificate->fill($certificateData);
                                $certificate->save();

                                $handover->update([
                                    'license_certification_id' => $certificate->id
                                ]);

                                $selectedModules = [
                                    'ta' => $handover->ta,
                                    'tl' => $handover->tl,
                                    'tc' => $handover->tc,
                                    'tp' => $handover->tp,
                                    'tapp' => $handover->tapp,
                                    'thire' => $handover->thire,
                                    'tacc' => $handover->tacc,
                                    'tpbi' => $handover->tpbi,
                                ];

                                $licenseService = app(\App\Services\HRV2LicenseService::class);

                                $startDateObj = \Carbon\Carbon::parse($data['paid_license_start']);
                                $endDateObj = \Carbon\Carbon::parse($data['paid_license_end']);

                                $apiResult = $licenseService->addPaidApplicationLicenses(
                                    $handover,
                                    $handover->hr_account_id,
                                    $handover->hr_company_id,
                                    $selectedModules,
                                    $handover->formatted_handover_id ?? "SW_{$handover->id}",
                                    $startDateObj,
                                    $endDateObj
                                );

                                if ($apiResult['success']) {
                                    $paidLicenseIds = [];
                                    if (isset($apiResult['results'])) {
                                        foreach ($apiResult['results'] as $app => $result) {
                                            if (isset($result['data']['periodId'])) {
                                                $paidLicenseIds[] = $result['data']['periodId'];
                                            }
                                        }
                                    }

                                    if (!empty($paidLicenseIds)) {
                                        $certificate->update([
                                            'paid_license_ids' => json_encode($paidLicenseIds)
                                        ]);

                                        $handover->update([
                                            'crm_paid_license_ids' => json_encode($paidLicenseIds)
                                        ]);
                                    }

                                    Notification::make()
                                        ->title('Paid License Created Successfully')
                                        ->body("Paid license created for {$handover->project_code} ({$handover->company_name}). Created {$apiResult['success_count']} licenses.")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Paid License Creation Failed')
                                        ->body("Failed to create CRM licenses: " . ($apiResult['error'] ?? 'Unknown error'))
                                        ->danger()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error Creating Paid License')
                                    ->body("An error occurred: " . $e->getMessage())
                                    ->danger()
                                    ->send();

                                \Illuminate\Support\Facades\Log::error("Paid license creation exception", [
                                    'handover_id' => $data['handover_id'] ?? null,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);
                            }
                        }),
                ])
                ->label(false)
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        // Add your authorization logic here
        return auth()->check();
    }
}
