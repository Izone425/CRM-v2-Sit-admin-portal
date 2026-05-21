<?php

namespace App\Livewire\ImplementerDashboard;

use App\Models\Ticket;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Livewire\Component;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class TicketReminderCommentPendingFe extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $selectedUser;
    public $lastRefreshTime;

    protected $listeners = [
        'ticket-status-updated' => '$refresh',
    ];

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

    public function getCompletedTicketsQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = Ticket::query()
            ->with(['module', 'priority', 'product', 'requestor'])
            ->whereIn('product_id', [1, 2])
            ->when(Ticket::hasPendingPartyColumn(),
                fn ($q) => $q->where('pending_party', 'Pending FE'),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->whereIn('status', ['New', 'In Progress'])
            ->whereHas('priority', fn ($q) => $q->whereIn('sort_order', [1, 2]));

        if ($this->selectedUser === 'all-implementer') {
            // no filter
        } elseif (is_numeric($this->selectedUser)) {
            $user = \App\Models\User::find($this->selectedUser);
            if ($user && $user->role_id === 4) {
                $query->whereHas('requestor', fn ($q) => $q->where('email', $user->email));
            }
        } else {
            $currentUser = auth()->user();
            if ($currentUser && $currentUser->role_id === 4) {
                $query->whereHas('requestor', fn ($q) => $q->where('email', $currentUser->email));
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getCompletedTicketsQuery())
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'On Hold' => 'On Hold',
                        'RND - In Progress' => 'RND - In Progress',
                        'Completed' => 'Completed',
                        'Closed' => 'Closed',
                        'Closed System Configuration' => 'Closed System Configuration',
                        'Closed with New CR' => 'Closed with New CR',
                    ])
                    ->multiple(),

                SelectFilter::make('requestor_id')
                    ->label('Front End')
                    ->multiple()
                    ->searchable()
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('users')
                            ->whereIn('id', function ($q) {
                                $q->select('requestor_id')
                                    ->from('tickets')
                                    ->whereIn('product_id', [1, 2])
                                    ->whereNotNull('requestor_id')
                                    ->distinct();
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
            ])
            ->columns([
                TextColumn::make('ticket_id')
                    ->label('Ticket ID')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module.name')
                    ->label('Module')
                    ->wrap()
                    ->limit(30)
                    ->default('N/A'),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? ''))
                    ->wrap()
                    ->limit(40),

                TextColumn::make('requestor.name')
                    ->label('Front End')
                    ->wrap()
                    ->limit(40)
                    ->default('N/A'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('overdue_days')
                    ->label('Overdue')
                    ->color('danger')
                    ->weight('bold')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        $isP1OrP2 = in_array($record->priority?->sort_order, [1, 2], true);
                        $isActiveStatus = in_array($record->status, ['New', 'In Progress'], true);

                        if (!$isP1OrP2 || !$isActiveStatus) {
                            return null;
                        }

                        $lastActivityDate = $record->updated_at ?? null;

                        if (!$lastActivityDate) {
                            return 'N/A';
                        }

                        $lastActivity = Carbon::parse($lastActivityDate);
                        $today = Carbon::today();
                        $days = $lastActivity->diffInDays($today);

                        return $days . ' day' . ($days != 1 ? 's' : '');
                    }),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public function view($recordId): void
    {
        $this->dispatch('openTicketModal', $recordId);
    }

    public function viewTicket($ticketId): void
    {
        $this->dispatch('openTicketModal', $ticketId);
    }

    #[On('refresh-ticket-tables')]
    public function refresh()
    {
        // Refresh
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.ticket-reminder-comment-pending-fe');
    }
}
