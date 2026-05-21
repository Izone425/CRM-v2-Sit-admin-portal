<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\View\View;
use Livewire\Attributes\On;

class AdminDatabaseCreationAll extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $lastRefreshTime;

    public $showDetailModal = false;
    public $selectedRequest = null;

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

    #[On('database-creation-updated')]
    public function refreshFromEvent()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openDetailModal($requestId)
    {
        $this->selectedRequest = ResellerDatabaseCreation::find($requestId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedRequest = null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerDatabaseCreation::query())
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewDetails')
                            ->action(fn (ResellerDatabaseCreation $record) => $this->openDetailModal($record->id))
                    ),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->sortable(),
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        'draft' => 'Draft',
                    ]),
                SelectFilter::make('reseller_id')
                    ->label('Reseller Name')
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->pluck('reseller_name', 'reseller_id')
                            ->map(fn ($name) => strtoupper($name))
                            ->toArray();
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-database-creation-all');
    }
}
