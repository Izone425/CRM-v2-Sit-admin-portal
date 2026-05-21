<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/qc-ticketing/release-list.css') }}">

    @php
        $tree = $this->versionTree;
        $release = $this->selectedRelease;
    @endphp

    <div class="releaseListPage">
        <div class="titleWrapper" style="margin-bottom: 24px;">
            <h1 style="font-size: 26px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Releases</h1>
            <p class="pageSubtitle">Manage and track product releases, versions, and deployment schedules across all platforms</p>
        </div>

        <!-- Product Tabs -->
        <div class="productTabsContainer">
            <div class="productTabs">
                @foreach ($this->products as $product)
                    <button type="button" wire:click="setProduct({{ $product->id }})"
                            class="productTab {{ $activeProductId === $product->id ? 'activeProductTab' : '' }}">
                        {{ $product->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Split View -->
        <div class="splitViewContainer">
            <!-- Left Panel -->
            <div class="splitLeftPanel">
                <div class="splitPanelHeader">
                    <select wire:change="setSolutionModule($event.target.value)"
                            style="appearance: none; -webkit-appearance: none; width: 100%; padding: 10px 36px 10px 14px; background: #fff url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;14&quot; height=&quot;14&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;%239CA3AF&quot; stroke-width=&quot;2&quot;><polyline points=&quot;6 9 12 15 18 9&quot;/></svg>') no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; cursor: pointer;">
                        <option value="all" @selected(!$selectedSolutionModule)>All Solutions/Modules</option>
                        <optgroup label="Solutions">
                            @foreach ($this->solutions as $s)
                                <option value="sol-{{ $s->id }}" @selected($selectedSolutionModule === 'sol-'.$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Modules">
                            @foreach ($this->modules as $m)
                                <option value="mod-{{ $m->id }}" @selected($selectedSolutionModule === 'mod-'.$m->id)>{{ $m->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="platformTabs">
                    @foreach (['all' => 'All', 'Web' => 'Web', 'App' => 'App'] as $key => $label)
                        <button type="button" wire:click="setPlatform('{{ $key }}')"
                                class="platformTab {{ $selectedPlatform === $key ? 'activePlatformTab' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="versionTreeWrapper" style="flex: 1; overflow-y: auto;">
                    @if (count($tree) === 0)
                        <div style="padding: 20px; text-align: center; color: #666;">No releases found</div>
                    @else
                        @foreach ($tree as $main => $group)
                            <div x-data="{ open: true }" style="margin-bottom: 8px;">
                                <button type="button" @click="open = !open"
                                        style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; color: #4F46E5;">
                                    <span style="display: flex; align-items: center; gap: 8px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open ? '' : 'transform: rotate(-90deg); transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
                                        {{ $main }}
                                    </span>
                                    <span style="font-size: 12px; color: #9CA3AF; background: #E5E7EB; border-radius: 9999px; padding: 2px 8px;">{{ $group['count'] }}</span>
                                </button>
                                <ul x-show="open" x-cloak style="list-style: none; padding: 6px 0 6px 24px; margin: 0;">
                                    @foreach ($group['children'] as $child)
                                        <li style="margin: 4px 0;">
                                            <button type="button" wire:click="setRelease({{ $child['id'] }})"
                                                    style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 8px 12px; background: {{ $selectedReleaseId === $child['id'] ? '#EEF2FF' : 'white' }}; border: 1px solid {{ $selectedReleaseId === $child['id'] ? '#C7D2FE' : 'transparent' }}; border-radius: 6px; cursor: pointer; font-size: 13px; color: {{ $selectedReleaseId === $child['id'] ? '#4F46E5' : '#374151' }}; font-weight: {{ $selectedReleaseId === $child['id'] ? '600' : '500' }};">
                                                <span style="display: flex; align-items: center; gap: 6px;">
                                                    <span style="width: 6px; height: 6px; background: #6366F1; border-radius: 50%;"></span>
                                                    {{ $child['version'] }}
                                                    @if (!empty($child['module']))
                                                        <span style="font-size: 10px; color: #9CA3AF;">({{ $child['module'] }})</span>
                                                    @endif
                                                </span>
                                                @php
                                                    $dotColor = match ($child['status']) {
                                                        'Live', 'Closed', 'Completed' => '#22c55e',
                                                        'In Development' => '#f97316',
                                                        'Planned' => '#3b82f6',
                                                        'Rejected' => '#ef4444',
                                                        default => '#9CA3AF',
                                                    };
                                                @endphp
                                                <span title="{{ $child['status'] }}" style="width: 10px; height: 10px; background: {{ $dotColor }}; border-radius: 50%; display: inline-block; flex-shrink: 0;"></span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div style="padding: 14px 16px; border-top: 1px solid #E5E7EB; background: #fafafa; font-size: 12px; color: #6B7280;">
                    {{ $this->mainVersionStats['mainCount'] }} main version{{ $this->mainVersionStats['mainCount'] === 1 ? '' : 's' }}<br>
                    {{ $this->mainVersionStats['totalCount'] }} total release{{ $this->mainVersionStats['totalCount'] === 1 ? '' : 's' }}
                </div>
            </div>

            <!-- Right Panel -->
            <div class="splitRightPanel">
                @if ($release)
                    @php
                        $statusColor = match ($release->status) {
                            'Live' => 'background: #DCFCE7; color: #166534;',
                            'Closed' => 'background: #DCFCE7; color: #166534;',
                            'In Development' => 'background: #FEF3C7; color: #92400E;',
                            'Planned' => 'background: #DBEAFE; color: #1E40AF;',
                            default => 'background: #F3F4F6; color: #4B5563;',
                        };
                    @endphp
                    <div style="padding: 24px;">
                        @php
                            $platforms = is_array($release->platform)
                                ? $release->platform
                                : (json_decode($release->platform, true) ?: array_filter([$release->platform]));
                            $platformStyles = [
                                'App' => 'background: #DBEAFE; color: #1E40AF;',
                                'Web' => 'background: #DCFCE7; color: #166534;',
                            ];
                        @endphp
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 6px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1;">
                                <h3 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">
                                    {{ $release->product?->name }} - {{ $release->version }}
                                </h3>
                                @foreach ($platforms as $platform)
                                    <span style="{{ $platformStyles[$platform] ?? 'background: #F3F4F6; color: #4B5563;' }} font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 6px;">{{ $platform }}</span>
                                @endforeach
                                <span style="{{ $statusColor }} font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 6px;">{{ $release->status }}</span>
                            </div>
                            <div x-data="{ open: false }" @click.away="open = false" style="position: relative; flex-shrink: 0;">
                                <button type="button" @click="open = !open"
                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #4F46E5; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Actions
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-transition style="position: absolute; right: 0; top: calc(100% + 6px); min-width: 220px; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); z-index: 50; overflow: hidden;" x-cloak>
                                    <button type="button" wire:click="openCreateTaskForRelease" @click="open = false"
                                            style="display: flex; width: 100%; align-items: center; gap: 10px; padding: 10px 14px; background: #fff; border: none; font-size: 13px; color: #111827; cursor: pointer; text-align: left;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Create New Task
                                    </button>
                                    <button type="button" wire:click="openBrowseItemsModal" @click="open = false"
                                            style="display: flex; width: 100%; align-items: center; gap: 10px; padding: 10px 14px; background: #fff; border: none; border-top: 1px solid #F3F4F6; font-size: 13px; color: #111827; cursor: pointer; text-align: left;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                        Browse Existing Tasks & Bugs
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 24px; margin-bottom: 24px; font-size: 13px; color: #6B7280;">
                            @if ($release->planned_live_date)
                                <span><strong style="color: #374151;">Planned Live:</strong> {{ $release->planned_live_date->format('d/m/Y') }}</span>
                            @endif
                            @if ($release->actual_live_date)
                                <span><strong style="color: #374151;">Actual Live:</strong> {{ $release->actual_live_date->format('d/m/Y') }}</span>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="position: relative; flex: 1;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="Search items..."
                                       style="width: 100%; padding: 10px 14px 10px 38px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px;">
                            </div>
                            <select wire:model.live="itemTypeFilter"
                                    style="appearance: none; -webkit-appearance: none; padding: 10px 36px 10px 14px; background: #fff url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;14&quot; height=&quot;14&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;%239CA3AF&quot; stroke-width=&quot;2&quot;><polyline points=&quot;6 9 12 15 18 9&quot;/></svg>') no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; cursor: pointer;">
                                <option value="All">All Types</option>
                                <option value="Tasks">Tasks</option>
                                <option value="Bugs">Bugs</option>
                            </select>
                            <span style="font-size: 13px; color: #6B7280; white-space: nowrap;">{{ $this->releaseItems->count() }} items</span>
                        </div>

                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <thead>
                                    <tr style="border-bottom: 1px solid #E5E7EB;">
                                        @foreach (['ID', 'TITLE', 'MODULE', 'SIZE', 'DEVELOPER', 'QC', 'STATUS', ''] as $h)
                                            <th style="text-align: left; padding: 12px 10px; font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">{{ $h }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($this->releaseItems as $item)
                                        <tr style="border-bottom: 1px solid #F3F4F6;">
                                            <td style="padding: 14px 10px;">
                                                <button type="button"
                                                        wire:click="$dispatch('open{{ $item['type'] === 'Bug' ? 'Bug' : 'Task' }}Modal', [{{ $item['id'] }}])"
                                                        style="background: transparent; border: 0; color: #4F46E5; font-weight: 700; cursor: pointer; padding: 0; font-size: 13px;">
                                                    {{ $item['id_label'] }}
                                                </button>
                                            </td>
                                            <td style="padding: 14px 10px; color: #111827;">{{ \Illuminate\Support\Str::limit($item['title'], 50) }}</td>
                                            <td style="padding: 14px 10px; color: #B91C1C;">{{ $item['module'] ?? '-' }}</td>
                                            <td style="padding: 14px 10px;">
                                                @if ($item['size'])
                                                    <span style="display: inline-block; padding: 3px 10px; background: #FEF3C7; color: #92400E; font-size: 11px; font-weight: 600; border-radius: 6px; text-transform: lowercase;">{{ $item['size'] }}</span>
                                                @else
                                                    <span style="color: #9CA3AF;">-</span>
                                                @endif
                                            </td>
                                            <td style="padding: 14px 10px; color: #374151;">{{ $item['developer'] ?? '-' }}</td>
                                            <td style="padding: 14px 10px; color: #374151;">{{ $item['qc'] ?? '-' }}</td>
                                            <td style="padding: 14px 10px;">
                                                @php
                                                    $itemStatusColor = match ($item['status']) {
                                                        'Live', 'Closed', 'Completed' => 'background: #EEF2FF; color: #4F46E5;',
                                                        'New', 'Reopen' => 'background: #DBEAFE; color: #1E40AF;',
                                                        'Rejected' => 'background: #FEE2E2; color: #B91C1C;',
                                                        default => 'background: #FEF3C7; color: #92400E;',
                                                    };
                                                @endphp
                                                <span style="{{ $itemStatusColor }} font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 9999px;">{{ $item['status'] }}</span>
                                            </td>
                                            <td style="padding: 14px 10px; text-align: right;">
                                                <button type="button"
                                                        wire:click="removeReleaseItem('{{ $item['type'] }}', {{ $item['id'] }})"
                                                        wire:confirm="Remove {{ $item['id_label'] }} from this release?"
                                                        title="Remove from release"
                                                        style="background: transparent; border: 0; cursor: pointer; color: #9CA3AF; padding: 4px; transition: color 0.15s;"
                                                        onmouseover="this.style.color='#DC2626'"
                                                        onmouseout="this.style.color='#9CA3AF'">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" style="text-align: center; padding: 40px 0; color: #9CA3AF; font-size: 14px;">No items</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 60px 24px; color: #9CA3AF;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px;"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
                        <p style="font-size: 14px;">Select a release to see its items</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <livewire:task-modal />
    <livewire:bug-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />

    {{-- Add Items to Release Drawer --}}
    @if ($showBrowseItemsModal && $this->selectedRelease)
        @php
            $tasks = $this->unassignedTasks;
            $bugs = $this->unassignedBugs;
            $selectedCount = count($browseForm['task_ids'] ?? []) + count($browseForm['bug_ids'] ?? []);
        @endphp
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }
        </style>
        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             wire:click.self="closeBrowseItemsModal"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999998; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 560px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Add Items to Release</h2>
                    <button type="button" wire:click="closeBrowseItemsModal"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <form wire:submit.prevent="submitBrowseItems" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 18px; flex: 1; overflow-y: auto;">

                        {{-- Tasks multi-select --}}
                        <div x-data="{ open: false }" @click.away="open = false">
                            <label style="display: block; font-size: 14px; font-weight: 700; color: #1E1B4B; margin-bottom: 8px;">Tasks</label>
                            <button type="button"
                                    @click="{{ $tasks->isEmpty() ? 'null' : 'open = !open' }}"
                                    {{ $tasks->isEmpty() ? 'disabled' : '' }}
                                    style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: {{ $tasks->isEmpty() ? '#9CA3AF' : '#111827' }}; cursor: {{ $tasks->isEmpty() ? 'not-allowed' : 'pointer' }};">
                                <span style="text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    @if ($tasks->isEmpty())
                                        No tasks available
                                    @elseif (empty($browseForm['task_ids']))
                                        Select tasks...
                                    @else
                                        {{ $tasks->whereIn('id', $browseForm['task_ids'])->pluck('task_id')->implode(', ') }}
                                    @endif
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            @if ($tasks->isNotEmpty())
                                <div x-show="open" x-transition x-cloak
                                     style="margin-top: 4px; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
                                    @foreach ($tasks as $t)
                                        <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #F3F4F6; font-size: 13px; color: #111827; cursor: pointer;">
                                            <input type="checkbox" value="{{ $t->id }}" wire:model.live="browseForm.task_ids">
                                            <span style="font-weight: 600; color: #4F46E5;">{{ $t->task_id }}</span>
                                            <span style="color: #6B7280;">— {{ $t->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Bugs multi-select --}}
                        <div x-data="{ open: false }" @click.away="open = false">
                            <label style="display: block; font-size: 14px; font-weight: 700; color: #1E1B4B; margin-bottom: 8px;">Bugs</label>
                            <button type="button"
                                    @click="{{ $bugs->isEmpty() ? 'null' : 'open = !open' }}"
                                    {{ $bugs->isEmpty() ? 'disabled' : '' }}
                                    style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: {{ $bugs->isEmpty() ? '#9CA3AF' : '#111827' }}; cursor: {{ $bugs->isEmpty() ? 'not-allowed' : 'pointer' }};">
                                <span style="text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    @if ($bugs->isEmpty())
                                        No bugs available
                                    @elseif (empty($browseForm['bug_ids']))
                                        Select bugs...
                                    @else
                                        {{ $bugs->whereIn('id', $browseForm['bug_ids'])->pluck('bug_id')->implode(', ') }}
                                    @endif
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            @if ($bugs->isNotEmpty())
                                <div x-show="open" x-transition x-cloak
                                     style="margin-top: 4px; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
                                    @foreach ($bugs as $b)
                                        <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid #F3F4F6; font-size: 13px; color: #111827; cursor: pointer;">
                                            <input type="checkbox" value="{{ $b->id }}" wire:model.live="browseForm.bug_ids">
                                            <span style="font-weight: 600; color: #DC2626;">{{ $b->bug_id }}</span>
                                            <span style="color: #6B7280;">— {{ $b->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($selectedCount === 0)
                            <div style="background: #EEF2FF; border-radius: 10px; padding: 14px; text-align: center; font-size: 13px; color: #818CF8;">
                                Select at least one task or bug to add to this release.
                            </div>
                        @endif
                    </div>
                    <div style="height: 1px; background: #F3F4F6; margin: 0 24px;"></div>
                    <div style="padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" wire:click="closeBrowseItemsModal"
                                style="padding: 10px 20px; background: #fff; color: #111827; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button type="submit" @disabled($selectedCount === 0)
                                style="padding: 10px 24px; background: {{ $selectedCount === 0 ? '#EEF2FF' : '#4F46E5' }}; color: {{ $selectedCount === 0 ? '#A5B4FC' : '#fff' }}; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: {{ $selectedCount === 0 ? 'not-allowed' : 'pointer' }};">Add</button>
                    </div>
                </form>
            </aside>
        </div>
    @endif
</x-filament-panels::page>
