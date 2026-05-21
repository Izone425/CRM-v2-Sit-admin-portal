<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
    @if ($showDrawer)
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }

            /* Styled flatpickr alt input */
            .datepicker-wrapper { position: relative; }
            .datepicker-wrapper .calendar-icon {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #6366F1;
                pointer-events: none;
            }
            input.flatpickr-styled {
                width: 100%;
                padding: 12px 40px 12px 14px;
                background: #f9fafb;
                border: 1px solid #E5E7EB;
                border-radius: 10px;
                font-size: 14px;
                color: #111827;
                cursor: pointer;
            }
            input.flatpickr-styled:focus {
                outline: none;
                border-color: #6366F1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            }

            /* Custom calendar popup */
            .flatpickr-calendar {
                z-index: 1000000 !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12) !important;
                border: 1px solid #E5E7EB !important;
                font-family: inherit !important;
                padding: 4px;
            }
            .flatpickr-calendar.arrowTop:before,
            .flatpickr-calendar.arrowTop:after { border-bottom-color: #E5E7EB !important; }
            .flatpickr-months { padding: 8px 0; }
            .flatpickr-months .flatpickr-month { color: #111827; }
            .flatpickr-current-month { font-size: 14px !important; font-weight: 600 !important; padding-top: 4px !important; }
            .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 600; color: #111827; }
            .flatpickr-current-month input.cur-year { font-weight: 600; color: #111827; }
            .flatpickr-months .flatpickr-prev-month,
            .flatpickr-months .flatpickr-next-month { color: #6B7280; fill: #6B7280; padding: 8px; }
            .flatpickr-months .flatpickr-prev-month:hover svg,
            .flatpickr-months .flatpickr-next-month:hover svg { fill: #4F46E5 !important; }
            .flatpickr-weekdays { background: transparent !important; }
            .flatpickr-weekday { color: #9CA3AF !important; font-size: 11px !important; font-weight: 700 !important; text-transform: uppercase; }
            .flatpickr-day {
                border-radius: 8px !important;
                color: #374151;
                font-size: 13px;
                font-weight: 500;
                max-width: 36px;
                height: 36px;
                line-height: 36px;
            }
            .flatpickr-day:hover { background: #EEF2FF !important; border-color: #EEF2FF !important; color: #4F46E5 !important; }
            .flatpickr-day.today {
                border-color: #6366F1 !important;
                color: #4F46E5 !important;
                font-weight: 700;
            }
            .flatpickr-day.selected,
            .flatpickr-day.selected:hover,
            .flatpickr-day.startRange,
            .flatpickr-day.endRange {
                background: #4F46E5 !important;
                border-color: #4F46E5 !important;
                color: #fff !important;
            }
            .flatpickr-day.flatpickr-disabled,
            .flatpickr-day.flatpickr-disabled:hover {
                color: #D1D5DB !important;
                background: transparent !important;
            }
            .flatpickr-day.prevMonthDay,
            .flatpickr-day.nextMonthDay { color: #D1D5DB !important; }
        </style>
        @php
            $labelStyle = 'display: block; font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 8px;';
            $labelMutedStyle = 'display: block; font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;';
            $inputStyle = 'width: 100%; padding: 12px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827;';
            $selectStyle = 'appearance: none; -webkit-appearance: none; width: 100%; padding: 12px 36px 12px 14px; background: #fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827; cursor: pointer;';
            $linkStyle = 'font-size: 13px; font-weight: 600; color: #6366F1; background: none; border: 0; cursor: pointer; padding: 0;';
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999998; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 560px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Create Task</h2>
                    <button type="button" wire:click="closeDrawer"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="submit" style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 24px;">
                    <div>
                        <label style="{{ $labelStyle }}">Related Ticket</label>
                        @php
                            $ticketMap = $this->tickets->mapWithKeys(fn ($t) => [$t->id => ['ticket_id' => $t->ticket_id, 'title' => $t->title]])->toArray();
                            $selectedTicket = $related_ticket_id ? ($ticketMap[$related_ticket_id] ?? null) : null;
                        @endphp
                        <div x-data="{ open: false, search: '' }" @click.away="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="{{ $selectStyle }} display: flex; align-items: center; justify-content: space-between; text-align: left; gap: 8px;">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; {{ $selectedTicket ? 'color: #111827;' : 'color: #9CA3AF;' }}">
                                    @if ($selectedTicket)
                                        <strong style="color: #4F46E5;">{{ $selectedTicket['ticket_id'] }}</strong> — {{ \Illuminate\Support\Str::limit($selectedTicket['title'], 60) }}
                                    @else
                                        Select Ticket
                                    @endif
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="open" x-transition x-cloak
                                 style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); z-index: 50; overflow: hidden;">
                                <div style="padding: 8px; border-bottom: 1px solid #F3F4F6;">
                                    <input type="text" x-model="search" placeholder="Search ticket..."
                                           @click.stop
                                           style="width: 100%; padding: 8px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #111827;">
                                </div>
                                <div style="max-height: 240px; overflow-y: auto;">
                                    <button type="button" @click="$wire.set('related_ticket_id', null); open = false; search = ''"
                                            style="display: block; width: 100%; text-align: left; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #9CA3AF; cursor: pointer; border-bottom: 1px solid #F3F4F6;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">Clear selection</button>
                                    @foreach ($this->tickets as $t)
                                        @php
                                            $label = $t->ticket_id . ' — ' . $t->title;
                                            $haystack = strtolower($t->ticket_id . ' ' . $t->title);
                                        @endphp
                                        <button type="button"
                                                x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                                @click="$wire.set('related_ticket_id', {{ $t->id }}); open = false; search = ''"
                                                style="display: flex; width: 100%; align-items: center; gap: 8px; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #111827; cursor: pointer; border-bottom: 1px solid #F3F4F6; text-align: left;"
                                                onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                            <strong style="color: #4F46E5;">{{ $t->ticket_id }}</strong>
                                            <span style="color: #6B7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">— {{ $t->title }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Title <span style="color: #DC2626;">*</span></label>
                        <input type="text" wire:model.defer="title" placeholder="Enter task title" style="{{ $inputStyle }}">
                        @error('title') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Description <span style="color: #DC2626;">*</span></label>
                        {{ $this->descriptionForm }}
                        @error('description') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>

                    <div style="display: grid; grid-template-columns: {{ $product_id ? '1fr 1fr' : '1fr' }}; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Product <span style="color: #DC2626;">*</span></label>
                            <select wire:model.live="product_id" style="{{ $selectStyle }} {{ $releaseLocked ? 'background: #F3F4F6; cursor: not-allowed;' : '' }}" @disabled($releaseLocked)>
                                <option value="">Select Product</option>
                                @foreach ($this->products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        @if ($product_id)
                            <div>
                                <label style="{{ $labelStyle }}">Module <span style="color: #DC2626;">*</span></label>
                                <select wire:model.live="module_id" style="{{ $selectStyle }} {{ $releaseLocked ? 'background: #F3F4F6; cursor: not-allowed;' : '' }}" @disabled($releaseLocked)>
                                    <option value="">Select Module</option>
                                    @foreach ($this->modules as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                                @error('module_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label style="{{ $labelMutedStyle }}">Platform</label>
                        <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="width: 100%; min-height: 48px; padding: 8px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left;">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; min-width: 0;">
                                    @if (empty($platform))
                                        <span style="color: #9CA3AF; font-size: 14px;">+ Add</span>
                                    @else
                                        @foreach ($platform as $p)
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #EEF2FF; color: #4F46E5; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                                {{ $p }}
                                                <span @click.stop="$wire.call('removePlatform', '{{ $p }}')"
                                                      style="cursor: pointer; line-height: 1; font-size: 14px; color: #4F46E5;">×</span>
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="flex-shrink: 0;" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 style="position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50;">
                                <ul style="padding: 6px; margin: 0; list-style: none; display: flex; flex-direction: column;">
                                    @foreach ($this->platformOptions as $opt)
                                        @php $isSel = in_array($opt, $platform); @endphp
                                        <li wire:click="togglePlatform('{{ $opt }}')" @click.stop
                                            style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; cursor: pointer; border-radius: 8px; width: 100%;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 2px solid {{ $isSel ? '#6366F1' : '#D1D5DB' }}; background: {{ $isSel ? '#6366F1' : 'white' }}; border-radius: 4px; flex-shrink: 0;">
                                                @if ($isSel)<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>@endif
                                            </span>
                                            <span style="font-size: 14px; color: #111827;">{{ $opt }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Urgency <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="priority_id" style="{{ $selectStyle }}">
                                <option value="">Select Priority</option>
                                @foreach ($this->priorities as $pr)
                                    <option value="{{ $pr->id }}">{{ $pr->name }}</option>
                                @endforeach
                            </select>
                            @error('priority_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Task Size <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="task_size" style="{{ $selectStyle }}">
                                <option value="">Select Task Size</option>
                                @foreach ($this->taskSizeOptions as $sz)
                                    <option value="{{ $sz }}">{{ $sz }}</option>
                                @endforeach
                            </select>
                            @error('task_size') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Start Date <span style="color: #DC2626;">*</span></label>
                            <div wire:ignore class="datepicker-wrapper"
                                 x-data="{
                                    fp: null,
                                    init() {
                                        const attach = () => {
                                            if (!window.flatpickr) return setTimeout(attach, 50);
                                            this.fp = flatpickr(this.$refs.input, {
                                                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', altInputClass: 'flatpickr-styled',
                                                defaultDate: @js($start_date),
                                                onChange: (_, dateStr) => $wire.set('start_date', dateStr, true),
                                            });
                                        };
                                        attach();
                                    }
                                 }">
                                <input type="text" x-ref="input" placeholder="dd/mm/yyyy" style="{{ $inputStyle }}">
                                <svg class="calendar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            @error('start_date') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Due Date <span style="color: #DC2626;">*</span></label>
                            <div wire:ignore class="datepicker-wrapper"
                                 x-data="{
                                    fp: null,
                                    init() {
                                        const attach = () => {
                                            if (!window.flatpickr) return setTimeout(attach, 50);
                                            this.fp = flatpickr(this.$refs.input, {
                                                dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', altInputClass: 'flatpickr-styled',
                                                defaultDate: @js($due_date),
                                                onChange: (_, dateStr) => $wire.set('due_date', dateStr, true),
                                            });
                                        };
                                        attach();
                                    }
                                 }">
                                <input type="text" x-ref="input" placeholder="dd/mm/yyyy" style="{{ $inputStyle }}">
                                <svg class="calendar-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            @error('due_date') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @php $hasPlannedDate = $release_id && $this->plannedReleaseDate; @endphp
                    <div style="display: grid; grid-template-columns: {{ $hasPlannedDate ? '1fr 1fr' : '1fr' }}; gap: 16px;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <label style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Release</label>
                            </div>
                            @php
                                $platformBadgeStyles = [
                                    'App' => 'background: #DBEAFE; color: #1E40AF;',
                                    'Web' => 'background: #DCFCE7; color: #166534;',
                                ];
                                $badgeBase = 'font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px;';
                                $selectedRelease = $release_id ? $this->releases->firstWhere('id', $release_id) : null;
                                $selectedPlatforms = $selectedRelease
                                    ? (is_array($selectedRelease->platform)
                                        ? $selectedRelease->platform
                                        : (json_decode($selectedRelease->platform, true) ?: array_filter([$selectedRelease->platform])))
                                    : [];
                            @endphp
                            @php
                                $hasAnyFilter = $product_id || $module_id || !empty($platform);
                                $releaseEnabled = $releaseLocked || $hasAnyFilter;
                                $releaseHint = (! $releaseLocked && ! $hasAnyFilter) ? 'Select Product, Module or Platform first' : null;
                            @endphp
                            @if ($releaseLocked && $selectedRelease)
                                <div style="{{ $selectStyle }} background: #F3F4F6; cursor: not-allowed; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-weight: 600; color: #111827;">{{ $selectedRelease->version }}</span>
                                    @foreach ($selectedPlatforms as $p)
                                        <span style="{{ $platformBadgeStyles[$p] ?? 'background: #E5E7EB; color: #4B5563;' }} {{ $badgeBase }}">{{ $p }}</span>
                                    @endforeach
                                </div>
                            @elseif (! $releaseEnabled)
                                <div style="{{ $selectStyle }} background: #F9FAFB; cursor: not-allowed; color: #9CA3AF; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $releaseHint }}</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                            @else
                                <div x-data="{ open: false }" @click.away="open = false" style="position: relative;">
                                    <button type="button" @click="open = !open"
                                            style="{{ $selectStyle }} display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; background: #fff;">
                                        <span style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            @if ($selectedRelease)
                                                <span style="font-weight: 600; color: #111827;">{{ $selectedRelease->version }}</span>
                                                @foreach ($selectedPlatforms as $p)
                                                    <span style="{{ $platformBadgeStyles[$p] ?? 'background: #E5E7EB; color: #4B5563;' }} {{ $badgeBase }}">{{ $p }}</span>
                                                @endforeach
                                            @else
                                                <span style="color: #9CA3AF;">Select Release</span>
                                            @endif
                                        </span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                    </button>
                                    <div x-show="open" x-transition x-cloak
                                         style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; max-height: 260px; overflow-y: auto; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); z-index: 60;">
                                        <button type="button" @click="$wire.set('release_id', null); open = false"
                                                style="display: block; width: 100%; text-align: left; padding: 8px 12px; border: none; background: #fff; font-size: 13px; color: #9CA3AF; cursor: pointer; border-bottom: 1px solid #F3F4F6;"
                                                onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">Select Release</button>
                                        @foreach ($this->releases as $r)
                                            @php
                                                $optPlatforms = is_array($r->platform)
                                                    ? $r->platform
                                                    : (json_decode($r->platform, true) ?: array_filter([$r->platform]));
                                            @endphp
                                            <button type="button" @click="$wire.set('release_id', {{ $r->id }}); open = false"
                                                    style="display: flex; width: 100%; align-items: center; gap: 8px; flex-wrap: wrap; padding: 8px 12px; border: none; background: #fff; font-size: 13px; color: #111827; cursor: pointer; border-bottom: 1px solid #F3F4F6; text-align: left;"
                                                    onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                                <span style="font-weight: 600;">{{ $r->version }}</span>
                                                @foreach ($optPlatforms as $p)
                                                    <span style="{{ $platformBadgeStyles[$p] ?? 'background: #E5E7EB; color: #4B5563;' }} {{ $badgeBase }}">{{ $p }}</span>
                                                @endforeach
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if ($hasPlannedDate)
                            <div>
                                <label style="{{ $labelStyle }}">Planned Release Date</label>
                                <input type="text" value="{{ \Illuminate\Support\Carbon::parse($this->plannedReleaseDate)->format('d/m/Y') }}" readonly style="{{ $inputStyle }} background: #F3F4F6; cursor: not-allowed;">
                            </div>
                        @endif
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <label style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Assignee(s) <span style="color: #DC2626;">*</span></label>
                            <button type="button" wire:click="assignToMe" style="{{ $linkStyle }}">Assign to me</button>
                        </div>
                        @php
                            $userMap = $this->users->keyBy('id');
                            $selectedNames = collect($assignee_ids)->map(fn ($id) => $userMap[$id]?->name ?? null)->filter()->all();
                        @endphp
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="width: 100%; min-height: 48px; padding: 8px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left;">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; min-width: 0;">
                                    @if (empty($assignee_ids))
                                        <span style="color: #9CA3AF; font-size: 14px;">Select Assignee(s)</span>
                                    @else
                                        @foreach ($assignee_ids as $uid)
                                            @if (isset($userMap[$uid]))
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #EEF2FF; color: #4F46E5; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                                    {{ $userMap[$uid]->name }}
                                                    <span @click.stop="$wire.call('removeAssignee', {{ $uid }})"
                                                          style="cursor: pointer; line-height: 1; font-size: 14px; color: #4F46E5;">×</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="flex-shrink: 0;" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 style="position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; display: flex; flex-direction: column;">
                                <div style="padding: 10px; border-bottom: 1px solid #F3F4F6;">
                                    <div style="position: relative;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                        <input type="text" x-model="search" placeholder="Search users..."
                                               @click.stop
                                               style="width: 100%; padding: 8px 10px 8px 32px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; background: #F9FAFB;">
                                    </div>
                                </div>
                                <ul style="max-height: 240px; overflow-y: auto; overflow-x: hidden; padding: 6px; margin: 0; list-style: none; display: flex; flex-direction: column;">
                                    @foreach ($this->users as $u)
                                        @php $isSelected = in_array($u->id, $assignee_ids); @endphp
                                        <li x-show="'{{ \Illuminate\Support\Str::lower(str_replace(["'", '"'], '', $u->name)) }}'.includes(search.toLowerCase())"
                                            wire:click="toggleAssignee({{ $u->id }})"
                                            @click.stop
                                            style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; cursor: pointer; border-radius: 8px; width: 100%;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 2px solid {{ $isSelected ? '#6366F1' : '#D1D5DB' }}; background: {{ $isSelected ? '#6366F1' : 'white' }}; border-radius: 4px; flex-shrink: 0;">
                                                @if ($isSelected)
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                @endif
                                            </span>
                                            <span style="font-size: 14px; color: #111827;">{{ $u->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @error('assignee_ids') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 16px 28px; border-top: 1px solid #E5E7EB;">
                    <button type="button" wire:click="closeDrawer"
                            style="padding: 10px 24px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        Cancel
                    </button>
                    <button type="button" wire:click="submit"
                            style="padding: 10px 28px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        Create
                    </button>
                </div>
            </aside>
        </div>
    @endif
</div>
