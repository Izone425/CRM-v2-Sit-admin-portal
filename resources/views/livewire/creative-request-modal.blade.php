<div>
    @if ($showModal && $request)
        @php
            $labelStyle = 'font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;';
            $valueStyle = 'font-size: 14px; color: #111827; font-weight: 500;';
        @endphp
        <style>
            @keyframes crmFadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes crmScaleIn { from { opacity: 0; transform: translateY(8px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        </style>
        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; align-items: center; justify-content: center; padding: 20px; animation: crmFadeIn 0.15s ease-out;"
             wire:click="closeModal">
            <div wire:click.stop
                 style="background: white; border-radius: 16px; width: 100%; max-width: 1200px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; animation: crmScaleIn 0.2s cubic-bezier(0.22, 1, 0.36, 1);">

                <!-- Header -->
                <div style="display: flex; align-items: flex-start; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #E5E7EB;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <span style="display: inline-block; padding: 4px 10px; background: #EEF2FF; color: #4F46E5; border-radius: 6px; font-size: 12px; font-weight: 600; letter-spacing: 0.3px; width: fit-content;">{{ $request->request_id }}</span>
                        <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">{{ $request->title }}</h2>
                        <div style="font-size: 12px; color: #6B7280;">
                            Created: {{ $request->created_at?->format('M j, Y') ?? '-' }}
                            @if ($request->requestor)
                                by <span style="color: #111827; font-weight: 600;">{{ $request->requestor->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" wire:click="closeModal"
                            style="padding: 7px 16px; background: #6B7280; color: #fff; border: 0; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="button" wire:click="closeModal"
                            style="width: 36px; height: 36px; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; color: #9CA3AF; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div style="flex: 1; min-height: 0; overflow-y: auto; display: grid; grid-template-columns: 1fr 360px;">
                    <!-- Left: Description + Tabs -->
                    <div style="padding: 24px; border-right: 1px solid #E5E7EB;">
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 10px;">Description</div>
                            <div style="background: #F7F7FE; padding: 14px 16px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 13px; color: #374151; line-height: 1.6;">
                                {!! $request->description ?: '<em style="color:#9CA3AF;">No description provided.</em>' !!}
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div style="display: flex; gap: 24px; border-bottom: 1px solid #E5E7EB; margin-bottom: 20px;">
                            @foreach ([['key' => 'comments', 'label' => 'Comments'], ['key' => 'submission', 'label' => 'Submission'], ['key' => 'attachments', 'label' => 'Attachments & Links'], ['key' => 'activity', 'label' => 'Activity'], ['key' => 'related', 'label' => 'Related Ticket/Task']] as $t)
                                @php $isActive = $activeTab === $t['key']; @endphp
                                <button type="button" wire:click="setTab('{{ $t['key'] }}')"
                                    style="padding: 10px 2px; background: transparent; border: 0; margin-bottom: -1px; font-size: 14px; font-weight: {{ $isActive ? '700' : '500' }}; color: {{ $isActive ? '#4F46E5' : '#9CA3AF' }}; cursor: pointer; border-bottom: 2px solid {{ $isActive ? '#4F46E5' : 'transparent' }};">
                                    {{ $t['label'] }}
                                </button>
                            @endforeach
                        </div>

                        @if ($activeTab === 'comments')
                            <div>
                                <form wire:submit.prevent="postComment" style="margin-bottom: 16px;">
                                    <textarea wire:model.defer="newComment" placeholder="Add a comment..." rows="3"
                                        style="width: 100%; padding: 12px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 13px; color: #111827; resize: vertical; font-family: inherit;"></textarea>
                                    <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
                                        <button type="submit"
                                            style="padding: 7px 16px; background: #4F46E5; color: #fff; border: 0; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">
                                            Post Comment
                                        </button>
                                    </div>
                                </form>

                                @if ($comments->isEmpty())
                                    <div style="padding: 40px 20px; text-align: center; color: #9CA3AF;">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; opacity: 0.4;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        <div style="font-size: 13px;">No comments yet</div>
                                    </div>
                                @else
                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        @foreach ($comments as $comment)
                                            <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px 14px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                                    <div style="font-size: 13px; font-weight: 600; color: #111827;">{{ $comment->user?->name ?? 'Unknown' }}</div>
                                                    <div style="font-size: 11px; color: #9CA3AF;">
                                                        {{ $comment->created_at?->diffForHumans() }}
                                                        @if ($comment->is_edited) · edited @endif
                                                    </div>
                                                </div>
                                                <div style="font-size: 13px; color: #374151; line-height: 1.6;">
                                                    {!! $comment->comment !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @elseif ($activeTab === 'attachments')
                            @if ($attachments->isEmpty() && $links->isEmpty())
                                <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">No attachments or links.</div>
                            @else
                                @if ($attachments->isNotEmpty())
                                    <div style="margin-bottom: 18px;">
                                        <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 10px;">Files ({{ $attachments->count() }})</div>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            @foreach ($attachments as $att)
                                                <div style="display: flex; align-items: center; justify-content: space-between; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px;">
                                                    <div style="display: flex; flex-direction: column; min-width: 0;">
                                                        <div style="font-size: 13px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $att->original_filename ?? $att->title ?? 'Untitled' }}</div>
                                                        <div style="font-size: 11px; color: #9CA3AF;">
                                                            @if ($att->file_size)
                                                                {{ number_format($att->file_size / 1024, 1) }} KB ·
                                                            @endif
                                                            {{ $att->uploader?->name ?? 'Unknown' }}
                                                        </div>
                                                    </div>
                                                    @if ($att->url || $att->file_path)
                                                        <a href="{{ $att->url ?: $att->file_path }}" target="_blank" style="font-size: 12px; font-weight: 600; color: #4F46E5; text-decoration: none;">Open</a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($links->isNotEmpty())
                                    <div>
                                        <div style="font-size: 12px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 10px;">Links ({{ $links->count() }})</div>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            @foreach ($links as $link)
                                                <a href="{{ $link->url }}" target="_blank" style="display: block; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 10px 12px; text-decoration: none;">
                                                    <div style="font-size: 13px; font-weight: 600; color: #4F46E5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $link->title ?: $link->url }}</div>
                                                    @if ($link->description)
                                                        <div style="font-size: 11px; color: #6B7280; margin-top: 2px;">{{ $link->description }}</div>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @elseif ($activeTab === 'activity')
                            @if ($logs->isEmpty())
                                <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">No activity yet.</div>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    @foreach ($logs as $log)
                                        <div style="display: flex; gap: 10px; padding: 10px 12px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px;">
                                            <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; background: #EEF2FF; color: #4F46E5; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                                {{ strtoupper(substr($log->user_name ?? '?', 0, 1)) }}
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 13px; color: #111827;">
                                                    <span style="font-weight: 600;">{{ $log->user_name ?? 'System' }}</span>
                                                    {{ $log->action_description ?: $log->action }}
                                                </div>
                                                <div style="font-size: 11px; color: #9CA3AF; margin-top: 2px;">{{ $log->created_at?->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @elseif ($activeTab === 'related')
                            <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">
                                @if ($request->related_task_id)
                                    Related task ID: {{ $request->related_task_id }}
                                @elseif ($request->related_ticket_id)
                                    Related ticket ID: {{ $request->related_ticket_id }}
                                @else
                                    No related ticket or task.
                                @endif
                            </div>
                        @elseif ($activeTab === 'submission')
                            <div style="padding: 40px 20px; text-align: center; color: #9CA3AF; font-size: 13px;">No submissions yet.</div>
                        @endif
                    </div>

                    <!-- Right: Request Details -->
                    <div style="padding: 24px; background: #FAFAFB;">
                        <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 18px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                                <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.04em;">
                                    Request Details
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                                <div>
                                    <div style="{{ $labelStyle }}">Priority</div>
                                    <div style="{{ $valueStyle }}">{{ $request->priority ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="{{ $labelStyle }}">Status</div>
                                    <div style="{{ $valueStyle }}">{{ $request->status ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="margin-bottom: 18px;">
                                <div style="{{ $labelStyle }}">Category</div>
                                <div style="{{ $valueStyle }}">{{ $request->category ?? '-' }}</div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                                <div>
                                    <div style="{{ $labelStyle }}">Product</div>
                                    <div style="{{ $valueStyle }}">{{ $request->product?->name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="{{ $labelStyle }}">Solution</div>
                                    <div style="{{ $valueStyle }}">{{ $request->solution?->name ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                                <div>
                                    <div style="{{ $labelStyle }}">Module</div>
                                    <div style="{{ $valueStyle }}">{{ $request->module?->name ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="{{ $labelStyle }}">Sub-Module</div>
                                    <div style="{{ $valueStyle }}">{{ $request->subModule?->name ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                            <div style="margin-bottom: 18px;">
                                <div style="{{ $labelStyle }}">Assignee</div>
                                <div style="{{ $valueStyle }}">{{ $request->assignee?->name ?? '-' }}</div>
                            </div>

                            <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                                <div>
                                    <div style="{{ $labelStyle }}">Due Date</div>
                                    <div style="{{ $valueStyle }}">{{ $request->due_date?->format('d/m/Y') ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="{{ $labelStyle }}">Start Date</div>
                                    <div style="{{ $valueStyle }}">{{ $request->created_at?->format('d/m/Y') ?? '-' }}</div>
                                </div>
                            </div>

                            <div>
                                <div style="{{ $labelStyle }}">Expected Completion</div>
                                <div style="{{ $valueStyle }}">{{ $request->expected_completion_date?->format('d/m/Y') ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
