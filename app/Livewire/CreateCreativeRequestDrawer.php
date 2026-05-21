<?php

namespace App\Livewire;

use App\Models\Ticketing\CreativeRequest;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateCreativeRequestDrawer extends Component
{
    use WithFileUploads;

    public bool $showDrawer = false;
    public string $title = '';
    public ?int $product_id = null;
    public ?int $module_id = null;
    public ?string $category = null;
    public ?string $priority = null;
    public string $description = '';
    public ?string $expected_completion_date = null;
    public array $attachments = [];
    public array $attachmentLinks = [];
    public string $newLinkLabel = '';
    public string $newLinkUrl = '';
    public bool $showLinkInput = false;
    public ?int $editingLinkIndex = null;
    public ?string $reference_link = null;
    public array $related_task_ids = [];

    protected $listeners = ['openCreateCreativeRequestModal' => 'openDrawer'];

    public function render() { return view('livewire.create-creative-request-drawer'); }

    public function openDrawer(): void
    {
        $this->reset(['title', 'product_id', 'module_id', 'category', 'priority', 'description', 'expected_completion_date', 'attachments', 'attachmentLinks', 'newLinkLabel', 'newLinkUrl', 'showLinkInput', 'editingLinkIndex', 'reference_link', 'related_task_ids']);
        $this->showDrawer = true;
    }

    public function closeDrawer(): void { $this->showDrawer = false; }

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

    public function toggleRelatedTask(int $taskId): void
    {
        if (in_array($taskId, $this->related_task_ids)) {
            $this->related_task_ids = array_values(array_filter($this->related_task_ids, fn ($id) => $id != $taskId));
        } else {
            $this->related_task_ids[] = $taskId;
        }
    }

    public function removeRelatedTask(int $taskId): void
    {
        $this->related_task_ids = array_values(array_filter($this->related_task_ids, fn ($id) => $id != $taskId));
    }

    public function getProductsProperty() { return Product::where('is_active', 1)->whereIn('id', [1, 2])->orderBy('name')->get(['id', 'name']); }

    public function updatedProductId(): void
    {
        $this->module_id = null;
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
    public function getTasksProperty() { return Task::orderBy('id', 'desc')->limit(200)->get(['id', 'task_id', 'title']); }
    public function getPriorityOptionsProperty(): array { return ['Highest', 'High', 'Medium', 'Low']; }
    public function getCategoryOptionsProperty(): array { return ['Banner', 'Poster', 'Video', 'Logo', 'Marketing Material', 'Other']; }

    public function submit(): void
    {
        $this->validate([
            'title' => 'required|string|max:500',
            'product_id' => 'required|integer',
            'module_id' => 'required|integer',
            'category' => 'required|string',
            'priority' => 'required|string',
            'description' => 'required|string',
        ], [
            'title.required' => 'Request title is required.',
            'product_id.required' => 'Product is required.',
            'module_id.required' => 'Module is required.',
            'category.required' => 'Category is required.',
            'priority.required' => 'Priority is required.',
            'description.required' => 'Description is required.',
        ]);

        try {
            $rid = $this->generateRequestId();
            $userId = $this->getTicketSystemUserId();

            CreativeRequest::create([
                'request_id' => $rid,
                'title' => $this->title,
                'description' => $this->description,
                'product_id' => $this->product_id,
                'module_id' => $this->module_id,
                'related_task_id' => $this->related_task_ids[0] ?? null,
                'priority' => $this->priority,
                'category' => $this->category,
                'status' => 'New',
                'requestor_id' => $userId,
                'expected_completion_date' => $this->expected_completion_date ?: null,
            ]);

            Notification::make()->title("Request {$rid} created")->success()->send();
            $this->closeDrawer();
            $this->dispatch('creative-request-created');
        } catch (\Exception $e) {
            Log::error('Create request failed: ' . $e->getMessage());
            Notification::make()->title('Failed to create request')->body($e->getMessage())->danger()->send();
        }
    }

    private function getTicketSystemUserId(): int
    {
        $email = auth()->user()?->email;
        if (!$email) return 22;
        return TicketingUser::where('email', $email)->value('id') ?? 22;
    }

    private function generateRequestId(): string
    {
        $rid = '';
        DB::connection('ticketingsystem_live')->transaction(function () use (&$rid) {
            $last = CreativeRequest::lockForUpdate()->orderByRaw('CAST(SUBSTRING(request_id, 4) AS UNSIGNED) DESC')->first();
            $next = $last ? ((int) substr($last->request_id, 3)) + 1 : 1;
            $rid = 'CR-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            while (CreativeRequest::where('request_id', $rid)->exists()) {
                $next++;
                $rid = 'CR-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            }
        });
        return $rid;
    }
}
