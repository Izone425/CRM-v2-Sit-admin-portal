<div>
    @if ($showModal && $suggestion)
        @php
            $labelStyle = 'font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;';
            $valueStyle = 'font-size: 13px; color: #111827; margin-top: 2px;';
            $comments = $this->comments;
            $rel = $this->relatedItems;
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 999990; display: flex; align-items: center; justify-content: center;"
             wire:click="closeModal">
            <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
                 wire:click.stop
                 x-data="{ rightPanelCollapsed: false }">

                <!-- Modal Header -->
                <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #EEF2FF; padding: 8px 12px; border-radius: 6px;">
                            <span style="color: #6366F1; font-size: 14px; font-weight: 600;">💡 SUGGESTION</span>
                        </div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">{{ $suggestion->suggestion_id }}</h2>
                    </div>
                    <button wire:click="closeModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">✕</button>
                </div>

                <!-- Modal Body -->
                <div style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; display: grid; grid-template-columns: 1fr 350px;"
                     x-bind:style="{ 'grid-template-columns': rightPanelCollapsed ? '1fr 48px' : '1fr 350px' }">

                    <!-- Left -->
                    <div style="padding: 24px; border-right: 1px solid #E5E7EB; word-break: break-word;">
                        <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 6px 0;">{{ $suggestion->title }}</h1>
                        <div style="font-size: 12px; color: #6B7280; margin-bottom: 24px;">
                            Submitted by
                            @if ($suggestion->requestor)
                                <span style="color: #111827; font-weight: 600;">{{ $suggestion->requestor->name }}</span>
                            @else
                                <span>-</span>
                            @endif
                            on {{ $suggestion->created_at?->format('M j, Y') ?? '-' }}
                        </div>

                        <!-- Description -->
                        <div style="margin-bottom: 24px;" x-data x-init="
                            $nextTick(() => {
                                const isImageUrl = (href) => {
                                    if (!href) return false;
                                    if (href.includes('storage') || href.includes('s3-file') || href.includes('s3/serve') || href.includes('s3-serve') || href.includes('s3.amazonaws.com')) return true;
                                    const exts = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                                    return exts.some(e => href.toLowerCase().includes(e));
                                };
                                const bind = (container) => {
                                    if (!container) return;
                                    container.querySelectorAll('a').forEach(link => {
                                        const img = link.querySelector('img');
                                        const href = link.getAttribute('href');
                                        if (img || isImageUrl(href)) {
                                            link.onclick = (e) => { e.preventDefault(); e.stopPropagation(); $wire.call('openImageModal', img ? img.src : href); return false; };
                                            link.style.cursor = 'zoom-in';
                                            if (img) img.style.cursor = 'zoom-in';
                                        }
                                    });
                                    container.querySelectorAll('img').forEach(img => {
                                        if (img.closest('a')) return;
                                        if (img.dataset.zoomSetup) return;
                                        img.dataset.zoomSetup = 'true';
                                        img.style.cursor = 'zoom-in';
                                        img.onclick = (e) => { e.preventDefault(); e.stopPropagation(); $wire.call('openImageModal', img.src); return false; };
                                    });
                                };
                                bind(document.querySelector('.suggestion-description-content'));
                                document.querySelectorAll('.suggestion-comment-content').forEach(bind);
                            });
                        ">
                            <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">Description</div>
                            <div class="suggestion-description-content" style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB; line-height: 1.6; color: #374151;">
                                @if ($suggestion->description)
                                    {!! $suggestion->description !!}
                                @else
                                    <em style="color: #9CA3AF;">No description provided.</em>
                                @endif
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div style="display: flex; gap: 0; border-bottom: 1px solid #E5E7EB; margin-bottom: 20px;">
                            @foreach ([['key' => 'comments', 'label' => 'Comments ('.$comments->count().')'], ['key' => 'attachments', 'label' => 'Attachments & Links'], ['key' => 'related', 'label' => 'Related Ticket/Task']] as $t)
                                @php $isActive = $activeTab === $t['key']; @endphp
                                <button type="button" wire:click="setTab('{{ $t['key'] }}')"
                                    style="padding: 12px 18px; background: transparent; border: 0; margin-bottom: -1px; font-size: 14px; font-weight: {{ $isActive ? '600' : '500' }}; color: {{ $isActive ? '#6366F1' : '#9CA3AF' }}; cursor: pointer; border-bottom: 2px solid {{ $isActive ? '#6366F1' : 'transparent' }};">
                                    {{ $t['label'] }}
                                </button>
                            @endforeach
                        </div>

                        @if ($activeTab === 'comments')
                            <div>
                                {{ $this->commentForm }}
                                <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                    <button type="button" wire:click="addComment"
                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: #6366F1; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                        Send
                                    </button>
                                </div>

                                <div style="margin-top: 20px;">
                                    @forelse ($comments as $c)
                                        <div style="padding: 12px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; margin-bottom: 10px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                                <span style="font-size: 13px; font-weight: 600; color: #111827;">{{ $c->user_name ?? 'Unknown' }}</span>
                                                <span style="font-size: 11px; color: #9CA3AF;">{{ \Illuminate\Support\Carbon::parse($c->created_at)->diffForHumans() }}</span>
                                            </div>
                                            <div class="suggestion-comment-content" style="font-size: 13px; color: #374151; line-height: 1.5;">{!! $c->comment !!}</div>
                                        </div>
                                    @empty
                                        <div style="padding: 40px 20px; text-align: center; color: #9CA3AF;">
                                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; opacity: 0.4;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            <div style="font-size: 13px;">No comments yet</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @elseif ($activeTab === 'attachments')
                            <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">
                                @if ($suggestion->reference_link)
                                    <a href="{{ $suggestion->reference_link }}" target="_blank" style="color: #4F46E5;">{{ $suggestion->reference_link }}</a>
                                @else
                                    No attachments or links.
                                @endif
                            </div>
                        @elseif ($activeTab === 'related')
                            <div>
                                <div style="display: flex; justify-content: flex-end; margin-bottom: 14px; position: relative;">
                                    <button type="button" wire:click="openLinkPicker"
                                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #6366F1; color: #fff; border: 0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                        Link Tickets / Tasks
                                    </button>

                                @if ($showLinkPicker)
                                    <div style="position: absolute; top: calc(100% + 8px); right: 0; width: 380px; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,0.15); overflow: hidden; z-index: 200;">
                                        @if ($linkPickerMode === 'choose')
                                            <div style="padding: 4px;">
                                                <button type="button" wire:click="setLinkPickerMode('ticket')"
                                                    style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: transparent; border: 0; border-radius: 6px; cursor: pointer; text-align: left; font-size: 13px; color: #111827;"
                                                    onmouseover="this.style.background='#F3F4F6';" onmouseout="this.style.background='transparent';">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                                    Link Ticket
                                                </button>
                                                <button type="button" wire:click="setLinkPickerMode('task')"
                                                    style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: transparent; border: 0; border-radius: 6px; cursor: pointer; text-align: left; font-size: 13px; color: #111827;"
                                                    onmouseover="this.style.background='#F3F4F6';" onmouseout="this.style.background='transparent';">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Link Task
                                                </button>
                                            </div>
                                        @else
                                            <!-- Select Ticket / Select Task panel -->
                                            @php $isTicket = $linkPickerMode === 'ticket'; @endphp
                                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #E5E7EB;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <button type="button" wire:click="setLinkPickerMode('choose')" style="background: transparent; border: 0; color: #6B7280; cursor: pointer; display: inline-flex; align-items: center;">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                                    </button>
                                                    <span style="font-size: 13px; font-weight: 700; color: #111827;">Select {{ $isTicket ? 'Ticket' : 'Task' }}</span>
                                                </div>
                                                <button type="button" wire:click="closeLinkPicker"
                                                    style="width: 24px; height: 24px; background: transparent; border: 0; color: #9CA3AF; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                            </div>
                                            <div style="padding: 8px 12px; border-bottom: 1px solid #F3F4F6;">
                                                <div style="display: flex; align-items: center; gap: 8px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 6px 10px;">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                                    <input type="text" wire:model.live.debounce.300ms="linkSearchTerm"
                                                        placeholder="Search {{ $isTicket ? 'tickets' : 'tasks' }}..."
                                                        style="flex: 1; border: 0; background: transparent; outline: none; font-size: 12px;">
                                                </div>
                                            </div>
                                            <div style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column;">
                                                @php $items = $isTicket ? $this->linkableTickets : $this->linkableTasks; @endphp
                                                @forelse ($items as $it)
                                                    @php $idField = $isTicket ? $it->ticket_id : $it->task_id; $clickAction = $isTicket ? 'linkTicket' : 'linkTask'; @endphp
                                                    <button type="button" wire:click="{{ $clickAction }}({{ $it->id }})"
                                                        style="display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; background: #fff; border: 0; border-bottom: 1px solid #F3F4F6; cursor: pointer; text-align: left;"
                                                        onmouseover="this.style.background='#F9FAFB';" onmouseout="this.style.background='#fff';">
                                                        <div style="width: 14px; height: 14px; border: 2px solid #D1D5DB; border-radius: 3px; flex-shrink: 0; margin-top: 2px;"></div>
                                                        <div style="flex: 1; min-width: 0;">
                                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                                                <span style="font-family: monospace; font-size: 11px; color: #4F46E5; font-weight: 600;">{{ $idField }}</span>
                                                                <span style="padding: 1px 8px; background: #DBEAFE; color: #1D4ED8; border-radius: 9999px; font-size: 10px; font-weight: 600;">{{ $it->status }}</span>
                                                            </div>
                                                            <div style="font-size: 12px; color: #111827; font-weight: 500; margin-top: 3px;">{{ \Illuminate\Support\Str::limit($it->title, 60) }}</div>
                                                            @if (!empty($it->description))
                                                                <div style="font-size: 11px; color: #9CA3AF; margin-top: 1px;">{{ \Illuminate\Support\Str::limit(strip_tags((string) $it->description), 50) }}</div>
                                                            @endif
                                                        </div>
                                                    </button>
                                                @empty
                                                    <div style="padding: 24px 12px; text-align: center; color: #9CA3AF; font-size: 12px;">
                                                        No {{ $isTicket ? 'tickets' : 'tasks' }} found.
                                                    </div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    @foreach ($rel['tickets'] as $ticket)
                                        <div wire:click="$dispatch('openTicketModal', [{{ $ticket->id }}])"
                                             style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; cursor: pointer; transition: all 0.15s;"
                                             onmouseover="this.style.background='#fff'; this.style.borderColor='#C7D2FE';" onmouseout="this.style.background='#F9FAFB'; this.style.borderColor='#E5E7EB';">
                                            <div style="width: 36px; height: 36px; background: #FFEDD5; color: #C2410C; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">TKT</div>
                                            <div style="flex: 1;">
                                                <div style="font-size: 11px; color: #9CA3AF; font-weight: 600; letter-spacing: 0.3px;">{{ $ticket->ticket_id }}</div>
                                                <div style="font-size: 13px; color: #111827; font-weight: 500; margin-top: 2px;">{{ \Illuminate\Support\Str::limit($ticket->title, 80) }}</div>
                                            </div>
                                            <span style="padding: 3px 12px; background: #D1FAE5; color: #059669; border-radius: 9999px; font-size: 12px; font-weight: 600;">{{ $ticket->status ?? '-' }}</span>
                                            <button type="button" wire:click.stop="unlinkTicket({{ $ticket->id }})" title="Unlink" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; font-size: 18px;">×</button>
                                        </div>
                                    @endforeach
                                    @foreach ($rel['tasks'] as $task)
                                        <div wire:click="$dispatch('openTaskModal', [{{ $task->id }}])"
                                             style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; cursor: pointer; transition: all 0.15s;"
                                             onmouseover="this.style.background='#fff'; this.style.borderColor='#C7D2FE';" onmouseout="this.style.background='#F9FAFB'; this.style.borderColor='#E5E7EB';">
                                            <div style="width: 36px; height: 36px; background: #DBEAFE; color: #1D4ED8; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">TSK</div>
                                            <div style="flex: 1;">
                                                <div style="font-size: 11px; color: #9CA3AF; font-weight: 600; letter-spacing: 0.3px;">{{ $task->task_id }}</div>
                                                <div style="font-size: 13px; color: #111827; font-weight: 500; margin-top: 2px;">{{ \Illuminate\Support\Str::limit($task->title, 80) }}</div>
                                            </div>
                                            <span style="padding: 3px 12px; background: #D1FAE5; color: #059669; border-radius: 9999px; font-size: 12px; font-weight: 600;">{{ $task->status ?? '-' }}</span>
                                            <button type="button" wire:click.stop="unlinkTask({{ $task->id }})" title="Unlink" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; font-size: 18px;">×</button>
                                        </div>
                                    @endforeach
                                    @if ($rel['tickets']->isEmpty() && $rel['tasks']->isEmpty() && !$showLinkPicker)
                                        <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">No related ticket or task.</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Right -->
                    <div style="padding: 24px; background: #FAFAFB; overflow-y: auto;" x-show="!rightPanelCollapsed">
                        <!-- Suggestion Information -->
                        <div style="margin-bottom: 16px;">
                            <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 10px 0;">
                                <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                                Suggestion Information
                            </h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; margin-bottom: 18px;">
                                <div><div style="{{ $labelStyle }}">Status</div><div style="{{ $valueStyle }}">{{ $suggestion->status ?? '-' }}</div></div>
                                <div><div style="{{ $labelStyle }}">Priority</div><div style="{{ $valueStyle }}">{{ $suggestion->priority ?? '-' }}</div></div>
                            </div>
                            <div style="margin-bottom: 18px;">
                                <div style="{{ $labelStyle }}">Category</div><div style="{{ $valueStyle }}">{{ $suggestion->category ?? '-' }}</div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; margin-bottom: 18px;">
                                <div><div style="{{ $labelStyle }}">Product</div><div style="{{ $valueStyle }}">{{ $suggestion->product?->name ?? '-' }}</div></div>
                                <div><div style="{{ $labelStyle }}">Solution</div><div style="{{ $valueStyle }}">{{ $suggestion->solution?->name ?? '-' }}</div></div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px;">
                                <div><div style="{{ $labelStyle }}">Module</div><div style="{{ $valueStyle }}">{{ $suggestion->module?->name ?? '-' }}</div></div>
                                <div><div style="{{ $labelStyle }}">Sub-Module</div><div style="{{ $valueStyle }}">{{ $suggestion->subModule?->name ?? '-' }}</div></div>
                            </div>
                        </div>

                        <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                        <div style="margin-bottom: 16px;">
                            <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 10px 0;">
                                <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                                Submitted By
                            </h4>
                            <div style="{{ $valueStyle }}">{{ $suggestion->requestor?->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($showImageModal && $selectedImageUrl)
            <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999999; display: flex; align-items: center; justify-content: center; padding: 20px;"
                 wire:click="closeImageModal"
                 x-data
                 @keydown.escape.window="$wire.closeImageModal()">
                <button wire:click="closeImageModal"
                    style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: #111827; cursor: pointer; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 10000;"
                    onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='white'">
                    ✕
                </button>
                <div style="max-width: 95%; max-height: 95%; display: flex; align-items: center; justify-content: center;" wire:click.stop>
                    <img src="{{ $selectedImageUrl }}" alt="Preview"
                         style="max-width: 100%; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
                </div>
            </div>
        @endif
    @endif
</div>
