<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\HeadcountHandover;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HeadcountCompletedTableSalesperson extends Component implements HasForms, HasTable
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

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getCompletedHeadcountHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HeadcountHandover::with(['lead.companyDetail', 'creator', 'completedBy'])
            ->where('status', 'Completed')
            ->orderBy('completed_at', 'desc');

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
                $selectedUser = $this->selectedUser; // Create a local variable
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
            ->query($this->getCompletedHeadcountHandovers())
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
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
                    ->visible(fn(): bool => auth()->user()->role_id !== 2), // Hide filter for salespersons
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('completed_at')
                    ->label('Date Completed')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('creator.name')
                    ->label('Salesperson')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2), // Hide for salespersons

                TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 20) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Completed' => new HtmlString('<span style="color: green; font-weight: bold;">Completed</span>'),
                        default => new HtmlString('<span style="font-weight: bold;">' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->defaultSort('completed_at', 'desc')
            ->poll('300s');
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.headcount-completed-table-salesperson');
    }
}
