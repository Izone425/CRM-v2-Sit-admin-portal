<div>
    @include('components.reseller-handover-table-styles')

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" wire:model.live="search" class="search-input" placeholder="Search by company name">
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <button wire:click="sortBy('id')">
                            ID
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'id')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('subscriber_name')">
                            Subscriber Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_name')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('updated_at')">
                            Last Modified
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'updated_at')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('overdue')">
                            Overdue
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortOverdue)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <span wire:click="openFilesModal({{ $handover->id }})" style="color: #3b82f6; font-weight: 600; cursor: pointer; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                {{ $handover->fg_id }}
                            </span>
                        </td>
                        <td class="subscriber-name">{{ $handover->subscriber_name }}</td>
                        <td class="date-cell">{{ $handover->updated_at->format('d M Y, H:i') }}</td>
                        <td>
                            @php
                                $today = now()->startOfDay();
                                $updatedAt = $handover->updated_at->startOfDay();
                                $daysDiff = $today->diffInDays($updatedAt);
                            @endphp
                            <span style="font-weight: {{ $daysDiff == 0 ? 'normal' : 'bold' }}; color: {{ $daysDiff == 0 ? '#10b981' : '#ef4444' }};">
                                {{ $daysDiff == 0 ? '0 DAY' : '-' . $daysDiff . ' Days' }}
                            </span>
                        </td>
                        <td>
                            <button wire:click="openCompleteModal({{ $handover->id }})" class="confirm-button">
                                <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Proceed
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Complete Task Modal -->
    @if($showCompleteModal && $selectedHandover)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Upload Payment Slip</h3>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label required">Reseller Payment Slip</label>
                        <div class="file-upload-wrapper {{ $paymentSlip ? 'has-file' : '' }}" style="{{ $paymentSlip ? 'pointer-events: none; cursor: not-allowed;' : '' }}">
                            <div class="file-upload-content">
                                <p class="file-upload-text">
                                    @if($paymentSlip)
                                        File selected
                                    @else
                                        Click to upload or drag and drop
                                    @endif
                                </p>
                                <p class="file-upload-hint">PDF, JPG, JPEG, PNG (Max 10MB)</p>
                            </div>
                            @if(!$paymentSlip)
                                <input
                                    type="file"
                                    wire:model="paymentSlip"
                                    accept="application/pdf,image/*"
                                    class="file-upload-input"
                                />
                            @endif
                        </div>
                        @if($paymentSlip)
                            <div class="file-selected-info">
                                <div class="file-selected-details">
                                    <p class="file-selected-name">{{ $paymentSlip->getClientOriginalName() }}</p>
                                    <p class="file-selected-size">{{ number_format($paymentSlip->getSize() / 1024, 2) }} KB</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removePaymentSlipFile"
                                    class="file-delete-button"
                                >
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endif
                        @error('paymentSlip')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="modal-actions">
                    <button wire:click="closeCompleteModal" class="modal-button-cancel">Cancel</button>
                    <button wire:click="completeTask" class="modal-button-confirm"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Proceed</span>
                        <span wire:loading>Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('components.handover-fg-files-modal')

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="success-message">
            <i class="mr-2 fas fa-check-circle"></i>{{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="error-message">
            <i class="mr-2 fas fa-exclamation-circle"></i>{{ session('error') }}
        </div>
    @endif
</div>
