<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\FinanceHandover;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;

class FinanceHandoverCompleted extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $lastRefreshTime;
    public $selectedUser;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        \Filament\Notifications\Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);

        $this->resetTable();
    }

    public function getCompletedFinanceHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = FinanceHandover::query()
            ->where('status', 'Completed')
            ->with(['lead.companyDetail', 'creator', 'reseller']);

        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getCompletedFinanceHandovers())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15)
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $salespersonNames = $data['value'];
                            $salespersonIds = User::whereIn('name', $salespersonNames)
                                ->where('role_id', '2')
                                ->pluck('id')
                                ->toArray();

                            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                                $leadQuery->whereIn('salesperson', $salespersonIds);
                            });
                        }
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),
            ])
            ->columns([
                TextColumn::make('formatted_handover_id')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewFinanceDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (FinanceHandover $record) {
                                return view('components.finance-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('creator.name')
                    ->label('SalesPerson')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $displayName = $state ?? $record->lead?->name ?? 'N/A';
                        $leadId = $record->lead_id;

                        if ($leadId) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($leadId);
                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $displayName . '
                                </a>');
                        }

                        return $displayName;
                    })
                    ->html(),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller Name')
                    ->sortable()
                    ->default('N/A'),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.finance-handover-completed');
    }
}
