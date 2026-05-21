<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TaskPriority;
use App\Models\Ticketing\TicketingUser;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class QcTicketingMyWorkspace extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string $view = 'filament.pages.qc-ticketing.my-workspace';
    protected static ?string $navigationLabel = 'My Workspace';
    protected static ?string $title = '';
    protected static ?string $slug = 'qc-ticketing/my-workspace';
    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'my_tasks';
    public ?int $ticketUserId = null;
    public ?string $userRole = null;

    public string $filterTimeframe = 'All Dates';
    public string $filterProduct = 'All Products';
    public string $filterPriority = 'All Urgency';

    public function mount(): void
    {
        $this->activeTab = session('qc_workspace_tab', 'my_tasks');
        if (! $this->showBugsTab() && $this->activeTab === 'my_bugs') {
            $this->activeTab = 'my_tasks';
        }
        $email = auth()->user()?->email;
        $this->ticketUserId = $email ? TicketingUser::where('email', $email)->value('id') : null;
        $this->userRole = $this->resolveUserRole();
    }

    public function showBugsTab(): bool
    {
        return true;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        session(['qc_workspace_tab' => $tab]);
    }

    public function clearFilters(): void
    {
        $this->filterTimeframe = 'All Dates';
        $this->filterProduct = 'All Products';
        $this->filterPriority = 'All Urgency';
    }

    public bool $showDueDrawer = false;
    public string $dueDrawerType = 'tasks';

    public function openDueDrawer(string $type): void
    {
        $this->dueDrawerType = $type;
        $this->showDueDrawer = true;
    }

    public function closeDueDrawer(): void
    {
        $this->showDueDrawer = false;
    }

    public function getDueDrawerItemsProperty()
    {
        if (!$this->ticketUserId) {
            return collect();
        }
        $today = now()->toDateString();

        if ($this->dueDrawerType === 'bugs') {
            return Bug::query()
                ->with(['product:id,name', 'relatedTask:id,task_id,due_date'])
                ->whereIn('product_id', [1, 2])
                ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
                ->whereHas('relatedTask', function ($q) use ($today) {
                    $q->whereDate('due_date', $today);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Task::query()
            ->with(['product:id,name'])
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->whereNotIn('status', ['Closed', 'Completed', 'Ready For Development'])
            ->whereRaw("status NOT LIKE '%Cancel%'")
            ->orderBy('due_date', 'asc')
            ->get();
    }

    // Serializable drawer datasets for client-side (Alpine) rendering.
    // Returning plain arrays so the view can embed them via @js() and open/close instantly.
    public function getDrawerDatasetsProperty(): array
    {
        if (!$this->ticketUserId) {
            return [];
        }
        $today = now()->toDateString();

        $tasks = Task::query()
            ->with(['product:id,name'])
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->whereNotIn('status', ['Closed', 'Completed', 'Ready For Development'])
            ->whereRaw("status NOT LIKE '%Cancel%'")
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($t) {
                $due = $t->due_date;
                $overdueDays = ($due && $due->lt(now()->startOfDay())) ? $due->diffInDays(now()) : 0;
                return [
                    'id' => $t->id,
                    'code' => $t->task_id,
                    'title' => (string) $t->title,
                    'status' => (string) $t->status,
                    'product' => $t->product?->name,
                    'due_date' => $due?->format('d/m/Y'),
                    'is_overdue' => (bool) ($due && $due->lt(now()->startOfDay())),
                    'overdue_days' => $overdueDays,
                    'created_date' => null,
                    'dispatch' => 'openTaskModal',
                ];
            })->values()->all();

        $bugs = Bug::query()
            ->with(['product:id,name', 'relatedTask:id,task_id,due_date'])
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereHas('relatedTask', function ($q) use ($today) {
                $q->whereDate('due_date', $today);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($b) {
                $due = $b->relatedTask?->due_date;
                $overdueDays = ($due && $due->lt(now()->startOfDay())) ? $due->diffInDays(now()) : 0;
                return [
                    'id' => $b->relatedTask?->id ?? 0,
                    'code' => $b->bug_id,
                    'title' => (string) $b->title,
                    'status' => (string) $b->status,
                    'product' => $b->product?->name,
                    'due_date' => $due?->format('d/m/Y'),
                    'is_overdue' => (bool) ($due && $due->lt(now()->startOfDay())),
                    'overdue_days' => $overdueDays,
                    'created_date' => null,
                    'dispatch' => 'openTaskModal',
                ];
            })->values()->all();

        return [
            'tasks' => ['title' => 'Tasks Due', 'empty' => 'Nothing due today', 'items' => $tasks],
            'bugs' => ['title' => 'Bugs Due Today', 'empty' => 'Nothing due today', 'items' => $bugs],
        ];
    }

    private function resolveUserRole(): string
    {
        if (!$this->ticketUserId) {
            return 'QC';
        }
        $roleNames = DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $this->ticketUserId)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('roles.name')
            ->all();

        foreach (['QC', 'RND', 'PDT', 'FE', 'ADMIN'] as $candidate) {
            foreach ($roleNames as $r) {
                if (stripos($r, $candidate) !== false) {
                    return $candidate;
                }
            }
        }
        return 'QC';
    }

    public function getTaskStatusesProperty(): array
    {
        // QC Ticketing always uses the QC 4-column board
        $this->userRole = 'QC';
        $map = [
            'FE' => [
                ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
                ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'In Progress', 'color' => '#ea580c'],
                ['id' => 'done', 'title' => 'Done', 'status' => 'Done', 'color' => '#10b981'],
            ],
            'PDT' => [
                ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
                ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'PDT - In Progress', 'color' => '#ea580c'],
                ['id' => 'onHold', 'title' => 'On Hold', 'status' => 'PDT - On Hold', 'color' => '#f59e0b'],
                ['id' => 'cancelled', 'title' => 'Cancelled', 'status' => 'PDT - Cancel', 'color' => '#dc2626'],
                ['id' => 'readyForDevelopment', 'title' => 'Ready for Development', 'status' => 'Ready For Development', 'color' => '#3b82f6'],
                ['id' => 'reopen', 'title' => 'Reopen', 'status' => 'Reopen', 'color' => '#ef4444'],
            ],
            'RND' => [
                ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
                ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'RND - In Progress', 'color' => '#ea580c'],
                ['id' => 'onHold', 'title' => 'On Hold', 'status' => 'RND - On Hold', 'color' => '#f59e0b'],
                ['id' => 'cancelled', 'title' => 'Cancelled', 'status' => 'RND - Cancel', 'color' => '#dc2626'],
                ['id' => 'completed', 'title' => 'Completed', 'status' => 'Completed', 'color' => '#10b981'],
                ['id' => 'readyForLive', 'title' => 'Ready for Live', 'status' => 'Ready For Live', 'color' => '#3b82f6'],
                ['id' => 'readyForTesting', 'title' => 'Ready For Testing', 'status' => 'Ready For Testing', 'color' => '#8b5cf6'],
                ['id' => 'reopen', 'title' => 'Reopen', 'status' => 'Reopen', 'color' => '#ef4444'],
                ['id' => 'live', 'title' => 'Live', 'status' => 'Live', 'color' => '#22c55e'],
            ],
            'QC' => [
                ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
                ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'QC - In Progress', 'color' => '#ea580c'],
                ['id' => 'onHold', 'title' => 'On Hold', 'status' => 'QC - On Hold', 'color' => '#f59e0b'],
                ['id' => 'cancelled', 'title' => 'Cancelled', 'status' => 'QC - Cancel', 'color' => '#dc2626'],
                ['id' => 'completed', 'title' => 'Completed', 'status' => 'Completed', 'color' => '#10b981'],
                ['id' => 'readyForTesting', 'title' => 'Ready For Testing', 'status' => 'Ready For Testing', 'color' => '#8b5cf6'],
            ],
            'ADMIN' => [
                ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
                ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'In Progress', 'color' => '#ea580c'],
                ['id' => 'onHold', 'title' => 'On Hold', 'status' => 'On Hold', 'color' => '#f59e0b'],
                ['id' => 'cancelled', 'title' => 'Cancelled', 'status' => 'Cancelled', 'color' => '#dc2626'],
                ['id' => 'completed', 'title' => 'Completed', 'status' => 'Completed', 'color' => '#10b981'],
                ['id' => 'readyForLive', 'title' => 'Ready for Live', 'status' => 'Ready for Live', 'color' => '#3b82f6'],
                ['id' => 'reopen', 'title' => 'Reopen', 'status' => 'Reopen', 'color' => '#ef4444'],
                ['id' => 'live', 'title' => 'Live', 'status' => 'Live', 'color' => '#22c55e'],
                ['id' => 'closed', 'title' => 'Closed', 'status' => 'Closed', 'color' => '#9ca3af'],
            ],
        ];
        return $map[$this->userRole] ?? $map['QC'];
    }

    public function getBugStatusesProperty(): array
    {
        return [
            ['id' => 'new', 'title' => 'New', 'status' => 'New', 'color' => '#6b7280'],
            ['id' => 'inProgress', 'title' => 'In Progress', 'status' => 'In Progress', 'color' => '#ea580c'],
            ['id' => 'rejected', 'title' => 'Rejected', 'status' => 'Rejected', 'color' => '#dc2626'],
            ['id' => 'readyForTesting', 'title' => 'Ready For Testing', 'status' => 'Ready For Testing', 'color' => '#3b82f6'],
            ['id' => 'readyForLive', 'title' => 'Ready For Live', 'status' => 'Ready For Live', 'color' => '#14b8a6'],
            ['id' => 'live', 'title' => 'Live', 'status' => 'Live', 'color' => '#22c55e'],
            ['id' => 'closed', 'title' => 'Closed', 'status' => 'Closed', 'color' => '#9ca3af'],
            ['id' => 'reopen', 'title' => 'Reopen', 'status' => 'Reopen', 'color' => '#ef4444'],
        ];
    }

    private function applyFilters($query, string $itemType)
    {
        if ($this->filterProduct !== 'All Products') {
            $productId = Product::where('name', $this->filterProduct)->value('id');
            if ($productId) {
                $query->where('product_id', $productId);
            }
        }

        if ($itemType === 'task' && $this->filterPriority !== 'All Urgency') {
            $priorityId = TaskPriority::where('name', $this->filterPriority)->value('id');
            if ($priorityId) {
                $query->where('priority_id', $priorityId);
            }
        }

        if ($this->filterTimeframe !== 'All Dates') {
            $today = now()->toDateString();
            $weekStart = now()->startOfWeek()->toDateString();
            $weekEnd = now()->endOfWeek()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();
            $monthEnd = now()->endOfMonth()->toDateString();

            switch ($this->filterTimeframe) {
                case 'Today': $query->whereDate('due_date', $today); break;
                case 'This Week': $query->whereBetween('due_date', [$weekStart, $weekEnd]); break;
                case 'This Month': $query->whereBetween('due_date', [$monthStart, $monthEnd]); break;
                case 'Overdue': $query->whereDate('due_date', '<', $today); break;
            }
        }

        return $query;
    }

    public function getMyTasksProperty()
    {
        if (!$this->ticketUserId) {
            return collect();
        }
        $query = Task::with(['product:id,name', 'priority:id,name,color,bg_color'])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)]);
        $this->applyFilters($query, 'task');
        return $query->get();
    }

    public function getMyBugsProperty()
    {
        if (!$this->ticketUserId) {
            return collect();
        }
        $query = Bug::with(['product:id,name', 'relatedTask:id,task_id'])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)]);
        $this->applyFilters($query, 'bug');
        return $query->get();
    }

    public function getKanbanColumnsProperty(): array
    {
        $statuses = $this->activeTab === 'my_bugs' ? $this->bugStatuses : $this->taskStatuses;
        $items = $this->activeTab === 'my_bugs' ? $this->myBugs : $this->myTasks;

        $columns = [];
        foreach ($statuses as $statusConfig) {
            $columns[$statusConfig['id']] = [
                'config' => $statusConfig,
                'items' => $items->filter(function ($item) use ($statusConfig) {
                    if ($item->status === $statusConfig['status']) {
                        return true;
                    }
                    return preg_match('/^(RND|QC|PDT|FE)\s*-\s*' . preg_quote($statusConfig['status'], '/') . '$/i', (string) $item->status);
                })->values(),
            ];
        }
        return $columns;
    }

    public function getProductOptionsProperty(): array
    {
        $items = $this->activeTab === 'my_bugs' ? $this->myBugs : $this->myTasks;
        $names = $items->pluck('product.name')->filter()->unique()->sort()->values()->all();
        return ['All Products', ...$names];
    }

    public function getPriorityOptionsProperty(): array
    {
        if ($this->activeTab === 'my_bugs') {
            return ['All Urgency'];
        }
        $names = TaskPriority::orderBy('id')->pluck('name')->all();
        return ['All Urgency', ...$names];
    }

    public function getTimeframeOptionsProperty(): array
    {
        return ['All Dates', 'Today', 'This Week', 'This Month', 'Overdue'];
    }

    public function getAnalyticsProperty(): array
    {
        if (!$this->ticketUserId) {
            return ['tasksDueToday' => 0, 'bugsDueToday' => 0];
        }
        $today = now()->toDateString();

        $tasksDueToday = Task::query()
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->whereNotIn('status', ['Closed', 'Completed', 'Ready For Development'])
            ->whereRaw("status NOT LIKE '%Cancel%'")
            ->count();

        // Bugs due today: based on related task's due date == today
        $bugsDueToday = Bug::query()
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereHas('relatedTask', function ($q) use ($today) {
                $q->whereDate('due_date', $today);
            })
            ->count();

        return [
            'tasksDueToday' => $tasksDueToday,
            'bugsDueToday' => $bugsDueToday,
        ];
    }
}
