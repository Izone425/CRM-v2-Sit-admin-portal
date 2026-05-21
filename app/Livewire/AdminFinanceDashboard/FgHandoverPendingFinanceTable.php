<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FgHandoverPendingFinanceTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];

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

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandoverFg::query()->where('status', 'pending_timetec_finance')->orderBy('rni_submitted_at', 'desc'))
            ->columns([
                TextColumn::make('fg_id')
                    ->label('FG ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFg $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FG', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),
                TextColumn::make('autocount_invoice_number')
                    ->label('A/C Invoice')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('timetec_proforma_invoice')
                    ->label('TT Invoice No')
                    ->searchable()
                    ->placeholder('-')
                    ->weight('bold')
                    ->url(fn (ResellerHandoverFg $record): ?string => $record->invoice_url)
                    ->openUrlInNewTab()
                    ->color(fn (ResellerHandoverFg $record): ?string => $record->invoice_url ? 'primary' : null),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('official_receipt_number')
                    ->label('Receipt No')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending_timetec_finance',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rni_submitted_at')
                    ->label('Payment Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (ResellerHandoverFg $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? '0 Day' : '-' . $daysDiff . ' Days';
                    })
                    ->color(function (ResellerHandoverFg $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'success' : 'danger';
                    })
                    ->weight(function (ResellerHandoverFg $record) {
                        $today = now()->startOfDay();
                        $updatedAt = $record->updated_at->startOfDay();
                        $daysDiff = $today->diffInDays($updatedAt);

                        return $daysDiff == 0 ? 'normal' : 'bold';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('updated_at', $direction === 'asc' ? 'desc' : 'asc');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalIcon(null)
                        ->modalHeading('Mark as Completed')
                        ->modalDescription(fn (ResellerHandoverFg $record) => new \Illuminate\Support\HtmlString(
                            '<div style="text-align: left;">' .
                            '<div>' . $record->fg_id . '</div>' .
                            '<div>' . $record->reseller_company_name . '</div>' .
                            '<div>' . $record->subscriber_name . '</div>' .
                            '</div>'
                        ))
                        ->action(function (ResellerHandoverFg $record): void {
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            // Send email to reseller
                            try {
                                Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                Log::error('Failed to send FG completed email', [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            Notification::make()
                                ->title('Marked as Completed')
                                ->success()
                                ->body("FG handover {$record->fg_id} completed. Email sent to reseller.")
                                ->send();

                            $this->resetTable();
                            $this->dispatch('refresh-leadowner-tables');
                        }),

                    Action::make('mark_rejected')
                        ->label('Mark as Rejected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label(false)
                                ->required()
                                ->markAsRequired(false)
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                ->rows(3),
                        ])
                        ->modalHeading('Rejection Reason')
                        ->modalSubmitActionLabel('Reject')
                        ->action(function (ResellerHandoverFg $record, array $data): void {
                            // Remove existing payment slip
                            if ($record->reseller_payment_slip) {
                                Storage::disk('public')->delete($record->reseller_payment_slip);
                            }

                            $record->update([
                                'status' => 'pending_reseller_payment',
                                'rejection_reason' => $data['rejection_reason'],
                                'reseller_payment_slip' => null,
                                'rni_submitted_at' => null,
                            ]);

                            // Send rejection email to reseller
                            try {
                                Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                Log::error('Failed to send FG rejection email', [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            Notification::make()
                                ->title('Payment Rejected')
                                ->danger()
                                ->body("FG handover {$record->fg_id} rejected. Payment slip removed. Email sent to reseller.")
                                ->send();

                            $this->resetTable();
                            $this->dispatch('refresh-leadowner-tables');
                        }),
                ])->button(),
            ])
            ->bulkActions([
                BulkAction::make('batch_complete')
                    ->label('Batch Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Batch Mark as Completed')
                    ->modalDescription(fn (\Illuminate\Database\Eloquent\Collection $records) => "Mark {$records->count()} record(s) as completed?")
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            try {
                                Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($record));
                            } catch (\Exception $e) {
                                Log::error('Failed to send FG completed email (batch)', [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            $count++;
                        }

                        Notification::make()
                            ->title("{$count} record(s) marked as completed")
                            ->success()
                            ->send();

                        $this->resetTable();
                        $this->dispatch('refresh-leadowner-tables');
                    }),
            ])
            ->defaultSort('rni_submitted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandoverFg::find($handoverId);

        if ($this->selectedHandover) {
            $this->handoverFiles = $this->selectedHandover->getCategorizedFilesForModal();
            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.fg-handover-pending-finance-table');
    }
}
