<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFd;
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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class AdminResellerHandoverFdAll extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
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
        $this->selectedHandover = ResellerHandoverFd::find($recordId);
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

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandoverFd::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('fd_id')
                    ->label('FD ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFd $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FD', DATE_FORMAT(created_at, '%y%m'), '-', LPAD((SELECT COUNT(*) FROM reseller_handover_fds AS rh2 WHERE YEAR(rh2.created_at) = YEAR(reseller_handover_fds.created_at) AND MONTH(rh2.created_at) = MONTH(reseller_handover_fds.created_at) AND rh2.id <= reseller_handover_fds.id), 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),
                TextColumn::make('autocount_invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'pending_quotation_confirmation',
                        'info' => 'pending_timetec_invoice',
                        'danger' => 'pending_invoice_confirmation',
                        'success' => 'pending_timetec_license',
                        'warning' => 'pending_reseller_payment',
                        'info' => 'pending_timetec_finance',
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
                    ->label('Updated At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'pending_quotation_confirmation' => 'Pending Quotation Confirmation',
                        'pending_timetec_invoice' => 'Pending TimeTec Invoice',
                        'pending_invoice_confirmation' => 'Pending Invoice Confirmation',
                        'pending_timetec_license' => 'Pending TimeTec License',
                        'pending_reseller_payment' => 'Pending Reseller Payment',
                        'pending_timetec_finance' => 'Pending TimeTec Finance',
                        'completed' => 'Completed',
                        'inactive' => 'InActive',
                    ])
                    ->default(null),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-fd-all');
    }
}
