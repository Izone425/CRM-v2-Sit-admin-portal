<?php

namespace App\Livewire;

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
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class AdminResellerHandoverFgPendingResellerPayment extends Component implements HasForms, HasTable
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
        $this->selectedHandover = ResellerHandoverFg::find($recordId);
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
            ->query(
                ResellerHandoverFg::query()
                    ->where('status', 'pending_reseller_payment')
            )
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
                        'danger' => 'pending_reseller_payment',
                    ])
                    ->formatStateUsing(fn (string $state): string => str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $state)))),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-handover-fg-pending-reseller-payment');
    }
}
