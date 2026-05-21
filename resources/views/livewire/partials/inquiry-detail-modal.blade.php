<div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@if($showDetailModal && $selectedInquiry)
    <div style="position: fixed; inset: 0; overflow-y: auto; z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem 1rem 5rem; text-align: center;">
            <!-- Background overlay -->
            <div
                wire:click="closeDetailModal"
                style="position: fixed; inset: 0; background-color: rgba(107, 114, 128, 0.75); transition: opacity 0.3s;"
                aria-hidden="true"></div>

            <!-- Modal panel -->
            <div style="display: inline-block; width: 100%; max-width: 48rem; margin-top: 6rem; overflow: hidden; text-align: left; vertical-align: bottom; transition: all 0.3s; transform: translateY(0); background-color: white; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                <!-- Header -->
                <div style="padding: 1rem 1.5rem; background: linear-gradient(to right, #4f46e5, #9333ea);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: white; margin: 0;">
                            {{ $selectedInquiry->formatted_id }}
                        </h3>
                        <button
                            wire:click="closeDetailModal"
                            type="button"
                            style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;">
                            <i class="text-2xl fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div style="padding: 1.5rem 1.5rem;" x-data="{
                    showTitleModal: false,
                    showDescriptionModal: false,
                    showRemarkModal: false,
                    showRejectReasonModal: false
                }">
                    <!-- Reseller Name -->
                    <div style="margin-bottom: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Reseller Name:</span>
                        <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827; text-transform: uppercase;">{{ $selectedInquiry->reseller_company_name ?? 'N/A' }}</span>
                    </div>

                    <!-- Subscriber Name -->
                    <div style="padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Subscriber Name:</span>
                        <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedInquiry->subscriber_name ?? 'N/A' }}</span>
                    </div>

                    <!-- Title, Description, Attachments Grid -->
                    <div style="margin-bottom: 1rem;">
                        <!-- Title Row -->
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 0.75rem;">
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Title:</span>
                                <button
                                    @click="showTitleModal = true"
                                    style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #4f46e5; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                    onmouseover="this.style.color='#3730a3'"
                                    onmouseout="this.style.color='#4f46e5'">
                                    View
                                </button>
                            </div>
                        </div>

                        <!-- Description & Remark Row -->
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 0.75rem;">
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Description:</span>
                                <button
                                    @click="showDescriptionModal = true"
                                    style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #4f46e5; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                    onmouseover="this.style.color='#3730a3'"
                                    onmouseout="this.style.color='#4f46e5'">
                                    View
                                </button>
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Remark by Admin:</span>
                                @if($selectedInquiry->admin_remark)
                                    <button
                                        @click="showRemarkModal = true"
                                        style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #9333ea; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                        onmouseover="this.style.color='#7e22ce'"
                                        onmouseout="this.style.color='#9333ea'">
                                        View
                                    </button>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">N/A</span>
                                @endif
                            </div>
                        </div>

                        <!-- Attachment Row -->
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 0.75rem;">
                            <div style="margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Attachment by Reseller:</span>
                                @if($selectedInquiry->attachment_path)
                                    @php
                                        $attachments = is_array($selectedInquiry->attachment_path)
                                            ? $selectedInquiry->attachment_path
                                            : (is_array(json_decode($selectedInquiry->attachment_path, true))
                                                ? json_decode($selectedInquiry->attachment_path, true)
                                                : [$selectedInquiry->attachment_path]);
                                    @endphp

                                    <div style="margin-top: 0.5rem;">
                                        @foreach($attachments as $index => $attachmentPath)
                                            <div style="display: inline-block; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                                <a href="{{ Storage::url($attachmentPath) }}" target="_blank"
                                                    style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: white; background-color: #4f46e5; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.3s;"
                                                    onmouseover="this.style.backgroundColor='#3730a3'"
                                                    onmouseout="this.style.backgroundColor='#4f46e5'">
                                                    <i class="fas fa-file" style="font-size: 0.75rem;"></i>
                                                    <span>File {{ $index + 1 }}</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">No attachment</span>
                                @endif
                            </div>

                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Attachment by Admin:</span>
                                @if($selectedInquiry->admin_attachment_path)
                                    @php
                                        $adminAttachments = is_array($selectedInquiry->admin_attachment_path)
                                            ? $selectedInquiry->admin_attachment_path
                                            : (is_array(json_decode($selectedInquiry->admin_attachment_path, true))
                                                ? json_decode($selectedInquiry->admin_attachment_path, true)
                                                : [$selectedInquiry->admin_attachment_path]);
                                    @endphp
                                    <div style="margin-top: 0.5rem;">
                                        @foreach($adminAttachments as $index => $adminAttachmentPath)
                                            <div style="display: inline-block; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                                <a href="{{ Storage::url($adminAttachmentPath) }}" target="_blank"
                                                    style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: white; background-color: #9333ea; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.3s;"
                                                    onmouseover="this.style.backgroundColor='#7e22ce'"
                                                    onmouseout="this.style.backgroundColor='#9333ea'">
                                                    <i class="fas fa-file" style="font-size: 0.75rem;"></i>
                                                    <span>File {{ $index + 1 }}</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">No attachment</span>
                                @endif
                            </div>
                        </div>

                        <!-- Title Modal -->
                        <div x-show="showTitleModal"
                             x-cloak
                             style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
                             @click.self="showTitleModal = false">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <h4 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">Title</h4>
                                    <button @click="showTitleModal = false" style="color: #9ca3af; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#4b5563'"
                                            onmouseout="this.style.color='#9ca3af'">
                                        <i class="text-xl fas fa-times"></i>
                                    </button>
                                </div>
                                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
                                    <div style="padding: 1rem; font-size: 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; text-align: left;">
                                        {!! nl2br(e($selectedInquiry->title)) ?: '<span style="color: #9ca3af;">N/A</span>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description Modal -->
                        <div x-show="showDescriptionModal"
                             x-cloak
                             style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
                             @click.self="showDescriptionModal = false">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <h4 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">Description</h4>
                                    <button @click="showDescriptionModal = false" style="color: #9ca3af; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#4b5563'"
                                            onmouseout="this.style.color='#9ca3af'">
                                        <i class="text-xl fas fa-times"></i>
                                    </button>
                                </div>
                                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
                                    <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; text-align: left;">
                                        {!! nl2br(e($selectedInquiry->description)) ?: '<span style="color: #9ca3af;">N/A</span>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remark Modal -->
                        <div x-show="showRemarkModal"
                             x-cloak
                             style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
                             @click.self="showRemarkModal = false">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <h4 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">Remark by Admin</h4>
                                    <button @click="showRemarkModal = false" style="color: #9ca3af; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#4b5563'"
                                            onmouseout="this.style.color='#9ca3af'">
                                        <i class="text-xl fas fa-times"></i>
                                    </button>
                                </div>
                                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
                                    <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; text-align: left;">
                                        {!! nl2br(e($selectedInquiry->admin_remark)) ?: '<span style="color: #9ca3af;">N/A</span>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Reason Modal -->
                        <div x-show="showRejectReasonModal"
                             x-cloak
                             style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
                             @click.self="showRejectReasonModal = false">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <h4 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">Reject Reason</h4>
                                    <button @click="showRejectReasonModal = false" style="color: #9ca3af; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#4b5563'"
                                            onmouseout="this.style.color='#9ca3af'">
                                        <i class="text-xl fas fa-times"></i>
                                    </button>
                                </div>
                                <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
                                    <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; text-align: left;">
                                        {!! nl2br(e($selectedInquiry->reject_reason)) ?: '<span style="color: #9ca3af;">N/A</span>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Reject Details (if has reject reason) -->
                    @if($selectedInquiry->reject_reason)
                        <div style="padding: 1rem; margin-bottom: 1rem; border-top: 2px solid #e5e7eb; background-color: #fef2f2; border-radius: 0.5rem;">
                            <div style="margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #991b1b;">Rejected At:</span>
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedInquiry->rejected_at ? \Carbon\Carbon::parse($selectedInquiry->rejected_at)->format('M d, Y H:i') : 'N/A' }}</span>
                            </div>
                            <div style="margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #991b1b;">Reject Reason:</span>
                                <button
                                    @click="showRejectReasonModal = true"
                                    style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #dc2626; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                    onmouseover="this.style.color='#991b1b'"
                                    onmouseout="this.style.color='#dc2626'">
                                    View
                                </button>
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #991b1b;">Reject Attachment:</span>
                                @if($selectedInquiry->reject_attachment_path)
                                    @php
                                        $rejectAttachments = is_array($selectedInquiry->reject_attachment_path)
                                            ? $selectedInquiry->reject_attachment_path
                                            : (is_array(json_decode($selectedInquiry->reject_attachment_path, true))
                                                ? json_decode($selectedInquiry->reject_attachment_path, true)
                                                : [$selectedInquiry->reject_attachment_path]);
                                    @endphp
                                    <div style="margin-top: 0.5rem;">
                                        @foreach($rejectAttachments as $index => $rejectAttachmentPath)
                                            <div style="display: inline-block; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                                <a href="{{ Storage::url($rejectAttachmentPath) }}" target="_blank"
                                                    style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: white; background-color: #dc2626; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.3s;"
                                                    onmouseover="this.style.backgroundColor='#991b1b'"
                                                    onmouseout="this.style.backgroundColor='#dc2626'">
                                                    <i class="fas fa-file" style="font-size: 0.75rem;"></i>
                                                    <span>File {{ $index + 1 }}</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">No attachment</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Dates -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; padding: 1rem; border-top: 2px solid #e5e7eb;">
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Submitted At:</span>
                            <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedInquiry->created_at ? \Carbon\Carbon::parse($selectedInquiry->created_at)->format('M d, Y H:i') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Completed At:</span>
                            <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedInquiry->completed_at ? \Carbon\Carbon::parse($selectedInquiry->completed_at)->format('M d, Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
