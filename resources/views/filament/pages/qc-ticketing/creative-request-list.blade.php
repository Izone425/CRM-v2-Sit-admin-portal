<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $requests = $this->requests;
        $statusColors = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::STATUS_COLORS;
        $prioritySymbols = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::PRIORITY_SYMBOLS;
        $priorityColors = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::PRIORITY_COLORS;
        $priorityBg = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::PRIORITY_BG;
        $statusBg = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::STATUS_BG;
        $kanbanCols = \App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList::KANBAN_COLUMNS;
        $grouped = [];
        foreach ($requests as $r) {
            $grouped[$r->status] = $grouped[$r->status] ?? [];
            $grouped[$r->status][] = $r;
        }
        $cardStyle = 'background: white; padding: 20px; border-radius: 4px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
    @endphp

    <!-- Summary Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #3498db;">{{ $summary['total'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Total Requests</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #9b59b6;">{{ $summary['in_progress'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">In Progress</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #27ae60;">{{ $summary['pending_review'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Pending Review</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #f39c12;">{{ $summary['completed'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Completed This Month</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #1abc9c;">{{ $summary['overdue'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Overdue</div>
        </div>
    </div>

    <!-- Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid #E5E7EB; padding-bottom: 0;">
        @foreach (['List View', 'Board View', 'Timeline View'] as $tab)
            @php $isActive = $activeTab === $tab; @endphp
            <button type="button" wire:click="setTab('{{ $tab }}')"
                style="padding: 12px 24px; background: transparent; border: 0; margin-bottom: -2px; font-size: 14px; font-weight: {{ $isActive ? '700' : '600' }}; color: {{ $isActive ? '#6366F1' : '#4B5563' }}; cursor: pointer; border-bottom: 2px solid {{ $isActive ? '#6366F1' : 'transparent' }};">
                {{ $tab }}@if ($tab === 'List View') ({{ $requests->count() }}) @endif
            </button>
        @endforeach
    </div>

    <!-- Card wrapper -->
    <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 24px;">
        <!-- Toolbar -->
        <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end;">
            <div style="display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 10px 14px; flex: 1; min-width: 250px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search requests..."
                    style="border: 0; outline: none; flex: 1; font-size: 14px; background: transparent;">
            </div>

            <div style="display: flex; align-items: flex-end; gap: 8px;">
                @php
                    $fg = 'display:flex;flex-direction:column;gap:4px;width:150px;';
                    $fl = 'font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.4px;';
                    $sel = 'appearance:none;-webkit-appearance:none;width:100%;padding:8px 32px 8px 12px;background:#fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 10px center;background-size:12px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;cursor:pointer;';
                @endphp
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Status</label>
                    <select wire:model.live="filterStatus" style="{{ $sel }}">
                        @foreach ($this->statusOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Category</label>
                    <select wire:model.live="filterCategory" style="{{ $sel }}">
                        @foreach ($this->categoryOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Priority</label>
                    <select wire:model.live="filterPriority" style="{{ $sel }}">
                        @foreach ($this->priorityOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Requestor</label>
                    <select wire:model.live="filterRequestor" style="{{ $sel }}">
                        <option value="All">All</option>
                        @foreach ($this->requestorOptions as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="clearFilters"
                    style="align-self:flex-end; padding: 8px 16px; background: transparent; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; cursor: pointer; color: #4B5563;">
                    Clear Filters
                </button>
            </div>
        </div>

        @if ($activeTab === 'Board View')
            <!-- Kanban Board -->
            <div style="display: grid; grid-template-columns: repeat({{ count($kanbanCols) }}, minmax(0, 1fr)); gap: 10px;">
                @foreach ($kanbanCols as $status)
                    @php
                        $colReqs = $grouped[$status] ?? [];
                        $headerColor = $statusColors[$status] ?? '#9CA3AF';
                    @endphp
                    <div style="background: #F3F4F6; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="padding: 10px 14px; background: {{ $headerColor }}; color: #fff; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: space-between;">
                            <span>{{ $status }}</span>
                            <span style="background: rgba(255,255,255,0.3); padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;">{{ count($colReqs) }}</span>
                        </div>
                        <div style="padding: 10px; display: flex; flex-direction: column; gap: 10px; min-height: 160px;">
                            @foreach ($colReqs as $r)
                                @php
                                    $priSym = $prioritySymbols[$r->priority] ?? '-';
                                    $priColor = $priorityColors[$r->priority] ?? '#6B7280';
                                    $isOverdue = $r->due_date && $r->due_date->lt(now()) && !in_array($r->status, ['Completed', 'Cancelled', 'Rejected']);
                                @endphp
                                <div wire:click="$dispatch('openCreativeRequest', [{{ $r->id }}])"
                                     style="background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s;"
                                     onmouseover="this.style.borderColor='#C7D2FE'; this.style.boxShadow='0 2px 6px rgba(99,102,241,0.1)';"
                                     onmouseout="this.style.borderColor='#E5E7EB'; this.style.boxShadow='none';">
                                    <div style="font-size: 11px; color: #6B7280; font-weight: 600;">{{ $r->request_id }}</div>
                                    <div style="font-size: 13px; color: #111827; font-weight: 500; margin-top: 4px; line-height: 1.3;">{{ \Illuminate\Support\Str::limit($r->title, 50) }}</div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                                        <span style="font-weight: 700; color: {{ $priColor }}; font-size: 12px;">{{ $priSym }}</span>
                                        @if ($r->due_date)
                                            <span style="font-size: 11px; font-weight: 600; color: {{ $isOverdue ? '#DC2626' : '#9CA3AF' }};">
                                                {{ $r->due_date->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if (empty($colReqs))
                                <div style="font-size: 11px; color: #9CA3AF; text-align: center; padding: 20px 0;">No requests</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($activeTab === 'Timeline View')
            @php
                $monthStart = now()->startOfMonth();
                $monthEnd = now()->endOfMonth();
                $daysInMonth = $monthStart->daysInMonth;
                $today = now()->startOfDay();
                $todayDay = $today->between($monthStart, $monthEnd) ? (int) $today->day : null;
                $monthLabel = $monthStart->format('F Y');
                $dayColWidth = 34;
                $labelColWidth = 260;
            @endphp
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <div style="position: relative; min-width: {{ $labelColWidth + ($dayColWidth * $daysInMonth) }}px;">
                        <!-- Month Header -->
                        <div style="display: grid; grid-template-columns: {{ $labelColWidth }}px repeat({{ $daysInMonth }}, {{ $dayColWidth }}px); align-items: center; padding: 12px 0; border-bottom: 1px solid #E5E7EB; background: #F9FAFB;">
                            <div style="padding-left: 16px; font-weight: 700; font-size: 13px; color: #111827;">{{ $monthLabel }}</div>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php $isToday = $todayDay === $d; @endphp
                                <div style="text-align: center; font-size: 12px; font-weight: {{ $isToday ? '700' : '500' }}; color: {{ $isToday ? '#DC2626' : '#9CA3AF' }};">{{ $d }}</div>
                            @endfor
                        </div>

                        <!-- Rows -->
                        @php
                            $timelineSegments = $this->timelineSegments;
                            $gridCols = $dayColWidth * $daysInMonth;

                            $clipRow = function ($segs) use ($monthStart, $monthEnd, $daysInMonth) {
                                $clipped = [];
                                foreach ($segs as $s) {
                                    if ($s['end']->lt($monthStart) || $s['start']->gt($monthEnd)) continue;
                                    $startDay = $s['start']->lt($monthStart) ? 1 : (int) $s['start']->day;
                                    $endDay = $s['end']->gt($monthEnd) ? $daysInMonth : (int) $s['end']->day;
                                    if ($endDay < $startDay) continue;
                                    $clipped[] = [
                                        'status' => $s['status'],
                                        'startDay' => $startDay,
                                        'endDay' => $endDay,
                                    ];
                                }
                                return $clipped;
                            };
                        @endphp
                        <div style="position: relative; z-index: 2;">
                            @php
                                $rowData = [];
                                foreach ($requests as $r) {
                                    $segs = $clipRow($timelineSegments[$r->id] ?? []);
                                    if (empty($segs)) continue;
                                    $rowData[] = ['request' => $r, 'segments' => $segs];
                                }
                            @endphp
                            @forelse ($rowData as $row)
                                @php
                                    $r = $row['request'];
                                    $segments = $row['segments'];
                                    $firstStart = $segments[0]['startDay'];
                                    $lastEnd = end($segments)['endDay'];
                                @endphp
                                <div wire:click="$dispatch('openCreativeRequest', [{{ $r->id }}])"
                                     style="display: grid; grid-template-columns: {{ $labelColWidth }}px repeat({{ $daysInMonth }}, {{ $dayColWidth }}px); align-items: center; border-bottom: 1px solid #F3F4F6; min-height: 44px; cursor: pointer;"
                                     onmouseover="this.style.background='#F9FAFB';" onmouseout="this.style.background='transparent';">
                                    <div style="padding-left: 16px; display: flex; align-items: center; gap: 10px; min-width: 0;">
                                        <span style="display: inline-block; padding: 2px 8px; background: #EEF2FF; color: #4F46E5; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $r->request_id }}</span>
                                        <span style="font-size: 13px; color: #111827; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ \Illuminate\Support\Str::limit($r->title, 22) }}</span>
                                    </div>
                                    <div style="grid-column: {{ $firstStart + 1 }} / {{ $lastEnd + 2 }}; display: flex; height: 26px; border-radius: 4px; overflow: hidden;">
                                        @foreach ($segments as $seg)
                                            @php
                                                $flex = $seg['endDay'] - $seg['startDay'] + 1;
                                                $barColor = $statusColors[$seg['status']] ?? '#9CA3AF';
                                            @endphp
                                            <div style="flex: {{ $flex }} {{ $flex }} 0; min-width: 0; background: {{ $barColor }}; color: #fff; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; padding: 0 {{ $flex >= 3 ? 6 : 0 }}px;">
                                                {{ $flex >= 3 ? $seg['status'] : '' }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 40px 16px; text-align: center; color: #9CA3AF;">No requests found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Today label row -->
                @if ($todayDay)
                    <div style="display: grid; grid-template-columns: {{ $labelColWidth }}px repeat({{ $daysInMonth }}, {{ $dayColWidth }}px); padding: 6px 0;">
                        <div></div>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <div style="text-align: center; font-size: 11px; font-weight: 700; color: #EA580C;">{{ $todayDay === $d ? 'Today' : '' }}</div>
                        @endfor
                    </div>
                @endif

                <!-- Status Legend -->
                <div style="display: flex; flex-wrap: wrap; gap: 16px 20px; padding: 14px 20px; border-top: 1px solid #E5E7EB; background: #FAFAFB;">
                    @foreach ($statusColors as $stLabel => $stColor)
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; width: 14px; height: 14px; border-radius: 3px; background: {{ $stColor }};"></span>
                            <span style="font-size: 12px; color: #4B5563; font-weight: 500;">{{ $stLabel }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <!-- List View -->
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #F9FAFB;">
                                @foreach (['ID', 'TITLE', 'CATEGORY', 'PRODUCT', 'PRIORITY', 'STATUS', 'REQUESTOR', 'ASSIGNEE', 'DUE DATE'] as $h)
                                    <th style="text-align: left; padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 11px; font-weight: 700; color: #6B7280; letter-spacing: 0.5px;">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $i => $r)
                                @php
                                    $sColor = $statusColors[$r->status] ?? '#6B7280';
                                    $sBg = $statusBg[$r->status] ?? '#F3F4F6';
                                    $pColor = $priorityColors[$r->priority] ?? '#6B7280';
                                    $pBg = $priorityBg[$r->priority] ?? '#F3F4F6';
                                    $rowBg = $i % 2 === 0 ? '#fff' : '#FAFAFB';
                                @endphp
                                <tr wire:click="$dispatch('openCreativeRequest', [{{ $r->id }}])"
                                    style="border-bottom: 1px solid #F3F4F6; background: {{ $rowBg }}; cursor: pointer;"
                                    onmouseover="this.style.background='#EEF2FF';" onmouseout="this.style.background='{{ $rowBg }}';">
                                    <td style="padding: 14px 16px; font-weight: 600; color: #6B7280; font-size: 12px; letter-spacing: 0.3px;">{{ $r->request_id }}</td>
                                    <td style="padding: 14px 16px; color: #111827; font-weight: 500;">{{ \Illuminate\Support\Str::limit($r->title, 60) }}</td>
                                    <td style="padding: 14px 16px; color: #374151;">{{ $r->category ?? '-' }}</td>
                                    <td style="padding: 14px 16px; color: #4F46E5;">{{ $r->product?->name ?? '-' }}</td>
                                    <td style="padding: 14px 16px;">
                                        @if ($r->priority)
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: {{ $pBg }}; color: {{ $pColor }}; font-size: 12px; font-weight: 600;">
                                                {{ $r->priority }}
                                            </span>
                                        @else
                                            <span style="color: #9CA3AF;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @if ($r->status)
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: {{ $sBg }}; color: {{ $sColor }}; font-size: 12px; font-weight: 600;">
                                                {{ $r->status }}
                                            </span>
                                        @else
                                            <span style="color: #9CA3AF;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px; color: #4F46E5; font-weight: 500;">{{ $r->requestor?->name ?? '-' }}</td>
                                    <td style="padding: 14px 16px; color: #4F46E5; font-weight: 500;">{{ $r->assignee?->name ?? '-' }}</td>
                                    <td style="padding: 14px 16px; color: #374151;">{{ $r->due_date?->format('n/j/Y') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="padding: 40px 16px; text-align: center; color: #9CA3AF;">No requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <livewire:creative-request-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>
