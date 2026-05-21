<?php

namespace App\Livewire\ImplementerDashboard;

use App\Models\ImplementerHandoverRequest;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectClosingApproved extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]);
        } else {
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable();
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

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getApprovedHandoverRequests(): Builder
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = ImplementerHandoverRequest::query()
            ->where('status', 'approved');

        if ($this->selectedUser === 'all-implementer') {
            // no filter
        } elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer_name', $user->name);
            }
        } else {
            $currentUser = auth()->user();

            if ($currentUser && $currentUser->role_id === 4) {
                $query->where('implementer_name', $currentUser->name);
            }
        }

        return $query->orderBy('approved_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getApprovedHandoverRequests())
            ->filters([
                SelectFilter::make('implementer_name')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),
            ])
            ->columns([
                TextColumn::make('softwareHandover.formatted_handover_id')
                    ->label('SW ID')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('softwareHandover', function (Builder $q) use ($search) {
                            $q->where('id', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (ImplementerHandoverRequest $record): View {
                                $handover = $record->softwareHandover;
                                if (!$handover) {
                                    return view('components.empty-state-question');
                                }
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $handover]);
                            })
                    ),

                TextColumn::make('implementer_name')
                    ->label('Implementer Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('date_request')
                    ->label('Date Request')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('team_lead_remark')
                    ->label('Team Lead Remark')
                    ->wrap()
                    ->limit(50),
            ])
            ->defaultSort('approved_at', 'desc');
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.project-closing-approved');
    }
}
