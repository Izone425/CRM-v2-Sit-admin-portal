<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\TicketModule;
use App\Models\TicketProduct;
use App\Models\TicketPriority;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class TicketDashboard extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.ticket-dashboard';
    protected static ?string $navigationLabel = 'Ticket Dashboard';
    protected static ?string $title = '';

    public $selectedCategory = null;
    public $selectedStatus = null;
    public $selectedEnhancementStatus = null;
    public $selectedEnhancementType = null;
    public $currentMonth;
    public $currentYear;
    public $selectedDate;

    // Track individual combined statuses
    public $selectedCombinedStatuses = [];

    // 1 = current default view; 2 = "P1/P2 · New & In Progress · Pending FE" view.
    public int $activeDashboard = 1;


    protected $listeners = [
        'ticket-status-updated' => '$refresh',
    ];

    public function mount()
    {
        $this->currentMonth = now()->subHours(8)->month;
        $this->currentYear = now()->subHours(8)->year;

        // Set default filter to Completed status
        $this->selectedStatus = 'Completed';
    }

    /**
     * Switch between Dashboard 1 (default) and Dashboard 2 (P1+P2 · New & In Progress · Pending FE).
     */
    public function setDashboard(int $dashboard): void
    {
        $dashboard = $dashboard === 2 ? 2 : 1;
        if ($this->activeDashboard === $dashboard) return;

        $this->activeDashboard = $dashboard;

        // Reset summary-card selections so the dashboard cards don't fight the new filter set.
        $this->selectedCategory = null;
        $this->selectedStatus = null;
        $this->selectedEnhancementType = null;
        $this->selectedCombinedStatuses = [];

        // Both dashboards are scoped to "Software Bugs" (sort_order 1) and "Back End Assistance" (sort_order 2).
        $priorityIds = TicketPriority::where('is_active', true)
            ->whereIn('sort_order', [1, 2])
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        if ($dashboard === 2) {
            // Dashboard 2: + status New & In Progress, Pending FE.
            $this->setFilterState('priority_id', ['values' => $priorityIds]);
            $this->setFilterState('status', ['values' => ['New', 'In Progress']]);
            $this->setFilterState('pending_party', ['values' => ['Pending FE']]);
            $this->setFilterState('category', ['value' => null]);
            $this->setFilterState('enhancement_type', ['value' => null]);
        } else {
            // Dashboard 1: same priority scope, default status set, no pending_party.
            $this->setFilterState('priority_id', ['values' => $priorityIds]);
            $this->setFilterState('status', ['values' => ['Completed', 'Tickets: Live']]);
            $this->setFilterState('pending_party', ['values' => []]);
            $this->setFilterState('category', ['value' => null]);
            $this->setFilterState('enhancement_type', ['value' => null]);
        }

        $this->applyFiltersAndRefresh();
    }



    /**
     * Dispatch event to open ticket modal via TicketModal component
     */
    public function viewTicket($ticketId): void
    {
        $this->dispatch('openTicketModal', $ticketId);
    }

    /**
     * Dispatch event to open reopen modal via TicketModal component
     */
    public function openReopenModal($ticketId): void
    {
        $this->dispatch('openTicketModal', $ticketId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ticket List')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->tooltip('View Details')
                    ->extraAttributes(fn (Ticket $record): array => [
                        'x-tooltip.html' => new \Illuminate\Support\HtmlString(''),
                        'x-tooltip.raw' => new \Illuminate\Support\HtmlString(
                            '<div><strong>Module:</strong> ' . ($record->module?->name ?? 'N/A') . '</div>' .
                            '<div><strong>Company:</strong> ' . strtoupper($record->company_name ?? '') . '</div>' .
                            '<div><strong>Title:</strong> ' . htmlspecialchars($record->title ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</div>'
                        ),
                    ]),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('eta_release')
                    ->label('ETA')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'gray',
                        'In Progress', 'In Review', 'Reopen' => 'warning',
                        'Completed', 'Tickets: Live' => 'success',
                        'Closed', 'Closed System Configuration' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('requestor.name')
                    ->label('Front End')
                    ->sortable()
                    ->default('N/A')
                    ->formatStateUsing(fn ($state) => implode(' ', array_slice(explode(' ', $state ?? 'N/A'), 0, 2))),

                Tables\Columns\TextColumn::make('completion_date_display')
                    ->label('Completion Date')
                    ->getStateUsing(function (Ticket $record): string {
                        $completionLog = DB::connection('ticketingsystem_live')
                            ->table('ticket_logs')
                            ->where('ticket_id', $record->id)
                            ->whereIn('new_value', ['Completed', 'Live'])
                            ->orderBy('created_at', 'desc')
                            ->first();
                        return $completionLog
                            ? Carbon::parse($completionLog->created_at)->addHours(8)->format('d M Y H:i')
                            : '-';
                    }),

                Tables\Columns\TextColumn::make('overdue_display')
                    ->label('Overdue')
                    ->getStateUsing(function (Ticket $record): string {
                        $completionLog = DB::connection('ticketingsystem_live')
                            ->table('ticket_logs')
                            ->where('ticket_id', $record->id)
                            ->whereIn('new_value', ['Completed', 'Live'])
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if (!$completionLog) return '-';

                        $completionDate = Carbon::parse($completionLog->created_at)->addHours(8)->startOfDay();
                        $today = Carbon::now()->startOfDay();
                        $daysDiff = $today->diffInDays($completionDate, false);

                        if ($daysDiff == 0) return '0 day';
                        return '-' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '');
                    })
                    ->color(fn (string $state): string => $state === '-' || $state === '0 day' ? 'gray' : 'danger')
                    ->weight(fn (string $state): ?string => $state !== '-' && $state !== '0 day' ? 'bold' : null),

                Tables\Columns\TextColumn::make('pending_party')
                    ->label('Comment')
                    ->badge()
                    ->getStateUsing(fn (Ticket $record) =>
                        in_array($record->status, ['New', 'In Progress']) ? ($record->pending_party ?? '-') : '-'
                    )
                    ->color(fn ($state) => match ($state) {
                        'Pending RND' => 'warning',
                        'Pending FE' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'softwareBugs' => 'Software Bugs',
                        'backendAssistance' => 'Backend Assistance',
                        'enhancement' => 'Enhancement',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return;
                        $query->whereHas('priority', function ($q) use ($data) {
                            if ($data['value'] === 'softwareBugs') {
                                $q->where(function ($q2) {
                                    $q2->whereRaw('LOWER(name) LIKE ?', ['%bug%'])
                                       ->orWhereRaw('LOWER(name) LIKE ?', ['%software%']);
                                });
                            } elseif ($data['value'] === 'backendAssistance') {
                                $q->where(function ($q2) {
                                    $q2->whereRaw('LOWER(name) LIKE ?', ['%backend%'])
                                       ->orWhereRaw('LOWER(name) LIKE ?', ['%assistance%']);
                                });
                            } elseif ($data['value'] === 'enhancement') {
                                $q->where(function ($q2) {
                                    $q2->whereRaw('LOWER(name) LIKE ?', ['%enhancement%'])
                                       ->orWhereRaw('LOWER(name) LIKE ?', ['%paid%'])
                                       ->orWhereRaw('LOWER(name) LIKE ?', ['%customization%'])
                                       ->orWhereRaw('LOWER(name) LIKE ?', ['%non-critical%']);
                                });
                            }
                        });
                    }),

                Tables\Filters\SelectFilter::make('enhancement_type')
                    ->label('Enhancement Type')
                    ->options([
                        'critical' => 'Critical Enhancement',
                        'paid' => 'Paid Customization',
                        'non-critical' => 'Non-Critical Enhancement',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return;
                        $query->whereHas('priority', function ($q) use ($data) {
                            match ($data['value']) {
                                'critical' => $q->whereRaw('LOWER(name) = ?', ['critical enhancement']),
                                'paid' => $q->whereRaw('LOWER(name) = ?', ['paid customization']),
                                'non-critical' => $q->whereRaw('LOWER(name) = ?', ['non-critical enhancement']),
                                default => null,
                            };
                        });
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->default(['Completed', 'Tickets: Live'])
                    ->options(function () {
                        return DB::connection('ticketingsystem_live')
                            ->table('tickets')
                            ->whereIn('product_id', [1, 2])
                            ->whereNotNull('status')
                            ->distinct()
                            ->pluck('status', 'status')
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('created_date')
                    ->label('Date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Created Date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date'])) {
                            $query->whereDate('created_date', $data['date']);
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['date'])) return null;
                        return 'Date: ' . Carbon::parse($data['date'])->format('d M Y');
                    }),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->multiple()
                    ->default(
                        TicketPriority::where('is_active', true)
                            ->whereIn('sort_order', [1, 2])
                            ->pluck('id')
                            ->map(fn($id) => (string) $id)
                            ->toArray()
                    )
                    ->options(
                        TicketPriority::where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('sort_order_suffix')
                            ->get()
                            ->mapWithKeys(function ($priority) {
                                $label = 'P' . $priority->sort_order;
                                if ($priority->sort_order_suffix) {
                                    $label .= $priority->sort_order_suffix;
                                }
                                $label .= ' - ' . $priority->name;
                                return [$priority->id => $label];
                            })
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(
                        TicketProduct::where('is_active', true)
                            ->whereIn('id', [1, 2])
                            ->pluck('name', 'id')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(function () {
                        $allowedModules = ['Profile', 'Attendance', 'Leave', 'Claim', 'Payroll'];
                        return TicketModule::where('is_active', true)
                            ->whereIn('name', $allowedModules)
                            ->pluck('name', 'id')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('requestor_id')
                    ->label('Front End')
                    ->options(function () {
                        return DB::connection('ticketingsystem_live')
                            ->table('users')
                            ->whereIn('id', function ($query) {
                                $query->select('requestor_id')
                                    ->from('tickets')
                                    ->whereIn('product_id', [1, 2])
                                    ->whereNotNull('requestor_id')
                                    ->distinct();
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('pending_party')
                    ->label('Comment')
                    ->multiple()
                    // ->default(['Pending FE'])
                    ->options([
                        'Pending RND' => 'Pending RND',
                        'Pending FE' => 'Pending FE',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['values'])) {
                            $query->where('status', '!=', 'Closed')
                                  ->whereIn('pending_party', $data['values']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pass')
                    ->label('Pass')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Ticket $record): bool =>
                        in_array($record->status, ['Completed', 'Tickets: Live', 'Closed']) && !$record->isPassed
                        && $record->requestor?->email === auth()->user()->email
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Passed')
                    ->modalDescription(fn (Ticket $record) => "Are you sure you want to mark ticket {$record->ticket_id} as passed?")
                    ->action(function (Ticket $record) {
                        $this->markAsPassed($record->id);
                    }),

                Tables\Actions\Action::make('fail')
                    ->label('Fail')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Ticket $record): bool =>
                        in_array($record->status, ['Completed', 'Tickets: Live', 'Closed']) && !$record->isPassed
                        && $record->requestor?->email === auth()->user()->email
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Failed')
                    ->modalDescription(fn (Ticket $record) => "Are you sure you want to mark ticket {$record->ticket_id} as failed?")
                    ->action(function (Ticket $record) {
                        $this->markAsFailed($record->id);
                    }),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->paginated([15, 30, 50, 100])
            ->poll('30s');
    }

    /**
     * Handle row click - dispatch event to TicketModal component
     */
    public function view($recordId): void
    {
        $this->dispatch('openTicketModal', $recordId);
    }

    /**
     * Base table query - all filtering is handled by Filament filters
     */
    protected function getTableQuery(): Builder
    {
        return Ticket::query()
            ->with(['product', 'module', 'priority', 'requestor'])
            ->whereIn('product_id', [1, 2]);
    }

    public function getViewData(): array
    {
        // Base query with optimized eager loading
        $baseQuery = Ticket::with(['product', 'module', 'priority', 'requestor'])
            ->whereIn('product_id', [1, 2]);

        // Apply date filter at database level
        if ($this->selectedDate) {
            $baseQuery->whereDate('created_date', $this->selectedDate);
        }

        // Get all tickets for metrics (cached query)
        $tickets = $baseQuery->get();

        $softwareBugsMetrics = $this->calculateBugsMetrics($tickets);
        $backendAssistanceMetrics = $this->calculateBackendMetrics($tickets);
        $enhancementMetrics = $this->calculateEnhancementMetrics($tickets);
        $softwareBugsNewBreakdown = $this->getSoftwareBugsNewBreakdown($tickets);
        $softwareBugsInProgressBreakdown = $this->getSoftwareBugsInProgressBreakdown($tickets);
        $softwareBugsCompletedBreakdown = $this->getSoftwareBugsCompletedBreakdown($tickets);
        $softwareBugsClosedBreakdown = $this->getSoftwareBugsClosedBreakdown($tickets);
        $backendNewBreakdown = $this->getBackendNewBreakdown($tickets);
        $backendInProgressBreakdown = $this->getBackendInProgressBreakdown($tickets);
        $backendCompletedBreakdown = $this->getBackendCompletedBreakdown($tickets);
        $backendClosedBreakdown = $this->getBackendClosedBreakdown($tickets);

        $calendarData = $this->getCalendarData();

        return [
            'softwareBugs' => $softwareBugsMetrics,
            'backendAssistance' => $backendAssistanceMetrics,
            'enhancement' => $enhancementMetrics,
            'softwareBugsNewBreakdown' => $softwareBugsNewBreakdown,
            'softwareBugsInProgressBreakdown' => $softwareBugsInProgressBreakdown,
            'softwareBugsCompletedBreakdown' => $softwareBugsCompletedBreakdown,
            'softwareBugsClosedBreakdown' => $softwareBugsClosedBreakdown,
            'backendNewBreakdown' => $backendNewBreakdown,
            'backendInProgressBreakdown' => $backendInProgressBreakdown,
            'backendCompletedBreakdown' => $backendCompletedBreakdown,
            'backendClosedBreakdown' => $backendClosedBreakdown,
            'calendar' => $calendarData,
            'currentMonth' => $this->currentMonth,
            'currentYear' => $this->currentYear,
        ];
    }

    public function markAsPassed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                // Only allow pass action for Completed, Tickets: Live, or Closed status
                if (!in_array($ticket->status, ['Completed', 'Tickets: Live', 'Closed'])) {
                    Notification::make()
                        ->title('Action Not Allowed')
                        ->body("Ticket {$ticket->ticket_id} cannot be marked as passed because its status is '{$ticket->status}'")
                        ->warning()
                        ->send();
                    return;
                }

                $authUser = auth()->user();

                $ticketSystemUser = null;
                if ($authUser) {
                    $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                        ->table('users')
                        ->where('email', $authUser->email)
                        ->first();
                }

                $userId = $ticketSystemUser?->id ?? 22;
                $userName = $ticketSystemUser?->name ?? 'HRcrm User';
                $userRole = $ticketSystemUser?->role ?? 'Internal Staff';

                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'Closed',
                    'isPassed' => 1,
                    'passed_at' => now()->subHours(8),
                ]);

                // ✅ Create a log entry for marking ticket as passed
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'old_value' => $oldStatus,
                    'new_value' => 'Closed',
                    'action' => "Marked ticket {$ticket->ticket_id} as passed - changed status from '{$oldStatus}' to 'Closed'.",
                    'field_name' => 'status',
                    'change_reason' => 'Ticket marked as passed',
                    'updated_by' => $userId,
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'change_type' => 'status_change',
                    'source' => 'dashboard_pass_action',
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);

                // ✅ Show success notification
                Notification::make()
                    ->title('Ticket Marked as Passed')
                    ->body("Ticket {$ticket->ticket_id} has been marked as passed and closed")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as passed: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->body('Failed to mark ticket as passed')
                ->danger()
                ->send();
        }
    }

    public function markAsFailed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                // Only allow fail action for Completed, Tickets: Live, or Closed status
                if (!in_array($ticket->status, ['Completed', 'Tickets: Live', 'Closed'])) {
                    Notification::make()
                        ->title('Action Not Allowed')
                        ->body("Ticket {$ticket->ticket_id} cannot be marked as failed because its status is '{$ticket->status}'")
                        ->warning()
                        ->send();
                    return;
                }

                $authUser = auth()->user();

                $ticketSystemUser = null;
                if ($authUser) {
                    $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                        ->table('users')
                        ->where('name', $authUser->name)
                        ->first();
                }

                $userId = $ticketSystemUser?->id ?? 22;
                $userName = $ticketSystemUser?->name ?? 'HRcrm User';
                $userRole = $ticketSystemUser?->role ?? 'Internal Staff';

                $oldStatus = $ticket->status;

                // ✅ Update ticket to Failed and Reopen status
                $ticket->update([
                    'isPassed' => 0,
                    'passed_at' => now()->subHours(8),
                    'status' => 'Reopen',
                ]);

                // ✅ Create a log entry for status change
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'old_value' => $oldStatus,
                    'new_value' => 'Reopen',
                    'updated_by' => $userId,
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'change_type' => 'status_change',
                    'source' => 'crm',
                    'remarks' => 'Ticket marked as failed and reopened',
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);

                // ✅ Show success notification
                Notification::make()
                    ->title('Ticket Marked as Failed')
                    ->body("Ticket status changed to Reopen")
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as failed: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->body('Failed to update ticket status')
                ->danger()
                ->send();
        }
    }

    private function calculateBugsMetrics(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'bug') ||
                str_contains($priorityName, 'software');
        });

        return [
            'total' => $bugs->count(),
            'new' => $bugs->where('status', 'New')->count(),
            'progress' => $bugs->whereIn('status', ['In Review', 'In Progress', 'Reopen'])->count(),
            'completed' => $bugs->whereIn('status', ['Completed', 'Tickets: Live', 'Pending RND'])->count(),
            'closed' => $bugs->whereIn('status', ['Closed', 'Closed System Configuration'])->count(),
        ];
    }

    private function getSoftwareBugsNewBreakdown(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'bug') || str_contains($priorityName, 'software'))
                && $ticket->status === 'New';
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($bugs as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getSoftwareBugsInProgressBreakdown(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'bug') || str_contains($priorityName, 'software'))
                && in_array($ticket->status, ['In Review', 'In Progress', 'Reopen']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($bugs as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getSoftwareBugsCompletedBreakdown(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'bug') || str_contains($priorityName, 'software'))
                && in_array($ticket->status, ['Completed', 'Tickets: Live', 'Pending RND']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($bugs as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getSoftwareBugsClosedBreakdown(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'bug') || str_contains($priorityName, 'software'))
                && in_array($ticket->status, ['Closed', 'Closed System Configuration']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($bugs as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getBackendNewBreakdown(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'backend') || str_contains($priorityName, 'assistance') || str_contains(str_replace(' ', '', $priorityName), 'backend'))
                && $ticket->status === 'New';
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($backend as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getBackendInProgressBreakdown(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'backend') || str_contains($priorityName, 'assistance') || str_contains(str_replace(' ', '', $priorityName), 'backend'))
                && in_array($ticket->status, ['In Review', 'In Progress', 'Reopen']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($backend as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getBackendCompletedBreakdown(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'backend') || str_contains($priorityName, 'assistance') || str_contains(str_replace(' ', '', $priorityName), 'backend'))
                && in_array($ticket->status, ['Completed', 'Tickets: Live', 'Pending RND']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($backend as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function getBackendClosedBreakdown(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');
            return (str_contains($priorityName, 'backend') || str_contains($priorityName, 'assistance') || str_contains(str_replace(' ', '', $priorityName), 'backend'))
                && in_array($ticket->status, ['Closed', 'Closed System Configuration']);
        });

        // Get requestor (frontend) names from users table
        $requestorNames = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->pluck('name', 'id');

        $breakdown = [];
        foreach ($backend as $ticket) {
            $requestorName = $requestorNames[$ticket->requestor_id] ?? 'Unassigned';

            if (!isset($breakdown[$requestorName])) {
                $breakdown[$requestorName] = [
                    'count' => 0,
                    'tickets' => []
                ];
            }

            $breakdown[$requestorName]['count']++;
            $breakdown[$requestorName]['tickets'][] = $ticket->ticket_id;
        }

        // Sort by highest count
        uasort($breakdown, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $breakdown;
    }

    private function calculateBackendMetrics(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'backend') ||
                str_contains($priorityName, 'assistance') ||
                str_contains(str_replace(' ', '', $priorityName), 'backend');
        });

        return [
            'total' => $backend->count(),
            'new' => $backend->where('status', 'New')->count(),
            'progress' => $backend->whereIn('status', ['In Review', 'In Progress', 'Reopen'])->count(),
            'completed' => $backend->whereIn('status', ['Completed', 'Tickets: Live', 'Pending RND'])->count(),
            'closed' => $backend->whereIn('status', ['Closed', 'Closed System Configuration'])->count(),
        ];
    }

    private function calculateEnhancementMetrics(Collection $tickets): array
    {
        $enhancements = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'enhancement') ||
                   str_contains($priorityName, 'critical enhancement') ||
                   str_contains($priorityName, 'paid') ||
                   str_contains($priorityName, 'customization') ||
                   str_contains($priorityName, 'non-critical');
        });

        if ($this->selectedEnhancementType) {
            $enhancements = $enhancements->filter(function ($ticket) {
                $priorityName = strtolower($ticket->priority?->name ?? '');

                switch ($this->selectedEnhancementType) {
                    case 'critical':
                        return str_contains($priorityName, 'critical enhancement');
                    case 'paid':
                        return str_contains($priorityName, 'paid customization');
                    case 'non-critical':
                        return str_contains($priorityName, 'non-critical enhancement');
                    default:
                        return true;
                }
            });
        }

        return [
            'total' => $enhancements->count(),
            'new' => $enhancements->where('status', 'New')->count(),
            'pending_release' => $enhancements->where('status', 'Pending Release')->count(),
            'system_go_live' => $enhancements->where('status', 'System Go Live')->count(),
        ];
    }

    private function getCalendarData(): array
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);

        return [
            'month' => $date->format('F Y'),
            'days_in_month' => $date->daysInMonth,
            'first_day_of_week' => $date->dayOfWeek,
            'current_date' => Carbon::now()->addHours(8),
        ];
    }

    /**
     * Set a Filament filter's form field state (same pattern as Filament's removeTableFilter)
     */
    protected function setFilterState(string $filterName, array $state): void
    {
        $filterFormGroup = $this->getTableFiltersForm()->getComponent($filterName);

        if (! $filterFormGroup) {
            return;
        }

        $fields = $filterFormGroup->getChildComponentContainer()->getFlatFields();

        foreach ($state as $fieldName => $value) {
            if (isset($fields[$fieldName])) {
                $fields[$fieldName]->state($value);
            }
        }
    }

    /**
     * Apply all pending filter changes and refresh the table
     */
    protected function applyFiltersAndRefresh(): void
    {
        $this->handleTableFilterUpdates();
    }

    public function selectCategory($category, $status = null): void
    {
        if ($this->selectedCategory === $category && $this->selectedStatus === $status) {
            // Toggle off
            $this->selectedCategory = null;
            $this->selectedStatus = null;
            $this->selectedCombinedStatuses = [];
            $this->setFilterState('category', ['value' => null]);
            $this->setFilterState('status', ['values' => []]);
            $this->setFilterState('enhancement_type', ['value' => null]);
        } else {
            $this->selectedCategory = $category;
            $this->selectedStatus = $status;
            $this->selectedEnhancementStatus = null;

            // Set Filament category filter
            $this->setFilterState('category', ['value' => $category]);
            $this->setFilterState('enhancement_type', ['value' => null]);

            // Set Filament status filter based on status group
            if ($status === 'In Progress') {
                $this->setFilterState('status', ['values' => ['In Review', 'In Progress', 'Reopen']]);
                $this->selectedCombinedStatuses = ['In Review', 'In Progress', 'Reopen'];
            } elseif ($status === 'Completed') {
                $this->setFilterState('status', ['values' => ['Completed', 'Tickets: Live', 'Pending RND']]);
                $this->selectedCombinedStatuses = [];
            } elseif ($status === 'Closed') {
                $this->setFilterState('status', ['values' => ['Closed', 'Closed System Configuration']]);
                $this->selectedCombinedStatuses = ['Closed', 'Closed System Configuration'];
            } elseif ($status === 'New') {
                $this->setFilterState('status', ['values' => ['New']]);
                $this->selectedCombinedStatuses = [];
            } elseif ($status) {
                $this->setFilterState('status', ['values' => [$status]]);
                $this->selectedCombinedStatuses = [];
            } else {
                $this->setFilterState('status', ['values' => []]);
                $this->selectedCombinedStatuses = [];
            }
        }
        $this->applyFiltersAndRefresh();
    }

    public function selectEnhancementType($type): void
    {
        if ($this->selectedEnhancementType === $type) {
            $this->selectedEnhancementType = null;
            $this->setFilterState('enhancement_type', ['value' => null]);
        } else {
            $this->selectedEnhancementType = $type;
            $this->selectedCategory = 'enhancement';
            $this->setFilterState('category', ['value' => 'enhancement']);
            $this->setFilterState('enhancement_type', ['value' => $type]);
        }
        $this->applyFiltersAndRefresh();
    }

    public function removeIndividualStatus($statusToRemove): void
    {
        if (!empty($this->selectedCombinedStatuses)) {
            $this->selectedCombinedStatuses = array_diff($this->selectedCombinedStatuses, [$statusToRemove]);

            if (empty($this->selectedCombinedStatuses)) {
                $this->selectedCategory = null;
                $this->selectedStatus = null;
                $this->setFilterState('category', ['value' => null]);
                $this->setFilterState('status', ['values' => []]);
            } else {
                $this->setFilterState('status', ['values' => array_values($this->selectedCombinedStatuses)]);
            }
        }
        $this->applyFiltersAndRefresh();
    }

    public function selectDate($year, $month, $day): void
    {
        $selectedDate = Carbon::create($year, $month, $day)->format('Y-m-d');

        if ($this->selectedDate === $selectedDate) {
            $this->selectedDate = null;
            $this->setFilterState('created_date', ['date' => null]);
        } else {
            $this->selectedDate = $selectedDate;
            $this->setFilterState('created_date', ['date' => $selectedDate]);
        }
        $this->applyFiltersAndRefresh();
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

}
