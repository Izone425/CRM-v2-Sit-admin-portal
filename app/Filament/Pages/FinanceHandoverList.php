<?php

namespace App\Filament\Pages;

use App\Models\CompanyDetail;
use App\Models\FinanceHandover;
use App\Models\HardwareHandoverV2;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Filament\Tables\Actions\Action;

class FinanceHandoverList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Finance Handover List';
    protected static ?string $title = 'Finance Handover';
    protected static ?string $navigationGroup = 'Handovers';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.finance-handover-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(FinanceHandover::query()->with(['lead.companyDetail', 'reseller']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Get the year from created_at, fallback to current year if null
                        $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');

                        return 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable()
                    ->action(
                        Action::make('viewDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (FinanceHandover $record): View {
                                return view('components.finance-handover-details', [
                                    'record' => $record,
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (FinanceHandover $record) {
                        if ($record->created_by) {
                            $user = \App\Models\User::find($record->created_by);
                            return $user ? $user->name : 'Unknown';
                        }
                        return 'Unknown';
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->getStateUsing(function (FinanceHandover $record) {
                        // Get company name from the lead's company detail relationship
                        if ($record->lead && $record->lead->companyDetail) {
                            return $record->lead->companyDetail->company_name;
                        }

                        // Fallback to lead name if no company detail
                        if ($record->lead && $record->lead->name) {
                            return $record->lead->name;
                        }

                        return 'Unknown Company';
                    })
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state || $state === 'Unknown Company') {
                            return $state;
                        }

                        // Create clickable link to lead
                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                            $displayName = mb_strlen($state) > 40 ? mb_substr($state, 0, 40) . '...' : $state;

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . e($displayName) . '
                                </a>');
                        }

                        return $state;
                    })
                    ->html(),

                TextColumn::make('reseller_invoice_number')
                    ->label('Invoice')
                    ->wrap(),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller Name')
                    ->sortable()
                    ->searchable()
                    ->default('Unknown'),

                TextColumn::make('submitted_at')
                    ->label('Date Submit')
                    ->date('d M Y')
                    ->sortable()
                    ->default('Not submitted'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Completed' => 'Completed',
                    ]),

                Tables\Filters\SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        return \App\Models\User::whereIn('role_id', [2])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->where('created_by', $data['value']);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('reseller')
                    ->label('Reseller Company')
                    ->relationship('reseller', 'company_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $records = $this->getFilteredTableQuery()->with(['lead.companyDetail', 'reseller'])->get();

                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        $headers = ['ID', 'Salesperson', 'Company Name', 'Reseller Company Name', 'Date Submit'];
                        foreach ($headers as $col => $header) {
                            $sheet->setCellValue([$col + 1, 1], $header);
                        }
                        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

                        $row = 2;
                        foreach ($records as $record) {
                            $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');
                            $id = 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);

                            $salesperson = 'Unknown';
                            if ($record->created_by) {
                                $user = \App\Models\User::find($record->created_by);
                                $salesperson = $user ? $user->name : 'Unknown';
                            }

                            $companyName = 'Unknown Company';
                            if ($record->lead && $record->lead->companyDetail) {
                                $companyName = $record->lead->companyDetail->company_name;
                            } elseif ($record->lead && $record->lead->name) {
                                $companyName = $record->lead->name;
                            }

                            $resellerName = $record->reseller->company_name ?? 'Unknown';

                            $dateSubmit = $record->submitted_at
                                ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y')
                                : 'Not submitted';

                            $sheet->setCellValue("A{$row}", $id);
                            $sheet->setCellValue("B{$row}", $salesperson);
                            $sheet->setCellValue("C{$row}", $companyName);
                            $sheet->setCellValue("D{$row}", $resellerName);
                            $sheet->setCellValue("E{$row}", $dateSubmit);
                            $row++;
                        }

                        foreach (range('A', 'E') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        $fileName = 'finance_handover_' . now()->format('Ymd_His') . '.xlsx';

                        return response()->streamDownload(function () use ($spreadsheet) {
                            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                            $writer->save('php://output');
                        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
                    }),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s'); // Auto refresh every 60 seconds
    }
}
