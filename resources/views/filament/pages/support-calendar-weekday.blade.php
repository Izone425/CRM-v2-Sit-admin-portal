<x-filament::page>
    <style>
        .otw-range {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #3730a3;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .otw-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .otw-day {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            min-height: 170px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: box-shadow 0.15s;
        }
        .otw-day:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
        .otw-day.today { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }

        .otw-day-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
        }
        .otw-day-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #6b7280;
            text-transform: uppercase;
        }
        .otw-day-num {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .otw-assign {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            font-size: 13px;
            color: #374151;
        }
        .otw-assign.unassigned {
            background: #4b5563;
            color: #f9fafb;
            font-style: italic;
        }
        .otw-assign.assigned {
            background: #bbf7d0;
            color: #14532d;
        }
        .otw-assign.completed {
            background: #bbf7d0;
            color: #14532d;
        }
        .otw-status {
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }

        .otw-select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            color: #111827;
            background: #fff;
        }

        .otw-combo-btn {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            color: #111827;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            cursor: pointer;
            text-align: left;
        }
        .otw-combo-btn .placeholder { color: #9ca3af; }
        .otw-combo-btn.assigned { background: #bbf7d0; color: #14532d; border-color: #86efac; }
        .otw-combo-btn.completed { background: #bbf7d0; color: #14532d; border-color: #86efac; }
        .otw-combo-pop {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.10);
            z-index: 50;
            overflow: hidden;
        }
        .otw-combo-search {
            width: 100%;
            padding: 8px 10px;
            border: 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            background: #f9fafb;
            color: #111827;
            outline: none;
        }
        .otw-combo-list {
            max-height: 220px;
            overflow-y: auto;
        }
        .otw-combo-item {
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
        .otw-combo-item:hover { background: #f3f4f6; }
        .otw-combo-item.clear { color: #9ca3af; }
        .otw-combo-item.selected { background: #eef2ff; color: #4f46e5; font-weight: 600; }

        .otw-slot {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-top: 6px;
            border-top: 1px dashed #e5e7eb;
        }
        .otw-slot:first-of-type {
            border-top: 0;
            padding-top: 0;
        }
        .otw-slot-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .otw-on-leave {
            border: 1px solid #FCA5A5;
            background: #FEF2F2;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .otw-on-leave-title {
            color: #B91C1C;
            font-size: 13px;
            font-weight: 700;
        }
        .otw-on-leave-sub {
            color: #B91C1C;
            font-size: 11px;
            font-weight: 500;
            margin-top: 2px;
        }
        .otw-on-leave-session {
            margin-left: 4px;
            color: #7F1D1D;
            font-weight: 600;
        }

        .staff-color-0 { background-color: #bfdbfe; }
        .staff-color-1 { background-color: #fef08a; }
        .staff-color-2 { background-color: #fecaca; }
        .staff-color-3 { background-color: #e9d5ff; }
        .staff-color-4 { background-color: #fbcfe8; }
        .staff-color-5 { background-color: #d1d5db; }
        .staff-color-6 { background-color: #bbf7d0; }

        @media (max-width: 900px) {
            .otw-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 520px) {
            .otw-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="otw-range">
        Week of {{ $this->weekRangeLabel }}
    </div>

    <div class="otw-grid">
        @foreach ($days as $day)
            <div class="otw-day {{ $day['is_today'] ? 'today' : '' }}">
                <div class="otw-day-head">
                    <span class="otw-day-label">{{ $day['day_label'] }}</span>
                    <span class="otw-day-num">{{ $day['day_num'] }}</span>
                </div>

                @foreach (['main' => 'Main Support', 'backup' => 'Backup Support'] as $slotKey => $slotLabel)
                    @php $slot = $day[$slotKey]; @endphp
                    <div class="otw-slot">
                        <div class="otw-slot-label">{{ $slotLabel }}</div>
                        @if ($editMode)
                            <div x-data="{ open: false, search: '' }" @click.away="open = false; search = ''" style="position: relative;">
                                <button type="button" class="otw-combo-btn {{ $slot['user_id'] ? $slot['css_class'] : '' }}" @click="open = !open">
                                    @if ($slot['user_id'])
                                        <span>{{ $slot['user_name'] }}</span>
                                    @else
                                        <span class="placeholder">-- Select Staff --</span>
                                    @endif
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity class="otw-combo-pop">
                                    <input type="text" x-model="search" @click.stop placeholder="Search staff..." class="otw-combo-search">
                                    <div class="otw-combo-list">
                                        <button type="button" class="otw-combo-item clear"
                                            @click="$wire.call('assignStaff', '{{ $day['date'] }}', '', '{{ $slotKey }}'); open = false; search = ''">
                                            -- Unassign --
                                        </button>
                                        @foreach ($users as $user)
                                            @php $haystack = strtolower(str_replace(["'", '"'], '', (string) $user->name)); @endphp
                                            <button type="button"
                                                x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                                class="otw-combo-item {{ $slot['user_id'] == $user->id ? 'selected' : '' }}"
                                                @click="$wire.call('assignStaff', '{{ $day['date'] }}', {{ $user->id }}, '{{ $slotKey }}'); open = false; search = ''">
                                                {{ $user->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="otw-assign {{ $slot['user_id'] ? $slot['css_class'] : 'unassigned' }}">
                                {{ $slot['user_name'] }}
                            </div>
                        @endif

                        @if ($slot['on_leave'] ?? false)
                            <div class="otw-on-leave">
                                <div class="otw-on-leave-title">On Leave</div>
                                <div class="otw-on-leave-sub">
                                    {{ $slot['leave_type'] ?? 'Leave' }}@if (!empty($slot['leave_session']) && $slot['leave_session'] !== 'full')
                                        <span class="otw-on-leave-session">({{ strtoupper($slot['leave_session']) }})</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament::page>
