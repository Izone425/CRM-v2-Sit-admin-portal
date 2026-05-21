<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFf;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\SelectFilter;

class AdminResellerHandoverFfPendingTimetecAdmin extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

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

    public function openFilesModal($recordId)
    {
        $this->selectedHandover = ResellerHandoverFf::find($recordId);
        if ($this->selectedHandover) {
            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ResellerHandoverFf::query()
                    ->where('status', 'new')
            )
            ->columns([
                TextColumn::make('ff_id')
                    ->label('FF ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFf $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending_quotation_confirmation',
                        'success' => 'completed',
                        'gray' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'inactive' ? 'InActive' : str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $state)))),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('complete_new')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerHandoverFf $record) => $record->status === 'new')
                    ->modalWidth('xl')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('handover_info')
                            ->label('')
                            ->content(fn (ResellerHandoverFf $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    "ID: {$record->ff_id}<br>RESELLER: {$record->reseller_company_name}<br>SUBSCRIBER: {$record->subscriber_name}"
                                )
                            )
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Placeholder::make('category_display')
                            ->label('')
                            ->content(fn (ResellerHandoverFf $record): \Illuminate\Support\HtmlString =>
                                new \Illuminate\Support\HtmlString(
                                    '<div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; padding: 10px 14px; color: #991b1b; font-weight: 600; font-size: 0.875rem;">'
                                    . 'Category: ' . ($record->category === 'renewal_subscription' ? 'Renewal Subscription' : ($record->category === 'addon_headcount' ? 'AddOn Headcount' : 'N/A'))
                                    . '</div>'
                                )
                            )
                            ->columnSpanFull(),
                        TextInput::make('timetec_proforma_invoice')
                            ->label('TimeTec Proforma Invoice Number')
                            ->required()
                            ->minLength(12)
                            ->maxLength(12)
                            ->alphanum()
                            ->validationMessages([
                                'min' => 'The TimeTec Proforma Invoice Number must be exactly 12 characters.',
                                'max' => 'The TimeTec Proforma Invoice Number must be exactly 12 characters.',
                                'required' => 'The TimeTec Proforma Invoice Number field is required.',
                            ])
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        Textarea::make('admin_reseller_remark')
                            ->label('Admin Reseller Remark')
                            ->rows(2)
                            ->maxLength(1000)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])
                    ->action(function (ResellerHandoverFf $record, array $data) {
                        $record->update([
                            'status' => 'pending_quotation_confirmation',
                            'timetec_proforma_invoice' => $data['timetec_proforma_invoice'] ?? null,
                            'ttpi_submitted_at' => now(),
                            'admin_reseller_remark' => $data['admin_reseller_remark'] ?? null,
                        ]);

                        // Send email notification
                        if (\App\Mail\ResellerHandoverFfStatusUpdate::shouldSend($record->status)) {
                            try {
                                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFfStatusUpdate($record));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to send FF handover email', [
                                    'handover_id' => $record->id,
                                    'status' => 'pending_quotation_confirmation',
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Task completed successfully')
                            ->success()
                            ->send();

                        $this->dispatch('refresh-leadowner-tables');
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Complete'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-ff-pending-timetec-admin');
    }
}
