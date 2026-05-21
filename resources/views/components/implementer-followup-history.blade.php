<div class="space-y-4">
    @php
        $lead = $this->getRecord();

        // Get implementer logs that are follow-ups
        $followUps = $lead->implementerLogs()
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalFollowUps = $followUps->count();
    @endphp

    <div x-data="{
        showModal: false,
        emailSubject: '',
        emailContent: '',
        emailSender: '',
        emailDate: '',
        emailRecipients: '',
        emailAttachments: [],
        openEmailFromButton(el) {
            let payload;
            try {
                payload = JSON.parse(el.dataset.email);
            } catch (e) {
                console.error('Failed to parse email payload:', e, el.dataset.email);
                return;
            }
            this.emailSubject = payload.subject || '';
            this.emailContent = payload.content || '';
            this.emailSender = payload.sender || '';
            this.emailDate = payload.date || '';
            this.emailRecipients = payload.recipients || '';
            this.emailAttachments = payload.attachments || [];
            this.showModal = true;
        }
    }" @keydown.escape.window="showModal = false">
        <!-- Modal -->
        <div x-show="showModal"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;"
             x-cloak>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showModal = false"></div>

            <!-- Modal content -->
            <div class="relative flex items-center justify-center min-h-screen p-4">
                <div class="relative w-full max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="pb-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium" x-text="emailSubject"></h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            <div><strong>From:</strong> <span x-text="emailSender"></span></div>
                            <div><strong>To:</strong> <span x-text="emailRecipients"></span></div>
                            <div><strong>Date:</strong> <span x-text="emailDate"></span></div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="py-4 max-h-[60vh] overflow-y-auto">
                        <div class="prose max-w-none" x-html="emailContent"></div>

                        <template x-if="emailAttachments.length > 0">
                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <div class="mb-2 text-sm font-semibold text-gray-700">
                                    Attachments (<span x-text="emailAttachments.length"></span>)
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="file in emailAttachments" :key="file.url">
                                        <a :href="file.url"
                                           :download="file.name"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-md hover:bg-gray-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <span x-text="file.name"></span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($followUps->count() > 0)
            <div class="overflow-y-auto bg-white rounded-lg max-h-96">
                <div class="space-y-0 divide-y divide-gray-200">
                    @foreach($followUps as $index => $followUp)
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="w-full space-y-1">
                                    <div class="flex flex-col w-full">
                                        <div class="flex items-center justify-between">
                                            <p class="text-gray-500" style="font-weight:bold; font-size: 1rem; color: #eb321a; text-decoration: underline;">
                                                Implementer Follow Up {{ $totalFollowUps - $index }}
                                                @if($followUp->manual_follow_up_count > 0)
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                        Follow-up #{{ $followUp->manual_follow_up_count }}
                                                    </span>
                                                @endif
                                            </p>

                                            @php
                                                // Check if there are any scheduled emails for this follow-up
                                                $scheduledEmails = DB::table('scheduled_emails')
                                                    ->where('email_data', 'like', '%"implementer_log_id":' . $followUp->id . '%')
                                                    ->get();

                                                $followUpAttachments = [];
                                                foreach ((array) ($followUp->attachments ?? []) as $_path) {
                                                    if (!$_path) continue;
                                                    $followUpAttachments[] = [
                                                        'name' => basename($_path),
                                                        'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($_path),
                                                    ];
                                                }
                                            @endphp

                                            <div class="flex flex-wrap items-center gap-2">
                                            @if(!empty($followUpAttachments))
                                                @foreach($followUpAttachments as $_i => $att)
                                                    <a href="{{ $att['url'] }}"
                                                       download="{{ $att['name'] }}"
                                                       target="_blank"
                                                       class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-full hover:bg-gray-200">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                        </svg>
                                                        File {{ $_i + 1 }}
                                                    </a>
                                                @endforeach
                                            @endif

                                            @if($scheduledEmails && $scheduledEmails->count() > 0)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($scheduledEmails as $email)
                                                        @php
                                                            $emailData = json_decode($email->email_data, true);
                                                            $templateName = isset($emailData['template_name']) ? $emailData['template_name'] : 'Custom Email';
                                                            $emailSubject = isset($emailData['subject']) ? $emailData['subject'] : 'No Subject';
                                                            $emailContent = isset($emailData['content']) ? $emailData['content'] : 'No Content';
                                                            $senderName = isset($emailData['sender_name']) ? $emailData['sender_name'] : 'Unknown Sender';
                                                            $senderEmail = isset($emailData['sender_email']) ? $emailData['sender_email'] : '';
                                                            $recipients = isset($emailData['recipients']) ? implode(', ', $emailData['recipients']) : 'No Recipients';
                                                            $emailDate = \Carbon\Carbon::parse($email->created_at)->format('d M Y g:i A');

                                                            $attachmentList = [];
                                                            foreach ($emailData['project_plan_attachments'] ?? [] as $attPath) {
                                                                $attachmentList[] = [
                                                                    'name' => basename($attPath),
                                                                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($attPath),
                                                                ];
                                                            }

                                                            $badgeColor = 'bg-blue-100 text-blue-800';
                                                            $sendType = 'Unknown';

                                                            if ($email->status === 'Done' && $email->scheduled_date === null) {
                                                                $badgeColor = 'bg-green-100 text-green-800';
                                                                $sendType = 'Sent Instantly';
                                                            } elseif ($email->status === 'Done') {
                                                                $badgeColor = 'bg-green-100 text-green-800';
                                                                $sendType = 'Sent';
                                                            } elseif ($email->status === 'New') {
                                                                if (strtotime($email->scheduled_date) > time()) {
                                                                    $badgeColor = 'bg-yellow-100 text-yellow-800';
                                                                    $sendType = 'Scheduled for ' . \Carbon\Carbon::parse($email->scheduled_date)->format('d M Y g:i A'). ' (Pending)';
                                                                }
                                                            }
                                                        @endphp

                                                        <button type="button"
                                                            @click="openEmailFromButton($el)"
                                                            data-email="{{ json_encode([
                                                                'subject' => $emailSubject,
                                                                'content' => $emailContent,
                                                                'sender' => $senderName . ' <' . $senderEmail . '>',
                                                                'date' => $emailDate,
                                                                'recipients' => $recipients,
                                                                'attachments' => $attachmentList,
                                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }} cursor-pointer"
                                                            title="{{ $sendType }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ $emailSubject }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                            </div>
                                        </div>

                                        <div class="flex flex-col mt-2">
                                            <p class="text-xs font-medium">
                                                Added {{ $followUp->created_at->format('d M Y, h:i A') }} by {{ $followUp->causer ? $followUp->causer->name : 'CRM System' }}
                                            </p>

                                            @php
                                                $softwareHandover = \App\Models\SoftwareHandover::where('id', $followUp->subject_id)->first();
                                                $followUpDate = $followUp->follow_up_date ? \Carbon\Carbon::parse($followUp->follow_up_date)->format('Y-m-d') : null;
                                            @endphp

                                            @if($followUpDate)
                                                <p class="mt-1 text-xs font-medium">
                                                    <span style="font-weight: bold; color: #eb911a;">Next Follow Up Date: {{ \Carbon\Carbon::parse($followUpDate)->format('d M Y') }}</span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <div class="p-3 mt-1 text-sm prose rounded max-w-none bg-gray-50">
                                            @php
                                                $remark = $followUp->remark;

                                                // Convert literal "&nbsp;" text to actual non-breaking spaces
                                                $remark = str_replace('&nbsp;', ' ', $remark);
                                                $remark = str_replace('&amp;nbsp;', ' ', $remark);

                                                // Handle any other common HTML entities that might be causing issues
                                                $remark = str_replace('&amp;', '&', $remark);

                                                // Final decode of any remaining entities
                                                $remark = html_entity_decode($remark, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                                                // Convert to uppercase after all decoding is done
                                                $remark = strtoupper($remark);
                                            @endphp

                                            {!! $remark !!}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex items-center justify-center p-6 text-gray-500 rounded-lg bg-gray-50">

            </div>
        @endif
    </div>
</div>
