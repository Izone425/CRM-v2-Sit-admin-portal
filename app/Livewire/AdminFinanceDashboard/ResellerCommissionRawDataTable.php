<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use App\Models\CrmInvoiceDetail;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;

class ResellerCommissionRawDataTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;

    public static function placeholder(array $params = [])
    {
        return view('components.rc-table-skeleton');
    }

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function exportExcel()
    {
        $records = $this->getFilteredTableQuery()->orderBy('f_created_time', 'desc')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'Date', 'AP Number', 'TT Number', 'Invoice', 'Reseller Name', 'Subscriber Name', 'Amount', 'TT Status', 'Currency'];
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        foreach ($records as $index => $record) {
            $row = $index + 2;
            $sheet->setCellValue([1, $row], $index + 1);
            $sheet->setCellValue([2, $row], $record->f_created_time ? \Carbon\Carbon::parse($record->f_created_time)->format('d M Y') : '');
            $sheet->setCellValue([3, $row], $record->f_invoice_no);
            $sheet->setCellValue([4, $row], $record->tt_invoice_no ?? '');
            $sheet->setCellValue([5, $row], $record->autocount_inv_no ?? '-');
            $sheet->setCellValue([6, $row], strtoupper($record->reseller_name ?? 'N/A'));
            $sheet->setCellValue([7, $row], strtoupper($record->subscriber_name ?? 'N/A'));
            $sheet->setCellValue([8, $row], (float) $record->f_total_amount);
            $sheet->setCellValue([9, $row], match ((int) ($record->tt_invoice_status ?? -1)) {
                0 => 'Paid',
                1 => 'Unpaid',
                default => $record->tt_invoice_status ?? '',
            });
            $sheet->setCellValue([10, $row], $record->f_currency ?? '');
        }

        // Format amount column as number with 2 decimal places
        $lastRow = count($records) + 1;
        $sheet->getStyle("H2:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'reseller-commission-raw-data-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CrmInvoiceDetail::query()
                    ->select([
                        'crm_invoice_details.f_id',
                        'crm_invoice_details.f_invoice_no',
                        'crm_invoice_details.f_desc',
                        'crm_invoice_details.f_company_id',
                        'crm_invoice_details.f_total_amount',
                        'crm_invoice_details.f_status',
                        'crm_invoice_details.f_created_time',
                        'crm_invoice_details.f_currency',
                        DB::raw("TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) as tt_invoice_no"),
                        DB::raw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) as autocount_inv_no"),
                        DB::raw("(SELECT rl.reseller_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as reseller_name"),
                        DB::raw("(SELECT rl.f_company_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as subscriber_name"),
                        DB::raw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) as tt_invoice_status"),
                    ])
                    ->where('crm_invoice_details.f_invoice_no', 'LIKE', 'AP%')
                    ->where('crm_invoice_details.f_created_time', '>=', '2026-01-01')
                    ->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IN (0, 1)")
            )
            ->defaultSort('f_created_time', 'desc')
            ->columns([
                TextColumn::make('f_created_time')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('f_invoice_no')
                    ->label('AP Number')
                    ->sortable()
                    ->searchable()
                    ->url(function ($record) {
                        if (!$record->f_invoice_no) return null;
                        $license = DB::connection('frontenddb')
                            ->table('crm_invoice_details')
                            ->where('f_invoice_no', $record->f_invoice_no)
                            ->first(['f_id']);
                        if (!$license || !$license->f_id) return null;
                        $encrypted = openssl_encrypt($license->f_id, 'AES-128-ECB', 'Epicamera@99');
                        return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted);
                    }, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('tt_invoice_no')
                    ->label('TT Number')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereRaw(
                            "TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIKE ?",
                            ['%' . $search . '%']
                        );
                    })
                    ->url(function ($record) {
                        if (!$record->tt_invoice_no) return null;
                        $license = DB::connection('frontenddb')
                            ->table('crm_invoice_details')
                            ->where('f_invoice_no', $record->tt_invoice_no)
                            ->first(['f_id']);
                        if (!$license || !$license->f_id) return null;
                        $encrypted = openssl_encrypt($license->f_id, 'AES-128-ECB', 'Epicamera@99');
                        return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . base64_encode($encrypted);
                    }, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('autocount_inv_no')
                    ->label('Invoice')
                    ->placeholder('-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereRaw(
                            "TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) LIKE ?",
                            ['%' . $search . '%']
                        );
                    }),

                TextColumn::make('tt_invoice_status')
                    ->label('TT Status')
                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            0 => 'Paid',
                            1 => 'Unpaid',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(fn ($state) => match ((int) $state) {
                        0 => 'success',
                        1 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('reseller_name')
                    ->label('Reseller Name')
                    ->placeholder('N/A')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereRaw(
                            "(SELECT rl.reseller_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) LIKE ?",
                            ['%' . $search . '%']
                        );
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->wrap(),

                TextColumn::make('f_total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->f_currency ?? 'MYR')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->placeholder('N/A')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereRaw(
                            "(SELECT rl.f_company_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) LIKE ?",
                            ['%' . $search . '%']
                        );
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->wrap(),
            ])
            ->filters([

                Filter::make('year_month')
                    ->form([
                        Select::make('year')
                            ->label('Year')
                            ->options(fn () => collect(range(now()->year, 2026, -1))->mapWithKeys(fn ($y) => [$y => $y])->toArray()),
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                '01' => 'January', '02' => 'February', '03' => 'March',
                                '04' => 'April', '05' => 'May', '06' => 'June',
                                '07' => 'July', '08' => 'August', '09' => 'September',
                                '10' => 'October', '11' => 'November', '12' => 'December',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['year'], fn (Builder $query, $year) => $query->whereYear('crm_invoice_details.f_created_time', $year))
                            ->when($data['month'], fn (Builder $query, $month) => $query->whereMonth('crm_invoice_details.f_created_time', $month));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['year'] ?? null) $indicators[] = 'Year: ' . $data['year'];
                        if ($data['month'] ?? null) $indicators[] = 'Month: ' . \Carbon\Carbon::create()->month((int) $data['month'])->format('F');
                        return $indicators;
                    }),

                SelectFilter::make('tt_invoice_status')
                    ->label('TT Status')
                    ->options([
                        '0' => 'Paid',
                        '1' => 'Unpaid',
                    ])
                    ->default('0')
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === null || $data['value'] === '') return $query;
                        return $query->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) = ?", [$data['value']]);
                    }),

                SelectFilter::make('f_currency')
                    ->label('Currency')
                    ->options(fn () => CrmInvoiceDetail::query()
                        ->where('f_invoice_no', 'LIKE', 'AP%')
                        ->where('f_created_time', '>=', '2026-01-01')
                        ->distinct()
                        ->pluck('f_currency', 'f_currency')
                        ->filter()
                        ->toArray()
                    ),

                SelectFilter::make('amount_type')
                    ->label('Amount Type')
                    ->options([
                        'zero_negative' => 'Zero + Negative Amount',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === null || $data['value'] === '') return $query;
                        return $query->where('crm_invoice_details.f_total_amount', '<=', 0);
                    }),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => $this->exportExcel()),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyState(fn () => view('components.empty-state-question'))
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->recordClasses(function (CrmInvoiceDetail $record) use (&$duplicateTtNos) {
                if (!isset($duplicateTtNos)) {
                    $duplicateTtNos = CrmInvoiceDetail::query()
                        ->where('f_invoice_no', 'LIKE', 'AP%')
                        ->where('f_created_time', '>=', '2026-01-01')
                        ->selectRaw("TRIM(SUBSTRING(f_desc, LOCATE('TT', f_desc))) as tt_inv, COUNT(*) as cnt")
                        ->groupByRaw("TRIM(SUBSTRING(f_desc, LOCATE('TT', f_desc)))")
                        ->having('cnt', '>', 1)
                        ->pluck('tt_inv')
                        ->flip()
                        ->all();
                }

                $ttInvoiceNo = $record->tt_invoice_no;
                return ($ttInvoiceNo && isset($duplicateTtNos[$ttInvoiceNo]))
                    ? 'bg-red-100 dark:bg-red-900/20'
                    : null;
            });
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.reseller-commission-raw-data-table');
    }
}
