<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoiceItem;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\SoftwareHandover;

class ExpiringInvoicesTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HrSalesInvoiceItem::query()
                    ->with('salesInvoice')
                    ->whereNotNull('license_end_date')
            )
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options(fn () => \App\Models\HrSalesInvoice::distinct()->pluck('currency', 'currency')->filter()->toArray())
                    ->placeholder('All Currencies')
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('salesInvoice', fn ($q) => $q->where('currency', $data['value']))
                        : $query),

                SelectFilter::make('created_by_name')
                    ->label('Sales Person')
                    ->options(fn () => \App\Models\HrSalesInvoice::distinct()->pluck('created_by_name', 'created_by_name')->filter()->toArray())
                    ->placeholder('All Sales Persons')
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('salesInvoice', fn ($q) => $q->where('created_by_name', $data['value']))
                        : $query),

                SelectFilter::make('license_type')
                    ->label('Product')
                    ->options(fn () => HrSalesInvoiceItem::distinct()->pluck('license_type', 'license_type')->filter()->toArray())
                    ->placeholder('All Products'),

                Filter::make('expiring_in')
                    ->form([
                        \Filament\Forms\Components\Select::make('months')
                            ->label('Expiring In')
                            ->options([
                                '1' => '1 Month',
                                '2' => '2 Months',
                                '3' => '3 Months',
                            ])
                            ->default('3'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['months'])) {
                            return $query;
                        }
                        $months = (int) $data['months'];
                        return $query
                            ->whereDate('license_end_date', '>=', Carbon::today())
                            ->whereDate('license_end_date', '<=', Carbon::today()->addMonths($months));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['months'])) {
                            return null;
                        }
                        return 'Expiring in ' . $data['months'] . ' month' . ((int) $data['months'] > 1 ? 's' : '');
                    }),
            ])
            ->columns([
                TextColumn::make('salesInvoice.invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoiceItem $record) => $record->salesInvoice
                        ? url('/admin/view-sales-invoice?invoiceId=' . $record->salesInvoice->id . '&softwareHandoverId=' . $record->salesInvoice->software_handover_id . '&from=expiring-invoices')
                        : null),

                TextColumn::make('salesInvoice.invoice_date')
                    ->label('Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('salesInvoice.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('primary')
                    ->size('sm')
                    ->url(function (HrSalesInvoiceItem $record) {
                        if (!$record->salesInvoice?->software_handover_id) {
                            return null;
                        }

                        $softwareHandover = SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                            ->find($record->salesInvoice->software_handover_id);

                        return url('/admin/hr-company-license-details?' . http_build_query([
                            'hrAccountId' => $softwareHandover?->hr_account_id,
                            'hrCompanyId' => $softwareHandover?->hr_company_id,
                        ]));
                    }),

                TextColumn::make('total_after_tax')
                    ->label('Invoice Amount')
                    ->sortable()
                    ->size('sm')
                    ->formatStateUsing(fn ($record) => ($record->salesInvoice?->currency ?? 'MYR') . ' ' . number_format($record->total_after_tax, 2)),

                TextColumn::make('quantity')
                    ->label('Unit')
                    ->sortable()
                    ->size('sm')
                    ->alignCenter(),

                TextColumn::make('license_type')
                    ->label('Product Name')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('license_start_date')
                    ->label('Start Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('license_end_date')
                    ->label('Expiry Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('salesInvoice.created_by_name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-'),
            ])
            ->striped()
            ->defaultSort('license_end_date', 'asc');
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->with('salesInvoice')->orderBy('license_end_date', 'asc')->get();

        $filename = 'expiring-invoices-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'Invoice No', 'Date', 'Company Name', 'Invoice Amount',
                'Unit', 'Product Name', 'Start Date', 'Expiry Date', 'Created By',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->salesInvoice?->invoice_no ?? '-',
                    $record->salesInvoice?->invoice_date?->format('Y-m-d') ?? '-',
                    $record->salesInvoice?->company_name ?? '-',
                    ($record->salesInvoice?->currency ?? 'MYR') . ' ' . number_format($record->total_after_tax ?? 0, 2),
                    $record->quantity,
                    $record->license_type,
                    $record->license_start_date?->format('Y-m-d') ?? '-',
                    $record->license_end_date?->format('Y-m-d') ?? '-',
                    $record->salesInvoice?->created_by_name ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.expiring-invoices-table');
    }
}
