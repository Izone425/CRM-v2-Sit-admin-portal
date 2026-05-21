<?php

namespace App\Livewire;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\BugCategory;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Release;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateBugDrawer extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public bool $showDrawer = false;
    public bool $releaseLocked = false;

    public ?int $related_task_id = null;
    public string $title = '';
    public string $description = '';
    public ?int $product_id = null;
    public ?int $module_id = null;
    public array $platform = [];
    public ?int $release_id = null;
    public ?string $severity = null;
    public ?int $category_id = null;
    public array $assignee_ids = [];
    public array $attachments = [];
    public array $attachmentLinks = [];
    public string $newLinkLabel = '';
    public string $newLinkUrl = '';
    public bool $showLinkInput = false;
    public ?int $editingLinkIndex = null;

    protected $listeners = [
        'openCreateBugModal' => 'openDrawer',
    ];

    public function render()
    {
        return view('livewire.create-bug-drawer');
    }

    public function openDrawer(?int $taskId = null, ?int $releaseId = null): void
    {
        $this->reset(['related_task_id', 'title', 'description', 'product_id', 'module_id', 'platform', 'release_id', 'severity', 'category_id', 'assignee_ids', 'attachments', 'attachmentLinks', 'newLinkLabel', 'newLinkUrl', 'showLinkInput', 'editingLinkIndex']);
        $this->releaseLocked = false;
        $this->showDrawer = true;

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

        if ($taskId) {
            $this->related_task_id = $taskId;
            $this->updatedRelatedTaskId();
            $this->severity = $this->severity ?: 'Medium';
        }
    }

    public function openLinkInput(): void
    {
        $this->showLinkInput = true;
        $this->editingLinkIndex = null;
        $this->newLinkLabel = '';
        $this->newLinkUrl = '';
    }

    public function cancelLinkInput(): void
    {
        $this->showLinkInput = false;
        $this->editingLinkIndex = null;
        $this->newLinkLabel = '';
        $this->newLinkUrl = '';
    }

    public function saveLink(): void
    {
        if (trim($this->newLinkLabel) === '' && trim($this->newLinkUrl) === '') {
            $this->cancelLinkInput();
            return;
        }
        $entry = [
            'label' => trim($this->newLinkLabel) ?: trim($this->newLinkUrl),
            'url' => trim($this->newLinkUrl),
        ];
        if ($this->editingLinkIndex !== null && isset($this->attachmentLinks[$this->editingLinkIndex])) {
            $this->attachmentLinks[$this->editingLinkIndex] = $entry;
        } else {
            $this->attachmentLinks[] = $entry;
        }
        $this->cancelLinkInput();
    }

    public function editLink(int $index): void
    {
        if (!isset($this->attachmentLinks[$index])) return;
        $this->editingLinkIndex = $index;
        $this->newLinkLabel = $this->attachmentLinks[$index]['label'] ?? '';
        $this->newLinkUrl = $this->attachmentLinks[$index]['url'] ?? '';
        $this->showLinkInput = true;
    }

    public function removeLink(int $index): void
    {
        if (isset($this->attachmentLinks[$index])) {
            unset($this->attachmentLinks[$index]);
            $this->attachmentLinks = array_values($this->attachmentLinks);
        }
    }

    public function removeFile(int $index): void
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
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

    public function getProductsProperty() { return Product::where('is_active', 1)->whereIn('id', [1, 2])->orderBy('name')->get(['id', 'name']); }

    public function updatedProductId(): void
    {
        if (!$this->related_task_id) {
            $this->module_id = null;
        }
        if (! $this->releaseLocked) {
            $this->release_id = null;
        }
    }

    public function updatedModuleId(): void
    {
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
    public function getCategoriesProperty() { return BugCategory::orderBy('name')->get(['id', 'name']); }
    public function getUsersProperty() { return TicketingUser::orderBy('name')->get(['id', 'name']); }
    public function getTasksProperty()
    {
        return Task::query()
            ->where(function ($q) {
                $q->whereIn('status', ['Ready For Testing', 'QC - In Progress']);
                if ($this->related_task_id) {
                    $q->orWhere('id', $this->related_task_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'task_id', 'title', 'product_id', 'module_id', 'assignee_ids', 'status', 'created_at']);
    }

    public function updatedRelatedTaskId(): void
    {
        if (!$this->related_task_id) {
            return;
        }
        $task = Task::find($this->related_task_id, ['id', 'title', 'product_id', 'module_id', 'assignee_ids', 'platform', 'release_id']);
        if (!$task) {
            return;
        }
        $this->title = (string) $task->title;
        $this->product_id = $task->product_id;
        $this->module_id = $task->module_id;
        $this->assignee_ids = array_map('intval', $task->assignee_ids ?? []);

        if (! $this->releaseLocked) {
            $taskPlatform = is_array($task->platform)
                ? $task->platform
                : (json_decode($task->platform, true) ?: []);
            $this->platform = $taskPlatform;
            $this->release_id = $task->release_id;
        }
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

    public function getPlatformOptionsProperty(): array { return ['Web', 'App', 'Others']; }
    public function getSeverityOptionsProperty(): array { return ['Critical', 'High', 'Medium', 'Low']; }

    public function submit(): void
    {
        $this->validate([
            'title' => 'required|string|max:500',
            'description' => 'required|string',
            'product_id' => 'required|integer',
            'module_id' => 'required|integer',
            'severity' => 'required|string',
            'category_id' => 'required|integer',
        ], [
            'title.required' => 'Bug name is required.',
            'description.required' => 'Description is required.',
            'product_id.required' => 'Product is required.',
            'module_id.required' => 'Module is required.',
            'severity.required' => 'Severity is required.',
            'category_id.required' => 'Category is required.',
        ]);

        try {
            $bugId = $this->generateBugId();
            $userId = $this->getTicketSystemUserId();

            Bug::create([
                'bug_id' => $bugId,
                'title' => $this->title,
                'description' => $this->description ?: null,
                'product_id' => $this->product_id,
                'module_id' => $this->module_id,
                'category_id' => $this->category_id,
                'severity' => $this->severity,
                'platform' => !empty($this->platform) ? $this->platform : null,
                'release_id' => $this->release_id,
                'status' => 'New',
                'reporter_id' => $userId,
                'related_task_id' => $this->related_task_id,
                'assignee_ids' => $this->assignee_ids,
                'submission_date' => now(),
            ]);

            Notification::make()->title("Bug {$bugId} created")->success()->send();
            $this->closeDrawer();
            $this->dispatch('bug-created');
        } catch (\Exception $e) {
            Log::error('Create bug failed: ' . $e->getMessage());
            Notification::make()->title('Failed to create bug')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'descriptionForm' => $this->makeForm()->schema([
                RichEditor::make('description')
                    ->label('')
                    ->placeholder('Describe the bug in detail')
                    ->required()
                    ->toolbarButtons(['attachFiles', 'bold', 'italic', 'underline', 'strike', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo'])
                    ->disableToolbarButtons(['codeBlock'])
                    ->fileAttachmentsDisk('s3-ticketing')
                    ->fileAttachmentsDirectory('bug_dev_images')
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

    private function generateBugId(): string
    {
        $bugId = '';
        DB::connection('ticketingsystem_live')->transaction(function () use (&$bugId) {
            $last = Bug::lockForUpdate()->orderByRaw('CAST(SUBSTRING(bug_id, 4) AS UNSIGNED) DESC')->first();
            $next = $last ? ((int) substr($last->bug_id, 3)) + 1 : 1;
            $bugId = 'BG-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            while (Bug::where('bug_id', $bugId)->exists()) {
                $next++;
                $bugId = 'BG-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            }
        });
        return $bugId;
    }
}
