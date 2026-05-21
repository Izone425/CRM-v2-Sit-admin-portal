<?php

namespace App\Livewire\ImplementerDashboard;

use App\Models\Ticket;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class TicketReminderAllStatus extends Component implements HasForms, HasTable
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

                TextColumn::make('status')
                    ->label('Status'),

                TextColumn::make('isPassed')
                    ->label('Pass Status')
                    ->formatStateUsing(function ($record) {
                        if ($record->isPassed === true) {
                            $passedAt = $record->passed_at
                                ? Carbon::parse($record->passed_at)->addHours(8)->format('d M Y, H:i')
                                : '';
                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex items-center gap-2">' .
                                '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 rounded-md bg-green-50 ring-1 ring-inset ring-green-600/20">Pass</span>' .
                                ($passedAt ? '<span class="text-xs text-gray-600">' . $passedAt . '</span>' : '') .
                                '</div>'
                            );
                        } elseif ($record->isPassed === false) {
                            return new \Illuminate\Support\HtmlString(
                                '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 rounded-md bg-red-50 ring-1 ring-inset ring-red-600/20">N/A</span>'
                            );
                        }
                        return 'Pending';
                    })
                    ->html(),
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
            ->whereIn('tickets.product_id', [1, 2]);

        if ($this->selectedUser === 'all-implementer') {
            // Show all tickets - no additional filtering
        }
        elseif (is_numeric($this->selectedUser)) {
            $user = \App\Models\User::find($this->selectedUser);

            if ($user && $user->role_id === 4) {
                $query->whereHas('requestor', function($q) use ($user) {
                    $q->where('email', $user->email);
                });
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser && $currentUser->role_id === 4) {
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
        return view('livewire.implementer_dashboard.ticket-reminder-all-status');
    }
}
