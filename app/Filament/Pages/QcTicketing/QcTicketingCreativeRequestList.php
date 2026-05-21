<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\CreativeRequest;
use App\Models\Ticketing\TicketingUser;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QcTicketingCreativeRequestList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static string $view = 'filament.pages.qc-ticketing.creative-request-list';
    protected static ?string $navigationLabel = 'Creative Request List';
    protected static ?string $title = 'Creative Request List';
    protected static ?string $slug = 'qc-ticketing/creative-request-list';
    protected static bool $shouldRegisterNavigation = false;

    // From ticketing KANBAN_COLUMNS
    public const KANBAN_COLUMNS = ['New', 'Accepted', 'In Progress', 'Pending Review', 'Approved', 'Completed'];

    // From ticketing STATUS_COLORS
    public const STATUS_COLORS = [
        'New' => '#999999',
        'Accepted' => '#56B4E9',
        'In Progress' => '#E69F00',
        'Pending Review' => '#CC79A7',
        'Need Revision' => '#D55E00',
        'Approved' => '#009E73',
        'Completed' => '#0072B2',
        'Rejected' => '#DC2626',
        'Cancelled' => '#6B7280',
        'KIV' => '#9333EA',
    ];

    // From CREATIVE_REQUEST_PRIORITIES
    public const PRIORITIES = ['Urgent', 'High', 'Medium', 'Low'];
    public const PRIORITY_SYMBOLS = [
        'Urgent' => '!!!',
        'Critical' => '!!!',
        'High' => '!!',
        'Medium' => '!',
        'Low' => '-',
    ];
    public const PRIORITY_COLORS = [
        'Urgent' => '#DC2626',
        'Critical' => '#DC2626',
        'High' => '#F97316',
        'Medium' => '#3B82F6',
        'Low' => '#10B981',
    ];

    // Pale backgrounds for badge pills
    public const PRIORITY_BG = [
        'Urgent' => '#FEE2E2',
        'Critical' => '#FEE2E2',
        'High' => '#FFEDD5',
        'Medium' => '#DBEAFE',
        'Low' => '#D1FAE5',
    ];

    // Pale backgrounds per status for badges (pairs with STATUS_COLORS text)
    public const STATUS_BG = [
        'New' => '#FEF3C7',
        'Accepted' => '#DBEAFE',
        'In Progress' => '#FEF3C7',
        'Pending Review' => '#FCE7F3',
        'Need Revision' => '#FFEDD5',
        'Approved' => '#D1FAE5',
        'Completed' => '#D1FAE5',
        'Rejected' => '#FEE2E2',
        'Cancelled' => '#E5E7EB',
        'KIV' => '#F3E8FF',
    ];

    // From CREATIVE_REQUEST_CATEGORIES
    public const CATEGORIES = [
        'Website', 'UI/UX', 'Graphic', 'Mailer', 'POSM',
        'Presentation Slide', 'Video',
    ];

    protected const HR_PRODUCT_IDS = [1, 2];

    public string $activeTab = 'List View';
    public string $searchTerm = '';
    public string $filterStatus = 'All';
    public string $filterCategory = 'All';
    public string $filterPriority = 'All';
    public string $filterRequestor = 'All';

    public function mount(): void
    {
        $this->activeTab = session('qc_creative_active_tab', 'List View');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        session(['qc_creative_active_tab' => $tab]);
    }

    public function clearFilters(): void
    {
        $this->filterStatus = 'All';
        $this->filterCategory = 'All';
        $this->filterPriority = 'All';
        $this->filterRequestor = 'All';
        $this->searchTerm = '';
    }

    public function getSummaryProperty(): array
    {
        $base = CreativeRequest::query()->whereIn('product_id', self::HR_PRODUCT_IDS);
        $monthStart = now()->startOfMonth()->toDateTimeString();
        return [
            'total' => (clone $base)->count(),
            'in_progress' => (clone $base)->where('status', 'In Progress')->count(),
            'pending_review' => (clone $base)->where('status', 'Pending Review')->count(),
            'completed' => (clone $base)->where('status', 'Completed')->where('updated_at', '>=', $monthStart)->count(),
            'overdue' => (clone $base)->whereNotNull('due_date')
                ->whereDate('due_date', '<', now())
                ->whereNotIn('status', ['Completed', 'Cancelled', 'Rejected'])
                ->count(),
        ];
    }

    public function getRequestsProperty()
    {
        $q = CreativeRequest::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->with(['product:id,name', 'module:id,name', 'requestor:id,name,email', 'assignee:id,name,email']);

        if ($this->filterStatus !== 'All') {
            $q->where('status', $this->filterStatus);
        }
        if ($this->filterCategory !== 'All') {
            $q->where('category', $this->filterCategory);
        }
        if ($this->filterPriority !== 'All') {
            $q->where('priority', $this->filterPriority);
        }
        if ($this->filterRequestor !== 'All') {
            $q->where('requestor_id', $this->filterRequestor);
        }
        if (trim($this->searchTerm) !== '') {
            $needle = '%' . trim($this->searchTerm) . '%';
            $q->where(function ($inner) use ($needle) {
                $inner->where('request_id', 'like', $needle)
                    ->orWhere('title', 'like', $needle)
                    ->orWhere('description', 'like', $needle);
            });
        }

        return $q->orderByDesc('created_at')->limit(500)->get();
    }

    public function getStatusOptionsProperty(): array
    {
        return array_merge(['All'], self::KANBAN_COLUMNS);
    }

    public function getCategoryOptionsProperty(): array
    {
        return array_merge(['All'], self::CATEGORIES);
    }

    public function getPriorityOptionsProperty(): array
    {
        return array_merge(['All'], self::PRIORITIES);
    }

    /**
     * Build timeline segments per request (mirroring ticketing's buildTimelineData).
     * Returns: ['id' => [ ['status' => ..., 'start' => Carbon, 'end' => Carbon], ... ]]
     */
    public function getTimelineSegmentsProperty(): array
    {
        $requests = $this->requests;
        if ($requests->isEmpty()) {
            return [];
        }

        $ids = $requests->pluck('id')->all();
        $logs = DB::connection('ticketingsystem_live')
            ->table('creative_request_logs')
            ->whereIn('request_id', $ids)
            ->where(function ($q) {
                $q->where('field_name', 'status')
                  ->orWhere('change_type', 'status_change');
            })
            ->orderBy('created_at')
            ->get(['request_id', 'old_value', 'new_value', 'created_at'])
            ->groupBy('request_id');

        $today = now();
        $terminal = ['Rejected', 'Cancelled', 'Completed'];
        $out = [];

        $normalize = function ($value): string {
            if ($value === null) return '';
            $v = trim((string) $value);
            if ($v === '') return '';
            $decoded = json_decode($v, true);
            if (is_string($decoded)) return trim($decoded);
            return trim($v, "\"' ");
        };

        foreach ($requests as $r) {
            $createdAt = $r->created_at ? Carbon::parse($r->created_at) : $today->copy();
            $reqLogs = ($logs[$r->id] ?? collect())->values();
            $terminalLog = $reqLogs->first(fn ($l) => in_array($normalize($l->new_value), $terminal, true));
            $lifecycleEnd = $terminalLog ? Carbon::parse($terminalLog->created_at) : $today->copy();

            $segments = [];
            if ($reqLogs->isEmpty()) {
                $segments[] = [
                    'status' => $r->status ?? 'New',
                    'start' => $createdAt,
                    'end' => $lifecycleEnd,
                ];
            } else {
                $firstLogDate = Carbon::parse($reqLogs[0]->created_at);
                if ($createdAt->lt($firstLogDate)) {
                    $segments[] = [
                        'status' => $normalize($reqLogs[0]->old_value) ?: 'New',
                        'start' => $createdAt,
                        'end' => $firstLogDate,
                    ];
                }
                foreach ($reqLogs as $i => $log) {
                    $next = $reqLogs[$i + 1] ?? null;
                    $segments[] = [
                        'status' => $normalize($log->new_value) ?: ($r->status ?? 'New'),
                        'start' => Carbon::parse($log->created_at),
                        'end' => $next ? Carbon::parse($next->created_at) : $lifecycleEnd,
                    ];
                }
            }

            $out[$r->id] = $segments;
        }

        return $out;
    }

    public function getRequestorOptionsProperty()
    {
        $ids = CreativeRequest::query()->whereIn('product_id', self::HR_PRODUCT_IDS)->whereNotNull('requestor_id')->distinct()->pluck('requestor_id');
        if ($ids->isEmpty()) {
            return collect();
        }
        return TicketingUser::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }
}
