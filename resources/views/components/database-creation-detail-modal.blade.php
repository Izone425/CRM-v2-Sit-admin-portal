<!-- Database Creation Detail Modal -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@if($showDetailModal && $selectedRequest)
<div style="position: fixed; inset: 0; overflow-y: auto; z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true"
     x-data="{
        showResellerRemarkModal: false,
        showAdminRemarkModal: false,
        showRejectReasonModal: false
     }">
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem 1rem 5rem; text-align: center;">
        <!-- Background overlay -->
        <div wire:click="closeDetailModal"
             style="position: fixed; inset: 0; background-color: rgba(107, 114, 128, 0.75); transition: opacity 0.3s;"
             aria-hidden="true"></div>

        <!-- Modal panel -->
        <div style="display: inline-block; width: 100%; max-width: 56rem; margin-top: 2rem; overflow: hidden; text-align: left; vertical-align: bottom; transition: all 0.3s; transform: translateY(0); background-color: white; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

            @if($selectedRequest)
                <!-- Modal Header -->
                <div style="padding: 1rem 1.5rem; background: linear-gradient(to right, #667eea, #764ba2);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 1.5rem; font-weight: 700; color: white; margin: 0;">{{ $selectedRequest->formatted_id }}</h3>
                                <div style="font-size: 0.95rem; font-weight: 500; color: rgba(255, 255, 255, 0.95); margin-top: 0.25rem;">{{ $selectedRequest->reseller_company_name ?? 'N/A' }}</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                    <span style="padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px;
                                        {{ $selectedRequest->status === 'new' ? 'background-color: #dbeafe; color: #1e40af;' : '' }}
                                        {{ $selectedRequest->status === 'completed' ? 'background-color: #d1fae5; color: #065f46;' : '' }}
                                        {{ $selectedRequest->status === 'rejected' ? 'background-color: #fee2e2; color: #991b1b;' : '' }}
                                        {{ $selectedRequest->status === 'draft' ? 'background-color: #fef3c7; color: #92400e;' : '' }}">
                                        {{ ucfirst($selectedRequest->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button wire:click="closeDetailModal" type="button"
                                style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;">
                            <i class="text-2xl fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div style="padding: 1.5rem 1.5rem;">
                    <!-- Subscriber Information -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Subscriber Company Name:</span>
                            <span style="margin-left: 0.5rem; font-size: 1.125rem; font-weight: 500; color: #111827;">{{ $selectedRequest->company_name }}</span>
                        </div>

                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Subscriber SSM Number:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->ssm_number }}</span>
                        </div>

                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Subscriber TIN Number:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->tax_identification_number }}</span>
                        </div>

                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">PIC Name:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->pic_name }}</span>
                        </div>

                        <div style="margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">PIC No Hp:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->pic_phone }}</span>
                        </div>

                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Master Login Email:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->master_login_email }}</span>
                        </div>
                    </div>

                    <hr style="margin: 1.5rem 0; border-top: 2px solid #d1d5db;">

                    <!-- Module and Headcount -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="margin-bottom: 1rem;">
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Module:</span>
                            <span style="margin-left: 0.5rem;">
                                @foreach($selectedRequest->modules as $module)
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; margin-right: 0.25rem; font-size: 0.875rem; font-weight: 500; color: #4338ca; background-color: #e0e7ff; border-radius: 9999px;">{{ $module }}</span>
                                @endforeach
                            </span>
                        </div>

                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Headcount:</span>
                            <span style="margin-left: 0.5rem; font-size: 1rem; color: #111827;">{{ $selectedRequest->headcount }}</span>
                        </div>
                    </div>

                    <hr style="margin: 1.5rem 0; border-top: 2px solid #d1d5db;">

                    <!-- Remarks and Timestamps -->
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1rem;">
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Reseller Remark:</span>
                                @if($selectedRequest->reseller_remark)
                                    <button @click="showResellerRemarkModal = true"
                                            style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #4f46e5; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#3730a3'"
                                            onmouseout="this.style.color='#4f46e5'">
                                        View
                                    </button>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Submitted At:</span>
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #111827;">{{ \Carbon\Carbon::parse($selectedRequest->created_at)->format('M d, Y H:i') }}</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Admin Remark:</span>
                                @if($selectedRequest->admin_remark)
                                    <button @click="showAdminRemarkModal = true"
                                            style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #4f46e5; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                            onmouseover="this.style.color='#3730a3'"
                                            onmouseout="this.style.color='#4f46e5'">
                                        View
                                    </button>
                                @else
                                    <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Completed At:</span>
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #111827;">
                                    @if($selectedRequest->completed_at)
                                        {{ \Carbon\Carbon::parse($selectedRequest->completed_at)->format('M d, Y H:i') }}
                                    @else
                                        <span style="color: #9ca3af;">-</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rejection Details (if exists) -->
                    @if($selectedRequest->reject_reason)
                        <hr style="margin: 1.5rem 0; border-top: 2px solid #fca5a5;">
                        <div style="padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #fecaca; border-radius: 0.5rem; background-color: #fef2f2;">
                            <div style="margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; font-weight: 600; color: #991b1b;">Reject Reason:</span>
                                <button @click="showRejectReasonModal = true"
                                        style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #dc2626; transition: color 0.3s; text-decoration: underline; background: none; border: none; cursor: pointer; padding: 0;"
                                        onmouseover="this.style.color='#991b1b'"
                                        onmouseout="this.style.color='#dc2626'">
                                    View
                                </button>
                            </div>
                            <div>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #991b1b;">Rejected At:</span>
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #111827;">
                                    @if($selectedRequest->rejected_at)
                                        {{ \Carbon\Carbon::parse($selectedRequest->rejected_at)->format('M d, Y H:i') }}
                                    @else
                                        <span style="color: #9ca3af;">-</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Reseller Remark Modal -->
    <div x-show="showResellerRemarkModal"
         x-cloak
         style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
         @click.self="showResellerRemarkModal = false">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #4f46e5;">
            <h4 style="font-size: 1.125rem; font-weight: 700; color: white; margin: 0;">Reseller Remark</h4>
            <button @click="showResellerRemarkModal = false"
                    style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                    onmouseover="this.style.color='#e5e7eb'"
                    onmouseout="this.style.color='white'">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
            <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; white-space: pre-wrap; text-align: left;">{{ $selectedRequest->reseller_remark ?? 'No remark provided' }}</div>
        </div>
    </div>
</div>

    <!-- Admin Remark Modal -->
    <div x-show="showAdminRemarkModal"
         x-cloak
         style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
         @click.self="showAdminRemarkModal = false">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #10b981;">
            <h4 style="font-size: 1.125rem; font-weight: 700; color: white; margin: 0;">Admin Remark</h4>
            <button @click="showAdminRemarkModal = false"
                    style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                    onmouseover="this.style.color='#e5e7eb'"
                    onmouseout="this.style.color='white'">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
            <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; white-space: pre-wrap; text-align: left;">{{ $selectedRequest->admin_remark ?? 'No remark provided' }}</div>
        </div>
    </div>
</div>

    <!-- Reject Reason Modal -->
    <div x-show="showRejectReasonModal"
         x-cloak
         style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background-color: rgba(0, 0, 0, 0.5);"
         @click.self="showRejectReasonModal = false">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 42rem; background-color: white; border-radius: 0.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; border-bottom: 1px solid #e5e7eb; background-color: #dc2626;">
            <h4 style="font-size: 1.125rem; font-weight: 700; color: white; margin: 0;">Reject Reason</h4>
            <button @click="showRejectReasonModal = false"
                    style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;"
                    onmouseover="this.style.color='#e5e7eb'"
                    onmouseout="this.style.color='white'">
                <i class="text-xl fas fa-times"></i>
            </button>
        </div>
        <div style="padding: 1.5rem; overflow-y: auto; max-height: calc(90vh - 60px); text-align: left;">
            <div style="padding: 1rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word; white-space: pre-wrap; text-align: left;">{{ $selectedRequest->reject_reason ?? 'No reject reason provided' }}</div>
        </div>
    </div>
</div>

</div>
@endif
