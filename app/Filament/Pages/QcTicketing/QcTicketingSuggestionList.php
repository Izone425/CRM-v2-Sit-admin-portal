<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Product;
use App\Models\Ticketing\Solution;
use App\Models\Ticketing\Suggestion;
use Filament\Pages\Page;

class QcTicketingSuggestionList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static string $view = 'filament.pages.qc-ticketing.suggestion-list';
    protected static ?string $navigationLabel = 'Suggestion List';
    protected static ?string $title = 'Suggestion List';
    protected static ?string $slug = 'qc-ticketing/suggestion-list';
    protected static bool $shouldRegisterNavigation = false;

    protected const HR_PRODUCT_IDS = [1, 2];

    // SUGGESTION_PRIORITIES (constants.ts:35) — display labels + colors
    public const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];
    public const PRIORITY_LABELS = [
        'Critical' => 'Urgent Need',
        'High' => 'Significant Impact',
        'Medium' => 'Important Improvement',
        'Low' => 'Nice to Have',
    ];
    public const PRIORITY_COLOR = [
        'Critical' => '#DC2626',
        'High' => '#F97316',
        'Medium' => '#3B82F6',
        'Low' => '#10B981',
    ];
    public const PRIORITY_BG = [
        'Critical' => '#FEE2E2',
        'High' => '#FFEDD5',
        'Medium' => '#DBEAFE',
        'Low' => '#D1FAE5',
    ];

    // REQUEST_STATUSES (+ New/Rejected)
    public const STATUSES = ['New', 'Approved', 'In Progress', 'Live'];
    public const STATUS_COLOR = [
        'New' => '#6B7280',
        'Approved' => '#059669',
        'In Progress' => '#D97706',
        'Live' => '#7C3AED',
        'Rejected' => '#DC2626',
    ];
    public const STATUS_BG = [
        'New' => '#F3F4F6',
        'Approved' => '#D1FAE5',
        'In Progress' => '#FEF3C7',
        'Live' => '#EDE9FE',
        'Rejected' => '#FEE2E2',
    ];

    // CATEGORY_DESCRIPTIONS (SuggestionList index.tsx:29)
    public const CATEGORIES = [
        'New Feature' => 'New functionality and feature requests',
        'Enhancement' => 'Improvements to existing features',
        'Usability/UX' => 'Visual design, usability, and interface improvements',
        'Performance' => 'Speed improvements and workflow optimization',
        'Integration' => 'Third-party integrations and API features',
        'Other' => 'Miscellaneous suggestions',
    ];

    public string $activeTab = 'List View';
    public string $searchTerm = '';
    public string $filterStatus = 'All';
    public string $filterPriority = 'All';
    public string $filterProduct = 'All';
    public string $filterSolution = 'All';
    public string $filterCategory = 'All';
    public string $filterModule = 'All';
    public array $expandedCategories = [];
    public bool $showRejected = false;

    public function mount(): void
    {
        $this->activeTab = session('qc_suggestion_active_tab', 'List View');
        $this->expandedCategories = array_keys(self::CATEGORIES);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        session(['qc_suggestion_active_tab' => $tab]);
    }

    public function toggleRejected(): void
    {
        $this->showRejected = !$this->showRejected;
    }

    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->expandedCategories, true)) {
            $this->expandedCategories = array_values(array_filter($this->expandedCategories, fn ($c) => $c !== $category));
        } else {
            $this->expandedCategories[] = $category;
        }
    }

    public function clearFilters(): void
    {
        $this->filterStatus = 'All';
        $this->filterPriority = 'All';
        $this->filterProduct = 'All';
        $this->filterSolution = 'All';
        $this->filterCategory = 'All';
        $this->filterModule = 'All';
        $this->searchTerm = '';
    }

    public function getSummaryProperty(): array
    {
        $base = Suggestion::query()->whereIn('product_id', self::HR_PRODUCT_IDS);
        return [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', 'New')->count(),
            'approved' => (clone $base)->where('status', 'Approved')->count(),
            'in_progress' => (clone $base)->where('status', 'In Progress')->count(),
            'live' => (clone $base)->where('status', 'Live')->count(),
        ];
    }

    public function getSuggestionsProperty()
    {
        $q = Suggestion::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->with(['product:id,name', 'module:id,name', 'solution:id,name', 'requestor:id,name']);

        if ($this->showRejected) {
            $q->where('status', 'Rejected');
        }

        if ($this->filterStatus !== 'All') {
            $q->where('status', $this->filterStatus);
        }
        if ($this->filterPriority !== 'All') {
            $q->where('priority', $this->filterPriority);
        }
        if ($this->filterCategory !== 'All') {
            $q->where('category', $this->filterCategory);
        }
        if ($this->filterModule !== 'All') {
            $q->whereHas('module', fn ($m) => $m->where('name', $this->filterModule));
        }
        if ($this->filterProduct !== 'All') {
            $q->whereHas('product', fn ($p) => $p->where('name', $this->filterProduct));
        }
        if ($this->filterSolution !== 'All') {
            $q->whereHas('solution', fn ($p) => $p->where('name', $this->filterSolution));
        }
        if (trim($this->searchTerm) !== '') {
            $needle = '%' . trim($this->searchTerm) . '%';
            $q->where(function ($inner) use ($needle) {
                $inner->where('suggestion_id', 'like', $needle)
                    ->orWhere('title', 'like', $needle)
                    ->orWhere('description', 'like', $needle);
            });
        }

        return $q->orderByDesc('created_at')->limit(1000)->get();
    }

    public function getStatusOptionsProperty(): array
    {
        return array_merge(['All'], self::STATUSES);
    }

    public function getPriorityOptionsProperty(): array
    {
        return array_merge(['All'], self::PRIORITIES);
    }

    public function getProductOptionsProperty(): array
    {
        return array_merge(['All'], Product::whereIn('id', self::HR_PRODUCT_IDS)->orderBy('name')->pluck('name')->all());
    }

    public function getSolutionOptionsProperty(): array
    {
        return array_merge(['All'], Solution::where('is_active', 1)
            ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('product_has_solutions')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('solution_id'))
            ->orderBy('name')->pluck('name')->all());
    }

    public function getModuleOptionsProperty(): array
    {
        $names = $this->suggestions->pluck('module.name')->filter()->unique()->sort()->values()->all();
        return array_merge(['All'], $names);
    }

    public function getCategoryOptionsProperty(): array
    {
        return array_merge(['All'], array_keys(self::CATEGORIES));
    }

    /**
     * Group suggestions by category for Tree View.
     */
    public function getByCategoryProperty(): array
    {
        $groups = array_fill_keys(array_keys(self::CATEGORIES), []);
        foreach ($this->suggestions as $s) {
            $cat = $s->category ?: 'Other';
            if (!isset($groups[$cat])) {
                $groups[$cat] = [];
            }
            $groups[$cat][] = $s;
        }
        return $groups;
    }

    /**
     * Build module × status matrix for Map View.
     */
    public function getMapMatrixProperty(): array
    {
        // columns = statuses (exclude New + Rejected)
        $cols = ['Approved', 'In Progress', 'Live'];
        $rows = [];
        foreach ($this->suggestions as $s) {
            $moduleName = $s->module?->name ?: '—';
            $status = $s->status;
            if (!in_array($status, $cols, true)) continue;
            if (!isset($rows[$moduleName])) {
                $rows[$moduleName] = array_fill_keys($cols, []);
            }
            $rows[$moduleName][$status][] = $s;
        }
        ksort($rows);
        return ['cols' => $cols, 'rows' => $rows];
    }
}
