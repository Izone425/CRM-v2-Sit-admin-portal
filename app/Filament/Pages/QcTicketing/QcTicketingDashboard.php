<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TicketingUser;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class QcTicketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string $view = 'filament.pages.qc-ticketing.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $slug = 'qc-ticketing/dashboard';
    protected static bool $shouldRegisterNavigation = false;

    private const CLOSED_TASK_STATUSES = ['Completed', 'Closed', 'Ready For Development', 'Cancel'];
    private const HR_PRODUCT_IDS = [1, 2];

    public ?int $ticketUserId = null;
    protected array $peerUserIds = [];
    public bool $showBugs = true;

    public function mount(): void
    {
        $email = auth()->user()?->email;
        $this->ticketUserId = $email ? TicketingUser::where('email', $email)->value('id') : null;
        $this->peerUserIds = $this->resolvePeerUserIds();
    }

    private function resolvePeerUserIds(): array
    {
        if (!$this->ticketUserId) {
            return [];
        }
        $roleIds = DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->where('model_id', $this->ticketUserId)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('role_id')
            ->all();
        if (empty($roleIds)) {
            return [$this->ticketUserId];
        }
        return DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id')
            ->unique()
            ->values()
            ->all();
    }

    private function canonicalTaskStatuses(): array
    {
        if (empty($this->peerUserIds)) {
            return [];
        }
        return Task::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->where(function ($q) {
                foreach ($this->peerUserIds as $id) {
                    $q->orWhereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $id)]);
                }
            })
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->all();
    }

    private function canonicalBugStatuses(): array
    {
        return Bug::query()->whereIn('product_id', self::HR_PRODUCT_IDS)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')->all();
    }

    private function fillStatuses(array $statuses, $counts): array
    {
        $base = array_fill_keys($statuses, 0);
        foreach ($counts as $status => $count) {
            $base[$status] = (int) $count;
        }
        return $base;
    }

    public function getTaskStatusCountsProperty(): array
    {
        if (!$this->ticketUserId) {
            return [];
        }
        $raw = Task::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        return $this->fillStatuses($this->canonicalTaskStatuses(), $raw);
    }

    public function getBugStatusCountsProperty(): array
    {
        if (!$this->ticketUserId) {
            return [];
        }
        $raw = Bug::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        return $this->fillStatuses($this->canonicalBugStatuses(), $raw);
    }

    public function getOverdueTasksProperty()
    {
        if (!$this->ticketUserId) {
            return collect();
        }
        return Task::query()
            ->with(['product:id,name'])
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $this->ticketUserId)])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', self::CLOSED_TASK_STATUSES)
            ->orderBy('due_date', 'asc')
            ->get();
    }
}
