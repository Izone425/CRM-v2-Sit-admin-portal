{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/partials/bug-modal.blade.php --}}
@php
    $bug = $selectedBug;
    $assignees = $this->bugAssignees;
    $reporter = $this->bugReporter;
    $comments = $this->bugComments;
    $logs = $this->bugLogs;
    $attachments = $this->bugAttachments;
@endphp

<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 999999; display: flex; align-items: center; justify-content: center;"
    wire:click="closeBugModal">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
        wire:click.stop
        x-data="{ rightPanelCollapsed: false }">

        <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div
                    x-data="{ copied: false }"
                    title="Click to copy link"
                    @click="navigator.clipboard.writeText('https://dt-dev.timeteccloud.com/bugs/{{ $bug->bug_id }}').then(() => { copied = true; setTimeout(() => copied = false, 1500); })"
                    :class="{ 'is-copied': copied }"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; user-select: none;"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>BUG</span>
                    <span style="width: 1px; height: 14px; background: rgba(185,28,28,0.25);"></span>
                    <span>{{ $bug->bug_id }}</span>
                </div>

                @if ($bug->relatedTask)
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <button
                        type="button"
                        wire:click="closeBugModal"
                        x-on:click="setTimeout(() => $dispatch('openTaskModal', [{{ $bug->relatedTask->id }}]), 100)"
                        title="Open related task"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #EEF2FF; color: #4F46E5; border: 1px solid #C7D2FE; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;"
                        onmouseover="this.style.background='#E0E7FF';" onmouseout="this.style.background='#EEF2FF';"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>{{ $bug->relatedTask->task_id }}</span>
                    </button>
                @endif

                <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                    {{ $bug->title }}
                </h2>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <button wire:click="closeBugModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">✕</button>
            </div>
        </div>

        <div style="flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; display: grid; grid-template-columns: 1fr 350px;">

            <!-- Left Side -->
            <div style="padding: 24px; border-right: 1px solid #E5E7EB; word-break: break-word;">
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 24px 0;">{{ $bug->title }}</h1>

                <div style="margin-bottom: 24px;">
                    <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">Description</div>
                    @php
                        $formatBytes = function ($bytes, $precision = 2) {
                            if (!is_numeric($bytes) || $bytes <= 0) return '';
                            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                            $i = 0;
                            while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
                            return round($bytes, $precision) . ' ' . $units[$i];
                        };

                        $bugDescHtml = $bug->description ?? '';

                        // Convert Trix attachment figures into inline images or clickable file links
                        $bugDescHtml = preg_replace_callback(
                            '/<figure\s+[^>]*data-trix-attachment="([^"]*)"[^>]*>.*?<\/figure>/s',
                            function ($matches) use ($formatBytes) {
                                $data = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true);
                                if (!is_array($data) || empty($data['url'])) {
                                    return $matches[0];
                                }
                                $url = $data['url'];
                                $filename = $data['filename'] ?? 'file';
                                $contentType = $data['contentType'] ?? '';
                                $sizeLabel = $formatBytes($data['filesize'] ?? 0);

                                if (str_starts_with($contentType, 'image/')) {
                                    return '<img src="' . e($url) . '" alt="' . e($filename) . '" style="max-width:100%;height:auto;border-radius:6px;margin:6px 0;" />';
                                }

                                $label = e($filename) . ($sizeLabel ? ' ' . e($sizeLabel) : '');
                                return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer" data-file-link="true" '
                                    . 'style="display:block;color:#4F46E5;text-decoration:underline;margin:6px 0;">' . $label . '</a>';
                            },
                            $bugDescHtml
                        );

                        // Rewrite full S3 URLs (including expired pre-signed ones) to the internal proxy route
                        $bugDescHtml = preg_replace_callback(
                            '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                            function ($matches) {
                                $path = urldecode($matches[1]);
                                return route('s3.serve', ['path' => $path]);
                            },
                            $bugDescHtml
                        );

                        // Rewrite bare relative S3 paths stored in src/href attributes (e.g. temp_desc_images/file.png)
                        $bugDescHtml = preg_replace_callback(
                            '/(src|href)="((?!https?:\/\/|\/)[^"]+\.(png|jpg|jpeg|gif|webp|bmp))"/i',
                            function ($matches) {
                                return $matches[1] . '="' . route('s3.serve', ['path' => $matches[2]]) . '"';
                            },
                            $bugDescHtml
                        );
                    @endphp
                    <div class="bug-description-content" style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB; line-height: 1.6; color: #374151;">
                        {!! $bugDescHtml ?: '<em style="color: #9CA3AF;">No description provided.</em>' !!}
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
                            const exts = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                            return exts.some(ext => href.toLowerCase().includes(ext));
                        };
                        const setupImageClickHandler = (img) => {
                            if (img.dataset.zoomSetup) return;
                            img.dataset.zoomSetup = 'true';
                            img.style.cursor = 'zoom-in';
                            img.onclick = function(e) {
                                e.preventDefault(); e.stopPropagation();
                                $wire.call('openImageModal', img.src);
                                return false;
                            };
                        };
                        const bindContainer = (container) => {
                            if (!container) return;
                            container.querySelectorAll('a').forEach(link => {
                                const img = link.querySelector('img');
                                const href = link.getAttribute('href');
                                if (img || isImageUrl(href)) {
                                    link.onclick = function(e) {
                                        e.preventDefault(); e.stopPropagation();
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
                        bindContainer(document.querySelector('.bug-description-content'));
                        document.querySelectorAll('.bug-comment-content').forEach(bindContainer);
                    });
                ">
                    <div style="display: flex; gap: 24px; border-bottom: 1px solid #E5E7EB;">
                        <button @click="activeTab = 'comments'"
                                :style="activeTab === 'comments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer;">
                            Comments
                        </button>
                        <button @click="activeTab = 'attachments'"
                                :style="activeTab === 'attachments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer;">
                            Attachments ({{ $attachments->count() }})
                        </button>
                        <button @click="activeTab = 'status'"
                                :style="activeTab === 'status' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="background: transparent; border: none; padding: 12px 4px; font-size: 14px; font-weight: 500; cursor: pointer;">
                            Status Log
                        </button>
                    </div>

                    <!-- Comments -->
                    <div x-show="activeTab === 'comments'" style="padding: 24px 0;">
                        <div x-data="{ showEditor: false }" style="margin-bottom: 24px;">
                            <div x-show="!showEditor">
                                <button @click="showEditor = true" type="button"
                                        style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;"
                                        onmouseover="this.style.background='#4F46E5'"
                                        onmouseout="this.style.background='#6366F1'">
                                    Add Comment
                                </button>
                            </div>
                            <div x-show="showEditor" x-transition>
                                <form wire:submit.prevent="addComment">
                                    {{ $this->form }}
                                    <div style="margin-top: 12px; display: flex; gap: 8px;">
                                        <button type="submit" style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Send</button>
                                        <button type="button" @click="showEditor = false" style="padding: 8px 20px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 14px;">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if ($comments->count() > 0)
                            <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;">
                                <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">Previous Comments</h4>
                            </div>
                            @foreach ($comments as $comment)
                                <div style="padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 12px; background: white;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #FEE2E2; color: #B91C1C; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->user?->name ?? '?', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div style="font-size: 13px; font-weight: 600; color: #111827;">{{ $comment->user?->name ?? 'Unknown' }}</div>
                                            <div style="font-size: 11px; color: #9CA3AF;">{{ $comment->created_at?->diffForHumans() }}@if ($comment->is_edited) · edited @endif</div>
                                        </div>
                                    </div>
                                    @php
                                        $commentHtml = $comment->comment ?? '';
                                        $commentHtml = preg_replace_callback(
                                            '/<figure\s+[^>]*data-trix-attachment="([^"]*)"[^>]*>.*?<\/figure>/s',
                                            function ($matches) use ($formatBytes) {
                                                $data = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true);
                                                if (!is_array($data) || empty($data['url'])) return $matches[0];
                                                $url = $data['url'];
                                                $filename = $data['filename'] ?? 'file';
                                                $contentType = $data['contentType'] ?? '';
                                                $sizeLabel = $formatBytes($data['filesize'] ?? 0);
                                                if (str_starts_with($contentType, 'image/')) {
                                                    return '<img src="' . e($url) . '" alt="' . e($filename) . '" style="max-width:100%;height:auto;border-radius:6px;margin:6px 0;" />';
                                                }
                                                $label = e($filename) . ($sizeLabel ? ' ' . e($sizeLabel) : '');
                                                return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer" data-file-link="true" style="display:block;color:#4F46E5;text-decoration:underline;margin:6px 0;">' . $label . '</a>';
                                            },
                                            $commentHtml
                                        );
                                        $commentHtml = preg_replace_callback(
                                            '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                                            function ($matches) { return route('s3.serve', ['path' => urldecode($matches[1])]); },
                                            $commentHtml
                                        );
                                        $commentHtml = preg_replace_callback(
                                            '/(src|href)="((?!https?:\/\/|\/)[^"]+\.(png|jpg|jpeg|gif|webp|bmp))"/i',
                                            function ($matches) { return $matches[1] . '="' . route('s3.serve', ['path' => $matches[2]]) . '"'; },
                                            $commentHtml
                                        );
                                    @endphp
                                    <div class="bug-comment-content" style="font-size: 14px; color: #374151; line-height: 1.5;">{!! $commentHtml !!}</div>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align: center; padding: 32px 0; color: #9CA3AF; font-size: 14px;">No comments yet</div>
                        @endif
                    </div>

                    <!-- Attachments -->
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

                    <!-- Status Log -->
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

            <!-- Right Side - Bug Information -->
            <aside style="background: #fafafa;">
                @php
                    $assigneeRole = '';
                    if ($assignees->isNotEmpty()) {
                        foreach (['RND', 'QC', 'PDT', 'FE'] as $r) {
                            foreach ($assignees as $a) {
                                if (collect($a->role_names ?? [])->contains(fn ($name) => stripos($name, $r) !== false)) {
                                    $assigneeRole = $r;
                                    break 2;
                                }
                            }
                        }
                    }

                    $labelStyle = 'font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;';
                    $valueStyle = 'font-size: 14px; color: #111827;';
                    $boxStyle = 'display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #fff; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; color: #111827;';
                    $chevronSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
                @endphp

                <div style="padding: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <h3 style="font-size: 14px; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Bug Information</h3>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="cursor: pointer;"><polyline points="9 18 15 12 9 6"/></svg>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin-bottom: 20px;"></div>

                    <!-- Status -->
                    <div style="margin-bottom: 20px;">
                        <div style="{{ $labelStyle }}">Status</div>
                        @if($bug->status === 'Ready for Testing' || $bug->status === 'Ready For Testing')
                            <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                                <button type="button" @click="open = !open" style="{{ $boxStyle }} cursor: pointer;">
                                    <span>{{ $bug->status }}</span>
                                    {!! $chevronSvg !!}
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity
                                     style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; padding: 4px;">
                                    <button type="button"
                                            wire:click="updateStatus('QC - In Progress')"
                                            @click="open = false"
                                            style="width: 100%; padding: 8px 12px; text-align: left; border: none; background: transparent; border-radius: 6px; font-size: 13px; color: #374151; cursor: pointer;"
                                            onmouseover="this.style.background='#F3F4F6'"
                                            onmouseout="this.style.background='transparent'">
                                        QC - In Progress
                                    </button>
                                </div>
                            </div>
                        @elseif($bug->status === 'QC - In Progress')
                            <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                                <button type="button" @click="open = !open" style="{{ $boxStyle }} cursor: pointer;">
                                    <span>{{ $bug->status }}</span>
                                    {!! $chevronSvg !!}
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity
                                     style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; padding: 4px;">
                                    <button type="button"
                                            wire:click="updateStatus('Ready For Live')"
                                            @click="open = false"
                                            style="width: 100%; padding: 8px 12px; text-align: left; border: none; background: transparent; border-radius: 6px; font-size: 13px; color: #374151; cursor: pointer;"
                                            onmouseover="this.style.background='#F3F4F6'"
                                            onmouseout="this.style.background='transparent'">
                                        Ready For Live
                                    </button>
                                    <button type="button"
                                            wire:click="updateStatus('Reopen')"
                                            @click="open = false"
                                            style="width: 100%; padding: 8px 12px; text-align: left; border: none; background: transparent; border-radius: 6px; font-size: 13px; color: #374151; cursor: pointer;"
                                            onmouseover="this.style.background='#F3F4F6'"
                                            onmouseout="this.style.background='transparent'">
                                        Reopen
                                    </button>
                                </div>
                            </div>
                        @else
                            <div style="{{ $valueStyle }}">{{ $bug->status ?? '-' }}</div>
                        @endif
                    </div>

                    <!-- Assignees -->
                    @php $currentAssigneeIds = array_map('intval', $bug->assignee_ids ?? []); @endphp
                    <div style="margin-bottom: 20px;">
                        <div style="{{ $labelStyle }}">Assignee(s){{ $assigneeRole ? ' ('.$assigneeRole.')' : '' }}</div>
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="width: 100%; min-height: 44px; padding: 8px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left;">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; min-width: 0;">
                                    @if ($assignees->isEmpty())
                                        <span style="color: #9CA3AF; font-size: 13px;">Select Assignee(s)</span>
                                    @else
                                        @foreach ($assignees as $a)
                                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 8px; background: #EEF2FF; color: #4F46E5; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                                {{ $a->name }}
                                                <span @click.stop="$wire.call('removeBugAssignee', {{ $a->id }})" style="cursor: pointer; line-height: 1; font-size: 14px; color: #4F46E5;">×</span>
                                            </span>
                                        @endforeach
                                    @endif
                                </div>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; display: flex; flex-direction: column;">
                                <div style="padding: 8px; border-bottom: 1px solid #F3F4F6;">
                                    <input type="text" x-model="search" @click.stop placeholder="Search users..."
                                           style="width: 100%; padding: 6px 10px; border: 1px solid #E5E7EB; border-radius: 6px; font-size: 13px; background: #F9FAFB;">
                                </div>
                                <ul style="max-height: 220px; overflow-y: auto; padding: 4px; margin: 0; list-style: none;">
                                    @forelse ($this->availableAssignees as $u)
                                        @php $isSel = in_array($u->id, $currentAssigneeIds, true); @endphp
                                        <li x-show="'{{ \Illuminate\Support\Str::lower(str_replace(["'", '"'], '', $u->name)) }}'.includes(search.toLowerCase())"
                                            wire:click="toggleBugAssignee({{ $u->id }})"
                                            @click.stop
                                            style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; cursor: pointer; border-radius: 6px;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border: 2px solid {{ $isSel ? '#6366F1' : '#D1D5DB' }}; background: {{ $isSel ? '#6366F1' : 'white' }}; border-radius: 4px; flex-shrink: 0;">
                                                @if ($isSel)<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>@endif
                                            </span>
                                            <span style="font-size: 13px; color: #111827;">{{ $u->name }}</span>
                                        </li>
                                    @empty
                                        <li style="padding: 12px; text-align: center; color: #9CA3AF; font-size: 12px;">
                                            No users have access to this product{{ $bug->module_id ? ' / module' : '' }}.
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Severity + Category -->
                    @php
                        $editableSelectStyle = "appearance: none; -webkit-appearance: none; width: 100%; padding: 12px 36px 12px 14px; background: #fff url(\"data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'><polyline points='6 9 12 15 18 9'/></svg>\") no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; color: #111827; cursor: pointer;";
                    @endphp
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div>
                            <div style="{{ $labelStyle }}">Severity</div>
                            <select wire:change="updateSeverity($event.target.value)" style="{{ $editableSelectStyle }}">
                                @foreach ($this->severityOptions as $sev)
                                    <option value="{{ $sev }}" @selected($bug->severity === $sev)>{{ $sev }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div style="{{ $labelStyle }}">Category</div>
                            <select wire:change="updateCategory($event.target.value)" style="{{ $editableSelectStyle }}">
                                @foreach ($this->categoryOptions as $cat)
                                    <option value="{{ $cat->id }}" @selected($bug->category_id === $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin-bottom: 20px;"></div>

                    <!-- Product + Module -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div>
                            <div style="{{ $labelStyle }}">Product</div>
                            <div style="{{ $valueStyle }}">{{ $bug->product?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="{{ $labelStyle }}">Module</div>
                            <div style="{{ $valueStyle }}">{{ $bug->module?->name ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #E5E7EB; margin-bottom: 20px;"></div>

                    <!-- Reporter + Reported Date -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <div style="{{ $labelStyle }}">Reporter</div>
                            <div style="{{ $valueStyle }}">{{ $reporter?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="{{ $labelStyle }}">Reported Date</div>
                            <div style="{{ $valueStyle }}">{{ $bug->submission_date?->format('M j, Y, h:i A') ?? $bug->created_at?->format('M j, Y, h:i A') ?? '-' }}</div>
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
            style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: #111827; cursor: pointer; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 10000000;">
        ✕
    </button>
    <div style="max-width: 95%; max-height: 95%; display: flex; align-items: center; justify-content: center;" wire:click.stop>
        <img src="{{ $selectedImageUrl }}" alt="Preview" style="max-width: 100%; max-height: 90vh; object-fit: contain; border-radius: 8px;">
    </div>
</div>
@endif
