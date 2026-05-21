<?php

namespace App\Livewire;

use App\Models\Ticketing\Product;
use App\Models\Ticketing\Suggestion;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateSuggestionDrawer extends Component
{
    use WithFileUploads;

    public bool $showDrawer = false;
    public ?int $product_id = null;
    public ?int $module_id = null;
    public string $title = '';
    public string $description = '';
    public ?string $priority = null;
    public ?string $category = null;
    public array $related_task_ids = [];
    public array $attachments = [];
    public ?string $reference_link = null;

    protected $listeners = ['openCreateSuggestionModal' => 'openDrawer'];

    public function render() { return view('livewire.create-suggestion-drawer'); }

    public function openDrawer(): void
    {
        $this->reset(['product_id', 'module_id', 'title', 'description', 'priority', 'category', 'related_task_ids', 'attachments', 'reference_link']);
        $this->showDrawer = true;
    }

    public function closeDrawer(): void { $this->showDrawer = false; }

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
    public function getCategoryOptionsProperty(): array { return ['Feature', 'Improvement', 'UI/UX', 'Performance', 'Other']; }

    public function submit(bool $addAnother = false): void
    {
        $this->validate([
            'product_id' => 'required|integer',
            'module_id' => 'required|integer',
            'title' => 'required|string|max:500',
            'description' => 'required|string',
            'priority' => 'required|string',
            'category' => 'required|string',
        ], [
            'product_id.required' => 'Product is required.',
            'module_id.required' => 'Module is required.',
            'title.required' => 'Title is required.',
            'description.required' => 'Description is required.',
            'priority.required' => 'Priority is required.',
            'category.required' => 'Category is required.',
        ]);

        try {
            $sid = $this->generateSuggestionId();
            $userId = $this->getTicketSystemUserId();

            Suggestion::create([
                'suggestion_id' => $sid,
                'title' => $this->title,
                'description' => $this->description,
                'product_id' => $this->product_id,
                'module_id' => $this->module_id,
                'related_task_id' => $this->related_task_ids[0] ?? null,
                'priority' => $this->priority,
                'category' => $this->category,
                'status' => 'New',
                'requestor_id' => $userId,
                'reference_link' => $this->reference_link ?: null,
            ]);

            Notification::make()->title("Suggestion {$sid} created")->success()->send();
            $this->dispatch('suggestion-created');

            if ($addAnother) {
                $this->reset(['title', 'description', 'priority', 'category', 'related_task_ids', 'attachments', 'reference_link']);
            } else {
                $this->closeDrawer();
            }
        } catch (\Exception $e) {
            Log::error('Create suggestion failed: ' . $e->getMessage());
            Notification::make()->title('Failed to create suggestion')->body($e->getMessage())->danger()->send();
        }
    }

    public function submitAndAddAnother(): void
    {
        $this->submit(true);
    }

    private function getTicketSystemUserId(): int
    {
        $email = auth()->user()?->email;
        if (!$email) return 22;
        return TicketingUser::where('email', $email)->value('id') ?? 22;
    }

    private function generateSuggestionId(): string
    {
        $sid = '';
        DB::connection('ticketingsystem_live')->transaction(function () use (&$sid) {
            $last = Suggestion::lockForUpdate()->orderByRaw('CAST(SUBSTRING(suggestion_id, 5) AS UNSIGNED) DESC')->first();
            $next = $last ? ((int) substr($last->suggestion_id, 4)) + 1 : 1;
            $sid = 'SUG-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            while (Suggestion::where('suggestion_id', $sid)->exists()) {
                $next++;
                $sid = 'SUG-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            }
        });
        return $sid;
    }
}
