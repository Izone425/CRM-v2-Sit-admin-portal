<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ResellerDatabaseCreationStatusUpdate;
use Illuminate\View\View;
use Livewire\Attributes\On;

class AdminDatabaseCreationNew extends Component implements HasTable, HasForms
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
            ->query(ResellerDatabaseCreation::query()->where('status', 'new'))
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
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Textarea::make('admin_remark')
                            ->label('Admin Remark')
                            ->rows(8)
                            ->required()
                            ->default("DATABASE NAME :  \nMASTER EMAIL :  \nPASSWORD :  \nDATABASE CREATION DATE :  \nTRIAL LICENSE END DATE: \nADMIN REMARK:"),
                    ])
                    ->action(function (ResellerDatabaseCreation $record, array $data): void {
                        $record->update([
                            'status' => 'completed',
                            'admin_remark' => !empty($data['admin_remark']) ? $data['admin_remark'] : null,
                            'completed_at' => now(),
                        ]);

                        try {
                            Mail::send(new ResellerDatabaseCreationStatusUpdate($record));
                        } catch (\Exception $e) {
                            Log::error('Failed to send reseller database creation status email', [
                                'error' => $e->getMessage(),
                                'database_creation_id' => $record->id,
                            ]);
                        }

                        Notification::make()
                            ->title('Database creation request completed successfully!')
                            ->success()
                            ->send();

                        $this->dispatch('database-creation-updated');
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Complete')
                    ->modalWidth('2xl'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('reject_reason')
                            ->label('Reject Reason')
                            ->required()
                            ->rows(4)
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
                            ->maxLength(1000),
                    ])
                    ->action(function (ResellerDatabaseCreation $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'reject_reason' => strtoupper($data['reject_reason']),
                            'rejected_at' => now(),
                        ]);

                        try {
                            Mail::send(new ResellerDatabaseCreationStatusUpdate($record));
                        } catch (\Exception $e) {
                            Log::error('Failed to send reseller database creation status email', [
                                'error' => $e->getMessage(),
                                'database_creation_id' => $record->id,
                            ]);
                        }

                        Notification::make()
                            ->title('Database creation request rejected!')
                            ->success()
                            ->send();

                        $this->dispatch('database-creation-updated');
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Reject')
                    ->modalWidth('2xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-database-creation-new');
    }
}
