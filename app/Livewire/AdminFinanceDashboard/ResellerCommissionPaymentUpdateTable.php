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
use App\Models\DebtorAging;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\ResellerHandoverFe;
use App\Models\ResellerCommissionHandover;
use App\Models\ResellerV2;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use App\Mail\ResellerCommissionStatusUpdate;
use Illuminate\Support\Facades\Mail;

class ResellerCommissionPaymentUpdateTable extends Component implements HasForms, HasTable
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

    private function getRecordIssues($record): array
    {
        $issues = [];

        if (ResellerHandoverFe::where('ap_document', $record->f_invoice_no)->exists()) {
            $issues[] = 'Duplicate AP Number (Bill as End User)';
        }

        if (!$record->crm_reseller_id || !ResellerV2::where('reseller_id', $record->crm_reseller_id)->exists()) {
            $issues[] = 'Reseller Account Not Available';
        } else {
            $reseller = ResellerV2::where('reseller_id', $record->crm_reseller_id)->first();
            if ($reseller && $reseller->reseller_commission !== 'enable') {
                $issues[] = 'FH ID Features Disable';
            }
        }

        $amount = (float) ($record->f_total_amount ?? 0);
        if ($amount == 0) {
            $issues[] = 'AP - Zero Amount';
        } elseif ($amount < 0) {
            $issues[] = 'AP - Negative Amount';
        }

        return $issues;
    }

    public function table(Table $table): Table
    {
        // Get autocount invoice numbers that have full payment (outstanding = 0)
        $fullPaymentInvNos = DebtorAging::where('outstanding', 0)
            ->pluck('invoice_number')
            ->toArray();

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
                        DB::raw("(SELECT LPAD(rl.reseller_id, 10, '0') FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as crm_reseller_id"),
                        DB::raw("(SELECT rl.reseller_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as reseller_name"),
                        DB::raw("(SELECT rl.f_company_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as subscriber_name"),
                        DB::raw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) as tt_invoice_status"),
                    ])
                    ->where('crm_invoice_details.f_invoice_no', 'LIKE', 'AP%')
                    ->where('crm_invoice_details.f_created_time', '>=', '2026-01-01')
                    ->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IN (0, 1)")
                    ->whereRaw("(SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IS NOT NULL")
                    ->whereRaw("(SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) != ''")
                    ->when(!empty($fullPaymentInvNos), function ($query) use ($fullPaymentInvNos) {
                        $placeholders = implode(',', array_fill(0, count($fullPaymentInvNos), '?'));
                        $query->whereRaw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) IN ({$placeholders})", $fullPaymentInvNos);
                    }, function ($query) {
                        // No full payment records exist, return empty
                        $query->whereRaw('1 = 0');
                    })
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

                TextColumn::make('ac_inv_status')
                    ->label('Payment Status')
                    ->state(function ($record) {
                        return 'Full Payment';
                    })
                    ->badge()
                    ->color('success'),

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

                TextColumn::make('f_total_amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->f_currency ?? 'MYR')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('f_currency')
                    ->label('Currency')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('action_required_status')
                    ->label('Action Required Status')
                    ->options([
                        'fh_disable' => 'FH ID Features Disable',
                        'reseller_unavailable' => 'Reseller Account Not Available',
                        'duplicate_ap' => 'Duplicate AP Number (Bill as End User)',
                        'zero_amount' => 'AP - Zero Amount',
                        'negative_amount' => 'AP - Negative Amount',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;

                        switch ($data['value']) {
                            case 'duplicate_ap':
                                $apNumbers = ResellerHandoverFe::whereNotNull('ap_document')
                                    ->pluck('ap_document')
                                    ->toArray();
                                return $query->whereIn('crm_invoice_details.f_invoice_no', $apNumbers);

                            case 'reseller_unavailable':
                                $validResellerIds = ResellerV2::pluck('reseller_id')
                                    ->map(fn ($id) => str_pad($id, 10, '0', STR_PAD_LEFT))
                                    ->toArray();
                                return $query->whereRaw("(
                                    (SELECT LPAD(rl.reseller_id, 10, '0') FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) IS NULL
                                    OR (SELECT LPAD(rl.reseller_id, 10, '0') FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) NOT IN ('" . implode("','", $validResellerIds) . "')
                                )");

                            case 'fh_disable':
                                $disabledResellerIds = ResellerV2::where('reseller_commission', '!=', 'enable')
                                    ->orWhereNull('reseller_commission')
                                    ->pluck('reseller_id')
                                    ->map(fn ($id) => str_pad($id, 10, '0', STR_PAD_LEFT))
                                    ->toArray();
                                if (empty($disabledResellerIds)) return $query->whereRaw('1 = 0');
                                return $query->whereRaw("(SELECT LPAD(rl.reseller_id, 10, '0') FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) IN ('" . implode("','", $disabledResellerIds) . "')");

                            case 'zero_amount':
                                return $query->where('crm_invoice_details.f_total_amount', 0);

                            case 'negative_amount':
                                return $query->where('crm_invoice_details.f_total_amount', '<', 0);
                        }

                        return $query;
                    }),

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
            ->actions([
                // Show issues when requirements are not met
                Action::make('issues_warning')
                    ->iconButton()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->tooltip(function ($record) {
                        $issues = $this->getRecordIssues($record);
                        return implode("\n", array_map(fn ($issue) => "• " . $issue, $issues));
                    })
                    ->action(fn () => null)
                    ->visible(function ($record) {
                        if (ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)->exists()) {
                            return false;
                        }
                        return !empty($this->getRecordIssues($record));
                    }),

                ActionGroup::make([
                    Action::make('complete_task')
                        ->label('Complete Task')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Task')
                        ->modalDescription('Are you sure you want to mark this task as completed?')
                        ->hidden(function ($record) {
                            if ((float) ($record->f_total_amount ?? 0) <= 0) return true;
                            return ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)->exists();
                        })
                        ->action(function ($record) {
                            $handover = ResellerCommissionHandover::create([
                                'reseller_id' => $record->crm_reseller_id,
                                'ap_invoice_no' => $record->f_invoice_no,
                                'tt_invoice_no' => $record->tt_invoice_no,
                                'autocount_inv_no' => $record->autocount_inv_no,
                                'reseller_name' => $record->reseller_name,
                                'subscriber_name' => $record->subscriber_name,
                                'amount' => $record->f_total_amount,
                                'currency' => $record->f_currency ?? 'MYR',
                                'status' => 'pending_reseller',
                            ]);

                            try {
                                if (ResellerCommissionStatusUpdate::shouldSend($handover->status)) {
                                    Mail::send(new ResellerCommissionStatusUpdate($handover));
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to send commission status email', [
                                    'handover_id' => $handover->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }

                            Notification::make()
                                ->title('Task completed')
                                ->body('PI No ' . $record->f_invoice_no . ' has been sent to Pending Reseller.')
                                ->success()
                                ->send();

                            $this->resetTable();
                        }),

                    Action::make('credit_note')
                        ->label('Credit Note')
                        ->icon('heroicon-o-document-minus')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as Credit Note')
                        ->modalDescription('Are you sure you want to mark this record as a Credit Note?')
                        ->hidden(function ($record) {
                            if ((float) ($record->f_total_amount ?? 0) <= 0) return true;
                            return ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)->exists();
                        })
                        ->action(function ($record) {
                            ResellerCommissionHandover::create([
                                'reseller_id' => $record->crm_reseller_id,
                                'ap_invoice_no' => $record->f_invoice_no,
                                'tt_invoice_no' => $record->tt_invoice_no,
                                'autocount_inv_no' => $record->autocount_inv_no,
                                'reseller_name' => $record->reseller_name,
                                'subscriber_name' => $record->subscriber_name,
                                'amount' => $record->f_total_amount,
                                'currency' => $record->f_currency ?? 'MYR',
                                'status' => 'credit_note',
                            ]);

                            Notification::make()
                                ->title('Credit Note')
                                ->body('AP ' . $record->f_invoice_no . ' has been marked as Credit Note.')
                                ->success()
                                ->send();

                            $this->resetTable();
                        }),
                ])
                ->visible(function ($record) {
                    return !ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)->exists()
                        && empty($this->getRecordIssues($record));
                }),

                Action::make('complete_task_done')
                    ->iconButton()
                    ->icon('heroicon-s-check-circle')
                    ->color('success')
                    ->tooltip(function ($record) {
                        $handover = ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)
                            ->whereIn('status', ['pending_finance', 'completed'])
                            ->first();
                        if (!$handover) return 'Task Completed';
                        return $handover->fh_id . ' | ' . ucwords(str_replace('_', ' ', $handover->status));
                    })
                    ->action(fn () => null)
                    ->visible(function ($record) {
                        return ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)
                            ->whereIn('status', ['pending_finance', 'completed'])
                            ->exists();
                    }),

                Action::make('credit_note_done')
                    ->iconButton()
                    ->icon('heroicon-s-x-circle')
                    ->color('danger')
                    ->tooltip('Credit Note')
                    ->action(fn () => null)
                    ->visible(function ($record) {
                        return ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)
                            ->where('status', 'credit_note')
                            ->exists();
                    }),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $records = $this->getFilteredTableQuery()->get();
                        $timestamp = now()->format('Y-m-d_H-i-s');

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\ResellerCommissionPaymentExport($records),
                            "reseller_commission_payment_{$timestamp}.xlsx"
                        );
                    }),
            ])
            ->bulkActions([])
            ->emptyState(fn () => view('components.empty-state-question'))
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->recordClasses(function (CrmInvoiceDetail $record) {
                // Check if marked as credit note
                if (ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)
                    ->where('status', 'credit_note')
                    ->exists()) {
                    return 'bg-red-100 dark:bg-red-900/20';
                }

                $ttInvoiceNo = $record->tt_invoice_no;
                if (!$ttInvoiceNo) return null;

                $count = CrmInvoiceDetail::query()
                    ->where('f_invoice_no', 'LIKE', 'AP%')
                    ->where('f_created_time', '>=', '2026-01-01')
                    ->whereRaw("TRIM(SUBSTRING(f_desc, LOCATE('TT', f_desc))) = ?", [$ttInvoiceNo])
                    ->count();

                return $count > 1 ? 'bg-red-100 dark:bg-red-900/20' : null;
            });
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.reseller-commission-payment-update-table');
    }
}
