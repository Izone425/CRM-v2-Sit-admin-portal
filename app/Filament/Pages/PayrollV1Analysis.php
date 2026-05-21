<?php

namespace App\Filament\Pages;

use App\Models\TimetecPayrollAnalysis;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class PayrollV1Analysis extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationLabel = 'Payroll Version 1';
    protected static ?string $title = 'Payroll Version 1';
    protected static ?string $slug = 'payroll-v1-analysis';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.payroll-v1-analysis';

    public array $licenseStats = ['active' => 0, 'inactive' => 0];
    public array $accountTypeStats = ['internal' => 0, 'paid' => 0];
    public array $activityStats = ['last_30_days' => 0, 'last_60_days' => 0, 'others' => 0];

    public bool $slideoverOpen = false;
    public string $slideoverTitle = '';
    public array $slideoverRows = [];
    public bool $showActions = false;

    public function mount(): void
    {
        $this->loadStats();
    }

    public function openSegment(string $chart, string $segment): void
    {
        $today = now()->toDateString();

        $base = DB::table('timetec_payroll_analysis')
            ->select(
                'account_code',
                'account_name',
                'account_database',
                'total_company',
                'company_license_count',
                'total_employee_active',
                'employee_license_count',
            );

        switch ($chart) {
            case 'license':
                if ($segment === 'active') {
                    $query = $base->whereNotNull('license_expiry_date')
                        ->whereDate('license_expiry_date', '>', $today);
                    $this->slideoverTitle = 'Active Licenses';
                } else {
                    $query = $base->whereNotNull('license_expiry_date')
                        ->whereDate('license_expiry_date', '<=', $today);
                    $this->slideoverTitle = 'Inactive Licenses';
                }
                break;

            case 'account_type':
                if ($segment === 'internal') {
                    $query = $base->where('testing_account', 1);
                    $this->slideoverTitle = 'Internal Use Accounts';
                } else {
                    $query = $base->where('testing_account', 0);
                    $this->slideoverTitle = 'Paid Customer Accounts';
                }
                break;

            case 'activity':
                if ($segment === 'last_30_days') {
                    $query = $base->where('last_30days_process', 1);
                    $this->slideoverTitle = 'Processed in Last 30 Days';
                } elseif ($segment === 'last_60_days') {
                    $query = $base->where('last_60days_process', 1)
                        ->where('last_30days_process', 0);
                    $this->slideoverTitle = 'Processed in Last 60 Days (not 30)';
                } else {
                    $query = $base->where(function ($q) {
                        $q->where('last_60days_process', 0)
                            ->orWhereNull('last_60days_process');
                    });
                    $this->slideoverTitle = 'Others (>60 days or never processed)';
                }
                break;

            default:
                return;
        }

        $rows = $query->orderBy('account_database')->orderBy('account_code')->get();

        $this->slideoverRows = $rows
            ->groupBy(fn ($row) => $row->account_database ?: 'Uncategorized')
            ->map(fn ($group) => $group->map(fn ($row) => [
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'total_company' => $row->total_company,
                'company_license_count' => $row->company_license_count,
                'total_employee_active' => $row->total_employee_active,
                'employee_license_count' => $row->employee_license_count,
            ])->values()->toArray())
            ->toArray();

        $this->slideoverOpen = true;
    }

    public function closeSlideover(): void
    {
        $this->slideoverOpen = false;
        $this->slideoverRows = [];
        $this->slideoverTitle = '';
    }

    public function table(Table $table): Table
    {
        $today = now()->toDateString();

        return $table
            ->query(TimetecPayrollAnalysis::query())
            ->defaultSort('created_date', 'desc')
            ->defaultPaginationPageOption(50)
            ->striped()
            ->columns([
                TextColumn::make('row_no')
                    ->label('No.')
                    ->state(function (\stdClass $rowLoop, $livewire): string {
                        return (string) ($rowLoop->iteration
                            + ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1)));
                    })
                    ->alignCenter(),

                TextColumn::make('created_date')
                    ->label('DB Creation')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('license_expiry_date')
                    ->label('Expiry Date')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('license_expiry_date_status')
                    ->label('DB Status')
                    ->state(fn (TimetecPayrollAnalysis $record) => $record->license_expiry_date)
                    ->formatStateUsing(function ($state) use ($today) {
                        if (! $state) return 'No Expiry';
                        return $state > $today ? 'Active' : 'InActive';
                    })
                    ->colors([
                        'success' => fn ($state) => $state && $state > $today,
                        'danger' => fn ($state) => $state && $state <= $today,
                        'gray' => fn ($state) => ! $state,
                    ])
                    ->sortable(['license_expiry_date']),

                BadgeColumn::make('testing_account')
                    ->label('DB Type')
                    ->formatStateUsing(fn ($state) => $state ? 'Internal' : 'Paid')
                    ->colors([
                        'warning' => fn ($state) => (int) $state === 1,
                        'primary' => fn ($state) => (int) $state === 0,
                    ])
                    ->sortable(),

                TextColumn::make('account_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('account_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(60),

                TextColumn::make('company_ratio')
                    ->label('Company')
                    ->state(fn (TimetecPayrollAnalysis $record) => ($record->total_company ?? 0).' / '.($record->company_license_count ?? 0))
                    ->alignRight(),

                TextColumn::make('employee_ratio')
                    ->label('Employee')
                    ->state(fn (TimetecPayrollAnalysis $record) => ($record->total_employee_active ?? 0).' / '.($record->employee_license_count ?? 0))
                    ->alignRight(),

                TextColumn::make('account_database')
                    ->label('Database')
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('testing_account')
                    ->label('Database Type')
                    ->placeholder('All')
                    ->trueLabel('Internal Use')
                    ->falseLabel('Paid Customer'),

                SelectFilter::make('license_status')
                    ->label('Database Status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'InActive',
                        'no_expiry' => 'No Expiry',
                    ])
                    ->query(function (Builder $query, array $data) use ($today) {
                        if (empty($data['value'])) return $query;
                        return match ($data['value']) {
                            'active' => $query->whereNotNull('license_expiry_date')->whereDate('license_expiry_date', '>', $today),
                            'expired' => $query->whereNotNull('license_expiry_date')->whereDate('license_expiry_date', '<=', $today),
                            'no_expiry' => $query->whereNull('license_expiry_date'),
                            default => $query,
                        };
                    }),

                SelectFilter::make('account_database')
                    ->label('Database')
                    ->options(fn () => TimetecPayrollAnalysis::query()
                        ->whereNotNull('account_database')
                        ->distinct()
                        ->orderBy('account_database')
                        ->pluck('account_database', 'account_database')
                        ->toArray()),
            ])
            ->headerActions([
                Action::make('toggleActionsColumn')
                    ->label(fn () => $this->showActions ? 'Hide Actions' : 'Show Actions')
                    ->icon(fn () => $this->showActions ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(function () {
                        $this->showActions = ! $this->showActions;
                    }),
            ])
            ->actions([
                Action::make('toggleInternal')
                    ->visible(fn () => $this->showActions)
                    ->label(fn (TimetecPayrollAnalysis $record) => $record->testing_account ? 'Mark as Paid' : 'Mark as Internal')
                    ->icon(fn (TimetecPayrollAnalysis $record) => $record->testing_account ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-beaker')
                    ->color(fn (TimetecPayrollAnalysis $record) => $record->testing_account ? 'primary' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (TimetecPayrollAnalysis $record) => $record->testing_account
                        ? 'Mark account as Paid Customer?'
                        : 'Mark account as Internal Use?')
                    ->modalDescription(fn (TimetecPayrollAnalysis $record) => $record->account_code.' — '.$record->account_name)
                    ->action(function (TimetecPayrollAnalysis $record) {
                        $newValue = $record->testing_account ? 0 : 1;
                        $record->testing_account = $newValue;
                        $record->save();

                        $this->loadStats();

                        $this->dispatch('pv1-stats-updated', stats: [
                            'license' => $this->licenseStats,
                            'account' => $this->accountTypeStats,
                            'activity' => $this->activityStats,
                        ]);

                        Notification::make()
                            ->success()
                            ->title($newValue ? 'Marked as Internal Use' : 'Marked as Paid Customer')
                            ->body($record->account_code.' — '.$record->account_name)
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulkMarkAsPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Mark selected accounts as Paid Customer?')
                    ->action(function ($records) {
                        $eligible = $records->filter(fn ($r) => (int) $r->testing_account === 1);
                        $skipped = $records->count() - $eligible->count();

                        if ($eligible->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Nothing to update')
                                ->body('All selected accounts are already Paid.')
                                ->send();
                            return;
                        }

                        $count = TimetecPayrollAnalysis::whereIn('id', $eligible->pluck('id'))
                            ->update(['testing_account' => 0]);

                        $this->loadStats();
                        $this->dispatch('pv1-stats-updated', stats: [
                            'license' => $this->licenseStats,
                            'account' => $this->accountTypeStats,
                            'activity' => $this->activityStats,
                        ]);

                        Notification::make()
                            ->success()
                            ->title("Marked {$count} account(s) as Paid Customer")
                            ->body($skipped > 0 ? "{$skipped} already Paid were skipped." : null)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulkMarkAsInternal')
                    ->label('Mark as Internal')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mark selected accounts as Internal Use?')
                    ->action(function ($records) {
                        $eligible = $records->filter(fn ($r) => (int) $r->testing_account === 0);
                        $skipped = $records->count() - $eligible->count();

                        if ($eligible->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Nothing to update')
                                ->body('All selected accounts are already Internal.')
                                ->send();
                            return;
                        }

                        $count = TimetecPayrollAnalysis::whereIn('id', $eligible->pluck('id'))
                            ->update(['testing_account' => 1]);

                        $this->loadStats();
                        $this->dispatch('pv1-stats-updated', stats: [
                            'license' => $this->licenseStats,
                            'account' => $this->accountTypeStats,
                            'activity' => $this->activityStats,
                        ]);

                        Notification::make()
                            ->success()
                            ->title("Marked {$count} account(s) as Internal Use")
                            ->body($skipped > 0 ? "{$skipped} already Internal were skipped." : null)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    protected function loadStats(): void
    {
        $today = now()->toDateString();

        // Chart 1: license_expiry_date — active if > today, inactive if <= today
        $this->licenseStats = [
            'active' => (int) DB::table('timetec_payroll_analysis')
                ->whereNotNull('license_expiry_date')
                ->whereDate('license_expiry_date', '>', $today)
                ->count(),
            'inactive' => (int) DB::table('timetec_payroll_analysis')
                ->whereNotNull('license_expiry_date')
                ->whereDate('license_expiry_date', '<=', $today)
                ->count(),
        ];

        // Chart 2: testing_account — 1 internal, 0 paid customer
        $this->accountTypeStats = [
            'internal' => (int) DB::table('timetec_payroll_analysis')
                ->where('testing_account', 1)
                ->count(),
            'paid' => (int) DB::table('timetec_payroll_analysis')
                ->where('testing_account', 0)
                ->count(),
        ];

        // Chart 3: activity — last_30days_process / last_60days_process / others
        $last30 = (int) DB::table('timetec_payroll_analysis')
            ->where('last_30days_process', 1)
            ->count();

        $last60Only = (int) DB::table('timetec_payroll_analysis')
            ->where('last_60days_process', 1)
            ->where('last_30days_process', 0)
            ->count();

        $others = (int) DB::table('timetec_payroll_analysis')
            ->where(function ($q) {
                $q->where('last_60days_process', 0)
                    ->orWhereNull('last_60days_process');
            })
            ->count();

        $this->activityStats = [
            'last_30_days' => $last30,
            'last_60_days' => $last60Only,
            'others' => $others,
        ];
    }
}
