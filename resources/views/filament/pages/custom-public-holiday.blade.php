<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .flatpickr-calendar { z-index: 100000 !important; }

        .fi-no,
        .fi-no-stack,
        .fi-no-notifications,
        .fi-notifications,
        [data-filament-panel] .fi-no,
        [data-filament-panel] .fi-no-stack,
        [data-filament-panel] .fi-no-notifications {
            z-index: 100001 !important;
        }

        .cph-list-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .cph-list-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .cph-list-heading {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        .cph-list-subheading {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .cph-list-add-btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
            transition: background 0.15s, transform 0.05s;
        }

        .cph-list-add-btn:hover { background: #1d4ed8; }
        .cph-list-add-btn:active { transform: translateY(1px); }

        .cph-list-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cph-list-table th,
        .cph-list-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }

        .cph-list-table thead th {
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e5e7eb;
        }

        .cph-list-table tbody tr {
            transition: background 0.12s;
        }

        .cph-list-table tbody tr:hover {
            background: #f9fafb;
        }

        .cph-list-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cph-list-table td {
            color: #111827;
            font-size: 14px;
            vertical-align: middle;
        }

        .cph-list-year {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cph-list-year-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .cph-list-count-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            background: #f3f4f6;
            color: #374151;
        }

        .cph-list-count-badge--zero {
            background: #fef3c7;
            color: #92400e;
        }

        .cph-list-edit-btn {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #2563eb;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.12s;
        }

        .cph-list-edit-btn:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .cph-list-empty {
            text-align: center !important;
            color: #9ca3af !important;
            padding: 40px 24px !important;
            font-size: 14px;
        }
    </style>

    <div class="cph-list-card">
        <div class="cph-list-toolbar">
            <div>
                <h2 class="cph-list-heading">Public Holidays</h2>
            </div>
            <button type="button" class="cph-list-add-btn" wire:click="openAddModal">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Holiday
            </button>
        </div>

        <table class="cph-list-table">
            <thead>
                <tr>
                    <th style="width: 220px;"></th>
                    <th></th>
                    <th style="width: 140px; text-align: right;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->yearsList as $row)
                    <tr wire:key="cph-list-row-{{ $row['year'] }}">
                        <td>
                            <span class="cph-list-year">
                                <span class="cph-list-year-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </span>
                                {{ $row['year'] }}
                            </span>
                        </td>
                        <td>
                            <span class="cph-list-count-badge {{ $row['count'] === 0 ? 'cph-list-count-badge--zero' : '' }}">
                                {{ $row['count'] }} holiday{{ $row['count'] === 1 ? '' : 's' }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="cph-list-edit-btn" wire:click="openAddModal({{ $row['year'] }})">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="cph-list-empty">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.4" stroke="#cbd5e1" width="44" height="44">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <div>No public holidays yet. Click <strong>Add Holiday</strong> to get started.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showAddModal)
        <style>
            .cph-overlay {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0, 0, 0, 0.55);
                padding: 16px;
            }

            .cph-modal {
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25) !important;
                width: 100% !important;
                max-width: 1024px !important;
                height: 85vh !important;
                position: relative !important;
                overflow: hidden !important;
            }

            .cph-header {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 56px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 14px 20px !important;
                border-bottom: 1px solid #e5e7eb !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }

            .cph-title {
                font-size: 17px;
                font-weight: 600;
                color: #111827;
                margin: 0;
            }

            .cph-close {
                background: none;
                border: none;
                font-size: 22px;
                line-height: 1;
                color: #6b7280;
                cursor: pointer;
                padding: 4px 8px;
                border-radius: 6px;
            }

            .cph-close:hover {
                color: #111827;
                background: #f3f4f6;
            }

            .cph-body {
                position: absolute !important;
                top: 56px !important;
                bottom: 56px !important;
                left: 0 !important;
                right: 0 !important;
                overflow-y: scroll !important;
                padding: 16px 20px !important;
                box-sizing: border-box !important;
            }

            .cph-body > * + * {
                margin-top: 12px;
            }

            .cph-year {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                overflow: hidden;
                background: #ffffff;
            }

            .cph-year-summary {
                cursor: pointer;
                padding: 10px 14px;
                background: #f9fafb;
                font-weight: 600;
                color: #111827;
                display: flex;
                align-items: center;
                justify-content: space-between;
                user-select: none;
            }

            .cph-year-summary .cph-caret {
                margin-right: 8px;
                color: #9ca3af;
                transition: transform 0.15s;
                display: inline-block;
            }

            .cph-year[data-open="true"] .cph-caret {
                transform: rotate(90deg);
            }

            .cph-year-count {
                font-size: 12px;
                font-weight: 500;
                color: #6b7280;
            }

            .cph-year-body {
                padding: 12px 14px;
            }

            .cph-row-header,
            .cph-row {
                display: grid;
                grid-template-columns: 1fr 1fr 4fr;
                gap: 8px;
                align-items: center;
            }

            .cph-row-header.cph-row--with-trash,
            .cph-row.cph-row--with-trash {
                grid-template-columns: 1fr 1fr 4fr 40px;
            }

            .cph-row-header {
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                padding: 0 4px 6px;
                border-bottom: 1px solid #f3f4f6;
                margin-bottom: 8px;
            }

            .cph-row {
                padding: 4px 4px 4px 28px;
                position: relative;
            }

            .cph-row-handle {
                position: absolute;
                left: 4px;
                top: 50%;
                transform: translateY(-50%);
                width: 18px;
                height: 18px;
                color: #cbd5e1;
                cursor: grab;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .cph-row-handle:hover { color: #64748b; }
            .cph-row-handle:active { cursor: grabbing; }

            .cph-row.sortable-ghost { opacity: 0.4; background: #eff6ff; }
            .cph-row.sortable-chosen { background: #f9fafb; }

            .cph-row-insert {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                width: 22px;
                height: 22px;
                border-radius: 999px;
                background: #ffffff;
                border: 1px solid #93c5fd;
                color: #2563eb;
                cursor: pointer;
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2;
                padding: 0;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            }

            .cph-row-insert:hover {
                background: #eff6ff;
                border-color: #3b82f6;
            }

            .cph-row-insert--above { top: -11px; }
            .cph-row-insert--below { bottom: -11px; }

            .cph-row:hover .cph-row-insert {
                display: inline-flex;
            }

            .cph-row + .cph-row {
                border-top: 1px solid #f9fafb;
            }

            .cph-input {
                width: 100%;
                padding: 6px 10px;
                font-size: 13px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: #ffffff;
                color: #111827;
                box-sizing: border-box;
            }

            .cph-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            }

            .cph-input--name {
                text-transform: uppercase;
            }

            .cph-date-error .cph-input,
            .cph-date-error .form-control,
            .cph-date-error input {
                border-color: #ef4444 !important;
                background: #fef2f2 !important;
            }

            .cph-date-error,
            .cph-name-error {
                animation: cph-shake 0.4s ease-in-out;
            }

            .cph-name-error {
                border-color: #ef4444 !important;
                background: #fef2f2 !important;
            }

            @keyframes cph-shake {
                0%, 100% { transform: translateX(0); }
                20%, 60% { transform: translateX(-6px); }
                40%, 80% { transform: translateX(6px); }
            }

            .cph-error-banner {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #b91c1c;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                line-height: 1.4;
                display: flex;
                align-items: flex-start;
                gap: 8px;
            }

            .cph-error-banner svg {
                flex-shrink: 0;
                margin-top: 1px;
            }

            .cph-day {
                font-size: 13px;
                color: #4b5563;
                padding-left: 4px;
            }

            .cph-day--empty {
                color: #9ca3af;
            }

            .cph-trash {
                background: none;
                border: none;
                color: #ef4444;
                cursor: pointer;
                padding: 6px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .cph-trash:hover {
                background: #fef2f2;
                color: #b91c1c;
            }

            .cph-add-row {
                margin: 0 0 10px;
                background: none;
                border: 1px dashed #93c5fd;
                color: #2563eb;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 13px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .cph-add-row:hover {
                background: #eff6ff;
                border-color: #3b82f6;
            }

            .cph-footer {
                position: absolute !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 56px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 8px !important;
                padding: 12px 20px !important;
                border-top: 1px solid #e5e7eb !important;
                background: #fafafa !important;
                box-sizing: border-box !important;
            }

            .cph-btn {
                padding: 7px 16px;
                font-size: 13px;
                font-weight: 500;
                border-radius: 6px;
                cursor: pointer;
                border: 1px solid transparent;
            }

            .cph-btn--cancel {
                background: #ffffff;
                border-color: #d1d5db;
                color: #374151;
            }

            .cph-btn--cancel:hover {
                background: #f9fafb;
            }

            .cph-btn--save {
                background: #2563eb;
                color: #ffffff;
            }

            .cph-btn--save:hover {
                background: #1d4ed8;
            }

            .cph-btn--save:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>

        <template x-teleport="body">
        <div
            class="cph-overlay"
            wire:click.self="closeAddModal"
            wire:key="cph-modal"
        >
            <div class="cph-modal" wire:click.stop>
                <div class="cph-header">
                    <h2 class="cph-title">Manage Public Holidays</h2>
                    <button type="button" class="cph-close" wire:click="closeAddModal" aria-label="Close">&times;</button>
                </div>

                <div class="cph-body">
                    @if ($errorMessage)
                        <div class="cph-error-banner" wire:key="cph-error-banner">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <span>{{ $errorMessage }}</span>
                        </div>
                    @endif

                    @foreach ($yearOrder as $year)
                        @php
                            $rows = $holidaysByYear[$year] ?? [];
                            $filledCount = collect($rows)->filter(fn ($r) => ! empty($r['date']))->count();
                        @endphp
                        <div class="cph-year" wire:key="cph-year-{{ $year }}">
                            <div class="cph-year-summary">
                                <span>{{ $year }}</span>
                                <span class="cph-year-count">{{ $filledCount }} holiday(s)</span>
                            </div>
                            <div class="cph-year-body">
                                @if ($isAddMode)
                                    <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                                        <button type="button" class="cph-add-row" wire:click="addRow({{ $year }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add Another Holiday
                                        </button>
                                    </div>
                                @endif

                                <div class="cph-row-header @if ($isAddMode) cph-row--with-trash @endif" style="padding-left: 28px;">
                                    <div>Date</div>
                                    <div>Day</div>
                                    <div>Holiday Name</div>
                                    @if ($isAddMode)
                                        <div></div>
                                    @endif
                                </div>

                                <div
                                    wire:ignore.self
                                    x-data
                                    x-init="
                                        const init = () => {
                                            if (! window.Sortable) { setTimeout(init, 50); return; }
                                            Sortable.create($el, {
                                                handle: '.cph-row-handle',
                                                animation: 150,
                                                onEnd: () => {
                                                    const order = Array.from($el.children)
                                                        .map(el => el.dataset.uid)
                                                        .filter(Boolean);
                                                    $wire.call('reorderRows', {{ $year }}, order);
                                                },
                                            });
                                        };
                                        init();
                                    "
                                >
                                @foreach ($rows as $index => $row)
                                    @php $uid = $row['uid'] ?? "fallback-{$year}-{$index}"; @endphp
                                    <div
                                        class="cph-row @if ($isAddMode) cph-row--with-trash @endif"
                                        data-uid="{{ $uid }}"
                                        wire:key="cph-row-{{ $uid }}"
                                    >
                                        <span class="cph-row-handle" title="Drag to reorder">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" width="14" height="14">
                                                <circle cx="5" cy="3" r="1.2"/><circle cx="11" cy="3" r="1.2"/>
                                                <circle cx="5" cy="8" r="1.2"/><circle cx="11" cy="8" r="1.2"/>
                                                <circle cx="5" cy="13" r="1.2"/><circle cx="11" cy="13" r="1.2"/>
                                            </svg>
                                        </span>
                                        @if (! $isAddMode)
                                            <button
                                                type="button"
                                                class="cph-row-insert cph-row-insert--above"
                                                wire:click="addRowAt({{ $year }}, {{ $index }})"
                                                title="Insert row above"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor" width="12" height="12">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                class="cph-row-insert cph-row-insert--below"
                                                wire:click="addRowAt({{ $year }}, {{ $index + 1 }})"
                                                title="Insert row below"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor" width="12" height="12">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                </svg>
                                            </button>
                                        @endif
                                        <div
                                            data-lw-path="holidaysByYear.{{ $year }}.{{ $index }}.date"
                                            wire:key="cph-date-outer-{{ $uid }}"
                                            class="@if ($errorYear === $year && $errorIndex === $index && $errorField === 'date') cph-date-error @endif"
                                        >
                                            <div wire:ignore wire:key="cph-date-wrap-{{ $uid }}">
                                                <input
                                                    type="text"
                                                    class="cph-input"
                                                    value="{{ $row['date'] ?? '' }}"
                                                x-init="
                                                    const run = () => {
                                                        if (! window.flatpickr) { setTimeout(run, 50); return; }
                                                        if ($el._flatpickr) return;
                                                        flatpickr($el, {
                                                            dateFormat: 'Y-m-d',
                                                            altInput: true,
                                                            altFormat: 'j F Y',
                                                            allowInput: true,
                                                            defaultDate: $el.value || null,
                                                            minDate: '{{ $year }}-01-01',
                                                            maxDate: '{{ $year }}-12-31',
                                                            locale: { firstDayOfWeek: 1 },
                                                            onChange: function (selectedDates, dateStr) {
                                                                const wrap = $el.closest('[data-lw-path]');
                                                                if (wrap) $wire.set(wrap.dataset.lwPath, dateStr);
                                                            },
                                                        });
                                                    };
                                                    run();
                                                "
                                            />
                                            </div>
                                        </div>
                                        <div class="cph-day {{ empty($row['date']) ? 'cph-day--empty' : '' }}">
                                            {{ ! empty($row['date']) ? \Carbon\Carbon::parse($row['date'])->format('l') : '—' }}
                                        </div>
                                        <input
                                            type="text"
                                            class="cph-input cph-input--name @if ($errorYear === $year && $errorIndex === $index && $errorField === 'name') cph-name-error @endif"
                                            wire:model="holidaysByYear.{{ $year }}.{{ $index }}.name"
                                            x-on:input="$el.value = $el.value.toUpperCase()"
                                        />
                                        @if ($isAddMode)
                                            <button
                                                type="button"
                                                class="cph-trash"
                                                wire:click="removeRow({{ $year }}, {{ $index }})"
                                                title="Remove row"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="cph-footer">
                    <button type="button" class="cph-btn cph-btn--cancel" wire:click="closeAddModal">Cancel</button>
                    <button
                        type="button"
                        class="cph-btn cph-btn--save"
                        wire:click="saveHolidays"
                        wire:loading.attr="disabled"
                        wire:target="saveHolidays"
                    >
                        <span wire:loading.remove wire:target="saveHolidays">Save</span>
                        <span wire:loading wire:target="saveHolidays">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
        </template>
    @endif
</x-filament-panels::page>
