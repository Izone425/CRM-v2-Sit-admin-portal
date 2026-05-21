<?php

namespace App\Livewire;

use App\Models\Ticketing\CreativeRequest;
use App\Models\Ticketing\CreativeRequestComment;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Collection;
use Livewire\Component;

class CreativeRequestModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?CreativeRequest $request = null;
    public bool $showModal = false;
    public string $activeTab = 'comments';
    public string $newComment = '';

    public Collection $comments;
    public Collection $attachments;
    public Collection $links;
    public Collection $logs;

    protected $listeners = [
        'openCreativeRequest' => 'viewRequest',
        'closeCreativeRequest' => 'closeModal',
    ];

    public function mount(): void
    {
        $this->comments = collect();
        $this->attachments = collect();
        $this->links = collect();
        $this->logs = collect();
    }

    public function render()
    {
        return view('livewire.creative-request-modal');
    }

    public function viewRequest(int $id): void
    {
        $this->request = CreativeRequest::with([
            'product:id,name',
            'module:id,name',
            'solution:id,name',
            'subModule:id,name',
            'requestor:id,name,email',
            'assignee:id,name,email',
        ])->find($id);

        if (!$this->request) {
            return;
        }

        $this->activeTab = 'comments';
        $this->newComment = '';
        $this->loadRelated();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->request = null;
        $this->newComment = '';
        $this->comments = collect();
        $this->attachments = collect();
        $this->links = collect();
        $this->logs = collect();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function postComment(): void
    {
        if (!$this->request) {
            return;
        }

        $body = trim(strip_tags($this->newComment));
        if ($body === '') {
            return;
        }

        CreativeRequestComment::create([
            'request_id' => $this->request->id,
            'user_id' => auth()->id(),
            'comment' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->loadRelated();
    }

    protected function loadRelated(): void
    {
        if (!$this->request) {
            return;
        }

        $this->comments = $this->request->comments()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        $allAttachments = $this->request->attachments()
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        $this->attachments = $allAttachments->where('attachment_type', '!=', 'link')->values();
        $this->links = $allAttachments->where('attachment_type', 'link')->values();

        $this->logs = $this->request->logs()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
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
                    ->fileAttachmentsDirectory('creative_request_images')
                    ->fileAttachmentsVisibility('private'),
            ]),
        ];
    }
}
