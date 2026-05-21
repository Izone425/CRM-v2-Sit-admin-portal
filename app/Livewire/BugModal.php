<?php

namespace App\Livewire;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\BugCategory;
use App\Models\Ticketing\BugComment;
use App\Models\Ticketing\BugLog;
use App\Models\Ticketing\TicketingUser;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class BugModal extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public $selectedBug = null;
    public $showBugModal = false;
    public $newComment = '';
    public $commentSort = 'desc';

    public $showImageModal = false;
    public $selectedImageUrl = '';

    protected $listeners = [
        'openBugModal' => 'viewBug',
        'closeBugModal' => 'closeBugModal',
    ];

    public function render()
    {
        return view('livewire.bug-modal');
    }

    public function viewBug($bugId): void
    {
        try {
            $this->selectedBug = Bug::with([
                'product:id,name',
                'module:id,name',
                'solution:id,name',
                'subModule:id,name',
                'category:id,name',
                'relatedTask:id,task_id,due_date,assignee_ids',
            ])->find($bugId);

            if ($this->selectedBug) {
                $this->newComment = '';
                $this->showBugModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Error viewing bug: ' . $e->getMessage());
            $this->showBugModal = false;
        }
    }

    public function closeBugModal(): void
    {
        $this->showBugModal = false;
        $this->selectedBug = null;
        $this->newComment = '';
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
        if (empty(trim(strip_tags($this->newComment))) || !$this->selectedBug) {
            return;
        }

        try {
            $userId = $this->getTicketSystemUserId();

            BugComment::create([
                'bug_id' => $this->selectedBug->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
                'is_edited' => false,
            ]);

            $this->newComment = '';
            $this->selectedBug->refresh();

            Notification::make()
                ->title('Comment Added')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error adding bug comment: ' . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add comment')
                ->send();
        }
    }

    public function updateStatus(string $value): void
    {
        if (!$this->selectedBug) return;
        $old = $this->selectedBug->status;
        $this->selectedBug->update(['status' => $value]);
        $this->logChange('status', $old, $value);
        $this->selectedBug->refresh();
        Notification::make()->title('Status updated')->success()->send();
    }

    public function updateSeverity(string $value): void
    {
        if (!$this->selectedBug) return;
        $old = $this->selectedBug->severity;
        $this->selectedBug->update(['severity' => $value]);
        $this->logChange('severity', $old, $value);
        $this->selectedBug->refresh();
        Notification::make()->title('Severity updated')->success()->send();
    }

    public function updateCategory($value): void
    {
        if (!$this->selectedBug) return;
        $value = $value === '' ? null : (int) $value;
        $oldCategoryId = $this->selectedBug->category_id;
        $oldName = $oldCategoryId ? optional(BugCategory::find($oldCategoryId))->name : null;
        $newName = $value ? optional(BugCategory::find($value))->name : null;
        $this->selectedBug->update(['category_id' => $value]);
        $this->logChange('category', $oldName, $newName);
        $this->selectedBug->load('category');
        Notification::make()->title('Category updated')->success()->send();
    }

    private function logChange(string $field, $oldValue, $newValue): void
    {
        try {
            $user = $this->getTicketSystemUser();
            BugLog::create([
                'bug_id' => $this->selectedBug->id,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'action' => "Changed {$field} from '" . ($oldValue ?: '—') . "' to '" . ($newValue ?: '—') . "' for bug {$this->selectedBug->bug_id}.",
                'field_name' => $field,
                'user_name' => $user?->name ?? auth()->user()?->name,
                'change_type' => $field . '_change',
                'source' => 'bug_modal',
            ]);
        } catch (\Exception $e) {
            Log::error('Bug log error: ' . $e->getMessage());
        }
    }

    public function getSeverityOptionsProperty(): array
    {
        return ['Critical', 'High', 'Medium', 'Low'];
    }

    public function getCategoryOptionsProperty()
    {
        return BugCategory::orderBy('name')->get(['id', 'name']);
    }

    public function getBugAssigneesProperty()
    {
        if (!$this->selectedBug) {
            return collect();
        }
        $ids = $this->selectedBug->assignee_ids ?? [];
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

    public function getAvailableAssigneesProperty()
    {
        if (!$this->selectedBug || !$this->selectedBug->product_id) {
            return collect();
        }

        $accessQuery = DB::connection('ticketingsystem_live')
            ->table('user_product_modules_access')
            ->where('product_id', $this->selectedBug->product_id);

        if ($this->selectedBug->module_id) {
            $accessQuery->where('module_id', $this->selectedBug->module_id);
        }

        $userIds = $accessQuery->distinct()->pluck('user_id');
        if ($userIds->isEmpty()) {
            return collect();
        }

        return TicketingUser::whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function toggleBugAssignee(int $userId): void
    {
        if (!$this->selectedBug) {
            return;
        }
        $ids = array_map('intval', $this->selectedBug->assignee_ids ?? []);
        if (in_array($userId, $ids, true)) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $userId));
        } else {
            $ids[] = $userId;
        }
        try {
            $this->selectedBug->update(['assignee_ids' => $ids]);
            $this->selectedBug->refresh();
            Notification::make()->title('Assignees updated')->success()->send();
        } catch (\Exception $e) {
            Log::error('Error updating bug assignees: ' . $e->getMessage());
            Notification::make()->title('Failed to update assignees')->body($e->getMessage())->danger()->send();
        }
    }

    public function removeBugAssignee(int $userId): void
    {
        if (!$this->selectedBug) {
            return;
        }
        $ids = array_map('intval', $this->selectedBug->assignee_ids ?? []);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== $userId));
        try {
            $this->selectedBug->update(['assignee_ids' => $ids]);
            $this->selectedBug->refresh();
            Notification::make()->title('Assignee removed')->success()->send();
        } catch (\Exception $e) {
            Log::error('Error removing bug assignee: ' . $e->getMessage());
        }
    }

    public function getBugReporterProperty(): ?TicketingUser
    {
        if (!$this->selectedBug?->reporter_id) {
            return null;
        }
        return TicketingUser::find($this->selectedBug->reporter_id);
    }

    public function getBugCommentsProperty()
    {
        if (!$this->selectedBug) {
            return collect();
        }
        return BugComment::with(['user:id,name,email'])
            ->where('bug_id', $this->selectedBug->id)
            ->orderBy('created_at', $this->commentSort)
            ->get();
    }

    public function getBugLogsProperty()
    {
        if (!$this->selectedBug) {
            return collect();
        }
        return BugLog::where('bug_id', $this->selectedBug->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getBugAttachmentsProperty()
    {
        if (!$this->selectedBug) {
            return collect();
        }
        return DB::connection('ticketingsystem_live')
            ->table('bug_attachments')
            ->where('bug_id', $this->selectedBug->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()->schema([
                RichEditor::make('newComment')
                    ->label('')
                    ->placeholder('Add a comment...')
                    ->required()
                    ->toolbarButtons([
                        'attachFiles', 'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo',
                    ])
                    ->disableToolbarButtons(['codeBlock'])
                    ->fileAttachmentsDisk('s3-ticketing')
                    ->fileAttachmentsDirectory('bug_dev_images')
                    ->fileAttachmentsVisibility('private'),
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
