<?php

namespace App\Livewire;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TaskComment;
use App\Models\Ticketing\TaskLog;
use App\Models\Ticketing\TicketingUser;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskModal extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public $selectedTask = null;
    public $showTaskModal = false;
    public $newComment = '';
    public $attachments = [];
    public $drawerTab = 'comments';
    public $commentSort = 'desc';

    // Image preview modal
    public $showImageModal = false;
    public $selectedImageUrl = '';

    protected $listeners = [
        'openTaskModal' => 'viewTask',
        'closeTaskModal' => 'closeTaskModal',
    ];

    public function render()
    {
        return view('livewire.task-modal');
    }

    public function viewTask($taskId): void
    {
        try {
            $this->selectedTask = Task::with([
                'product:id,name',
                'module:id,name',
                'solution:id,name',
                'subModule:id,name',
                'priority:id,name,color,bg_color',
                'release:id,version,planned_live_date,actual_live_date',
            ])->find($taskId);

            if ($this->selectedTask) {
                $this->drawerTab = 'comments';
                $this->newComment = '';
                $this->showTaskModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Error viewing task: ' . $e->getMessage());
            $this->showTaskModal = false;
        }
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->selectedTask = null;
        $this->newComment = '';
        $this->attachments = [];

        $this->dispatch('taskModalClosed');
    }

    public function setDrawerTab(string $tab): void
    {
        $this->drawerTab = $tab;
    }

    public function getAvailableStatusesProperty(): array
    {
        $task = $this->selectedTask;
        if (!$task) {
            return [];
        }

        $userId = (int) $this->getTicketSystemUserId();
        $assignees = array_map('intval', $task->assignee_ids ?? []);
        $isAssignee = in_array($userId, $assignees, true);
        $isCreator = (int) ($task->requestor_id ?? 0) === $userId;

        $currentStatus = (string) ($task->status ?? '');
        $task_id = (string) ($task->task_id ?? '');

        $userRole = $this->resolveTicketingRole($userId);
        $requestorRole = $this->resolveTicketingRole((int) ($task->requestor_id ?? 0));

        $ticketPriority = null;
        $ticketSuffix = null;
        if ($task->related_ticket_id) {
            $ticket = DB::connection('ticketingsystem_live')
                ->table('tickets')
                ->leftJoin('ticket_priorities', 'tickets.priority_id', '=', 'ticket_priorities.id')
                ->where('tickets.id', $task->related_ticket_id)
                ->selectRaw('ticket_priorities.sort_order as sort_order, ticket_priorities.sort_order_suffix as suffix')
                ->first();
            if ($ticket) {
                $ticketPriority = $ticket->sort_order !== null ? (int) $ticket->sort_order : null;
                $ticketSuffix = $ticket->suffix;
            }
        }

        $availableStatuses = [];

        if ($currentStatus === 'Ready For Testing' && stripos($userRole, 'QC') !== false) {
            $availableStatuses = ['QC - In Progress'];
        }

        if (stripos($currentStatus, 'Cancel') !== false) {
            $availableStatuses = ['Reopen'];
        }

        if ($ticketPriority === null) {
            if ($isAssignee || $isCreator) {
                if (str_contains($task_id, "TS-{$userRole}") && ($currentStatus === 'New' || $currentStatus === 'Reopen')) {
                    $availableStatuses = [
                        strtoupper($userRole) . ' - In Progress',
                        strtoupper($userRole) . ' - On Hold',
                        strtoupper($userRole) . ' - Cancel',
                    ];
                } elseif ($currentStatus === strtoupper($userRole) . ' - On Hold') {
                    $availableStatuses = [
                        strtoupper($userRole) . ' - In Progress',
                        strtoupper($userRole) . ' - Cancel',
                    ];
                } elseif ($currentStatus === strtoupper($userRole) . ' - In Progress') {
                    if (stripos($userRole, 'RND') !== false) {
                        $availableStatuses = ['Ready For Testing', 'Completed'];
                    } elseif (stripos($userRole, 'PDT') !== false) {
                        $availableStatuses = ['Ready For Development', 'Completed'];
                    } elseif (stripos($userRole, 'QC') !== false) {
                        if (stripos($requestorRole, 'QC') !== false) {
                            $availableStatuses = ['Completed'];
                        } else {
                            $availableStatuses = ['Ready For Live', 'Reopen'];
                        }
                    }
                } elseif ($userRole === 'RND' && $currentStatus === 'Ready For Live') {
                    $availableStatuses = ['Live'];
                } elseif ($userRole === 'RND' && $currentStatus === 'Completed') {
                    $availableStatuses = ['Reopen'];
                } elseif ($userRole === 'PDT' && $currentStatus === 'Completed') {
                    $availableStatuses = ['Reopen'];
                } elseif ($userRole === 'QC' && $currentStatus === 'Live') {
                    $availableStatuses = ['Closed'];
                }
            }
        } else {
            if (($isAssignee || $isCreator) && stripos($userRole, 'RND') !== false) {
                if ($currentStatus === 'New' || $currentStatus === 'Reopen') {
                    $availableStatuses = ['RND - In Progress', 'RND - On Hold', 'RND - Cancel'];
                } elseif ($currentStatus === 'RND - On Hold') {
                    $availableStatuses = ['RND - In Progress', 'RND - Cancel'];
                } elseif ($currentStatus === 'RND - In Progress') {
                    if ($ticketSuffix === 'F') {
                        if ($ticketPriority === 2) {
                            $availableStatuses = ['Completed'];
                        } else {
                            $availableStatuses = ['Ready For Testing'];
                        }
                    } elseif (in_array($ticketPriority, [1, 2], true)) {
                        $availableStatuses = ['Completed'];
                    } elseif (in_array($ticketPriority, [3, 4, 5], true)) {
                        $availableStatuses = ['Ready For Testing', 'Live'];
                    } else {
                        $availableStatuses = ['Ready For Testing', 'Completed'];
                    }
                } elseif ($currentStatus === 'Ready For Live') {
                    $availableStatuses = ['Live'];
                }
            } elseif (strtolower($userRole) === 'qc' && $ticketSuffix !== 'F') {
                if ($currentStatus === 'Ready For Testing') {
                    $availableStatuses = ['QC - In Progress'];
                } elseif ($currentStatus === 'QC - In Progress') {
                    $availableStatuses = ['Ready For Live', 'Reopen'];
                } elseif ($currentStatus === 'Live') {
                    $availableStatuses = ['Closed'];
                }
            } elseif (($isAssignee || $isCreator) && stripos($userRole, 'PDT') !== false) {
                if ($currentStatus === 'New' || $currentStatus === 'Reopen') {
                    $availableStatuses = ['PDT - In Progress', 'PDT - On Hold', 'PDT - Cancel'];
                } elseif ($currentStatus === 'PDT - On Hold') {
                    $availableStatuses = ['PDT - In Progress', 'PDT - Cancel'];
                } elseif ($currentStatus === 'PDT - In Progress') {
                    $availableStatuses = ['Ready For Development'];
                    if ($ticketPriority === 4 && $ticketSuffix === 'b') {
                        $availableStatuses[] = 'SRS Ready';
                    } elseif ($ticketPriority === 3 || $ticketPriority === 5) {
                        $availableStatuses[] = 'Completed';
                    }
                }
            }
        }

        return $availableStatuses;
    }

    private function resolveTicketingRole(int $userId): string
    {
        if (!$userId) {
            return 'User';
        }
        $roles = DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $userId)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('roles.name')
            ->all();

        foreach (['RND', 'PDT', 'QC', 'FE'] as $r) {
            foreach ($roles as $role) {
                if (stripos($role, $r) !== false) {
                    return $r;
                }
            }
        }
        return $roles[0] ?? 'User';
    }

    public function getAssignableReleasesProperty()
    {
        $task = $this->selectedTask;
        if (!$task || !$task->product_id) {
            return collect();
        }

        $query = \App\Models\Ticketing\Release::query()
            ->where('product_id', $task->product_id)
            ->orderByDesc('id')
            ->limit(200);

        if ($task->module_id) {
            $query->where('module_id', $task->module_id);
        }

        $platforms = is_array($task->platform)
            ? $task->platform
            : (json_decode($task->platform ?? '', true) ?: array_filter([$task->platform]));

        if (!empty($platforms)) {
            $query->where(function ($q) use ($platforms) {
                foreach ($platforms as $p) {
                    $q->orWhereJsonContains('platform', $p);
                }
            });
        }

        return $query->get(['id', 'version', 'module_id', 'platform', 'planned_live_date']);
    }

    public function updateRelease($releaseId): void
    {
        if (!$this->selectedTask) {
            return;
        }
        $releaseId = $releaseId === '' || $releaseId === null ? null : (int) $releaseId;
        try {
            $this->selectedTask->update(['release_id' => $releaseId]);
            $this->selectedTask->load('release:id,version,planned_live_date,actual_live_date');
            Notification::make()->title('Release updated')->success()->send();
        } catch (\Exception $e) {
            Log::error('Error updating task release: ' . $e->getMessage());
            Notification::make()->title('Failed to update release')->body($e->getMessage())->danger()->send();
        }
    }

    public function getPlatformOptionsProperty(): array
    {
        return ['Web', 'App', 'Others'];
    }

    public function togglePlatform(string $platform): void
    {
        if (!$this->selectedTask) {
            return;
        }

        $current = is_array($this->selectedTask->platform)
            ? $this->selectedTask->platform
            : (json_decode($this->selectedTask->platform ?? '', true) ?: array_filter([$this->selectedTask->platform]));

        if (in_array($platform, $current, true)) {
            $current = array_values(array_filter($current, fn ($p) => $p !== $platform));
        } else {
            $current[] = $platform;
        }

        try {
            $this->selectedTask->update(['platform' => !empty($current) ? $current : null]);
            $this->selectedTask->refresh();
            Notification::make()->title('Platform updated')->success()->send();
        } catch (\Exception $e) {
            Log::error('Error updating task platform: ' . $e->getMessage());
            Notification::make()->title('Failed to update platform')->body($e->getMessage())->danger()->send();
        }
    }

    public function updateStatus(string $newStatus): void
    {
        if (!$this->selectedTask) {
            return;
        }
        if (!in_array($newStatus, $this->availableStatuses, true)) {
            Notification::make()->title('Status change not allowed')->danger()->send();
            return;
        }

        try {
            $this->selectedTask->update(['status' => $newStatus]);
            $this->selectedTask->refresh();
            $this->dispatch('task-status-updated');
            Notification::make()->title("Status updated to {$newStatus}")->success()->send();
        } catch (\Exception $e) {
            Log::error('Error updating task status: ' . $e->getMessage());
            Notification::make()->title('Failed to update status')->body($e->getMessage())->danger()->send();
        }
    }

    public function openImageModal($imageUrl): void
    {
        $this->selectedImageUrl = $imageUrl;
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->selectedImageUrl = '';
    }

    public function addComment(): void
    {
        if (empty(trim(strip_tags($this->newComment))) || !$this->selectedTask) {
            return;
        }

        try {
            $userId = $this->getTicketSystemUserId();

            TaskComment::create([
                'task_id' => $this->selectedTask->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
                'is_edited' => false,
            ]);

            $this->newComment = '';
            $this->selectedTask->refresh();

            Notification::make()
                ->title('Comment Added')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error adding task comment: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add comment')
                ->send();
        }
    }

    public function getOpenTaskAssigneesProperty()
    {
        if (!$this->selectedTask) {
            return collect();
        }
        $ids = $this->selectedTask->assignee_ids ?? [];
        if (empty($ids)) {
            return collect();
        }
        $users = TicketingUser::whereIn('id', $ids)->get(['id', 'name', 'email']);

        $roleMap = DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('model_has_roles.model_id', $ids)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('model_has_roles.model_id', 'roles.name')
            ->get()
            ->groupBy('model_id')
            ->map(fn ($rows) => $rows->pluck('name')->all());

        return $users->map(function ($u) use ($roleMap) {
            $u->role_names = $roleMap[$u->id] ?? [];
            return $u;
        });
    }

    public function getTaskRequestorProperty(): ?TicketingUser
    {
        if (!$this->selectedTask?->requestor_id) {
            return null;
        }
        return TicketingUser::find($this->selectedTask->requestor_id);
    }

    public function getTaskCommentsProperty()
    {
        if (!$this->selectedTask) {
            return collect();
        }
        return TaskComment::with(['user:id,name,email'])
            ->where('task_id', $this->selectedTask->id)
            ->orderBy('created_at', $this->commentSort)
            ->get();
    }

    public function getTaskLogsProperty()
    {
        if (!$this->selectedTask) {
            return collect();
        }
        return TaskLog::where('task_id', $this->selectedTask->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTaskAttachmentsProperty()
    {
        if (!$this->selectedTask) {
            return collect();
        }
        return DB::connection('ticketingsystem_live')
            ->table('task_attachments')
            ->where('task_id', $this->selectedTask->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTaskBugsProperty()
    {
        if (!$this->selectedTask) {
            return collect();
        }
        return Bug::where('related_task_id', $this->selectedTask->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'bug_id', 'title', 'status']);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    RichEditor::make('newComment')
                        ->label('')
                        ->placeholder('Add a comment...')
                        ->required()
                        ->toolbarButtons([
                            'attachFiles',
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                            'undo',
                            'redo',
                        ])
                        ->disableToolbarButtons([
                            'codeBlock',
                        ])
                        ->fileAttachmentsDisk('s3-ticketing')
                        ->fileAttachmentsDirectory('task_dev_images')
                        ->fileAttachmentsVisibility('private')
                ]),
        ];
    }

    private function getTicketSystemUserId(): int
    {
        $ticketSystemUser = $this->getTicketSystemUser();
        return $ticketSystemUser?->id ?? 22;
    }

    private function getTicketSystemUser(): ?object
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return null;
        }

        return DB::connection('ticketingsystem_live')
            ->table('users')
            ->where('email', $authUser->email)
            ->first();
    }
}
