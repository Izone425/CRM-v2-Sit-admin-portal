<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $suggestions = $this->suggestions;
        $priColor = \App\Filament\Pages\QcTicketing\QcTicketingSuggestionList::PRIORITY_COLOR;
        $priBg = \App\Filament\Pages\QcTicketing\QcTicketingSuggestionList::PRIORITY_BG;
        $stColor = \App\Filament\Pages\QcTicketing\QcTicketingSuggestionList::STATUS_COLOR;
        $stBg = \App\Filament\Pages\QcTicketing\QcTicketingSuggestionList::STATUS_BG;
        $categories = \App\Filament\Pages\QcTicketing\QcTicketingSuggestionList::CATEGORIES;
        $cardStyle = 'background: white; padding: 20px; border-radius: 4px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        $fg = 'display:flex;flex-direction:column;gap:4px;width:150px;';
        $fl = 'font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.4px;';
        $sel = 'appearance:none;-webkit-appearance:none;width:100%;padding:8px 32px 8px 12px;background:#fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 10px center;background-size:12px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;cursor:pointer;';
    @endphp

    <!-- Summary Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #3498db;">{{ $summary['total'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Total Suggestions</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #9b59b6;">{{ $summary['new'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">New</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #27ae60;">{{ $summary['approved'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Approved</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #f39c12;">{{ $summary['in_progress'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">In Progress</div>
        </div>
        <div style="{{ $cardStyle }}">
            <div style="font-size: 1.5rem; font-weight: 700; color: #1abc9c;">{{ $summary['live'] }}</div>
            <div style="font-size: 0.7rem; color: #7f8c8d;">Live</div>
        </div>
    </div>

    <!-- Tabs + Rejected link -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #E5E7EB;">
        <div style="display: flex; gap: 8px;">
            @foreach (['List View', 'Tree View', 'Map View'] as $tab)
                @php $isActive = $activeTab === $tab && !$showRejected; @endphp
                <button type="button" wire:click="setTab('{{ $tab }}')"
                    style="padding: 12px 24px; background: transparent; border: 0; margin-bottom: -2px; font-size: 14px; font-weight: {{ $isActive ? '700' : '600' }}; color: {{ $isActive ? '#6366F1' : '#4B5563' }}; cursor: pointer; border-bottom: 2px solid {{ $isActive ? '#6366F1' : 'transparent' }};">
                    {{ $tab }}@if ($tab === 'List View') ({{ $summary['total'] }}) @endif
                </button>
            @endforeach
        </div>
        <button type="button" wire:click="toggleRejected"
            style="padding: 12px 24px; background: transparent; border: 0; font-size: 14px; font-weight: {{ $showRejected ? '700' : '500' }}; color: {{ $showRejected ? '#DC2626' : '#9CA3AF' }}; cursor: pointer;">
            Rejected
        </button>
    </div>

    <!-- Card wrapper -->
    <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 24px; margin-top: 0;">
        <!-- Toolbar -->
        <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end;">
            <div style="display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 10px 14px; flex: 1; min-width: 250px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search suggestions..."
                    style="border: 0; outline: none; flex: 1; font-size: 14px; background: transparent;">
            </div>
            <div style="display: flex; align-items: flex-end; gap: 8px;">
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Status</label>
                    <select wire:model.live="filterStatus" style="{{ $sel }}">
                        @foreach ($this->statusOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Priority</label>
                    <select wire:model.live="filterPriority" style="{{ $sel }}">
                        @foreach ($this->priorityOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Product</label>
                    <select wire:model.live="filterProduct" style="{{ $sel }}">
                        @foreach ($this->productOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                    </select>
                </div>
                <div style="{{ $fg }}">
                    <label style="{{ $fl }}">Solution</label>
                    <select wire:model.live="filterSolution" style="{{ $sel }}">
                        @foreach ($this->solutionOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                    </select>
                </div>
                <button type="button" wire:click="clearFilters"
                    style="align-self:flex-end; padding: 8px 16px; background: transparent; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; cursor: pointer; color: #4B5563;">
                    Clear Filters
                </button>
            </div>
        </div>

        @if ($activeTab === 'List View' || $showRejected)
            <!-- List View -->
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #F9FAFB;">
                                @foreach (['ID', 'MODULE', 'CATEGORY', 'TITLE', 'DESCRIPTION', 'PRIORITY', 'STATUS'] as $h)
                                    <th style="text-align: left; padding: 12px 16px; border-bottom: 1px solid #E5E7EB; font-size: 11px; font-weight: 700; color: #6B7280; letter-spacing: 0.5px;">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suggestions as $i => $s)
                                @php
                                    $pc = $priColor[$s->priority] ?? '#6B7280';
                                    $pb = $priBg[$s->priority] ?? '#F3F4F6';
                                    $sc = $stColor[$s->status] ?? '#6B7280';
                                    $sb = $stBg[$s->status] ?? '#F3F4F6';
                                    $rowBg = $i % 2 === 0 ? '#fff' : '#FAFAFB';
                                @endphp
                                <tr wire:click="$dispatch('openSuggestion', [{{ $s->id }}])"
                                    style="border-bottom: 1px solid #F3F4F6; background: {{ $rowBg }}; cursor: pointer;"
                                    onmouseover="this.style.background='#EEF2FF';" onmouseout="this.style.background='{{ $rowBg }}';">
                                    <td style="padding: 14px 16px; font-weight: 600; color: #6B7280; font-size: 12px;">{{ $s->suggestion_id }}</td>
                                    <td style="padding: 14px 16px; color: #374151;">{{ $s->module?->name ?? '-' }}</td>
                                    <td style="padding: 14px 16px; color: #374151;">{{ $s->category ?? '-' }}</td>
                                    <td style="padding: 14px 16px; color: #4F46E5; font-weight: 500;">{{ \Illuminate\Support\Str::limit($s->title, 40) }}</td>
                                    <td style="padding: 14px 16px; color: #6B7280;">{{ \Illuminate\Support\Str::limit(strip_tags((string) $s->description), 60) }}</td>
                                    <td style="padding: 14px 16px;">
                                        @if ($s->priority)
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: {{ $pb }}; color: {{ $pc }}; font-size: 12px; font-weight: 600;">{{ $s->priority }}</span>
                                        @else
                                            <span style="color: #9CA3AF;">-</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        @if ($s->status)
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: {{ $sb }}; color: {{ $sc }}; font-size: 12px; font-weight: 600;">{{ $s->status }}</span>
                                        @else
                                            <span style="color: #9CA3AF;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="padding: 40px 16px; text-align: center; color: #9CA3AF;">No suggestions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($activeTab === 'Tree View')
            @php
                $categoryPalette = [
                    'New Feature'    => ['bg' => '#EEF2FF', 'accent' => '#6366F1', 'icon' => '✨'],
                    'Enhancement'    => ['bg' => '#D1FAE5', 'accent' => '#059669', 'icon' => '⬆'],
                    'Usability/UX'   => ['bg' => '#FCE7F3', 'accent' => '#DB2777', 'icon' => '✨'],
                    'Performance'    => ['bg' => '#FEF3C7', 'accent' => '#D97706', 'icon' => '⚡'],
                    'Integration'    => ['bg' => '#E0E7FF', 'accent' => '#4F46E5', 'icon' => '🔗'],
                    'Other'          => ['bg' => '#F3E8FF', 'accent' => '#9333EA', 'icon' => '•'],
                ];
            @endphp
            <!-- Tree View -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($this->byCategory as $category => $items)
                    @php
                        $isOpen = in_array($category, $expandedCategories, true);
                        $pal = $categoryPalette[$category] ?? ['bg' => '#F3F4F6', 'accent' => '#6B7280', 'icon' => '•'];
                    @endphp
                    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden; border-left: 4px solid {{ $pal['accent'] }};">
                        <button type="button" wire:click="toggleCategory('{{ $category }}')"
                            style="width: 100%; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; background: {{ $pal['bg'] }}; border: 0; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $pal['accent'] }}" stroke-width="2" style="transform: rotate({{ $isOpen ? '90deg' : '0deg' }}); transition: transform 0.15s;"><polyline points="9 6 15 12 9 18"/></svg>
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #fff; color: {{ $pal['accent'] }}; border-radius: 6px; font-size: 14px; font-weight: 700; border: 1px solid rgba(0,0,0,0.05);">{{ $pal['icon'] }}</span>
                                <div style="text-align: left;">
                                    <div style="font-size: 14px; font-weight: 700; color: #111827;">{{ $category }}</div>
                                    <div style="font-size: 12px; color: #6B7280; margin-top: 2px;">{{ $categories[$category] ?? '' }}</div>
                                </div>
                            </div>
                            <span style="background: {{ $pal['accent'] }}; color: #fff; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700;">{{ count($items) }}</span>
                        </button>
                        @if ($isOpen && count($items) > 0)
                            <div style="padding: 12px 20px; display: flex; flex-direction: column; gap: 8px;">
                                @foreach ($items as $s)
                                    @php
                                        $pc = $priColor[$s->priority] ?? '#6B7280';
                                        $pb = $priBg[$s->priority] ?? '#F3F4F6';
                                        $sc = $stColor[$s->status] ?? '#6B7280';
                                        $sb = $stBg[$s->status] ?? '#F3F4F6';
                                    @endphp
                                    <div wire:click="$dispatch('openSuggestion', [{{ $s->id }}])"
                                        style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid #F3F4F6; border-radius: 8px; cursor: pointer;"
                                        onmouseover="this.style.background='#EEF2FF';" onmouseout="this.style.background='transparent';">
                                        <span style="font-size: 11px; font-weight: 600; color: #6B7280;">{{ $s->suggestion_id }}</span>
                                        <span style="flex: 1; font-size: 13px; color: #111827; font-weight: 500;">{{ \Illuminate\Support\Str::limit($s->title, 60) }}</span>
                                        @if ($s->priority)
                                            <span style="padding: 2px 10px; border-radius: 9999px; background: {{ $pb }}; color: {{ $pc }}; font-size: 11px; font-weight: 600;">{{ $s->priority }}</span>
                                        @endif
                                        @if ($s->status)
                                            <span style="padding: 2px 10px; border-radius: 9999px; background: {{ $sb }}; color: {{ $sc }}; font-size: 11px; font-weight: 600;">{{ $s->status }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif ($activeTab === 'Map View')
            <!-- Map View -->
            @php $matrix = $this->mapMatrix; $cols = $matrix['cols']; $rows = $matrix['rows']; @endphp
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #111827; min-width: 180px; background: #fff; border-right: 1px solid #E5E7EB;">Module</th>
                                @foreach ($cols as $col)
                                    @php $cColor = $stColor[$col] ?? '#6B7280'; @endphp
                                    <th style="padding: 14px 16px; text-align: left; background: {{ $cColor }}; color: #fff; font-size: 13px; font-weight: 700;">
                                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                                            <span style="width: 8px; height: 8px; background: #fff; border-radius: 50%; display: inline-block; opacity: 0.7;"></span>
                                            {{ $col }}
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $modulePalette = [
                                    ['bg' => '#EEF2FF', 'accent' => '#6366F1'],
                                    ['bg' => '#FEF3C7', 'accent' => '#D97706'],
                                    ['bg' => '#D1FAE5', 'accent' => '#059669'],
                                    ['bg' => '#FCE7F3', 'accent' => '#DB2777'],
                                    ['bg' => '#DBEAFE', 'accent' => '#2563EB'],
                                    ['bg' => '#FEE2E2', 'accent' => '#DC2626'],
                                    ['bg' => '#E0E7FF', 'accent' => '#4F46E5'],
                                    ['bg' => '#FFEDD5', 'accent' => '#EA580C'],
                                    ['bg' => '#F3E8FF', 'accent' => '#9333EA'],
                                    ['bg' => '#CFFAFE', 'accent' => '#0891B2'],
                                ];
                                $moduleIdx = 0;
                            @endphp
                            @forelse ($rows as $module => $buckets)
                                @php
                                    $moduleTotal = array_sum(array_map('count', $buckets));
                                    $pal = $modulePalette[$moduleIdx % count($modulePalette)];
                                    $moduleIdx++;
                                @endphp
                                <tr style="border-top: 1px solid #F3F4F6;">
                                    <td style="padding: 14px 16px; font-size: 13px; color: #111827; font-weight: 500; vertical-align: top; border-right: 1px solid #F3F4F6; background: {{ $pal['bg'] }}; border-left: 4px solid {{ $pal['accent'] }};">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span>{{ $module }}</span>
                                            <span style="background: {{ $pal['accent'] }}; color: #fff; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;">{{ $moduleTotal }}</span>
                                        </div>
                                    </td>
                                    @foreach ($cols as $col)
                                        <td style="padding: 10px 12px; vertical-align: top;">
                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                @foreach ($buckets[$col] ?? [] as $s)
                                                    @php $pc = $priColor[$s->priority] ?? '#6B7280'; @endphp
                                                    <button type="button" wire:click="$dispatch('openSuggestion', [{{ $s->id }}])"
                                                        style="display: inline-flex; align-items: center; padding: 4px 12px; border: 1px solid {{ $pc }}; color: {{ $pc }}; background: #fff; border-radius: 9999px; font-size: 12px; font-weight: 500; cursor: pointer; text-align: left; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                        onmouseover="this.style.background='{{ $priBg[$s->priority] ?? '#F3F4F6' }}';" onmouseout="this.style.background='#fff';">
                                                        {{ \Illuminate\Support\Str::limit($s->title, 28) }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($cols) + 1 }}" style="padding: 40px 16px; text-align: center; color: #9CA3AF;">No suggestions to map.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <livewire:suggestion-modal />
    <livewire:task-modal />
    <livewire:ticket-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>
