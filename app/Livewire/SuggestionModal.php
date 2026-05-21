<?php

namespace App\Livewire;

use App\Models\Ticketing\Suggestion;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class SuggestionModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?Suggestion $suggestion = null;
    public bool $showModal = false;
    public string $activeTab = 'comments';
    public string $newComment = '';
    public bool $showLinkPicker = false;
    public string $linkPickerMode = 'choose'; // 'choose' | 'ticket' | 'task'
    public ?string $linkSearchTerm = '';
    public bool $showImageModal = false;
    public string $selectedImageUrl = '';

    protected $listeners = [
        'openSuggestion' => 'viewSuggestion',
        'closeSuggestion' => 'closeModal',
    ];

    public function render()
    {
        return view('livewire.suggestion-modal');
    }

    public function viewSuggestion(int $id): void
    {
        $this->suggestion = Suggestion::with([
            'product:id,name',
            'module:id,name',
            'solution:id,name',
            'subModule:id,name',
            'requestor:id,name,email',
        ])->find($id);

        if ($this->suggestion) {
            $this->activeTab = 'comments';
            $this->newComment = '';
            $this->showModal = true;
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->suggestion = null;
        $this->newComment = '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openImageModal(string $url): void
    {
        $this->selectedImageUrl = $url;
        $this->showImageModal = true;
    }

    public function closeImageModal(): void
    {
        $this->showImageModal = false;
        $this->selectedImageUrl = '';
    }

    public function getRelatedItemsProperty(): array
    {
        if (!$this->suggestion) {
            return ['tickets' => collect(), 'tasks' => collect()];
        }

        $ticketIds = array_filter((array) ($this->suggestion->related_ticket_id ?? []));
        $taskIds = array_filter((array) ($this->suggestion->related_task_id ?? []));

        $tickets = collect();
        if (!empty($ticketIds)) {
            $tickets = DB::connection('ticketingsystem_live')
                ->table('tickets')
                ->leftJoin('ticket_priorities', 'tickets.priority_id', '=', 'ticket_priorities.id')
                ->leftJoin('users', 'tickets.requestor_id', '=', 'users.id')
                ->whereIn('tickets.id', $ticketIds)
                ->get([
                    'tickets.id', 'tickets.ticket_id', 'tickets.title', 'tickets.status',
                    'tickets.description', 'tickets.created_at', 'tickets.company_name',
                    'ticket_priorities.name as priority_name',
                    'users.name as requestor_name',
                ]);
        }

        $tasks = collect();
        if (!empty($taskIds)) {
            $tasks = Task::whereIn('id', $taskIds)
                ->with(['priority:id,name'])
                ->get(['id', 'task_id', 'title', 'status', 'description', 'priority_id', 'due_date', 'created_at']);
        }

        return ['tickets' => $tickets, 'tasks' => $tasks];
    }

    public function openLinkPicker(): void
    {
        $this->showLinkPicker = !$this->showLinkPicker;
        $this->linkPickerMode = 'choose';
        $this->linkSearchTerm = '';
    }

    public function setLinkPickerMode(string $mode): void
    {
        $this->linkPickerMode = $mode; // 'choose' | 'ticket' | 'task'
        $this->linkSearchTerm = '';
    }

    public function closeLinkPicker(): void
    {
        $this->showLinkPicker = false;
        $this->linkPickerMode = 'choose';
        $this->linkSearchTerm = '';
    }

    public function getLinkableTasksProperty()
    {
        if (!$this->suggestion) return collect();
        $q = Task::query()->whereIn('product_id', [1, 2])->orderByDesc('created_at');
        if (trim((string) $this->linkSearchTerm) !== '') {
            $needle = '%' . trim($this->linkSearchTerm) . '%';
            $q->where(function ($inner) use ($needle) {
                $inner->where('task_id', 'like', $needle)->orWhere('title', 'like', $needle);
            });
        }
        return $q->limit(50)->get(['id', 'task_id', 'title', 'status', 'description']);
    }

    public function getLinkableTicketsProperty()
    {
        if (!$this->suggestion) return collect();
        $q = DB::connection('ticketingsystem_live')
            ->table('tickets')
            ->whereIn('product_id', [1, 2])
            ->orderByDesc('created_at');
        if (trim((string) $this->linkSearchTerm) !== '') {
            $needle = '%' . trim($this->linkSearchTerm) . '%';
            $q->where(function ($inner) use ($needle) {
                $inner->where('ticket_id', 'like', $needle)->orWhere('title', 'like', $needle);
            });
        }
        return $q->limit(50)->get(['id', 'ticket_id', 'title', 'status', 'description']);
    }

    public function linkTask(int $taskId): void
    {
        if (!$this->suggestion) return;
        try {
            $ids = array_values(array_unique(array_merge(array_map('intval', (array) ($this->suggestion->related_task_id ?? [])), [$taskId])));
            $this->suggestion->update(['related_task_id' => $ids]);
            $this->suggestion->refresh();
            $this->closeLinkPicker();
            Notification::make()->title('Task linked')->success()->send();
        } catch (\Exception $e) {
            Log::error('Link task failed: ' . $e->getMessage());
            Notification::make()->title('Failed to link task')->body($e->getMessage())->danger()->send();
        }
    }

    public function linkTicket(int $ticketId): void
    {
        if (!$this->suggestion) return;
        try {
            $ids = array_values(array_unique(array_merge(array_map('intval', (array) ($this->suggestion->related_ticket_id ?? [])), [$ticketId])));
            $this->suggestion->update(['related_ticket_id' => $ids]);
            $this->suggestion->refresh();
            $this->closeLinkPicker();
            Notification::make()->title('Ticket linked')->success()->send();
        } catch (\Exception $e) {
            Log::error('Link ticket failed: ' . $e->getMessage());
            Notification::make()->title('Failed to link ticket')->body($e->getMessage())->danger()->send();
        }
    }

    public function unlinkTask(int $taskId): void
    {
        if (!$this->suggestion) return;
        $ids = array_values(array_filter(array_map('intval', (array) ($this->suggestion->related_task_id ?? [])), fn ($id) => $id !== $taskId));
        $this->suggestion->update(['related_task_id' => $ids]);
        $this->suggestion->refresh();
        Notification::make()->title('Task unlinked')->success()->send();
    }

    public function unlinkTicket(int $ticketId): void
    {
        if (!$this->suggestion) return;
        $ids = array_values(array_filter(array_map('intval', (array) ($this->suggestion->related_ticket_id ?? [])), fn ($id) => $id !== $ticketId));
        $this->suggestion->update(['related_ticket_id' => $ids]);
        $this->suggestion->refresh();
        Notification::make()->title('Ticket unlinked')->success()->send();
    }

    public function getCommentsProperty()
    {
        if (!$this->suggestion) return collect();
        return DB::connection('ticketingsystem_live')
            ->table('suggestion_comments')
            ->leftJoin('users', 'suggestion_comments.user_id', '=', 'users.id')
            ->where('suggestion_id', $this->suggestion->id)
            ->orderByDesc('suggestion_comments.created_at')
            ->get(['suggestion_comments.id', 'suggestion_comments.comment', 'suggestion_comments.created_at', 'users.name as user_name']);
    }

    public function addComment(): void
    {
        if (!$this->suggestion || trim(strip_tags($this->newComment)) === '') return;
        try {
            $email = auth()->user()?->email;
            $userId = $email ? TicketingUser::where('email', $email)->value('id') : null;
            DB::connection('ticketingsystem_live')
                ->table('suggestion_comments')
                ->insert([
                    'suggestion_id' => $this->suggestion->id,
                    'user_id' => $userId ?? 22,
                    'comment' => $this->newComment,
                    'is_edited' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $this->newComment = '';
            Notification::make()->title('Comment added')->success()->send();
        } catch (\Exception $e) {
            Log::error('Add suggestion comment failed: ' . $e->getMessage());
            Notification::make()->title('Failed to add comment')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'commentForm' => $this->makeForm()->schema([
                RichEditor::make('newComment')
                    ->label('')
                    ->placeholder('Add a comment...')
                    ->toolbarButtons([
                        'attachFiles', 'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo',
                    ])
                    ->disableToolbarButtons(['codeBlock'])
                    ->fileAttachmentsDisk('s3-ticketing')
                    ->fileAttachmentsDirectory('suggestion_images')
                    ->fileAttachmentsVisibility('private'),
            ]),
        ];
    }
}
