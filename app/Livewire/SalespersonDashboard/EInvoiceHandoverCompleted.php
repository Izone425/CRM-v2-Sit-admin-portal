<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\EInvoiceHandover;
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

class EInvoiceHandoverCompleted extends Component implements HasForms, HasTable
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

    public function getCompletedEInvoiceHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = EInvoiceHandover::query()
            ->where('status', 'Completed')
            ->orderBy('completed_at', 'desc')
            ->with(['lead.companyDetail', 'createdBy', 'completedBy']);

        if ($this->selectedUser === 'all-salespersons') {
            // Show all salespersons' handovers
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            // Validate that the selected user exists and is a salesperson
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                // Invalid user ID or not a salesperson, fall back to default
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                // Salesperson - show only THEIR OWN records
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
            // For non-salesperson users, no additional filtering
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getCompletedEInvoiceHandovers())
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
                TextColumn::make('project_code')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewEInvoiceDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (EInvoiceHandover $record) {
                                return view('components.einvoice-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'In Progress' => new HtmlString('<span style="color: orange;">In Progress</span>'),
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    })
                    ->sortable(),
            ])
            ->defaultSort('completed_at', 'desc');
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.e-invoice-handover-completed');
    }
}
