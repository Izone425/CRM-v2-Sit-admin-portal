<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Module;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Release;
use App\Models\Ticketing\Solution;
use App\Models\Ticketing\Task;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class QcTicketingReleases extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string $view = 'filament.pages.qc-ticketing.releases';
    protected static ?string $navigationLabel = 'Releases';
    protected static ?string $title = '';
    protected static ?string $slug = 'qc-ticketing/releases';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $activeProductId = null;
    public ?string $selectedSolutionModule = null; // 'sol-3' or 'mod-7' or null
    public string $selectedPlatform = 'all'; // all|Web|App
    public ?int $selectedReleaseId = null;
    public string $itemSearch = '';
    public string $itemTypeFilter = 'All';

    public function mount(): void
    {
        $first = $this->products->first();
        $this->activeProductId = $first?->id;
    }

    public function setProduct(int $productId): void
    {
        $this->activeProductId = $productId;
        $this->selectedReleaseId = null;
    }

    public function setSolutionModule(?string $value): void
    {
        $this->selectedSolutionModule = $value === 'all' || $value === '' ? null : $value;
    }

    public function setPlatform(string $platform): void
    {
        $this->selectedPlatform = $platform;
    }

    public function setRelease(int $releaseId): void
    {
        $this->selectedReleaseId = $releaseId;
    }

    public function removeReleaseItem(string $type, int $itemId): void
    {
        if (!$this->selectedReleaseId) return;
        try {
            if ($type === 'Task') {
                Task::where('id', $itemId)->where('release_id', $this->selectedReleaseId)->update(['release_id' => null]);
            } elseif ($type === 'Bug') {
                Bug::where('id', $itemId)->where('release_id', $this->selectedReleaseId)->update(['release_id' => null]);
            }
            Notification::make()->title("{$type} removed from release")->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed to remove')->body($e->getMessage())->danger()->send();
        }
    }

    public function getProductsProperty()
    {
        return Product::where('is_active', 1)
            ->whereIn('id', [1, 2])
            ->orderByRaw("FIELD(id, 1, 2)")
            ->get();
    }

    public function getSolutionsProperty()
    {
        return Solution::where('is_active', 1)
            ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('product_has_solutions')->whereIn('product_id', [1, 2])->pluck('solution_id'))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function getModulesProperty()
    {
        return Module::where('is_active', 1)
            ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('product_has_modules')->whereIn('product_id', [1, 2])->pluck('module_id'))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function getFilteredReleasesProperty()
    {
        if (!$this->activeProductId) return collect();

        $query = Release::query()->with(['module:id,name'])->where('product_id', $this->activeProductId);

        if ($this->selectedPlatform !== 'all') {
            $query->whereJsonContains('platform', $this->selectedPlatform);
        }

        if ($this->selectedSolutionModule) {
            [$type, $id] = explode('-', $this->selectedSolutionModule, 2);
            if ($type === 'sol') $query->where('solution_id', (int) $id);
            elseif ($type === 'mod') $query->where('module_id', (int) $id);
        }

        return $query->orderByDesc('version')->get();
    }

    public function getVersionTreeProperty(): array
    {
        $tree = [];
        foreach ($this->filteredReleases as $r) {
            $main = preg_replace('/^([vV]?\d+\.\d+).*/', '$1', $r->version);
            if (!$main) $main = $r->version;
            $tree[$main]['main'] = $main;
            $tree[$main]['count'] = ($tree[$main]['count'] ?? 0) + 1;
            $tree[$main]['children'][] = [
                'id' => $r->id,
                'version' => $r->version,
                'status' => $r->status,
                'platform' => $r->platform,
                'module' => $r->module?->name,
            ];
        }
        krsort($tree);
        return $tree;
    }

    public function getSelectedReleaseProperty(): ?Release
    {
        if (!$this->selectedReleaseId) return null;
        return Release::with(['product', 'solution', 'module'])->find($this->selectedReleaseId);
    }

    public function getReleaseItemsProperty()
    {
        $release = $this->selectedRelease;
        if (!$release) return collect();

        $items = collect();

        if ($this->itemTypeFilter === 'All' || $this->itemTypeFilter === 'Tasks') {
            $tasks = $release->tasks()
                ->with(['module:id,name'])
                ->select('id', 'task_id', 'title', 'status', 'release_id', 'module_id', 'task_size', 'assignee_ids', 'requestor_id')
                ->get()
                ->map(function ($t) {
                    [$dev, $qc] = $this->classifyAssignees($t->assignee_ids ?? [], $t->requestor_id);
                    return [
                        'id' => $t->id,
                        'type' => 'Task',
                        'id_label' => $t->task_id,
                        'title' => $t->title,
                        'module' => $t->module?->name,
                        'size' => $t->task_size,
                        'developer' => $dev,
                        'qc' => $qc,
                        'status' => $t->status,
                    ];
                });
            $items = $items->merge($tasks);
        }

        if ($this->itemTypeFilter === 'All' || $this->itemTypeFilter === 'Bugs') {
            $bugs = $release->bugs()
                ->with(['module:id,name'])
                ->select('id', 'bug_id', 'title', 'status', 'release_id', 'module_id', 'severity', 'assignee_ids', 'reporter_id')
                ->get()
                ->map(function ($b) {
                    [$dev, $qc] = $this->classifyAssignees($b->assignee_ids ?? [], $b->reporter_id);
                    return [
                        'id' => $b->id,
                        'type' => 'Bug',
                        'id_label' => $b->bug_id,
                        'title' => $b->title,
                        'module' => $b->module?->name,
                        'size' => $b->severity,
                        'developer' => $dev,
                        'qc' => $qc,
                        'status' => $b->status,
                    ];
                });
            $items = $items->merge($bugs);
        }

        if ($this->itemSearch) {
            $needle = strtolower($this->itemSearch);
            $items = $items->filter(fn ($i) => str_contains(strtolower($i['title'] ?? ''), $needle)
                || str_contains(strtolower($i['id_label'] ?? ''), $needle));
        }

        return $items->values();
    }

    private function classifyAssignees(array $ids, ?int $requestorId): array
    {
        if (empty($ids)) return [null, null];

        $allIds = array_unique(array_merge($ids, $requestorId ? [$requestorId] : []));
        $rows = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('users')
            ->leftJoin('model_has_roles', function ($j) {
                $j->on('model_has_roles.model_id', '=', 'users.id')
                  ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('users.id', $allIds)
            ->select('users.id', 'users.name', 'roles.name as role_name')
            ->get()
            ->groupBy('id')
            ->map(function ($g) {
                $first = $g->first();
                return [
                    'name' => $first->name,
                    'roles' => $g->pluck('role_name')->filter()->all(),
                ];
            });

        $dev = null;
        $qc = null;
        foreach ($ids as $uid) {
            $u = $rows[$uid] ?? null;
            if (!$u) continue;
            $isQc = collect($u['roles'])->contains(fn ($r) => stripos($r, 'QC') !== false);
            $isRnd = collect($u['roles'])->contains(fn ($r) => stripos($r, 'RnD') !== false || stripos($r, 'RND') !== false || stripos($r, 'Developer') !== false);
            if ($isRnd && !$dev) $dev = $u['name'];
            if ($isQc && !$qc) $qc = $u['name'];
        }
        return [$dev, $qc];
    }

    public function getMainVersionStatsProperty(): array
    {
        $tree = $this->versionTree;
        return [
            'mainCount' => count($tree),
            'totalCount' => collect($tree)->sum('count'),
        ];
    }

    public bool $showBrowseItemsModal = false;

    public array $browseForm = [
        'task_ids' => [],
        'bug_ids' => [],
    ];

    public function openCreateTaskForRelease(): void
    {
        if (! $this->selectedRelease) return;
        $this->dispatch('openCreateTaskModal', releaseId: $this->selectedRelease->id);
    }

    public function openBrowseItemsModal(): void
    {
        if (! $this->selectedRelease) return;
        $this->browseForm = ['task_ids' => [], 'bug_ids' => []];
        $this->showBrowseItemsModal = true;
    }

    public function closeBrowseItemsModal(): void
    {
        $this->showBrowseItemsModal = false;
    }

    public function submitBrowseItems(): void
    {
        $release = $this->selectedRelease;
        if (! $release) return;

        $taskIds = array_filter(array_map('intval', $this->browseForm['task_ids'] ?? []));
        $bugIds = array_filter(array_map('intval', $this->browseForm['bug_ids'] ?? []));

        $taskCount = 0;
        $bugCount = 0;

        if (! empty($taskIds)) {
            $taskCount = Task::whereIn('id', $taskIds)->whereNull('release_id')->update(['release_id' => $release->id]);
        }
        if (! empty($bugIds)) {
            $bugCount = Bug::whereIn('id', $bugIds)->whereNull('release_id')->update(['release_id' => $release->id]);
        }

        if ($taskCount === 0 && $bugCount === 0) {
            Notification::make()->title('Nothing selected')->warning()->send();
            return;
        }

        Notification::make()
            ->title('Items attached')
            ->body("{$taskCount} task(s), {$bugCount} bug(s) added to release.")
            ->success()
            ->send();

        $this->showBrowseItemsModal = false;
    }

    public function getUnassignedTasksProperty()
    {
        $release = $this->selectedRelease;
        if (! $release) return collect();

        [$ticketUserId, $isLeadOrManager] = $this->getTicketingRoleContext();
        if (! $ticketUserId) return collect();

        return Task::whereNull('release_id')
            ->where('product_id', $release->product_id)
            ->when($release->solution_id, function ($q) use ($release) {
                $q->where('solution_id', $release->solution_id);
            }, function ($q) use ($release) {
                if ($release->module_id) {
                    $q->where('module_id', $release->module_id);
                }
            })
            ->when(! $isLeadOrManager, function ($q) use ($ticketUserId) {
                $q->where(function ($sub) use ($ticketUserId) {
                    $sub->whereJsonContains('assignee_ids', $ticketUserId)
                        ->orWhere('requestor_id', $ticketUserId);
                });
            })
            ->orderByDesc('created_at')
            ->get(['id', 'task_id', 'title']);
    }

    public function getUnassignedBugsProperty()
    {
        $release = $this->selectedRelease;
        if (! $release) return collect();

        [$ticketUserId, $isLeadOrManager] = $this->getTicketingRoleContext();
        if (! $ticketUserId) return collect();

        return Bug::whereNull('release_id')
            ->where('product_id', $release->product_id)
            ->when($release->solution_id, function ($q) use ($release) {
                $q->where('solution_id', $release->solution_id);
            }, function ($q) use ($release) {
                if ($release->module_id) {
                    $q->where('module_id', $release->module_id);
                }
            })
            ->when(! $isLeadOrManager, function ($q) use ($ticketUserId) {
                $q->where(function ($sub) use ($ticketUserId) {
                    $sub->whereJsonContains('assignee_ids', $ticketUserId)
                        ->orWhere('reporter_id', $ticketUserId);
                });
            })
            ->orderByDesc('created_at')
            ->get(['id', 'bug_id', 'title']);
    }

    private function getTicketingRoleContext(): array
    {
        $email = auth()->user()?->email;
        if (! $email) return [null, false];

        $ticketUserId = \App\Models\Ticketing\TicketingUser::where('email', $email)->value('id');
        if (! $ticketUserId) return [null, false];

        $roles = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
            ->table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $ticketUserId)
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('roles.name');

        $isLeadOrManager = $roles->contains(fn ($name) => stripos($name, 'Lead') !== false || stripos($name, 'Manager') !== false);

        return [(int) $ticketUserId, $isLeadOrManager];
    }
}
