{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/partials/ticket-modal.blade.php --}}
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000001; display: flex; align-items: center; justify-content: center;"
    wire:click="closeTicketModal">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
        wire:click.stop
        x-data="{ rightPanelCollapsed: false, showCloseConfirm: false }">

        <!-- Modal Header -->
        <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #FEF3C7; padding: 8px 12px; border-radius: 6px;">
                    <span style="color: #F59E0B; font-size: 14px; font-weight: 600;">📋 TICKET</span>
                </div>
                <h2 onclick="navigator.clipboard.writeText('https://dt.timeteccloud.com/ticket/{{ $selectedTicket->ticket_id }}').then(function() { window.dispatchEvent(new CustomEvent('ticket-link-copied')); });"
                    style="font-size: 18px; font-weight: 700; color: #111827; margin: 0; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                    onmouseover="this.style.color='#6366F1'"
                    onmouseout="this.style.color='#111827'"
                    title="Click to copy ticket link">
                    {{ $selectedTicket->ticket_id }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                    </svg>
                </h2>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                @if(in_array($selectedTicket->status, ['New', 'In Progress']) && in_array($selectedTicket->priority->sort_order ?? 0, [1, 2]))
                <button wire:click="openOnHoldModal"
                    style="background: #FEF3C7; border: 1px solid #F59E0B; color: #92400E; cursor: pointer; font-size: 13px; font-weight: 600; padding: 6px 14px; border-radius: 6px; display: flex; align-items: center; gap: 6px;"
                    onmouseover="this.style.background='#FDE68A'"
                    onmouseout="this.style.background='#FEF3C7'">
                    ⏸ On Hold
                </button>
                @endif
                @if($selectedTicket->status === 'On Hold' && in_array($selectedTicket->priority->sort_order ?? 0, [1, 2]))
                <button @click="showCloseConfirm = true"
                    style="background: #FEE2E2; border: 1px solid #FECACA; color: #991B1B; cursor: pointer; font-size: 13px; font-weight: 600; padding: 6px 14px; border-radius: 6px; display: flex; align-items: center; gap: 6px;"
                    onmouseover="this.style.background='#FECACA'"
                    onmouseout="this.style.background='#FEE2E2'">
                    ✕ Close Ticket
                </button>
                @endif
                <button wire:click="closeTicketModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">
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
                    {{ $selectedTicket->title }}
                </h1>

                <!-- Description Section -->
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

                        $descriptionHtml = $selectedTicket->description ?? 'No description provided.';

                        // Convert Trix attachment figures into inline images or clickable file links
                        $descriptionHtml = preg_replace_callback(
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
                            $descriptionHtml
                        );

                        // Rewrite direct S3 URLs to internal s3.serve route
                        $descriptionHtml = preg_replace_callback(
                            '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                            function ($matches) {
                                $path = urldecode($matches[1]);
                                return route('s3.serve', ['path' => $path]);
                            },
                            $descriptionHtml
                        );
                    @endphp
                    <div class="ticket-description-content" style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB; line-height: 1.6; color: #374151; max-height: 300px; overflow-y: auto;">
                        {!! $descriptionHtml !!}
                    </div>
                </div>

                <!-- Tabs -->
                <div x-data="{ activeTab: 'comments' }" x-init="
                    $nextTick(() => {
                        // Helper function to check if URL is an image
                        const isImageUrl = (href) => {
                            if (!href) return false;
                            // Check for storage URLs
                            if (href.includes('storage')) return true;
                            // Check for S3 file serve route (actual route is /s3-file)
                            if (href.includes('s3-file') || href.includes('s3/serve') || href.includes('s3-serve')) return true;
                            // Check for S3 direct URLs
                            if (href.includes('s3.amazonaws.com')) return true;
                            // Check for common image extensions in URL or query params
                            const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
                            return imageExtensions.some(ext => href.toLowerCase().includes(ext));
                        };

                        // Helper function to setup click handler on an image element
                        const setupImageClickHandler = (img) => {
                            if (img.dataset.zoomSetup) return; // Prevent duplicate handlers
                            img.dataset.zoomSetup = 'true';
                            img.style.cursor = 'zoom-in';
                            img.onclick = function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                $wire.call('openImageModal', img.src);
                                return false;
                            };
                        };

                        // Setup image click handlers for description
                        const descriptionDiv = $el.closest('[style*=\'overflow-y: auto\']').querySelector('.ticket-description-content');
                        if (descriptionDiv) {
                            // Handle images inside <a> tags
                            const imageLinks = descriptionDiv.querySelectorAll('a');
                            imageLinks.forEach(link => {
                                if (link.dataset.fileLink === 'true') return;
                                const img = link.querySelector('img');
                                const href = link.getAttribute('href');
                                if (img || isImageUrl(href)) {
                                    link.onclick = function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        const imageUrl = img ? img.src : href;
                                        $wire.call('openImageModal', imageUrl);
                                        return false;
                                    };
                                    link.style.cursor = 'zoom-in';
                                    if (img) img.style.cursor = 'zoom-in';
                                }
                            });

                            // Handle standalone images (not inside <a> tags) - for reopen uploads
                            const standaloneImages = descriptionDiv.querySelectorAll('img');
                            standaloneImages.forEach(img => {
                                if (!img.closest('a')) {
                                    setupImageClickHandler(img);
                                }
                            });
                        }

                        // Setup image click handlers for comments
                        const commentDivs = document.querySelectorAll('.ticket-comment-content');
                        commentDivs.forEach(commentDiv => {
                            // Handle images inside <a> tags
                            const imageLinks = commentDiv.querySelectorAll('a');
                            imageLinks.forEach(link => {
                                if (link.dataset.fileLink === 'true') return;
                                const img = link.querySelector('img');
                                const href = link.getAttribute('href');
                                if (img || isImageUrl(href)) {
                                    link.onclick = function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        const imageUrl = img ? img.src : href;
                                        $wire.call('openImageModal', imageUrl);
                                        return false;
                                    };
                                    link.style.cursor = 'zoom-in';
                                    if (img) img.style.cursor = 'zoom-in';
                                }
                            });

                            // Handle standalone images (not inside <a> tags) - for reopen uploads
                            const standaloneImages = commentDiv.querySelectorAll('img');
                            standaloneImages.forEach(img => {
                                if (!img.closest('a')) {
                                    setupImageClickHandler(img);
                                }
                            });
                        });
                    });
                " style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 24px; border-bottom: 2px solid #F3F4F6;">
                        <button @click="activeTab = 'comments'"
                                :style="activeTab === 'comments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Comments
                        </button>
                        <button @click="activeTab = 'attachments'"
                                :style="activeTab === 'attachments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Attachments ({{ $selectedTicket->attachments->count() }})
                        </button>
                        <button @click="activeTab = 'status'"
                                :style="activeTab === 'status' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Status Log
                        </button>
                        <button @click="activeTab = 'internal'"
                                :style="activeTab === 'internal' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Internal Discussion
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

                        @php
                            $publicComments = $selectedTicket->comments->filter(fn($c) => !$c->is_internal);
                        @endphp
                        @if($publicComments->count() > 0)
                            <div style="margin-top: 24px;">
                                <div x-data="{ commentSort: '{{ $commentSort ?? 'desc' }}' }" style="margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;">
                                        <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">
                                            Previous Comments
                                        </h4>
                                        <button
                                            @click="commentSort = commentSort === 'desc' ? 'asc' : 'desc'; $wire.set('commentSort', commentSort)"
                                            style="display: flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 12px; font-weight: 500; color: #6366F1; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 6px; cursor: pointer;">
                                            <span x-text="commentSort === 'desc' ? 'Newest First' : 'Oldest First'"></span>
                                            <svg x-show="commentSort === 'desc'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                                            <svg x-show="commentSort === 'asc'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                        </button>
                                    </div>
                                </div>
                                @foreach(($commentSort ?? 'desc') === 'asc' ? $publicComments->sortBy('created_at')->values() : $publicComments->sortByDesc('created_at')->values() as $commentIndex => $comment)
                                    <div style="margin-bottom: 20px; padding: 16px; background: #F9FAFB; border-radius: 8px; border-left: 3px solid #6366F1;">
                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #6366F1; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                {{ ($commentSort ?? 'desc') === 'asc' ? $commentIndex + 1 : $publicComments->count() - $commentIndex }}
                                            </div>
                                            <div style="flex: 1; display: flex; align-items: center; justify-content: space-between;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span style="font-weight: 600; font-size: 14px; color: #111827;">
                                                        {{ $comment->user->name ?? 'Unknown User' }}
                                                    </span>
                                                    <span style="padding: 2px 8px; background: #E0E7FF; color: #4F46E5; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                                        {{ $comment->user->role ?? 'HRcrm User' }}
                                                    </span>
                                                </div>
                                                <div style="font-size: 12px; color: #9CA3AF;">
                                                    {{ $comment->created_at->addHours(8)->format('d M Y, h:iA') }} ({{ $comment->created_at->addHours(8)->diffForHumans() }})
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ticket-comment-content" style="color: #374151; margin: 0; font-size: 14px; line-height: 1.6;">
                                            {!! preg_replace_callback(
                                                '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                                                function ($matches) {
                                                    $path = urldecode($matches[1]);
                                                    return route('s3.serve', ['path' => $path]);
                                                },
                                                $comment->comment
                                            ) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">💬</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No comments yet</p>
                            </div>
                        @endif
                    </div>

                    <!-- Attachments Tab -->
                    <div x-show="activeTab === 'attachments'" style="padding: 24px 0;">
                        @if($selectedTicket->device_type === 'Mobile' && $selectedTicket->version_screenshot)
                            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Version Screenshot</h3>

                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 24px; border-left: 3px solid #6366F1;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 14px; color: #111827;">App Version Screenshot</div>
                                    <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">Mobile App Version</div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    @php
                                        $screenshotUrl = str_starts_with($selectedTicket->version_screenshot, 'http')
                                            ? $selectedTicket->version_screenshot
                                            : \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($selectedTicket->version_screenshot, now()->addMinutes(60));
                                    @endphp
                                    <a href="{{ $screenshotUrl }}"
                                       target="_blank"
                                       style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        View
                                    </a>
                                </div>
                            </div>

                            <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                        @endif

                        @if($selectedTicket->attachments->count() > 0)
                            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Current Attachments</h3>

                            @foreach($selectedTicket->attachments as $attachment)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 8px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; font-size: 14px; color: #111827;">{{ $attachment->original_filename }}</div>
                                        <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                                            {{ number_format($attachment->file_size / 1024, 2) }} KB • {{ $attachment->created_at->addHours(8)->format('d M Y, H:i A') }}
                                            <br>
                                            <span style="font-style: italic;">by {{ $attachment->uploader->name ?? 'Unknown' }}</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        @php
                                            $attachUrl = str_starts_with($attachment->file_path, 'http')
                                                ? $attachment->file_path
                                                : \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($attachment->file_path, now()->addMinutes(60));
                                        @endphp
                                        <a href="{{ $attachUrl }}"
                                           target="_blank"
                                           style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            View
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                        @else
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📎</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No attachments yet</p>
                            </div>
                        @endif

                        <!-- Upload Section -->
                        <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Upload New Attachments</h3>

                        <div style="margin-bottom: 16px;">
                            <input type="file"
                                   wire:model="attachments"
                                   multiple
                                   style="width: 100%; padding: 12px; border: 2px dashed #E5E7EB; border-radius: 8px; cursor: pointer;"
                                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">

                            @error('attachments.*')
                                <div style="color: #DC2626; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                            @enderror

                            <div wire:loading wire:target="attachments" style="margin-top: 12px; color: #6366F1; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="0.75"></path>
                                </svg>
                                Uploading files...
                            </div>
                        </div>

                        @if(!empty($attachments))
                            <div style="margin-bottom: 16px; padding: 12px; background: #F9FAFB; border-radius: 8px;">
                                <div style="font-size: 13px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">
                                    Selected Files ({{ count($attachments) }})
                                </div>
                                @foreach($attachments as $file)
                                    <div style="font-size: 12px; color: #374151; padding: 4px 0;">
                                        • {{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button wire:click="uploadAttachments"
                                @if(empty($attachments)) disabled @endif
                                style="width: 100%; padding: 12px; background: {{ empty($attachments) ? '#D1D5DB' : '#6366F1' }}; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: {{ empty($attachments) ? 'not-allowed' : 'pointer' }}; transition: all 0.2s;">
                            Upload Files
                        </button>
                    </div>

                    <!-- Status Log Tab (copy from ticket-dashboard.blade.php) -->
                    <div x-show="activeTab === 'status'" style="padding: 24px 0;">
                        @php
                            $filteredLogs = $selectedTicket->logs->filter(function($log) {
                                return !is_null($log->new_value);
                            })->sortByDesc('created_at');
                        @endphp
                        @if($filteredLogs->count() > 0)
                            <div style="position: relative;">
                                @foreach($filteredLogs as $index => $log)
                                    <div style="display: flex; gap: 16px; margin-bottom: {{ $index < $selectedTicket->logs->count() - 1 ? '24px' : '0' }};">
                                        {{-- Timeline Connector --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; position: relative;">
                                            {{-- Timeline Dot --}}
                                            <div style="width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; background:
                                                @if($log->new_value === 'Completed') #F97316
                                                @elseif($log->new_value === 'Reopen') white
                                                @elseif($log->new_value === 'Closed' || $log->new_value === 'Closed System Configuration') #F97316
                                                @elseif($log->new_value === 'In Progress') white
                                                @elseif($log->new_value === 'New') white
                                                @else white
                                                @endif;
                                                border: 2px solid
                                                @if($log->new_value === 'Completed') #F97316
                                                @elseif($log->new_value === 'Reopen') #9CA3AF
                                                @elseif($log->new_value === 'Closed' || $log->new_value === 'Closed System Configuration') #F97316
                                                @elseif($log->new_value === 'In Progress') #9CA3AF
                                                @elseif($log->new_value === 'New') #9CA3AF
                                                @else #9CA3AF
                                                @endif;"></div>

                                            {{-- Timeline Line --}}
                                            @if($index < $filteredLogs->count() - 1)
                                                <div style="width: 2px; background: #E5E7EB; flex: 1; margin-top: 4px; min-height: 40px;"></div>
                                            @endif
                                        </div>

                                        {{-- Status Content --}}
                                        <div style="flex: 1; padding-bottom: 8px;">
                                            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 8px;">
                                                <div class="status-badge-wrapper" style="position: relative; display: inline-block;">
                                                    <span style="padding: 4px 12px; background:
                                                        @if($log->new_value === 'Completed') #FEF3C7
                                                        @elseif($log->new_value === 'Reopen') #F3F4F6
                                                        @elseif($log->new_value === 'Closed System Configuration') #FEF3C7
                                                        @elseif($log->new_value === 'In Progress') #FEF3C7
                                                        @elseif($log->new_value === 'New') #F3F4F6
                                                        @else #F3F4F6
                                                        @endif;
                                                        color:
                                                        @if($log->new_value === 'Completed') #D97706
                                                        @elseif($log->new_value === 'Reopen') #6B7280
                                                        @elseif($log->new_value === 'Closed System Configuration') #D97706
                                                        @elseif($log->new_value === 'In Progress') #D97706
                                                        @elseif($log->new_value === 'New') #6B7280
                                                        @else #6B7280
                                                        @endif;
                                                        border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid
                                                        @if($log->new_value === 'Completed') #FDE047
                                                        @elseif($log->new_value === 'Reopen') #E5E7EB
                                                        @elseif($log->new_value === 'Closed System Configuration') #FDE047
                                                        @elseif($log->new_value === 'In Progress') #FDE047
                                                        @elseif($log->new_value === 'New') #E5E7EB
                                                        @else #E5E7EB
                                                        @endif; @if($log->change_type === 'ticket_creation') display: inline-block; text-align: left; @endif">
                                                        @if($log->change_type === 'ticket_creation')
                                                            {!! nl2br(e($log->new_value)) !!}
                                                        @else
                                                            {{ $log->new_value }}
                                                        @endif
                                                    </span>
                                                    <div class="status-tooltip" style="position: absolute; bottom: 100%; left: 0; margin-bottom: 8px; padding: 6px 10px; background: #1F2937; color: white; font-size: 11px; border-radius: 6px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 10;">
                                                        {{ $log->created_at->addHours(8)->format('M d') }} • {{ $log->created_at->addHours(8)->format('Y, g:i A') }}
                                                    </div>
                                                </div>

                                                @if($index === 0)
                                                    @php
                                                        $createdAt = $log->created_at;
                                                        $now = now();
                                                        $diff = $createdAt->diff($now);
                                                        $elapsed = '';
                                                        if ($diff->d > 0) $elapsed .= $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ';
                                                        if ($diff->h > 0) $elapsed .= $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ';
                                                        if ($diff->i > 0) $elapsed .= $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ';
                                                        $elapsed .= $diff->s . ' sec' . ($diff->s > 1 ? 's' : '');
                                                    @endphp
                                                    <div style="display: flex; align-items: center; gap: 4px; color: #3B82F6; font-size: 12px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <polyline points="12 6 12 12 16 14"></polyline>
                                                        </svg>
                                                        <span>Elapsed: {{ trim($elapsed) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div style="font-size: 13px; color: #6B7280;">
                                                Updated by {{ $log->user_name ?? 'Unknown User' }} - {{ $log->user_role ?? 'User' }}
                                            </div>
                                            @if($log->new_value === 'On Hold' && $log->change_reason)
                                            <div style="margin-top: 8px; background: #FEF3C7; border-left: 3px solid #F59E0B; border-radius: 4px; padding: 8px 12px;">
                                                <div style="font-size: 11px; font-weight: 700; color: #92400E; margin-bottom: 4px;">On Hold Reason</div>
                                                <div style="font-size: 13px; color: #78350F; line-height: 1.5;">
                                                    {!! html_entity_decode($log->change_reason) !!}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📋</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No status changes recorded</p>
                            </div>
                        @endif
                    </div>

                    <!-- Internal Discussion Tab -->
                    <div x-show="activeTab === 'internal'" style="padding: 24px 0;">
                        <div x-data="{ showEditor: false }" style="margin-bottom: 24px;">
                            <div x-show="!showEditor">
                                <button @click="showEditor = true" type="button"
                                        style="padding: 8px 20px; background: #DC2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                        onmouseover="this.style.background='#B91C1C'"
                                        onmouseout="this.style.background='#DC2626'">
                                    Add Internal Comment
                                </button>
                            </div>
                            <div x-show="showEditor" x-transition>
                                <form wire:submit.prevent="addInternalComment">
                                    {{ $this->internalForm }}

                                    <div style="margin-top: 12px; display: flex; gap: 8px;">
                                        <button type="submit"
                                                style="padding: 8px 20px; background: #DC2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                                onmouseover="this.style.background='#B91C1C'"
                                                onmouseout="this.style.background='#DC2626'">
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

                        @php
                            $internalComments = $selectedTicket->comments->filter(fn($c) => $c->is_internal);
                        @endphp
                        @if($internalComments->count() > 0)
                            <div style="margin-top: 24px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB; margin-bottom: 16px;">
                                    <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin: 0;">
                                        Previous Internal Comments
                                    </h4>
                                </div>
                                @foreach($internalComments->sortByDesc('created_at')->values() as $commentIndex => $comment)
                                    <div style="margin-bottom: 20px; padding: 16px; background: #FEF2F2; border-radius: 8px; border-left: 3px solid #DC2626;">
                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #DC2626; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                {{ $internalComments->count() - $commentIndex }}
                                            </div>
                                            <div style="flex: 1; display: flex; align-items: center; justify-content: space-between;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span style="font-weight: 600; font-size: 14px; color: #111827;">
                                                        {{ $comment->user->name ?? 'Unknown User' }}
                                                    </span>
                                                    <span style="padding: 2px 8px; background: #FEE2E2; color: #DC2626; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                                        Internal
                                                    </span>
                                                </div>
                                                <div style="font-size: 12px; color: #9CA3AF;">
                                                    {{ $comment->created_at->addHours(8)->format('d M Y, h:iA') }} ({{ $comment->created_at->addHours(8)->diffForHumans() }})
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ticket-comment-content" style="color: #374151; margin: 0; font-size: 14px; line-height: 1.6;">
                                            {!! preg_replace_callback(
                                                '/https:\/\/[^\/]+\.s3[^\/]*\.amazonaws\.com\/([^\?"]+)(\?[^"]*)?/',
                                                function ($matches) {
                                                    $path = urldecode($matches[1]);
                                                    return route('s3.serve', ['path' => $path]);
                                                },
                                                $comment->comment
                                            ) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">🔒</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No internal discussions yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side - Other Information (copy from ticket-dashboard.blade.php) -->
            <div style="background: #F9FAFB; transition: all 0.3s ease; padding: 24px;"
                 x-bind:style="{ padding: rightPanelCollapsed ? '24px 8px' : '24px' }">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 x-show="!rightPanelCollapsed" style="font-size: 14px; font-weight: 600; color: #6B7280; margin: 0;">Other Information</h3>
                    <button @click="rightPanelCollapsed = !rightPanelCollapsed"
                            style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; transition: all 0.2s;"
                            x-bind:style="{ margin: rightPanelCollapsed ? '0 auto' : '' }"
                            onmouseover="this.style.background='#F3F4F6'; this.style.borderColor='#9CA3AF'"
                            onmouseout="this.style.background='white'; this.style.borderColor='#E5E7EB'"
                            x-bind:title="rightPanelCollapsed ? 'Expand panel' : 'Collapse panel'">
                        <svg x-show="!rightPanelCollapsed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6B7280;">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                        <svg x-show="rightPanelCollapsed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6B7280;">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                </div>

                <div x-show="!rightPanelCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <!-- Priority -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Priority</div>
                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->priority->name ?? $selectedTicket->priority ?? '-' }}</div>
                </div>

                <!-- Status -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Status</div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <!-- Current Status Badge -->
                        <span style="padding: 4px 12px; background:
                            @if($selectedTicket->status === 'Completed') #FEF3C7
                            @elseif($selectedTicket->status === 'New') #F3F4F6
                            @elseif($selectedTicket->status === 'In Progress') #FEF3C7
                            @elseif($selectedTicket->status === 'Closed') #FEE2E2
                            @elseif($selectedTicket->status === 'Reopen') #FEF3C7
                            @else #F3F4F6
                            @endif;
                            color:
                            @if($selectedTicket->status === 'Completed') #D97706
                            @elseif($selectedTicket->status === 'New') #6B7280
                            @elseif($selectedTicket->status === 'In Progress') #D97706
                            @elseif($selectedTicket->status === 'Closed') #DC2626
                            @elseif($selectedTicket->status === 'Reopen') #D97706
                            @else #6B7280
                            @endif;
                            border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid
                            @if($selectedTicket->status === 'Completed') #FDE047
                            @elseif($selectedTicket->status === 'New') #E5E7EB
                            @elseif($selectedTicket->status === 'In Progress') #FDE047
                            @elseif($selectedTicket->status === 'Closed') #FECACA
                            @elseif($selectedTicket->status === 'Reopen') #FDE047
                            @else #E5E7EB
                            @endif;">
                            {{ $selectedTicket->status ?? '-' }}
                        </span>
                        @if($selectedTicket->pending_party && in_array($selectedTicket->status, ['New', 'In Progress']))
                            <span style="font-size: 11px; color: #92400E; font-weight: 500;">{{ $selectedTicket->pending_party }}</span>
                        @endif

                        <!-- Edit Icon & Dropdown - Show for Completed, Live and Closed status -->
                        @if(in_array($selectedTicket->status, ['Completed', 'Live', 'Closed System Configuration']))
                            <div style="position: relative;" x-data="{ open: false }">
                                <button @click="open = !open"
                                        style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: white; border: 1px solid #D1D5DB; border-radius: 6px; cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.background='#F9FAFB'; this.style.borderColor='#9CA3AF'"
                                        onmouseout="this.style.background='white'; this.style.borderColor='#D1D5DB'"
                                        title="Change Status">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #6B7280;">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                </button>

                                <!-- Dropdown Menu - ONLY Reopen and Closed options -->
                                <div x-show="open"
                                    @click.outside="open = false"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    style="position: absolute; top: 100%; right: 0; z-index: 50; margin-top: 4px; background: white; border: 1px solid #E5E7EB; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); min-width: 180px;">

                                    <!-- Reopen Option -->
                                    <button wire:click="openReopenModal({{ $selectedTicket->id }})"
                                            @click="open = false"
                                            style="width: 100%; padding: 10px 12px; text-align: left; border: none; background: transparent; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                            onmouseover="this.style.background='#FEF3C7'"
                                            onmouseout="this.style.background='transparent'">
                                        <span style="padding: 2px 8px; background: #FEF3C7; color: #D97706; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid #FDE047;">
                                            Reopen
                                        </span>
                                        <span style="color: #D97706; font-size: 12px; font-weight: 600;">Reopen</span>
                                    </button>

                                    <!-- Closed Option -->
                                    <button wire:click="updateTicketStatus({{ $selectedTicket->id }}, 'Closed')"
                                            @click="open = false"
                                            style="width: 100%; padding: 10px 12px; text-align: left; border: none; background: transparent; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                                            onmouseover="this.style.background='#F9FAFB'"
                                            onmouseout="this.style.background='transparent'">
                                        <span style="padding: 2px 8px; background: #FEE2E2; color: #DC2626; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid #FECACA;">
                                            Closed
                                        </span>
                                        <span style="color: #6B7280; font-size: 12px;">Close ticket</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if(in_array(strtolower(trim((string) $selectedTicket->status)), ['pending mandays', 'mandays updated', 'closed']))
                    @php
                        $estimatedPdtMandays = (float) ($selectedTicket->estimated_pdt_mandays ?? 0);
                        $estimatedRndMandays = (float) ($selectedTicket->estimated_rnd_mandays ?? 0);
                        $totalEstimatedMandays = $estimatedPdtMandays + $estimatedRndMandays;
                        $pdtEditable = strtolower(trim((string) $selectedTicket->status)) === 'pending mandays';
                    @endphp
                    <div style="margin-bottom: 16px; padding: 10px 12px; background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border: 1px solid #E2E8F0; border-radius: 10px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);">
                        <div style="display: grid; grid-template-columns: 1.25fr 1fr; gap: 14px; align-items: stretch;">
                            <div>
                                <div style="font-size: 10px; color: #94A3B8; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700;">Estimated Mandays</div>
                                <div style="display: flex; gap: 24px; align-items: center; margin-bottom: 2px;">
                                    <span style="font-size: 11px; color: #64748B; font-weight: 700;">PDT</span>
                                    <span style="font-size: 11px; color: #64748B; font-weight: 700;">RND</span>
                                </div>
                                <div style="display: flex; gap: 20px; align-items: flex-start;">
                                    @if ($pdtEditable)
                                        <div x-data="{ pdtVal: '{{ $estimatedPdtMandays > 0 ? ($estimatedPdtMandays + 0) : '' }}', saving: false }"
                                             style="display: inline-flex; flex-direction: column; gap: 4px;">
                                            <style>
                                                .pdt-mandays-input::-webkit-outer-spin-button,
                                                .pdt-mandays-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
                                                .pdt-mandays-input { -moz-appearance: textfield; appearance: textfield; }
                                            </style>
                                            <input type="number" min="0.5" step="0.5" x-model="pdtVal"
                                                   class="pdt-mandays-input"
                                                   @keydown.enter.prevent="if (parseFloat(pdtVal) > 0) { saving = true; $wire.call('updatePdtMandays', pdtVal).finally(() => saving = false); }"
                                                   placeholder="0"
                                                   style="width: 36px; padding: 4px 8px; font-size: 16px; color: #1E3A8A; font-weight: 700; background: #fff; border: 1px solid #CBD5E1; border-radius: 6px; outline: none;">
                                            <button type="button"
                                                    :disabled="saving || !(parseFloat(pdtVal) > 0)"
                                                    @click="saving = true; $wire.call('updatePdtMandays', pdtVal).finally(() => saving = false);"
                                                    :style="(saving || !(parseFloat(pdtVal) > 0)) ? 'width: 56px; padding: 3px 6px; background: #CBD5E1; color: #fff; border: none; border-radius: 5px; font-size: 10px; font-weight: 600; cursor: not-allowed;' : 'width: 56px; padding: 3px 6px; background: #2563EB; color: #fff; border: none; border-radius: 5px; font-size: 10px; font-weight: 600; cursor: pointer;'">
                                                <span x-show="!saving">Save</span>
                                                <span x-show="saving" x-cloak>...</span>
                                            </button>
                                        </div>
                                    @else
                                        <span style="font-size: 18px; color: #1E3A8A; font-weight: 700; line-height: 1; min-width: 56px;">{{ $estimatedPdtMandays + 0 }}</span>
                                    @endif
                                    <span style="font-size: 18px; color: #1E3A8A; font-weight: 700; line-height: 1;">{{ $estimatedRndMandays + 0 }}</span>
                                </div>
                            </div>

                            <div style="border-left: 1px solid #E2E8F0; padding-left: 12px; display: flex; flex-direction: column; justify-content: center;">
                                <div style="font-size: 10px; color: #94A3B8; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700;">Total Estimated Mandays</div>
                                <div style="font-size: 18px; color: #1E3A8A; font-weight: 700; line-height: 1;">{{ $totalEstimatedMandays + 0 }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Assignees</div>
                    <div style="font-weight: 500; color: #111827; font-size: 14px;">
                        @php
                            // Get tasks related to this ticket
                            $relatedTasks = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                ->table('tasks')
                                ->where('related_ticket_id', $selectedTicket->id)
                                ->pluck('assignee_ids')
                                ->toArray();

                            $allAssigneeIds = [];

                            // Process each task's assignee_ids (which are JSON encoded)
                            foreach ($relatedTasks as $assigneeIdsJson) {
                                if (!empty($assigneeIdsJson)) {
                                    $assigneeIds = json_decode($assigneeIdsJson, true);
                                    if (is_array($assigneeIds)) {
                                        $allAssigneeIds = array_merge($allAssigneeIds, $assigneeIds);
                                    }
                                }
                            }

                            // Remove duplicates
                            $allAssigneeIds = array_unique($allAssigneeIds);

                            // Get assignee names if we have IDs
                            $assignees = [];
                            if (!empty($allAssigneeIds)) {
                                $assignees = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                    ->table('users')
                                    ->whereIn('id', $allAssigneeIds)
                                    ->pluck('name')
                                    ->toArray();
                            }
                        @endphp

                        @if(count($assignees) > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                @foreach($assignees as $assignee)
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #EEF2FF; color: #4338CA; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #C7D2FE;">
                                        {{ $assignee }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span style="color: #9CA3AF; font-style: italic; font-size: 13px;">Not assigned</span>
                        @endif
                    </div>
                </div>

                <!-- ETA Release Date & Live Release Date -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">ETA Release Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">
                            @php
                                // Get ETA release dates from tasks related to this ticket
                                $relatedTaskEtaDates = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                    ->table('tasks')
                                    ->where('related_ticket_id', $selectedTicket->id)
                                    ->whereNotNull('eta_release')
                                    ->pluck('eta_release')
                                    ->filter()
                                    ->toArray();

                                $earliestEtaDate = null;
                                if (!empty($relatedTaskEtaDates)) {
                                    // Find the earliest ETA release date
                                    $earliestEtaDate = min($relatedTaskEtaDates);
                                }
                            @endphp
                            {{ $earliestEtaDate ? \Carbon\Carbon::parse($earliestEtaDate)->addHours(8)->format('M d, Y') : '-' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Live Release Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">
                            @php
                                // Get live release dates from tasks related to this ticket
                                $relatedTaskLiveDates = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                    ->table('tasks')
                                    ->where('related_ticket_id', $selectedTicket->id)
                                    ->whereNotNull('live_release')
                                    ->pluck('live_release')
                                    ->filter()
                                    ->toArray();

                                $earliestLiveDate = null;
                                if (!empty($relatedTaskLiveDates)) {
                                    // Find the earliest live release date
                                    $earliestLiveDate = min($relatedTaskLiveDates);
                                }
                            @endphp
                            {{ $earliestLiveDate ? \Carbon\Carbon::parse($earliestLiveDate)->addHours(8)->format('M d, Y') : '-' }}
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                <!-- Product & Module -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Product</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->product->name ?? $selectedTicket->product ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Module</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->module->name ?? $selectedTicket->module ?? '-' }}</div>
                    </div>
                </div>

                <!-- Company Name & Zoho ID -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Company Name</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->company_name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Zoho ID</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->zoho_id ?? 'N/A' }}</div>
                    </div>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                <!-- Device Type, Browser Type & Windows Version -->
                @if($selectedTicket->device_type === 'Browser')
                    <!-- ✅ BROWSER SPECIFIC FIELDS -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Browser Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->browser_type ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Windows Version</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->windows_version ?? '-' }}</div>
                    </div>
                @elseif($selectedTicket->device_type === 'Mobile')
                    <!-- ✅ MOBILE SPECIFIC FIELDS -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Mobile Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->mobile_type ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device ID</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_id ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">OS Version</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->os_version ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">App Version</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->app_version ?? '-' }}</div>
                    </div>

                    @if($selectedTicket->version_screenshot)
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Version Screenshot</div>
                            @php
                                $versionScreenshotUrl = str_starts_with($selectedTicket->version_screenshot, 'http')
                                    ? $selectedTicket->version_screenshot
                                    : \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($selectedTicket->version_screenshot, now()->addMinutes(60));
                            @endphp
                            <a href="{{ $versionScreenshotUrl }}" target="_blank"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #6366F1; font-size: 13px; font-weight: 500; transition: all 0.2s;"
                            onmouseover="this.style.background='#F3F4F6'"
                            onmouseout="this.style.background='white'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                View Screenshot
                            </a>
                        </div>
                    @endif
                @else
                    <!-- ✅ FALLBACK if device_type is not set -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">-</div>
                    </div>
                @endif

                <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                <!-- Zoho Ticket Number & Created Date -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Requester</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->requestor->name ?? $selectedTicket->created_by ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Created Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->created_at ? $selectedTicket->created_at->addHours(8)->format('M d, Y') : '-' }}</div>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Close Ticket Confirmation Modal -->
        <div :class="showCloseConfirm ? '' : 'close-confirm-hidden'"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; display: flex; align-items: center; justify-content: center;"
             @click.self="showCloseConfirm = false">
            <div x-show="showCloseConfirm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 style="background: white; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 20px 60px -10px rgba(0,0,0,0.3); overflow: hidden; margin: 16px;">

                <!-- Header -->
                <div style="padding: 20px 24px 0 24px; display: flex; align-items: flex-start; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <!-- Danger Icon -->
                        <div style="flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; background: #FEE2E2; display: flex; align-items: center; justify-content: center;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;">Close Ticket</h3>
                    </div>
                    <button @click="showCloseConfirm = false"
                            style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; margin-top: 2px; transition: color 0.15s;"
                            onmouseover="this.style.color='#374151'"
                            onmouseout="this.style.color='#9CA3AF'">
                        ✕
                    </button>
                </div>

                <!-- Body -->
                <div style="padding: 16px 24px 24px 24px;">
                    <p style="font-size: 14px; color: #374151; margin: 0 0 6px 0; line-height: 1.6;">
                        Are you sure you want to close ticket
                        <span style="font-weight: 700; color: #111827;">"{{ $selectedTicket->ticket_id }}"</span>?
                    </p>
                    <p style="font-size: 13px; color: #6B7280; margin: 0; line-height: 1.5;">
                        This action cannot be undone.
                    </p>
                </div>

                <!-- Footer -->
                <div style="padding: 16px 24px; background: #F9FAFB; border-top: 1px solid #E5E7EB; display: flex; justify-content: flex-end; gap: 10px;">
                    <button @click="showCloseConfirm = false"
                            style="padding: 9px 20px; background: white; border: 1px solid #D1D5DB; color: #374151; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s;"
                            onmouseover="this.style.background='#F3F4F6'"
                            onmouseout="this.style.background='white'">
                        Cancel
                    </button>
                    <button wire:click="updateTicketStatus({{ $selectedTicket->id }}, 'Closed')"
                            @click="showCloseConfirm = false"
                            style="padding: 9px 20px; background: #DC2626; border: none; color: white; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px;"
                            onmouseover="this.style.background='#B91C1C'"
                            onmouseout="this.style.background='#DC2626'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Close Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div x-data="{ show: false }"
     @ticket-link-copied.window="show = true; setTimeout(() => show = false, 2000)"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full opacity-0"
     x-transition:enter-end="translate-x-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0 opacity-100"
     x-transition:leave-end="translate-x-full opacity-0"
     style="position: fixed; top: 24px; right: 24px; background: #10B981; color: white; padding: 12px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; z-index: 9999; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2); display: flex; align-items: center; gap: 8px;"
     x-cloak>
    <span style="font-size: 18px;">✓</span>
    Link copied to clipboard!
</div>

{{-- Image Preview Modal --}}
@if($showImageModal && $selectedImageUrl)
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999999; display: flex; align-items: center; justify-content: center; padding: 20px;"
     wire:click="closeImageModal"
     x-data
     @keydown.escape.window="$wire.closeImageModal()">

    <!-- Close Button -->
    <button wire:click="closeImageModal"
            style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: #111827; cursor: pointer; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.2s; z-index: 99999999;"
            onmouseover="this.style.background='#F3F4F6'"
            onmouseout="this.style.background='white'">
        ✕
    </button>

    <!-- Image Container -->
    <div style="max-width: 95%; max-height: 95%; display: flex; align-items: center; justify-content: center;"
         wire:click.stop>
        <img src="{{ $selectedImageUrl }}"
             alt="Preview"
             style="max-width: 100%; max-height: 95vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); min-width: 60vw;">
    </div>
</div>
@endif

{{-- On Hold Modal --}}
@if(isset($showOnHoldModal) && $showOnHoldModal)
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999999; display: flex; align-items: center; justify-content: center; padding: 20px;"
     wire:click="closeOnHoldModal">
    <div style="background: white; border-radius: 12px; width: 100%; max-width: 550px; overflow: hidden;"
         wire:click.stop>
        <!-- Header -->
        <div style="padding: 20px 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 18px;">⏸</span>
                <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">
                    Mark Ticket as On Hold - {{ $selectedTicket->ticket_id }}
                </h3>
            </div>
            <button wire:click="closeOnHoldModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 20px;">
                ✕
            </button>
        </div>
        <!-- Body -->
        <div style="padding: 24px;">
            <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                On Hold Reason <span style="color: #EF4444;">*</span>
            </label>
            {{ $this->kivForm }}
        </div>
        <!-- Footer -->
        <div style="padding: 16px 24px; border-top: 1px solid #E5E7EB; display: flex; justify-content: flex-end; gap: 12px;">
            <button wire:click="closeOnHoldModal"
                style="padding: 8px 20px; border: 1px solid #D1D5DB; background: white; color: #374151; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;"
                onmouseover="this.style.background='#F9FAFB'"
                onmouseout="this.style.background='white'">
                Cancel
            </button>
            <button wire:click="markAsOnHold"
                style="padding: 8px 20px; border: none; background: #F59E0B; color: white; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;"
                onmouseover="this.style.background='#D97706'"
                onmouseout="this.style.background='#F59E0B'">
                Mark as On Hold
            </button>
        </div>
    </div>
</div>
@endif

<style>
    .close-confirm-hidden { display: none !important; }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .status-badge-wrapper:hover .status-tooltip {
        opacity: 1 !important;
    }

    @keyframes chatBubbleIn {
        0% {
            transform: translateX(400px) scale(0.8);
            opacity: 0;
        }
        50% {
            transform: translateX(-10px) scale(1.05);
        }
        100% {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes chatBubbleOut {
        0% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        100% {
            transform: translateY(-20px) scale(0.9);
            opacity: 0;
        }
    }
</style>

<script>
    // Helper function to check if URL is an image (handles S3 serve URLs)
    function isImageUrl(href) {
        if (!href) return false;
        // Check for storage URLs
        if (href.includes('storage')) return true;
        // Check for S3 file serve route (actual route is /s3-file)
        if (href.includes('s3-file') || href.includes('s3/serve') || href.includes('s3-serve')) return true;
        // Check for S3 direct URLs
        if (href.includes('s3.amazonaws.com')) return true;
        // Check for common image extensions in URL or query params
        const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.bmp'];
        return imageExtensions.some(ext => href.toLowerCase().includes(ext));
    }

    // Helper function to setup click handler on standalone image
    function setupStandaloneImageClick(img, wireId) {
        if (img.dataset.zoomSetup) return;
        img.dataset.zoomSetup = 'true';
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.Livewire && wireId) {
                window.Livewire.find(wireId).call('openImageModal', img.src);
            }
        });
    }

    // Setup image click handlers when modal is rendered
    function setupImagePreview() {
        // Small delay to ensure DOM is ready
        setTimeout(() => {
            const descriptionContent = document.querySelector('.ticket-description-content');
            const commentContents = document.querySelectorAll('.ticket-comment-content');
            const wireEl = document.querySelector('[wire\\:id]');
            const wireId = wireEl ? wireEl.getAttribute('wire:id') : null;

            // Setup for description content
            if (descriptionContent) {
                // Handle images inside <a> tags
                const imageLinks = descriptionContent.querySelectorAll('a');
                imageLinks.forEach(link => {
                    const img = link.querySelector('img');
                    const href = link.getAttribute('href');

                    if (img || isImageUrl(href)) {
                        const newLink = link.cloneNode(true);
                        link.parentNode.replaceChild(newLink, link);

                        newLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const imageUrl = img ? img.src : href;
                            if (window.Livewire && wireId) {
                                window.Livewire.find(wireId).call('openImageModal', imageUrl);
                            }
                        });
                        newLink.style.cursor = 'zoom-in';
                        if (img) img.style.cursor = 'zoom-in';
                    }
                });

                // Handle standalone images (not inside <a> tags) - for reopen uploads
                const standaloneImages = descriptionContent.querySelectorAll('img');
                standaloneImages.forEach(img => {
                    if (!img.closest('a')) {
                        setupStandaloneImageClick(img, wireId);
                    }
                });
            }

            // Setup for comment contents
            commentContents.forEach(commentDiv => {
                // Handle images inside <a> tags
                const imageLinks = commentDiv.querySelectorAll('a');
                imageLinks.forEach(link => {
                    const img = link.querySelector('img');
                    const href = link.getAttribute('href');

                    if (img || isImageUrl(href)) {
                        const newLink = link.cloneNode(true);
                        link.parentNode.replaceChild(newLink, link);

                        newLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const imageUrl = img ? img.src : href;
                            if (window.Livewire && wireId) {
                                window.Livewire.find(wireId).call('openImageModal', imageUrl);
                            }
                        });
                        newLink.style.cursor = 'zoom-in';
                        if (img) img.style.cursor = 'zoom-in';
                    }
                });

                // Handle standalone images (not inside <a> tags) - for reopen uploads
                const standaloneImages = commentDiv.querySelectorAll('img');
                standaloneImages.forEach(img => {
                    if (!img.closest('a')) {
                        setupStandaloneImageClick(img, wireId);
                    }
                });
            });
        }, 100);
    }

    // Run on initial load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupImagePreview);
    } else {
        setupImagePreview();
    }

    // Re-run when Livewire updates
    document.addEventListener('livewire:initialized', () => {
        setupImagePreview();
    });

    // Listen for Livewire updates
    if (window.Livewire) {
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                setupImagePreview();
            });
        });
    }
</script>
