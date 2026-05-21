@php
    use Carbon\Carbon;
@endphp

<x-filament::page>
    <style>
        /* Month row styling */
        .month-cell {
            background-color: #f3f4f6;
            font-weight: 600;
            padding: 0.75rem 0.5rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            width: 16.66%; /* REQUEST 1: Equal width for all headers (1/6 = 16.66%) */
            text-align: center; /* Center text for better appearance */
        }

        /* Current month styling */
        .current-month {
            background-color: #d1fae5; /* Light green color */
            border-left: 3px solid #10b981; /* Medium green border */
        }

        /* Week header styling */
        .week-header {
            background-color: #dbeafe;
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            width: 16.66%; /* REQUEST 1: Equal width for all headers (1/6 = 16.66%) */
        }

        /* Date cell styling */
        .date-cell {
            padding: 0.25rem 0.5rem;
            border-right: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            vertical-align: top;
        }

        /* Date number styling */
        .date-number {
            font-size: 0.75rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        /* User assignment boxes */
        .assignment-box {
            padding: 0.5rem;
            border-radius: 0.25rem;
        }

        /* Unassigned styling - now dark grey (REQUEST 2) */
        .unassigned {
            background-color: #4b5563; /* Dark grey for unassigned */
            color: #f9fafb; /* Light text for contrast */
            font-style: italic;
        }

        /* Assigned styling - now gray */
        .assigned {
            background-color: #fecaca; /* Light red for assigned */
            color: #374151;
        }

        /* Staff colors - keeping these for completed tasks or other statuses */
        .staff-color-0 { background-color: #bfdbfe; } /* Light blue */
        .staff-color-1 { background-color: #fef08a; } /* Light yellow */
        .staff-color-2 { background-color: #fecaca; } /* Light red */
        .staff-color-3 { background-color: #e9d5ff; } /* Light purple */
        .staff-color-4 { background-color: #fbcfe8; } /* Light pink */
        .staff-color-5 { background-color: #d1d5db; } /* Light gray */
        .staff-color-6 { background-color: #bbf7d0; } /* Light green for completed */

        /* Legend styling */
        .legend-container {
            margin-top: 1.5rem;
            padding: 0.75rem 1rem;
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .legend-title {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
        }

        .legend-color {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border-radius: 0.25rem;
        }

        .legend-text {
            font-size: 0.875rem;
        }

        .assigned-cell {
            background-color: #fecaca !important; /* Light red background */
        }

        /* Completed cell styling */
        .completed-cell {
            background-color: #bbf7d0 !important; /* Light green background */
        }

        /* Make sure completed takes precedence over assigned */
        .completed-cell.assigned-cell {
            background-color: #bbf7d0 !important;
        }

        /* Searchable combobox */
        .sc-combo-btn {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #111827;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            cursor: pointer;
            text-align: left;
        }
        .sc-combo-btn .placeholder { color: #9ca3af; }
        .sc-combo-pop {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.10);
            z-index: 50;
            overflow: hidden;
        }
        .sc-combo-search {
            width: 100%;
            padding: 8px 10px;
            border: 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            background: #f9fafb;
            color: #111827;
            outline: none;
        }
        .sc-combo-list { max-height: 220px; overflow-y: auto; }
        .sc-combo-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 8px 12px;
            border: 0;
            background: #fff;
            font-size: 13px;
            color: #111827;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }
        .sc-combo-item:hover { background: #f3f4f6; }
        .sc-combo-item.clear { color: #9ca3af; }
        .sc-combo-item.selected { background: #eef2ff; color: #4f46e5; font-weight: 600; }
    </style>

    @if ($viewMode === 'table')
        {{-- Staff Summary Table View --}}
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <table class="w-full border border-gray-300">
                <thead>
                    <tr>
                        <th class="month-cell" style="width: 1%; white-space: nowrap;">Name</th>
                        <th class="week-header">Assigned Weekends ({{ $selectedYear }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffSummary as $staff)
                        <tr>
                            <td class="px-4 py-3 font-medium border-r border-b border-gray-300 bg-gray-50" style="white-space: nowrap;">
                                {{ $staff['name'] }}
                            </td>
                            <td class="px-4 py-3 border-b border-gray-300">
                                {{ implode(' / ', $staff['weekends']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-gray-500">
                                No assignments found for {{ $selectedYear }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- Calendar View --}}
        <div class="overflow-hidden bg-white rounded-lg shadow">
            <table class="w-full border border-gray-300">
                <thead>
                    <tr>
                        <th class="month-cell">
                            {{ $selectedYear }}
                        </th>
                        @php
                            $maxWeeks = 0;
                            foreach ($months as $month) {
                                $weekCount = count($month['weeks']);
                                $maxWeeks = max($maxWeeks, $weekCount);
                            }
                        @endphp
                        @for ($w = 1; $w <= $maxWeeks; $w++)
                            <th class="week-header">
                                Week {{ $w }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $monthName => $month)
                        <tr>
                            <td class="month-cell {{ Carbon::now()->format('F') === $monthName && Carbon::now()->year == $selectedYear ? 'current-month' : '' }}">
                                {{ $monthName }}
                            </td>

                            @for ($w = 1; $w <= $maxWeeks; $w++)
                                @php
                                    $weekKey = "W{$w}";
                                    $week = $month['weeks'][$weekKey] ?? null;
                                @endphp

                                @php
                                    $isFiltered = !empty($selectedUserIds);
                                    $matchesFilter = $week && $week['user_id'] && in_array($week['user_id'], $selectedUserIds);
                                @endphp
                                <td class="date-cell
                                    {{ Carbon::now()->format('F') === $monthName && Carbon::now()->year == $selectedYear ? 'current-month' : '' }}
                                    {{ ($editMode && $week && $week['user_id']) ? 'assigned-cell' : '' }}
                                    {{ ($week && $week['status'] === 'completed') ? 'completed-cell' : '' }}"
                                    style="{{ $isFiltered && $week && !$matchesFilter ? 'opacity: 0.3;' : '' }}">
                                    @if ($week)
                                        <div class="flex flex-col h-full">
                                            <div class="date-number">{{ $week['dates'] }}</div>

                                            @if ($editMode)
                                                <div x-data="{ open: false, search: '' }" @click.away="open = false; search = ''" style="position: relative;">
                                                    <button type="button" class="sc-combo-btn" @click="open = !open">
                                                        @if ($week['user_id'])
                                                            <span>{{ $week['user_name'] }}</span>
                                                        @else
                                                            <span class="placeholder">-- Select Staff --</span>
                                                        @endif
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                                    </button>
                                                    <div x-show="open" x-cloak x-transition.opacity class="sc-combo-pop">
                                                        <input type="text" x-model="search" @click.stop placeholder="Search staff..." class="sc-combo-search">
                                                        <div class="sc-combo-list">
                                                            <button type="button" class="sc-combo-item clear"
                                                                @click="$wire.call('assignStaff', '{{ $week['date_start'] }}', ''); open = false; search = ''">
                                                                -- Unassign --
                                                            </button>
                                                            @foreach ($users as $user)
                                                                @php $haystack = strtolower(str_replace(["'", '"'], '', (string) $user->name)); @endphp
                                                                <button type="button"
                                                                    x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                                                    class="sc-combo-item {{ $week['user_id'] == $user->id ? 'selected' : '' }}"
                                                                    @click="$wire.call('assignStaff', '{{ $week['date_start'] }}', {{ $user->id }}); open = false; search = ''">
                                                                    {{ $user->name }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="assignment-box {{ $week['user_id'] ? 'assigned' : 'unassigned' }} {{ $week['status'] === 'completed' ? 'staff-color-6' : '' }}">
                                                    <div class="{{ $week['user_id'] ? 'font-medium' : '' }}">
                                                        {{ $week['user_name'] }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>
