<?php

namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class DebtorRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Raw Data';
    protected static ?string $title = 'Debtor Raw Data';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.debtor-raw-data';

    public $allDebtorStats;
    public $hrdfDebtorStats;
    public $productDebtorStats;
    public $resellerDebtorStats;

    public $activeAnalysisFilter = 'all';

    public $filterDecimalPlaces = 2;

    public function setAnalysisFilter(string $filter): void
    {
        $this->activeAnalysisFilter = in_array($filter, ['all', 'hrdf', 'product', 'reseller'], true)
            ? $filter
            : 'all';

        $this->resetTable();
    }

    protected function applyAnalysisFilter(Builder $query): Builder
    {
        return match ($this->activeAnalysisFilter) {
            'hrdf' => $query->where('invoice_number', 'like', 'EHIN%'),
            'product' => $query->where('invoice_number', 'like', 'EPIN%'),
            'reseller' => $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('invoice_number', 'not like', 'EPIN%')
                        ->where('invoice_number', 'not like', 'EHIN%');
                })->orWhereIn('company_name', function ($subq) {
                    $subq->select('company_name')
                        ->from('reseller_v2')
                        ->whereNotNull('company_name');
                });
            }),
            default => $query,
        };
    }

    public function mount(): void
    {
        $this->loadData();
    }

    protected function getBaseQuery()
    {
        return DebtorAging::query()
            ->where('outstanding', '>', 0)
            ->where(function ($q) {
                $q->where('debtor_code', 'like', 'ARM%')
                    ->orWhere('debtor_code', 'like', 'ARU%');
            })
            ->whereNotExists(function ($sub) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('invoices')
                    ->whereColumn('invoices.invoice_no', 'debtor_agings.invoice_number')
                    ->where('invoices.invoice_status', 'V');
            });
    }

    protected function loadData(): void
    {
        $baseQuery = $this->getBaseQuery();

        $this->allDebtorStats = $this->getStats(clone $baseQuery);
        $this->hrdfDebtorStats = $this->getStats(clone $baseQuery, 'EHIN%');
        $this->productDebtorStats = $this->getStats(clone $baseQuery, 'EPIN%');
        $this->resellerDebtorStats = $this->getStats(clone $baseQuery, null, 'reseller');
    }

    protected function getStats($query, ?string $invoicePrefix = null, ?string $invoiceType = null): array
    {
        if ($invoicePrefix) {
            $query->where('invoice_number', 'like', $invoicePrefix);
        }

        if ($invoiceType === 'reseller') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('invoice_number', 'not like', 'EPIN%')
                        ->where('invoice_number', 'not like', 'EHIN%');
                })->orWhereIn('company_name', function ($subq) {
                    $subq->select('company_name')
                        ->from('reseller_v2')
                        ->whereNotNull('company_name');
                });
            });
        }

        $totalInvoices = $query->count();
        $totalAmount = $query->sum(DB::raw('
            CASE
                WHEN currency_code = "MYR" THEN outstanding
                WHEN outstanding IS NOT NULL AND exchange_rate IS NOT NULL THEN outstanding * exchange_rate
                ELSE 0
            END
        '));

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'formatted_amount' => number_format($totalAmount, 2),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->applyAnalysisFilter($this->getBaseQuery()))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                TextColumn::make('support')
                    ->label('Support')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),

                BadgeColumn::make('payment_status_display')
                    ->label('Payment Status')
                    ->getStateUsing(fn(DebtorAging $record): string => $this->determinePaymentStatus($record))
                    ->colors([
                        'danger' => 'UnPaid',
                        'warning' => 'Partial Payment',
                        'success' => 'Full Payment',
                    ]),

                TextColumn::make('outstanding_rm')
                    ->label('Outstanding (RM)')
                    ->getStateUsing(function (DebtorAging $record): float {
                        return $record->currency_code === 'MYR'
                            ? $record->outstanding
                            : ($record->outstanding * $record->exchange_rate);
                    })
                    ->numeric(
                        decimalPlaces: fn() => $this->filterDecimalPlaces,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->alignRight(),

                TextColumn::make('debtor_code')
                    ->label('Debtor Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('aging')
                    ->label('Debtor Aging')
                    ->getStateUsing(fn(DebtorAging $record): string => $this->calculateAgingText($record))
                    ->colors([
                        'success' => 'Current',
                        'info' => '1 Month',
                        'warning' => fn($state) => in_array($state, ['2 Months', '3 Months']),
                        'danger' => fn($state) => in_array($state, ['4 Months', '5+ Months']),
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('invoice_type_display')
                    ->label('Invoice Type')
                    ->getStateUsing(function (DebtorAging $record): string {
                        if (str_starts_with($record->invoice_number, 'EPIN')) return 'Product';
                        if (str_starts_with($record->invoice_number, 'EHIN')) return 'HRDF';
                        return 'Reseller';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('decimal_places')
                    ->options([
                        2 => '2 Decimal Places',
                        4 => '4 Decimal Places',
                    ])
                    ->label('Decimal Places')
                    ->default(2)
                    ->query(function (Builder $query, array $data) {
                        $this->filterDecimalPlaces = $data['value'] ?? 2;
                        return $query;
                    }),

                SelectFilter::make('currency_code')
                    ->options(function () {
                        return DebtorAging::query()
                            ->where('outstanding', '>', 0)
                            ->where(function ($q) {
                                $q->where('debtor_code', 'like', 'ARM%')
                                    ->orWhere('debtor_code', 'like', 'ARU%');
                            })
                            ->whereNotNull('currency_code')
                            ->where('currency_code', '!=', '')
                            ->distinct()
                            ->pluck('currency_code', 'currency_code')
                            ->toArray();
                    })
                    ->label('Currency Type')
                    ->multiple(),

                SelectFilter::make('support')
                    ->options(function () {
                        $options = DebtorAging::query()
                            ->where('outstanding', '>', 0)
                            ->where(function ($q) {
                                $q->where('debtor_code', 'like', 'ARM%')
                                    ->orWhere('debtor_code', 'like', 'ARU%');
                            })
                            ->whereNotNull('support')
                            ->where('support', '!=', '')
                            ->distinct()
                            ->pluck('support', 'support')
                            ->toArray();
                        $options['blank'] = 'Blank/Missing';
                        return $options;
                    })
                    ->label('Support')
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) return $query;
                        return $query->where(function ($q) use ($data) {
                            $q->whereIn('support', $data['values']);
                            if (in_array('blank', $data['values'])) {
                                $q->orWhereNull('support')->orWhere('support', '');
                            }
                        });
                    }),

                SelectFilter::make('salesperson')
                    ->options(function () {
                        return DebtorAging::query()
                            ->where('outstanding', '>', 0)
                            ->where(function ($q) {
                                $q->where('debtor_code', 'like', 'ARM%')
                                    ->orWhere('debtor_code', 'like', 'ARU%');
                            })
                            ->whereNotNull('salesperson')
                            ->where('salesperson', '!=', '')
                            ->distinct()
                            ->pluck('salesperson', 'salesperson')
                            ->toArray();
                    })
                    ->label('Salesperson')
                    ->multiple(),

                SelectFilter::make('invoice_type')
                    ->options([
                        'hrdf' => 'HRDF',
                        'product' => 'Product',
                    ])
                    ->label('Invoice Type')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        if ($data['value'] === 'hrdf') return $query->where('invoice_number', 'like', 'EHIN%');
                        if ($data['value'] === 'product') return $query->where('invoice_number', 'like', 'EPIN%');
                    }),

                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial Payment',
                    ])
                    ->label('Payment Status')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        if ($data['value'] === 'unpaid') return $query->whereRaw('outstanding = invoice_amount');
                        if ($data['value'] === 'partial') return $query->whereRaw('outstanding < invoice_amount')->where('outstanding', '>', 0);
                    }),

                SelectFilter::make('debtor_aging')
                    ->options([
                        'current' => 'Current',
                        '1_month' => '1 Month',
                        '2_months' => '2 Months',
                        '3_months' => '3 Months',
                        '4_months' => '4 Months',
                        '5_plus_months' => '5+ Months',
                    ])
                    ->label('Debtor Aging')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        $this->applyDebtorAgingFilter($query, $data['value']);
                        return $query;
                    }),

                Filter::make('invoice_date_range')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Invoice Date Range')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                            $query->whereBetween('invoice_date', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            return 'Invoice Date: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' to ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),

                SelectFilter::make('year')
                    ->options(function () {
                        $years = [];
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 3; $i--) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->label('Year')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereYear('invoice_date', $data['value']);
                    }),

                SelectFilter::make('month')
                    ->options([
                        1 => 'January', 2 => 'February', 3 => 'March',
                        4 => 'April', 5 => 'May', 6 => 'June',
                        7 => 'July', 8 => 'August', 9 => 'September',
                        10 => 'October', 11 => 'November', 12 => 'December',
                    ])
                    ->label('Month')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereMonth('invoice_date', $data['value']);
                    }),

                SelectFilter::make('invoice_age_days')
                    ->options([
                        '30_days' => 'More than 30 Days',
                        '60_days' => 'More than 60 Days',
                        '90_days' => 'More than 90 Days',
                        '120_days' => 'More than 120 Days',
                    ])
                    ->label('Invoice Age')
                    ->placeholder('All Invoice Ages')
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        $this->applyInvoiceAgeDaysFilter($query, $data['value']);
                        return $query;
                    }),
            ])
            ->filtersFormColumns(3)
            ->defaultSort('invoice_date', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginated([50, 100])
            ->headerActions([
                // \Filament\Tables\Actions\Action::make('sync_n8n')
                //     ->label('Sync AutoCount')
                //     ->icon('heroicon-o-arrow-path')
                //     ->color('primary')
                //     ->visible(fn () => in_array(auth()->id(), [1, 14]))
                //     ->requiresConfirmation()
                //     ->modalHeading('Sync Debtor Aging via n8n')
                //     ->modalDescription('This will run the n8n workflow to refresh debtor aging from AutoCount. Continue?')
                //     ->action(function () {
                //         $webhookUrl = config('services.n8n.debtor_aging_webhook_url');

                //         if (empty($webhookUrl)) {
                //             \Filament\Notifications\Notification::make()
                //                 ->title('n8n webhook URL not configured')
                //                 ->body('Set N8N_DEBTOR_AGING_WEBHOOK_URL in .env')
                //                 ->danger()
                //                 ->send();
                //             return;
                //         }

                //         try {
                //             $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                //                 ->timeout(60)
                //                 ->post($webhookUrl);

                //             if ($response->successful()) {
                //                 \Filament\Notifications\Notification::make()
                //                     ->title('Sync triggered')
                //                     ->body('n8n workflow is running. Refresh the page in a moment to see updated data.')
                //                     ->success()
                //                     ->send();
                //             } else {
                //                 \Filament\Notifications\Notification::make()
                //                     ->title('n8n returned an error')
                //                     ->body('Status ' . $response->status() . ': ' . \Illuminate\Support\Str::limit($response->body(), 200))
                //                     ->danger()
                //                     ->send();
                //             }
                //         } catch (\Throwable $e) {
                //             \Filament\Notifications\Notification::make()
                //                 ->title('Failed to reach n8n')
                //                 ->body($e->getMessage())
                //                 ->danger()
                //                 ->send();
                //         }
                //     }),

                \Filament\Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $records = $this->getFilteredTableQuery()->orderBy('invoice_date', 'desc')->get();

                        $timestamp = now()->format('Y-m-d_H-i-s');
                        $filename = "debtor_raw_data_{$timestamp}.csv";

                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];

                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');

                            // BOM for Excel UTF-8
                            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                            fputcsv($file, [
                                'Debtor Code', 'Company Name', 'Invoice Number', 'Invoice Date',
                                'Debtor Aging', 'Salesperson', 'Invoice Type', 'Payment Status',
                                'Currency', 'Support', 'Outstanding (RM)',
                            ]);

                            foreach ($records as $record) {
                                $outstandingRm = $record->currency_code === 'MYR'
                                    ? $record->outstanding
                                    : ($record->outstanding * $record->exchange_rate);

                                $salesperson = $record->salesperson;
                                if ((!$salesperson || trim($salesperson) === '') && $record->support && trim($record->support) !== '') {
                                    $salesperson = $record->support;
                                }

                                $invoiceType = 'Other';
                                if (str_starts_with($record->invoice_number, 'EPIN')) $invoiceType = 'Product';
                                if (str_starts_with($record->invoice_number, 'EHIN')) $invoiceType = 'HRDF';

                                fputcsv($file, [
                                    $record->debtor_code,
                                    $record->company_name,
                                    $record->invoice_number,
                                    $record->invoice_date ? Carbon::parse($record->invoice_date)->format('d/m/Y') : '',
                                    $this->calculateAgingText($record),
                                    $salesperson ?? 'N/A',
                                    $invoiceType,
                                    $this->determinePaymentStatus($record),
                                    $record->currency_code,
                                    $record->support ?? 'N/A',
                                    number_format($outstandingRm, 2),
                                ]);
                            }

                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
            ]);
    }

    protected function determinePaymentStatus($record): string
    {
        if (!isset($record->outstanding) || (float) $record->outstanding === 0.0) {
            return 'Full Payment';
        }
        if ((float) $record->outstanding === (float) $record->invoice_amount) {
            return 'UnPaid';
        }
        if ((float) $record->outstanding < (float) $record->invoice_amount && (float) $record->outstanding > 0) {
            return 'Partial Payment';
        }
        return 'UnPaid';
    }

    protected function calculateAgingText(DebtorAging $record): string
    {
        if (!$record->aging_date) {
            return 'N/A';
        }

        $due = Carbon::parse($record->aging_date);
        $now = Carbon::now();

        if ($due->greaterThanOrEqualTo($now)) {
            return 'Current';
        }

        $monthsDiff = $now->diffInMonths($due);

        return match (true) {
            $monthsDiff == 0 => 'Current',
            $monthsDiff == 1 => '1 Month',
            $monthsDiff == 2 => '2 Months',
            $monthsDiff == 3 => '3 Months',
            $monthsDiff == 4 => '4 Months',
            default => '5+ Months',
        };
    }

    protected function applyDebtorAgingFilter($query, $agingFilter): void
    {
        $now = Carbon::now();

        match ($agingFilter) {
            'current' => $query->where(function ($q) use ($now) {
                $q->where('aging_date', '>=', $now)
                    ->orWhere(function ($subQ) use ($now) {
                        $subQ->where('aging_date', '<', $now)
                            ->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 0', [$now]);
                    });
            }),
            '1_month' => $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 1', [$now]),
            '2_months' => $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 2', [$now]),
            '3_months' => $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 3', [$now]),
            '4_months' => $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) = 4', [$now]),
            '5_plus_months' => $query->whereRaw('TIMESTAMPDIFF(MONTH, aging_date, ?) >= 5', [$now]),
            default => null,
        };
    }

    protected function applyInvoiceAgeDaysFilter($query, $daysFilter): void
    {
        $today = Carbon::now()->startOfDay();

        $days = match ($daysFilter) {
            '30_days' => 30,
            '60_days' => 60,
            '90_days' => 90,
            '120_days' => 120,
            default => null,
        };

        if ($days) {
            $query->where('invoice_date', '<', $today->copy()->subDays($days));
        }
    }
}
