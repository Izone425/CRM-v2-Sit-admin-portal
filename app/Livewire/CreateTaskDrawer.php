<?php

namespace App\Livewire;

use App\Models\Ticket;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Release;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TaskPriority;
use App\Models\Ticketing\TicketingUser;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateTaskDrawer extends Component implements HasForms
{
    use InteractsWithForms;

    public bool $showDrawer = false;
    public bool $releaseLocked = false;

    public ?int $related_ticket_id = null;
    public string $title = '';
    public string $description = '';
    public ?int $product_id = null;
    public ?int $module_id = null;
    public ?int $solution_id = null;
    public ?int $sub_module_id = null;
    public array $platform = [];
    public ?int $priority_id = null;
    public ?string $task_size = null;
    public ?string $start_date = null;
    public ?string $due_date = null;
    public ?int $release_id = null;
    public array $assignee_ids = [];

    protected $listeners = [
        'openCreateTaskModal' => 'openDrawer',
    ];

    public function render()
    {
        return view('livewire.create-task-drawer');
    }

    public function openDrawer(?int $releaseId = null, ?int $ticketId = null): void
    {
        $this->reset(['related_ticket_id', 'title', 'description', 'product_id', 'module_id', 'solution_id', 'sub_module_id', 'platform', 'priority_id', 'task_size', 'start_date', 'due_date', 'release_id', 'assignee_ids']);
        $this->releaseLocked = false;
        $this->start_date = now()->toDateString();

        if ($releaseId) {
            $release = Release::find($releaseId);
            if ($release) {
                $this->release_id = $release->id;
                $this->product_id = $release->product_id;
                $this->module_id = $release->module_id;
                $this->platform = is_array($release->platform)
                    ? $release->platform
                    : (json_decode($release->platform, true) ?: []);
                $this->releaseLocked = true;
            }
        }

        if ($ticketId) {
            $this->related_ticket_id = $ticketId;
            $this->updatedRelatedTicketId($ticketId);
        }

        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function assignToMe(): void
    {
        $email = auth()->user()?->email;
        $userId = $email ? TicketingUser::where('email', $email)->value('id') : null;
        if ($userId && !in_array($userId, $this->assignee_ids)) {
            $this->assignee_ids[] = $userId;
        }
    }

    public function togglePlatform(string $platform): void
    {
        if (in_array($platform, $this->platform)) {
            $this->platform = array_values(array_filter($this->platform, fn ($p) => $p !== $platform));
        } else {
            $this->platform[] = $platform;
        }

        if (! $this->releaseLocked) {
            $this->release_id = null;
        }
    }

    public function removePlatform(string $platform): void
    {
        $this->platform = array_values(array_filter($this->platform, fn ($p) => $p !== $platform));

        if (! $this->releaseLocked) {
            $this->release_id = null;
        }
    }

    public function toggleAssignee(int $userId): void
    {
        if (in_array($userId, $this->assignee_ids)) {
            $this->assignee_ids = array_values(array_filter($this->assignee_ids, fn ($id) => $id != $userId));
        } else {
            $this->assignee_ids[] = $userId;
        }
    }

    public function removeAssignee(int $userId): void
    {
        $this->assignee_ids = array_values(array_filter($this->assignee_ids, fn ($id) => $id != $userId));
    }

    public function getProductsProperty()
    {
        return Product::where('is_active', 1)->whereIn('id', [1, 2])->orderBy('name')->get(['id', 'name']);
    }

    public function getTicketsProperty()
    {
        return Ticket::whereNotIn('status', ['Closed', 'Cancelled', 'Rejected'])
            ->whereIn('product_id', [1, 2])
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'ticket_id', 'title', 'product_id', 'solution_id', 'module_id', 'sub_module_id']);
    }

    public function updatedRelatedTicketId($value): void
    {
        if (! $value) {
            return;
        }

        $ticket = Ticket::find($value);
        if (! $ticket) {
            return;
        }

        if ($this->releaseLocked) {
            return;
        }

        $this->product_id = $ticket->product_id;
        $this->solution_id = $ticket->solution_id;
        $this->module_id = $ticket->module_id;
        $this->sub_module_id = $ticket->sub_module_id;
    }

    public function updatedProductId(): void
    {
        $this->module_id = null;
        $this->release_id = null;
        $this->assignee_ids = [];
    }

    public function updatedModuleId(): void
    {
        $this->assignee_ids = [];
        if (! $this->releaseLocked) {
            $this->release_id = null;
        }
    }

    public function getModulesProperty()
    {
        if (!$this->product_id) {
            return collect();
        }
        return DB::connection('ticketingsystem_live')
            ->table('product_has_modules')
            ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
            ->where('product_has_modules.product_id', $this->product_id)
            ->where('modules.is_active', 1)
            ->orderBy('modules.name')
            ->get(['modules.id', 'modules.name']);
    }

    public function getPrioritiesProperty()
    {
        return TaskPriority::where('is_active', 1)->orderBy('id')->get(['id', 'name']);
    }

    public function getUsersProperty()
    {
        if (!$this->product_id) {
            return collect();
        }

        $accessQuery = DB::connection('ticketingsystem_live')
            ->table('user_product_modules_access')
            ->where('product_id', $this->product_id);

        if ($this->module_id) {
            $accessQuery->where('module_id', $this->module_id);
        }

        $userIds = $accessQuery->distinct()->pluck('user_id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        return TicketingUser::whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getReleasesProperty()
    {
        $query = Release::query()->orderByDesc('id')->limit(100);

        if ($this->product_id) {
            $query->where('product_id', $this->product_id);
        }

        if ($this->module_id) {
            $query->where('module_id', $this->module_id);
        }

        if (!empty($this->platform)) {
            $query->where(function ($q) {
                foreach ($this->platform as $p) {
                    $q->orWhereJsonContains('platform', $p);
                }
            });
        }

        return $query->get(['id', 'version', 'product_id', 'module_id', 'platform', 'planned_live_date']);
    }

    public function getPlannedReleaseDateProperty(): ?string
    {
        if (!$this->release_id) {
            return null;
        }
        return optional($this->releases->firstWhere('id', $this->release_id))->planned_live_date;
    }

    public function getPlatformOptionsProperty(): array
    {
        return ['Web', 'App', 'Others'];
    }

    public function getTaskSizeOptionsProperty(): array
    {
        return ['Small', 'Medium', 'Large', 'Major'];
    }

    public function submit(): void
    {
        $this->validate([
            'title' => 'required|string|max:500',
            'description' => 'required|string',
            'product_id' => 'required|integer',
            'module_id' => 'required|integer',
            'priority_id' => 'required|integer',
            'task_size' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'assignee_ids' => 'required|array|min:1',
        ], [
            'title.required' => 'Title is required.',
            'description.required' => 'Description is required.',
            'product_id.required' => 'Product is required.',
            'module_id.required' => 'Module is required.',
            'priority_id.required' => 'Urgency is required.',
            'task_size.required' => 'Task size is required.',
            'start_date.required' => 'Start date is required.',
            'due_date.required' => 'Due date is required.',
            'assignee_ids.required' => 'At least one assignee is required.',
            'assignee_ids.min' => 'At least one assignee is required.',
        ]);

        try {
            $taskId = $this->generateTaskId();
            $userId = $this->getTicketSystemUserId();

            Task::create([
                'task_id' => $taskId,
                'related_ticket_id' => $this->related_ticket_id,
                'title' => $this->title,
                'description' => $this->description ?: null,
                'product_id' => $this->product_id,
                'solution_id' => $this->solution_id,
                'module_id' => $this->module_id,
                'sub_module_id' => $this->sub_module_id,
                'priority_id' => $this->priority_id,
                'task_size' => $this->task_size,
                'platform' => !empty($this->platform) ? $this->platform : null,
                'release_id' => $this->release_id,
                'status' => 'New',
                'requestor_id' => $userId,
                'assignee_ids' => $this->assignee_ids,
                'start_date' => $this->start_date ?: null,
                'due_date' => $this->due_date ?: null,
            ]);

            // Mirrors dt-dev: creating a task from a New/Reopen ticket transitions the ticket
            // into "PDT - In Progress" to complete the acceptance flow.
            $transitionedTicketId = null;
            if ($this->related_ticket_id) {
                $ticket = Ticket::find($this->related_ticket_id);
                if ($ticket && in_array($ticket->status, ['New', 'Reopen'])) {
                    $ticket->update(['status' => 'PDT - In Progress']);
                    $transitionedTicketId = (int) $ticket->id;
                }
            }

            Notification::make()->title("Task {$taskId} created")->success()->send();
            $this->closeDrawer();
            $this->dispatch('task-created', ticketId: $transitionedTicketId);
            $this->dispatch('ticket-status-updated', ticketId: $transitionedTicketId);
        } catch (\Exception $e) {
            Log::error('Create task failed: ' . $e->getMessage());
            Notification::make()->title('Failed to create task')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'descriptionForm' => $this->makeForm()->schema([
                RichEditor::make('description')
                    ->label('')
                    ->placeholder('Describe the task in detail')
                    ->required()
                    ->toolbarButtons([
                        'attachFiles', 'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo',
                    ])
                    ->disableToolbarButtons(['codeBlock'])
                    ->fileAttachmentsDisk('s3-ticketing')
                    ->fileAttachmentsDirectory('task_dev_images')
                    ->fileAttachmentsVisibility('private'),
            ]),
        ];
    }

    private function getTicketSystemUserId(): int
    {
        $email = auth()->user()?->email;
        if (!$email) return 22;
        return TicketingUser::where('email', $email)->value('id') ?? 22;
    }

    private function generateTaskId(): string
    {
        $taskId = '';

        DB::connection('ticketingsystem_live')->transaction(function () use (&$taskId) {
            $email = auth()->user()?->email;
            $userId = $email ? TicketingUser::where('email', $email)->value('id') : null;
            $creatorRole = 'QC';
            if ($userId) {
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
                            $creatorRole = $r;
                            break 2;
                        }
                    }
                }
            }

            $lastTask = Task::lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING(task_id, 4) AS UNSIGNED) DESC')
                ->first();

            $nextNumber = 1;
            if ($lastTask) {
                $parts = explode('-', $lastTask->task_id);
                $nextNumber = ((int) end($parts)) + 1;
            }
            $taskId = "TS-{$creatorRole}-" . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            while (Task::where('task_id', $taskId)->exists()) {
                $nextNumber++;
                $taskId = "TS-{$creatorRole}-" . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });

        return $taskId;
    }
}
