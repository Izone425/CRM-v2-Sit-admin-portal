<?php

namespace App\Filament\Pages;

use App\Models\HrdfClaim;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;

class HrdfClaimTracker extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Claim Tracker';
    protected static ?string $title = 'HRDF Claim Tracker';
    protected static string $view = 'filament.pages.hrdf-claim-tracker';

    /**
     * Get summary statistics for all statuses
     */
    public function getStats(): array
    {
        $allClaims = HrdfClaim::query();

        $stats = [
            'all' => [
                'label' => 'ALL',
                'count' => $allClaims->count(),
                'amount' => $allClaims->sum('invoice_amount'),
            ],
            'pending' => [
                'label' => 'PENDING',
                'count' => (clone $allClaims)->where('claim_status', 'PENDING')->count(),
                'amount' => (clone $allClaims)->where('claim_status', 'PENDING')->sum('invoice_amount'),
            ],
            'submitted' => [
                'label' => 'SUBMITTED',
                'count' => (clone $allClaims)->where('claim_status', 'SUBMITTED')->count(),
                'amount' => (clone $allClaims)->where('claim_status', 'SUBMITTED')->sum('invoice_amount'),
            ],
            'approved' => [
                'label' => 'APPROVED',
                'count' => (clone $allClaims)->where('claim_status', 'APPROVED')->count(),
                'amount' => (clone $allClaims)->where('claim_status', 'APPROVED')->sum('invoice_amount'),
            ],
            'received' => [
                'label' => 'RECEIVED',
                'count' => (clone $allClaims)->where('claim_status', 'RECEIVED')->count(),
                'amount' => (clone $allClaims)->where('claim_status', 'RECEIVED')->sum('invoice_amount'),
            ],
        ];

        return $stats;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HrdfClaim::query()
                    ->leftJoin('hrdf_handovers', 'hrdf_claims.hrdf_grant_id', '=', 'hrdf_handovers.hrdf_grant_id')
                    ->select('hrdf_claims.*', 'hrdf_handovers.id as handover_id', 'hrdf_handovers.lead_id as handover_lead_id')
                    // ->orderByRaw('
                    //     CASE hrdf_claims.claim_status
                    //         WHEN "PENDING" THEN 1
                    //         WHEN "SUBMITTED" THEN 2
                    //         WHEN "APPROVED" THEN 3
                    //         WHEN "RECEIVED" THEN 4
                    //         ELSE 5
                    //     END,
                    //     hrdf_claims.created_at DESC
                    // ')
            )
            ->columns([
                TextColumn::make('hrdfHandover.id')
                    ->label('HRDF ID')
                    ->formatStateUsing(function ($state, HrdfClaim $record) {
                        // Check if handover exists
                        if (!$record->hrdfHandover || !$record->hrdfHandover->id) {
                            return 'N/A';
                        }

                        $handover = $record->hrdfHandover;

                        // Get year from created_at (last 2 digits)
                        $year = $handover->created_at ? $handover->created_at->format('y') : '25';

                        // Format: HRDF_{year}{padded_id}
                        return 'HRDF_' . $year . str_pad($handover->id, 4, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable()
                    ->searchable()
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HrdfClaim $record): View {
                                // Get the handover from the claim record
                                $handoverRecord = $record->hrdfHandover;

                                return view('components.hrdf-handover')
                                    ->with('extraAttributes', ['record' => $handoverRecord]);
                            })
                    ),

                TextColumn::make('sales_person')
                    ->label('SalesPerson'),

                TextColumn::make('hrdfHandover.lead_id')
                    ->label('Lead ID')
                    ->formatStateUsing(function ($state, HrdfClaim $record) {
                        // Get lead_id from hrdfHandover relationship
                        return $record->hrdfHandover?->lead_id ?? 'N/A';
                    })
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->sortable()
                    ->copyable()
                    ->weight('medium')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('hrdf_claims.hrdf_grant_id', 'like', "%{$search}%");
                    }),

                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable(),

                TextColumn::make('invoice_amount')
                    ->label('Invoice Amount')
                    ->money('MYR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('hrdf_training_date')
                    ->label('Training Date')
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('claim_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'PENDING',
                        'primary' => 'SUBMITTED',
                        'success' => 'APPROVED',
                        'info' => 'RECEIVED',
                    ])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('hrdf_claims.claim_status', $direction);
                    }),

                TextColumn::make('updated_at')
                    ->label('Last Modified At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('received_at')
                    ->label('Received At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('hrdf_claim_id')
                    ->label('HRDF Claim ID')
                    ->default(fn (HrdfClaim $record) => $record->hrdf_claim_id ?: 'N/A')
                    ->copyable(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->weight('bold')
                    ->color('danger')
                    ->getStateUsing(function (HrdfClaim $record) {
                        if (strtoupper($record->claim_status) !== 'PENDING') {
                            return null;
                        }

                        $raw = trim((string) $record->hrdf_training_date);
                        if ($raw === '') {
                            return null;
                        }

                        $startStr = trim(explode('To', $raw)[0]);
                        try {
                            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startStr);
                        } catch (\Throwable) {
                            return null;
                        }

                        $days = $startDate->startOfDay()->diffInDays(now()->startOfDay(), false);

                        return $days > 0 ? $days . ' days' : null;
                    }),
            ])
            ->filters([
                SelectFilter::make('claim_status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'SUBMITTED' => 'Submitted',
                        'APPROVED' => 'Approved',
                        'RECEIVED' => 'Received',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                // Filter by Sales Person
                SelectFilter::make('sales_person')
                    ->label('Sales Person')
                    ->options(function (): array {
                        return HrdfClaim::query()
                            ->whereNotNull('sales_person')
                            ->where('sales_person', '!=', '')
                            ->distinct()
                            ->orderBy('sales_person')
                            ->pluck('sales_person', 'sales_person')
                            ->toArray();
                    })
                    ->placeholder('All Sales Persons')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('invoice_number_filter')
                    ->label('Invoice Number')
                    ->options([
                        'with_invoice' => 'With Invoice',
                        'without_invoice' => 'Without Invoice',
                    ])
                    ->placeholder('All')
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'with_invoice') {
                            return $query->whereNotNull('hrdf_claims.invoice_number')
                                ->where('hrdf_claims.invoice_number', '!=', '');
                        }

                        if ($data['value'] === 'without_invoice') {
                            return $query->where(function ($q) {
                                $q->whereNull('hrdf_claims.invoice_number')
                                  ->orWhere('hrdf_claims.invoice_number', '');
                            });
                        }

                        return $query;
                    }),

                // Filter by Training Date
                Filter::make('hrdf_training_date')
                    ->form([
                        DatePicker::make('training_date_from')
                            ->label('Training Date From'),
                        DatePicker::make('training_date_until')
                            ->label('Training Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['training_date_from'],
                                function (Builder $query, $date) {
                                    // Parse varchar format "DD/MM/YYYY To : DD/MM/YYYY" or "DD/MM/YYYY"
                                    // Extract the first date from the string and compare
                                    $filterDate = \Carbon\Carbon::parse($date)->format('d/m/Y');
                                    return $query->where(function ($q) use ($filterDate) {
                                        $q->where('hrdf_claims.hrdf_training_date', 'like', $filterDate . '%')
                                          ->orWhereRaw("STR_TO_DATE(SUBSTRING_INDEX(hrdf_claims.hrdf_training_date, ' To', 1), '%d/%m/%Y') >= STR_TO_DATE(?, '%d/%m/%Y')", [$filterDate]);
                                    });
                                },
                            )
                            ->when(
                                $data['training_date_until'],
                                function (Builder $query, $date) {
                                    // Parse varchar format and compare against the end date if exists, or start date
                                    $filterDate = \Carbon\Carbon::parse($date)->format('d/m/Y');
                                    return $query->where(function ($q) use ($filterDate) {
                                        // If there's a "To :" then check the end date, otherwise check the single date
                                        $q->whereRaw("
                                            CASE
                                                WHEN hrdf_claims.hrdf_training_date LIKE '%To :%'
                                                THEN STR_TO_DATE(TRIM(SUBSTRING_INDEX(hrdf_claims.hrdf_training_date, 'To :', -1)), '%d/%m/%Y') <= STR_TO_DATE(?, '%d/%m/%Y')
                                                ELSE STR_TO_DATE(hrdf_claims.hrdf_training_date, '%d/%m/%Y') <= STR_TO_DATE(?, '%d/%m/%Y')
                                            END
                                        ", [$filterDate, $filterDate]);
                                    });
                                },
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['training_date_from'] ?? null) {
                            $indicators[] = 'Training date from ' . \Carbon\Carbon::parse($data['training_date_from'])->toFormattedDateString();
                        }

                        if ($data['training_date_until'] ?? null) {
                            $indicators[] = 'Training date until ' . \Carbon\Carbon::parse($data['training_date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('
                CASE hrdf_claims.claim_status
                    WHEN "PENDING" THEN 1
                    WHEN "SUBMITTED" THEN 2
                    WHEN "APPROVED" THEN 3
                    WHEN "RECEIVED" THEN 4
                    ELSE 5
                END,
                hrdf_claims.created_at DESC
            '))
            ->striped()
            ->paginated([50, 100]);
    }

    /**
     * Update invoice mapping and auto-update sales person and HRDF claim ID
     */
    private function updateInvoiceMapping(HrdfClaim $record, ?string $invoiceNumber): void
    {
        if (!$invoiceNumber) {
            return;
        }

        // Find the invoice
        $invoice = Invoice::where('invoice_no', $invoiceNumber)->first();

        if (!$invoice) {
            Notification::make()
                ->title('Invoice not found')
                ->danger()
                ->send();
            return;
        }

        // Update the HRDF claim
        $record->mapInvoiceDetails(
            $invoiceNumber,
            $invoice->sales_person ?? $record->sales_person
        );

        // Update the invoice with HRDF claim reference
        $invoice->update([
            'hrdf_claim_id' => $record->hrdf_claim_id,
            'hrdf_grant_id' => $record->hrdf_grant_id,
        ]);

        // Auto-update status if needed
        if ($record->claim_status === 'PENDING') {
            $record->updateStatus('SUBMITTED');
        }
    }
}
