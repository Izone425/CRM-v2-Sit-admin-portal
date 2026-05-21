<?php

namespace App\Livewire\SupportDashboard;

use App\Models\Ticket;
use App\Models\TicketLog;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class SupportCompletedToday extends Component implements HasForms, HasTable
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

    public function table(Table $table): Table
    {
        $query = $this->getCompletedTicketsQuery();

        return $table
            ->query($query)
            ->filters([
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
                    ->tooltip('View Details')
                    ->extraAttributes(fn (Ticket $record): array => [
                        'x-tooltip.html' => new \Illuminate\Support\HtmlString(''),
                        'x-tooltip.raw' => new \Illuminate\Support\HtmlString(
                            '<div><strong>Module:</strong> ' . ($record->module?->name ?? 'N/A') . '</div>' .
                            '<div><strong>Company:</strong> ' . strtoupper($record->company_name ?? '') . '</div>' .
                            '<div><strong>Title:</strong> ' . htmlspecialchars($record->title ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</div>'
                        ),
                    ]),

                TextColumn::make('module.name')
                    ->label('Module')
                    ->wrap()
                    ->limit(30),

                TextColumn::make('requestor.name')
                    ->label('Front End')
                    ->wrap()
                    ->limit(40)
                    ->default('N/A'),

                TextColumn::make('completion_log_date')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->formatStateUsing(fn ($record) =>
                        Carbon::parse($record->completion_log_date)->addHours(8)->format('d M Y, H:i')
                    ),

                TextColumn::make('overdue_days')
                    ->label('Overdue')
                    ->color('danger')
                    ->weight('bold')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        $completionDate = $record->completion_log_date ?? null;

                        if (!$completionDate) {
                            return 'N/A';
                        }

                        $completed = Carbon::parse($completionDate);
                        $today = Carbon::today();
                        $days = $completed->diffInDays($today);

                        return $days . ' day' . ($days != 1 ? 's' : '');
                    }),
            ])
            ->actions([
                Action::make('pass')
                    ->label('Pass')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Ticket $record): bool =>
                        in_array($record->status, ['Completed', 'Tickets: Live', 'Closed'])
                            && !$record->isPassed
                            && $record->requestor?->email === auth()->user()->email
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Passed')
                    ->modalDescription('Are you sure you want to mark this ticket as passed?')
                    ->action(function ($record) {
                        $authUser = auth()->user();
                        $ticketSystemUser = null;
                        if ($authUser) {
                            $ticketSystemUser = DB::connection('ticketingsystem_live')
                                ->table('users')
                                ->where('email', $authUser->email)
                                ->first();
                        }
                        $userId = $ticketSystemUser?->id ?? 22;
                        $oldStatus = $record->status;

                        $record->update([
                            'isPassed' => true,
                            'passed_at' => now()->subHours(8),
                            'status' => 'Closed',
                        ]);

                        TicketLog::create([
                            'ticket_id' => $record->id,
                            'old_value' => $oldStatus,
                            'new_value' => 'Closed',
                            'action' => "Marked ticket {$record->ticket_id} as Passed and changed status from '{$oldStatus}' to 'Closed'.",
                            'field_name' => 'status',
                            'change_reason' => 'Ticket marked as passed and closed via Ticket Reminder',
                            'old_eta' => null,
                            'new_eta' => null,
                            'updated_by' => $userId,
                            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                            'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                            'change_type' => 'status_change',
                            'source' => 'ticket_reminder',
                            'created_at' => now()->subHours(8),
                            'updated_at' => now()->subHours(8),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Ticket marked as passed')
                            ->body("Ticket {$record->ticket_id} has been marked as passed")
                            ->send();

                        $this->dispatch('refresh-ticket-tables');
                    }),

                Action::make('fail')
                    ->label('Fail')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Ticket $record): bool =>
                        in_array($record->status, ['Completed', 'Tickets: Live', 'Closed'])
                            && !$record->isPassed
                            && $record->requestor?->email === auth()->user()->email
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Failed')
                    ->modalDescription('Are you sure you want to mark this ticket as failed?')
                    ->action(function ($record) {
                        $authUser = auth()->user();
                        $ticketSystemUser = null;
                        if ($authUser) {
                            $ticketSystemUser = DB::connection('ticketingsystem_live')
                                ->table('users')
                                ->where('email', $authUser->email)
                                ->first();
                        }
                        $userId = $ticketSystemUser?->id ?? 22;

                        $record->update([
                            'isPassed' => false,
                            'passed_at' => now()->subHours(8),
                        ]);

                        TicketLog::create([
                            'ticket_id' => $record->id,
                            'old_value' => $record->isPassed === null ? 'Pending' : ($record->isPassed ? 'Passed' : 'Failed'),
                            'new_value' => 'Failed',
                            'action' => "Marked ticket {$record->ticket_id} as Failed.",
                            'field_name' => 'isPassed',
                            'change_reason' => 'Ticket marked as failed via Ticket Reminder',
                            'old_eta' => null,
                            'new_eta' => null,
                            'updated_by' => $userId,
                            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                            'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                            'change_type' => 'status_change',
                            'source' => 'ticket_reminder',
                            'created_at' => now()->subHours(8),
                            'updated_at' => now()->subHours(8),
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Ticket marked as failed')
                            ->body("Ticket {$record->ticket_id} has been marked as failed")
                            ->send();

                        $this->dispatch('refresh-ticket-tables');
                    }),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('ticket_logs.created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Dispatch event to open ticket modal via TicketModal component
     */
    public function view($recordId): void
    {
        $this->dispatch('openTicketModal', $recordId);
    }

    /**
     * Dispatch event to open ticket modal via TicketModal component
     */
    public function viewTicket($ticketId): void
    {
        $this->dispatch('openTicketModal', $ticketId);
    }

    public function getCompletedTicketsQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = Ticket::query()
            ->with(['module', 'priority', 'product', 'requestor'])
            ->join('ticket_logs', function($join) {
                $join->on('tickets.id', '=', 'ticket_logs.ticket_id')
                    ->where('ticket_logs.field_name', '=', 'status')
                    ->where('ticket_logs.new_value', '=', 'Completed');
            })
            ->select('tickets.*', 'ticket_logs.created_at as completion_log_date')
            ->whereIn('tickets.product_id', [1, 2])
            ->where('tickets.status', 'Completed');

        if ($this->selectedUser === 'all-support') {
            $supportEmails = \App\Models\User::whereIn('role_id', [8, 9])->pluck('email')->toArray();
            $query->whereHas('requestor', fn ($q) => $q->whereIn('email', $supportEmails));
        }
        elseif (is_numeric($this->selectedUser)) {
            $user = \App\Models\User::find($this->selectedUser);

            if ($user && in_array($user->role_id, [8, 9], true)) {
                $query->whereHas('requestor', function($q) use ($user) {
                    $q->where('email', $user->email);
                });
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser && in_array($currentUser->role_id, [8, 9], true)) {
                $query->whereHas('requestor', function($q) use ($currentUser) {
                    $q->where('email', $currentUser->email);
                });
            }
        }

        $query->orderBy('ticket_logs.created_at', 'desc');

        return $query;
    }

    #[On('refresh-ticket-tables')]
    public function refresh()
    {
        // Refresh the component
    }

    public function render()
    {
        return view('livewire.support_dashboard.support-completed-today');
    }
}
