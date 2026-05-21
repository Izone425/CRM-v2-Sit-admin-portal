{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/partials/task-modal.blade.php --}}
<style>
    .task-description-content,
    .task-comment-content { overflow-wrap: anywhere; }
    .task-description-content img,
    .task-comment-content img {
        max-width: 100%;
        height: auto;
        display: inline-block;
    }
</style>
@php
    $task = $selectedTask;
    $assignees = $this->openTaskAssignees;
    $requestor = $this->taskRequestor;
    $comments = $this->taskComments;
    $logs = $this->taskLogs;
    $attachments = $this->taskAttachments;
    $bugs = $this->taskBugs;

    $developer = $assignees->first(fn ($u) => collect($u->role_names ?? [])->contains(fn ($r) => stripos($r, 'RnD') !== false || stripos($r, 'Developer') !== false));
    $qc = $assignees->first(fn ($u) => collect($u->role_names ?? [])->contains(fn ($r) => stripos($r, 'QC') !== false));

    $isOverdue = $task->due_date && $task->due_date->lt(now());
    $dueDays = $isOverdue ? $task->due_date->diffInDays(now()) : null;
@endphp

<div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
    style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000001; display: flex; align-items: center; justify-content: center;"
    wire:click="closeTaskModal">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
        wire:click.stop
        x-data="{ rightPanelCollapsed: false }">

        <!-- Modal Header -->
        <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #EEF2FF; padding: 8px 12px; border-radius: 6px;">
                    <span style="color: #6366F1; font-size: 14px; font-weight: 600;">✓ TASK</span>
                </div>
                <h2 onclick="navigator.clipboard.writeText('https://dt-dev.timeteccloud.com/task/{{ $task->task_id }}').then(function() { window.dispatchEvent(new CustomEvent('task-link-copied')); });"
                    style="font-size: 18px; font-weight: 700; color: #111827; margin: 0; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                    onmouseover="this.style.color='#6366F1'"
                    onmouseout="this.style.color='#111827'"
                    title="Click to copy task link">
                    {{ $task->task_id }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                    </svg>
                </h2>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button type="button"
                        x-on:click="$dispatch('openCreateBugModal', [{{ $task->id }}])"
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #6366F1; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Bug
                </button>
                <button wire:click="closeTaskModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">
                    ✕
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; display: grid; grid-template-columns: 1fr 350px;"
             x-bind:style="{ 'grid-template-columns': rightPanelCollapsed ? '1fr 48px' : '1fr 350px' }">

            <!-- Left Side - Main Content -->
            <div style="padding: 24px; border-right: 1px solid #E5E7EB; word-break: break-word;">
                <!-- Title -->
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 24px 0;">
                    {{ $task->title }}
                </h1>

                <!-- Description Section -->
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">Description</div>
                    <div class="task-description-content" style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB; line-height: 1.6; color: #374151;">
                        @if ($task->description)
                            @php
                                $desc = preg_replace_callback(
                                    '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                                    fn ($m) => route('s3.serve', ['path' => urldecode($m[1])]),
                                    $task->description
                                );
                                $desc = preg_replace_callback(
                                    '/(src|href)="([a-z][a-z0-9_]*_images\/[^"]+)"/i',
                                    fn ($m) => $m[1] . '="' . route('s3.serve', ['path' => urldecode($m[2])]) . '"',
                                    $desc
                                );
                            @endphp
                            {!! $desc !!}
                        @else
                            <em style="color: #9CA3AF;">No description provided.</em>
                        @endif
                    </div>
                </div>

                <!-- Tabs -->
                <div x-data="{ activeTab: 'comments' }" x-init="
                    $nextTick(() => {
                        const isImageUrl = (href) => {
                            if (!href) return false;
                            if (href.includes('storage')) return true;
                            if (href.includes('s3-file') || href.includes('s3/serve') || href.includes('s3-serve')) return true;
                            if (href.includes('s3.amazonaws.com')) return true;
                            const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                            return imageExtensions.some(ext => href.toLowerCase().includes(ext));
                        };
                        const setupImageClickHandler = (img) => {
                            if (img.dataset.zoomSetup) return;
                            img.dataset.zoomSetup = 'true';
                            img.style.cursor = 'zoom-in';
                            img.onclick = function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                $wire.call('openImageModal', img.src);
                                return false;
                            };
                        };
                        const wire = (target, action, ...args) => $wire.call(action, ...args);
                        const bindContainer = (container) => {
                            if (!container) return;
                            container.querySelectorAll('a').forEach(link => {
                                const img = link.querySelector('img');
                                const href = link.getAttribute('href');
                                if (img || isImageUrl(href)) {
                                    link.onclick = function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        $wire.call('openImageModal', img ? img.src : href);
                                        return false;
                                    };
                                    link.style.cursor = 'zoom-in';
                                    if (img) img.style.cursor = 'zoom-in';
                                }
                            });
                            container.querySelectorAll('img').forEach(img => {
                                if (!img.closest('a')) setupImageClickHandler(img);
                            });
                        };
                        bindContainer(document.querySelector('.task-description-content'));
                        document.querySelectorAll('.task-comment-content').forEach(bindContainer);
                    });
                ">
                    <div style="display: flex; gap: 24px; border-bottom: 1px solid #E5E7EB;">
                        <button @click="activeTab = 'comments'"
                                :style="activeTab === 'comments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Comments
                        </button>
                        <button @click="activeTab = 'attachments'"
                                :style="activeTab === 'attachments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Attachments ({{ $attachments->count() }})
                        </button>
                        <button @click="activeTab = 'bugs'"
                                :style="activeTab === 'bugs' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Bug List ({{ $bugs->count() }})
                        </button>
                        <button @click="activeTab = 'status'"
                                :style="activeTab === 'status' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Status Log
                        </button>
                    </div>

                    <!-- Comments Tab -->
                    <div x-show="activeTab === 'comments'" style="padding: 24px 0;">
                        <div x-data="{ showEditor: false }" style="margin-bottom: 24px;">
                            <div x-show="!showEditor">
                                <button @click="showEditor = true" type="button"
                                        style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                        onmouseover="this.style.background='#4F46E5'"
                                        onmouseout="this.style.background='#6366F1'">
                                    Add Comment
                                </button>
                            </div>
                            <div x-show="showEditor" x-transition>
                                <form wire:submit.prevent="addComment">
                                    {{ $this->form }}

                                    <div style="margin-top: 12px; display: flex; gap: 8px;">
                                        <button type="submit"
                                                style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                                onmouseover="this.style.background='#4F46E5'"
                                                onmouseout="this.style.background='#6366F1'">
                                            Send
                                        </button>
                                        <button type="button" @click="showEditor = false"
                                                style="padding: 8px 20px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                                onmouseover="this.style.background='#F9FAFB'"
                                                onmouseout="this.style.background='white'">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if ($comments->count() > 0)
                            <div style="margin-top: 24px;">
                                <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;">
                                    <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Previous Comments</h4>
                                </div>
                                @foreach ($comments as $comment)
                                    <div style="padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 12px; background: white;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #EEF2FF; color: #4F46E5; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->user?->name ?? '?', 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div style="font-size: 13px; font-weight: 600; color: #111827;">{{ $comment->user?->name ?? 'Unknown' }}</div>
                                                    <div style="font-size: 11px; color: #9CA3AF;">{{ $comment->created_at?->diffForHumans() }}@if ($comment->is_edited) · edited @endif</div>
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $cmt = preg_replace_callback(
                                                '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                                                fn ($m) => route('s3.serve', ['path' => urldecode($m[1])]),
                                                $comment->comment
                                            );
                                            $cmt = preg_replace_callback(
                                                '/(src|href)="([a-z][a-z0-9_]*_images\/[^"]+)"/i',
                                                fn ($m) => $m[1] . '="' . route('s3.serve', ['path' => urldecode($m[2])]) . '"',
                                                $cmt
                                            );
                                        @endphp
                                        <div class="task-comment-content" style="font-size: 14px; color: #374151; line-height: 1.5;">{!! $cmt !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 32px 0; color: #9CA3AF; font-size: 14px;">No comments yet</div>
                        @endif
                    </div>

                    <!-- Attachments Tab -->
                    <div x-show="activeTab === 'attachments'" x-cloak style="padding: 24px 0;">
                        @if ($attachments->isEmpty())
                            <div style="text-align: center; padding: 32px 0; color: #9CA3AF; font-size: 14px;">No attachments</div>
                        @else
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @foreach ($attachments as $att)
                                    <div style="display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; background: white; font-size: 14px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                        @if ($att->url)
                                            <a href="{{ $att->url }}" target="_blank" rel="noopener" style="color: #6366F1; font-weight: 500; text-decoration: none;">{{ $att->original_filename ?? $att->title ?? 'Attachment' }}</a>
                                        @else
                                            <span>{{ $att->original_filename ?? $att->title ?? 'Attachment' }}</span>
                                        @endif
                                        @if ($att->file_size)
                                            <span style="margin-left: auto; color: #9CA3AF; font-size: 11px;">{{ number_format($att->file_size / 1024, 1) }} KB</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Bug List Tab -->
                    <div x-show="activeTab === 'bugs'" x-cloak style="padding: 24px 0;">
                        @if ($bugs->isEmpty())
                            <div style="text-align: center; padding: 32px 0; color: #9CA3AF; font-size: 14px;">No related bugs</div>
                        @else
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @foreach ($bugs as $bug)
                                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; background: white; font-size: 14px;">
                                        <strong style="color: #6366F1;">{{ $bug->bug_id }}</strong>
                                        <span style="flex: 1;">{{ $bug->title }}</span>
                                        <span style="padding: 2px 8px; background: #F3F4F6; border-radius: 9999px; color: #374151; font-size: 11px; font-weight: 600;">{{ $bug->status }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Status Log Tab -->
                    <div x-show="activeTab === 'status'" x-cloak style="padding: 24px 0;">
                        @if ($logs->isEmpty())
                            <div style="text-align: center; padding: 32px 0; color: #9CA3AF; font-size: 14px;">No activity yet</div>
                        @else
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                @foreach ($logs as $log)
                                    <div style="display: flex; gap: 10px;">
                                        <div style="width: 8px; height: 8px; margin-top: 6px; border-radius: 50%; background: #6366F1; flex-shrink: 0;"></div>
                                        <div style="flex: 1;">
                                            <div style="font-size: 14px; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                                <strong style="color: #111827;">{{ $log->user_name ?? 'System' }}</strong>
                                                <span style="color: #6B7280;">{{ $log->action ?? 'updated' }}</span>
                                                @if ($log->field_name)
                                                    <span style="padding: 2px 8px; background: #F3F4F6; border-radius: 6px; font-size: 11px; font-weight: 600; color: #374151;">{{ $log->field_name }}</span>
                                                @endif
                                            </div>
                                            @if ($log->old_value || $log->new_value)
                                                <div style="margin-top: 4px; font-size: 12px; color: #6B7280; display: flex; gap: 6px; align-items: center;">
                                                    <span style="text-decoration: line-through;">{{ $log->old_value ?: '—' }}</span>
                                                    <span style="color: #9CA3AF;">→</span>
                                                    <span style="color: #4F46E5; font-weight: 500;">{{ $log->new_value ?: '—' }}</span>
                                                </div>
                                            @endif
                                            @if ($log->change_reason)
                                                <div style="margin-top: 4px; font-size: 12px; color: #9CA3AF; font-style: italic;">{{ $log->change_reason }}</div>
                                            @endif
                                            <div style="margin-top: 4px; font-size: 11px; color: #9CA3AF;">{{ $log->created_at?->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side - Task Information -->
            <aside style="background: #fafafa;">
                <div x-show="!rightPanelCollapsed" style="padding: 20px;">
                    <h3 style="font-size: 14px; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 16px 0;">Task Information</h3>

                    <!-- Progress Overview -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 14px 0;">
                            <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                            Progress Overview
                        </h4>
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px;">Status</div>
                            @php $transitions = $this->availableStatuses; @endphp
                            @if (!empty($transitions))
                                <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                                    <button type="button" @click="open = !open"
                                            style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #111827; cursor: pointer;">
                                        {{ $task->status ?? '-' }}
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.opacity
                                         style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 10; padding: 4px;">
                                        @foreach ($transitions as $opt)
                                            <button type="button"
                                                    @click="open = false"
                                                    wire:click="updateStatus('{{ $opt }}')"
                                                    style="display: block; width: 100%; text-align: left; padding: 8px 12px; background: transparent; border: 0; border-radius: 6px; font-size: 13px; color: #111827; cursor: pointer;"
                                                    onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                                {{ $opt }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div style="font-size: 13px; color: #111827;">{{ $task->status ?? '-' }}</div>
                            @endif
                        </div>
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Start Date</div>
                            <div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->start_date?->format('M j, Y') ?? '-' }}</div>
                        </div>
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Due Date</div>
                            <div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->due_date?->format('M j, Y') ?? '-' }}</div>
                            @if ($isOverdue)
                                <div style="font-size: 11px; color: #DC2626; margin-top: 2px;">{{ $dueDays }} days overdue</div>
                            @endif
                        </div>
                        <div style="margin-bottom: 14px;">
                            <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Planned Release Date</div>
                            <div style="font-size: 13px; color: #111827; margin-top: 2px;">
                                {{ $task->eta_release ? \Illuminate\Support\Carbon::parse($task->eta_release)->format('M j, Y') : '-' }}
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Live Release Date</div>
                            <div style="font-size: 13px; color: #111827; margin-top: 2px;">
                                {{ $task->live_release ? \Illuminate\Support\Carbon::parse($task->live_release)->format('M j, Y') : '-' }}
                            </div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                    <!-- Release Schedule -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 10px 0;">
                            <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                            Release Schedule
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; margin-bottom: 18px;">
                            <div>
                                <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Planned Live Date</div>
                                <div style="font-size: 13px; color: #111827; margin-top: 2px;">
                                    {{ $task->release && $task->release->planned_live_date ? \Illuminate\Support\Carbon::parse($task->release->planned_live_date)->format('M j, Y') : '-' }}
                                </div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px;">
                            <div>
                                <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">Assigned Release</div>
                                <select wire:change="updateRelease($event.target.value)"
                                        style="appearance: none; -webkit-appearance: none; width: 100%; padding: 8px 32px 8px 12px; background: #fff url(&quot;data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'><polyline points='6 9 12 15 18 9'/></svg>&quot;) no-repeat right 10px center; background-size: 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #111827; cursor: pointer;">
                                    @if (!$task->release_id)
                                        <option value="" disabled selected>Select Release</option>
                                    @endif
                                    @foreach ($this->assignableReleases as $r)
                                        <option value="{{ $r->id }}" @selected($task->release_id == $r->id)>
                                            {{ $r->version }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">Platform</div>
                                @php $taskPlatforms = is_array($task->platform) ? $task->platform : (json_decode($task->platform ?? '', true) ?: array_filter([$task->platform])); @endphp
                                <div style="font-size: 13px; color: #111827; padding: 8px 12px; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 8px; min-height: 36px; display: flex; align-items: center; flex-wrap: wrap; gap: 4px;">
                                    @if (empty($taskPlatforms))
                                        <span style="color: #9CA3AF;">-</span>
                                    @else
                                        @foreach ($taskPlatforms as $p)
                                            <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
                                                {{ $p === 'App' ? 'background: #DBEAFE; color: #1E40AF;' : ($p === 'Web' ? 'background: #DCFCE7; color: #166534;' : 'background: #E5E7EB; color: #4B5563;') }}">
                                                {{ $p }}
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                    <!-- Person In Charge -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 10px 0;">
                            <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                            Person In Charge
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px;">
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Developer</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $developer?->name ?? '-' }}</div></div>
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">QC</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $qc?->name ?? '-' }}</div></div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin: 14px 0;"></div>

                    <!-- Other Details -->
                    <div>
                        <h4 style="position: relative; font-size: 14px; font-weight: 600; color: #111827; padding-left: 10px; margin: 0 0 10px 0;">
                            <span style="position: absolute; left: 0; top: 2px; bottom: 2px; width: 3px; background: #6366F1; border-radius: 2px;"></span>
                            Other Details
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; margin-bottom: 18px;">
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Product</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->product?->name ?? '-' }}</div></div>
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Module</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->module?->name ?? '-' }}</div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; margin-bottom: 18px;">
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Urgency</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->priority?->name ?? '-' }}</div></div>
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Task Size</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->task_size ? \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($task->task_size)) : '-' }}</div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px;">
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Creator</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $requestor?->name ?? '-' }}</div></div>
                            <div><div style="font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">Created Date</div><div style="font-size: 13px; color: #111827; margin-top: 2px;">{{ $task->created_at?->format('M j, Y') ?? '-' }}</div></div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

@if($showImageModal && $selectedImageUrl)
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999999; display: flex; align-items: center; justify-content: center; padding: 20px;"
     wire:click="closeImageModal"
     x-data
     @keydown.escape.window="$wire.closeImageModal()">
    <button wire:click="closeImageModal"
            style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: #111827; cursor: pointer; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 10000;"
            onmouseover="this.style.background='#F3F4F6'"
            onmouseout="this.style.background='white'">
        ✕
    </button>
    <div style="max-width: 95%; max-height: 95%; display: flex; align-items: center; justify-content: center;" wire:click.stop>
        <img src="{{ $selectedImageUrl }}" alt="Preview"
             style="max-width: 100%; max-height: 90vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
    </div>
</div>
@endif
