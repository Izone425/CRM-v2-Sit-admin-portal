<?php

namespace App\Livewire;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Livewire\Component;

class StatusListDrawer extends Component
{
    public bool $showDrawer = false;
    public string $type = 'task';
    public ?string $status = null;

    protected $listeners = [
        'openStatusList' => 'openDrawer',
        'closeStatusList' => 'closeDrawer',
    ];

    public function render()
    {
        return view('livewire.status-list-drawer');
    }

    public function openDrawer(string $type, string $status): void
    {
        $this->type = in_array($type, ['task', 'bug'], true) ? $type : 'task';
        $this->status = $status;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    protected function getTicketUserId(): ?int
    {
        $email = auth()->user()?->email;
        return $email ? TicketingUser::where('email', $email)->value('id') : null;
    }

    public function getItemsProperty()
    {
        if (!$this->showDrawer || !$this->status) {
            return collect();
        }

        $userId = $this->getTicketUserId();
        if (!$userId) {
            return collect();
        }

        if ($this->type === 'bug') {
            return Bug::query()
                ->with(['product:id,name'])
                ->whereIn('product_id', [1, 2])
                ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $userId)])
                ->where('status', $this->status)
                ->orderByDesc('created_at')
                ->get(['id', 'bug_id', 'title', 'status', 'severity', 'product_id', 'created_at']);
        }

        return Task::query()
            ->with(['product:id,name'])
            ->whereIn('product_id', [1, 2])
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $userId)])
            ->where('status', $this->status)
            ->orderByDesc('created_at')
            ->get(['id', 'task_id', 'title', 'status', 'product_id', 'due_date', 'created_at']);
    }
}
