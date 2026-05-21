<div>
    @if ($showDrawer)
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }
        </style>
        @php
            $labelStyle = 'display: block; font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 8px;';
            $labelMutedStyle = 'display: block; font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;';
            $inputStyle = 'width: 100%; padding: 12px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827;';
            $selectStyle = 'appearance: none; -webkit-appearance: none; width: 100%; padding: 12px 36px 12px 14px; background: #fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827; cursor: pointer;';
            $userMap = $this->users->keyBy('id');
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999998; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 560px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Create Bug</h2>
                    <button type="button" wire:click="closeDrawer" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="submit" style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 24px;">
                    <div>
                        <label style="{{ $labelStyle }}">Related Task</label>
                        @php
                            $taskMap = $this->tasks->mapWithKeys(fn ($t) => [$t->id => ['task_id' => $t->task_id, 'title' => $t->title]])->toArray();
                            $selectedTask = $related_task_id ? ($taskMap[$related_task_id] ?? null) : null;
                        @endphp
                        <div x-data="{ open: false, search: '' }" @click.away="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="{{ $selectStyle }} display: flex; align-items: center; justify-content: space-between; text-align: left; gap: 8px;">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; {{ $selectedTask ? 'color: #111827;' : 'color: #9CA3AF;' }}">
                                    @if ($selectedTask)
                                        <strong style="color: #4F46E5;">{{ $selectedTask['task_id'] }}</strong> — {{ \Illuminate\Support\Str::limit($selectedTask['title'], 60) }}
                                    @else
                                        Select Task
                                    @endif
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="open" x-transition x-cloak
                                 style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); z-index: 50; overflow: hidden;">
                                <div style="padding: 8px; border-bottom: 1px solid #F3F4F6;">
                                    <input type="text" x-model="search" placeholder="Search task..."
                                           @click.stop
                                           style="width: 100%; padding: 8px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #111827;">
                                </div>
                                <div style="max-height: 240px; overflow-y: auto;">
                                    <button type="button" @click="$wire.set('related_task_id', null); open = false; search = ''"
                                            style="display: block; width: 100%; text-align: left; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #9CA3AF; cursor: pointer; border-bottom: 1px solid #F3F4F6;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">Clear selection</button>
                                    @foreach ($this->tasks as $t)
                                        @php $haystack = strtolower($t->task_id . ' ' . $t->title); @endphp
                                        <button type="button"
                                                x-show="search === '' || '{{ e($haystack) }}'.includes(search.toLowerCase())"
                                                @click="$wire.set('related_task_id', {{ $t->id }}); open = false; search = ''"
                                                style="display: flex; width: 100%; align-items: center; gap: 8px; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #111827; cursor: pointer; border-bottom: 1px solid #F3F4F6; text-align: left;"
                                                onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                            <strong style="color: #4F46E5;">{{ $t->task_id }}</strong>
                                            <span style="color: #6B7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">— {{ $t->title }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Bug Name <span style="color: #DC2626;">*</span></label>
                        <input type="text" wire:model.defer="title" placeholder="Enter bug name" style="{{ $inputStyle }}">
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
                            <select wire:model.live="product_id" style="{{ $selectStyle }}">
                                <option value="">Select Product</option>
                                @foreach ($this->products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                            </select>
                            @error('product_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        @if ($product_id)
                            <div>
                                <label style="{{ $labelStyle }}">Module <span style="color: #DC2626;">*</span></label>
                                <select wire:model.defer="module_id" style="{{ $selectStyle }}">
                                    <option value="">Select Module</option>
                                    @foreach ($this->modules as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
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

                    @php $hasPlannedDate = $release_id && $this->plannedReleaseDate; @endphp
                    <div style="display: grid; grid-template-columns: {{ $hasPlannedDate ? '1fr 1fr' : '1fr' }}; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Release</label>
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

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Severity <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="severity" style="{{ $selectStyle }}">
                                <option value="">Select Severity</option>
                                @foreach ($this->severityOptions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                            </select>
                            @error('severity') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Category <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="category_id" style="{{ $selectStyle }}">
                                <option value="">Select Category</option>
                                @foreach ($this->categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                            </select>
                            @error('category_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelMutedStyle }}">Assignee(s)</label>
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
                                                    <span @click.stop="$wire.call('removeAssignee', {{ $uid }})" style="cursor: pointer; line-height: 1; font-size: 14px; color: #4F46E5;">×</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 style="position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; display: flex; flex-direction: column;">
                                <div style="padding: 10px; border-bottom: 1px solid #F3F4F6;">
                                    <input type="text" x-model="search" placeholder="Search users..." @click.stop
                                           style="width: 100%; padding: 8px 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; background: #F9FAFB;">
                                </div>
                                <ul style="max-height: 240px; overflow-y: auto; padding: 6px; margin: 0; list-style: none; display: flex; flex-direction: column;">
                                    @foreach ($this->users as $u)
                                        @php $isSel = in_array($u->id, $assignee_ids); @endphp
                                        <li x-show="'{{ \Illuminate\Support\Str::lower(str_replace(["'", '"'], '', $u->name)) }}'.includes(search.toLowerCase())"
                                            wire:click="toggleAssignee({{ $u->id }})" @click.stop
                                            style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; cursor: pointer; border-radius: 8px; width: 100%;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 2px solid {{ $isSel ? '#6366F1' : '#D1D5DB' }}; background: {{ $isSel ? '#6366F1' : 'white' }}; border-radius: 4px; flex-shrink: 0;">
                                                @if ($isSel)<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>@endif
                                            </span>
                                            <span style="font-size: 14px; color: #111827;">{{ $u->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                            <label style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Attachments (Images)</label>
                            <div style="display: flex; gap: 8px;">
                                <label for="bugAttachments" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: white; color: #6366F1; border: 1px solid #E0E7FF; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Add File
                                </label>
                                <input type="file" wire:model="attachments" multiple id="bugAttachments" style="display: none;">
                                <button type="button" wire:click="openLinkInput"
                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: white; color: #6366F1; border: 1px solid #E0E7FF; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                    Add Link
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach ($attachmentLinks as $i => $link)
                                <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                    <div style="flex: 1; color: #4F46E5; font-weight: 600; font-size: 14px; word-break: break-all;">
                                        @if (!empty($link['url']))
                                            <a href="{{ $link['url'] }}" target="_blank" style="color: #4F46E5; text-decoration: none;">{{ $link['label'] }}</a>
                                        @else
                                            {{ $link['label'] }}
                                        @endif
                                    </div>
                                    <button type="button" wire:click="editLink({{ $i }})" style="background: white; border: 1px solid #E5E7EB; padding: 6px; border-radius: 6px; cursor: pointer; color: #6B7280;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" wire:click="removeLink({{ $i }})" style="background: transparent; border: 0; cursor: pointer; color: #9CA3AF; font-size: 18px; padding: 4px;">×</button>
                                </div>
                            @endforeach

                            @foreach ($attachments as $i => $file)
                                <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <div style="flex: 1;">
                                        <div style="color: #111827; font-weight: 600; font-size: 14px; word-break: break-all;">{{ $file->getClientOriginalName() }}</div>
                                        <div style="display: inline-block; margin-top: 4px; padding: 2px 8px; background: #E0E7FF; color: #4F46E5; font-size: 11px; font-weight: 500; border-radius: 6px;">
                                            {{ number_format($file->getSize() / 1024, 2) }} KB
                                        </div>
                                    </div>
                                    <button type="button" style="background: white; border: 1px solid #E5E7EB; padding: 6px; border-radius: 6px; cursor: pointer; color: #6B7280;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" wire:click="removeFile({{ $i }})" style="background: transparent; border: 0; cursor: pointer; color: #9CA3AF; font-size: 18px; padding: 4px;">×</button>
                                </div>
                            @endforeach
                        </div>

                        @if ($showLinkInput)
                            <div style="margin-top: 10px; padding: 14px 16px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; display: flex; flex-direction: column; gap: 10px;">
                                <input type="text" wire:model.defer="newLinkLabel" placeholder="Label (e.g. Figma mockup)" style="{{ $inputStyle }} background: white;">
                                <input type="url" wire:model.defer="newLinkUrl" placeholder="https://..." style="{{ $inputStyle }} background: white;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" wire:click="cancelLinkInput" style="padding: 6px 14px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; cursor: pointer;">Cancel</button>
                                    <button type="button" wire:click="saveLink" style="padding: 6px 14px; background: #6366F1; color: white; border: 0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">{{ $editingLinkIndex !== null ? 'Update' : 'Add' }}</button>
                                </div>
                            </div>
                        @endif

                        @if (empty($attachmentLinks) && empty($attachments) && !$showLinkInput)
                            <div style="margin-top: 4px; font-size: 12px; color: #9CA3AF;">No attachments yet. Click <strong>Add File</strong> or <strong>Add Link</strong>.</div>
                        @endif
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 16px 28px; border-top: 1px solid #E5E7EB;">
                    <button type="button" wire:click="closeDrawer" style="padding: 10px 24px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                    <button type="button" wire:click="submit" style="padding: 10px 28px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Create Bug</button>
                </div>
            </aside>
        </div>
    @endif
</div>
