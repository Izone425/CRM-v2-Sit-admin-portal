<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use App\Models\HrSalesInvoice;
use App\Models\HrOfficialReceipt;
use App\Models\ResellerV2;
use App\Models\SoftwareHandover;

class HrBillingSalesInvoice extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.hr-billing-sales-invoice';
    protected static ?string $navigationLabel = 'Sales Invoice';
    protected static ?string $title = 'Sales of Invoice';
    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-sales-invoice';

    public function table(Table $table): Table
    {
        return $table
            ->query(HrSalesInvoice::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'PAID' => 'Paid',
                        'CANCEL' => 'Cancel',
                    ])
                    ->placeholder('All Invoices'),
            ])
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (HrSalesInvoice $record) => url("/admin/view-sales-invoice?" . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'billing',
                    ]))),

                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->sortable()
                    ->date('d M Y'),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('primary')
                    ->url(function (HrSalesInvoice $record) {
                        if (!$record->handover_id) {
                            return null;
                        }

                        $softwareHandover = $record->software_handover_id
                            ? SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])->find($record->software_handover_id)
                            : null;

                        return url('/admin/hr-company-license-details?' . http_build_query([
                            'hrAccountId' => $softwareHandover?->hr_account_id,
                            'hrCompanyId' => $softwareHandover?->hr_company_id,
                        ]));
                    }),

                TextColumn::make('lead.country')
                    ->label('Country')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? \Illuminate\Support\Str::title($state) : '-'),

                TextColumn::make('reseller')
                    ->label('Reseller')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('sales_amount')
                    ->label('Sales Amt')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn ($record) => $record->sales_amount
                        ? $record->currency . ' ' . number_format($record->sales_amount, 2)
                        : '-'),

                TextColumn::make('commission')
                    ->label('Commission')
                    ->wrap()
                    ->formatStateUsing(function ($record) {
                        $handover = $record->software_handover_id
                            ? SoftwareHandover::select(['id', 'reseller_id'])->find($record->software_handover_id)
                            : null;

                        if (!$handover?->reseller_id) {
                            return '-';
                        }

                        $rate = ResellerV2::where('id', $handover->reseller_id)->value('commission_rate');

                        if (!$rate || !$record->sales_amount) {
                            return '-';
                        }

                        $commission = $record->sales_amount * ($rate / 100);

                        return $record->currency . ' ' . number_format($commission, 2);
                    }),

                TextColumn::make('pi_no')
                    ->label('PI No.')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('invoice_amount')
                    ->label('Inv. Amount')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn ($record) => $record->invoice_amount
                        ? $record->currency . ' ' . number_format($record->invoice_amount, 2)
                        : '-'),

                TextColumn::make('created_by_name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAID' => 'success',
                        'PENDING' => 'warning',
                        'CANCEL' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->url(fn (HrSalesInvoice $record) => url("/admin/view-sales-invoice?" . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'billing',
                    ])))
                    ->visible(fn (HrSalesInvoice $record) => $record->status === 'pending'),

                Action::make('view_receipt')
                    ->label('View Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(function (HrSalesInvoice $record) {
                        $or = HrOfficialReceipt::where('invoice_no', $record->invoice_no)->first();

                        if (!$or) {
                            return null;
                        }

                        return url('/admin/view-official-receipt?' . http_build_query([
                            'orNo' => $or->or_no,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]));
                    })
                    ->visible(fn (HrSalesInvoice $record) => $record->status === 'paid'),
            ])
            ->defaultSort('invoice_date', 'desc');
    }
}
