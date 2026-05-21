<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoice;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\SoftwareHandover;

class HrAutoRenewalTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HrSalesInvoice::query()
            )
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                        'pending' => 'Pending',
                    ])
                    ->placeholder('All Status'),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From Date'),
                        DatePicker::make('end_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['start_date']) && empty($data['end_date'])) {
                            return null;
                        }
                        $parts = [];
                        if ($data['start_date']) {
                            $parts[] = 'From ' . Carbon::parse($data['start_date'])->format('Y-m-d');
                        }
                        if ($data['end_date']) {
                            $parts[] = 'To ' . Carbon::parse($data['end_date'])->format('Y-m-d');
                        }
                        return implode(' ', $parts);
                    }),
            ])
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (HrSalesInvoice $record) => $record->software_handover_id
                        ? url('/admin/view-sales-invoice?' . http_build_query([
                            'invoiceId' => $record->id,
                            'softwareHandoverId' => $record->software_handover_id,
                            'from' => 'auto-renewal',
                        ]))
                        : null),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('primary')
                    ->url(function (HrSalesInvoice $record) {
                        if (!$record->software_handover_id) return null;

                        $softwareHandover = SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                            ->find($record->software_handover_id);

                        if (!$softwareHandover?->hr_account_id || !$softwareHandover?->hr_company_id) return null;

                        return url('/admin/hr-company-license-details?' . http_build_query([
                            'hrAccountId' => $softwareHandover->hr_account_id,
                            'hrCompanyId' => $softwareHandover->hr_company_id,
                        ]));
                    }),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->sortable()
                    ->date('Y-m-d'),

                TextColumn::make('invoice_amount')
                    ->label('Amount')
                    ->sortable()
                    ->money('MYR')
                    ->alignEnd(),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('auto_renewal')
                    ->label('Auto Renewal')
                    ->formatStateUsing(function ($state, $record) {
                        $isEnabled = $state === 'Enabled';
                        $color = $isEnabled ? '#10b981' : '#d1d5db';
                        $translate = $isEnabled ? 'translateX(20px)' : 'translateX(0)';

                        return new \Illuminate\Support\HtmlString(
                            '<button wire:click="toggleAutoRenewal(' . $record->id . ')" style="'
                            . 'position:relative;display:inline-flex;align-items:center;width:44px;height:24px;'
                            . 'border-radius:9999px;border:none;cursor:pointer;transition:all 0.2s;'
                            . 'background-color:' . $color . ';">'
                            . '<span style="display:block;width:18px;height:18px;border-radius:9999px;'
                            . 'background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);transition:all 0.2s;'
                            . 'transform:' . $translate . ';margin-left:3px;"></span>'
                            . '</button>'
                        );
                    })
                    ->html(),
            ])
            ->striped()
            ->defaultSort('invoice_date', 'desc')
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        return $this->exportCsv();
                    }),
            ]);
    }

    public function toggleAutoRenewal(int $recordId): void
    {
        $invoice = HrSalesInvoice::find($recordId);
        if (!$invoice) return;

        $newValue = $invoice->auto_renewal === 'Enabled' ? 'Disabled' : 'Enabled';
        $invoice->update(['auto_renewal' => $newValue]);

        // Also update the hr_licenses auto_renewal for this handover
        if ($invoice->software_handover_id) {
            \App\Models\HrLicense::where('software_handover_id', $invoice->software_handover_id)
                ->where('type', 'PAID')
                ->where('status', 'Enabled')
                ->update(['auto_renewal' => $newValue]);
        }
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->orderBy('invoice_date', 'desc')->get();

        $filename = 'auto-renewals-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'Invoice No', 'Company Name',
                'Invoice Date', 'Amount', 'Payment Status', 'Auto Renewal',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->invoice_no,
                    $record->company_name,
                    $record->invoice_date?->format('Y-m-d'),
                    $record->invoice_amount,
                    $record->payment_status,
                    $record->auto_renewal,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-auto-renewal-table');
    }
}
