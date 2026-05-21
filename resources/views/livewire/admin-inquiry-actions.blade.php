<div>
    <!-- Complete Task Modal -->
    @if($showCompleteModal && $inquiry)
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 10000;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div
                    wire:click="closeCompleteModal"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <div class="inline-block w-full max-w-2xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle">
                    <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                <i class="mr-2 fas fa-check-circle"></i>Complete Inquiry
                            </h3>
                            <button
                                wire:click="closeCompleteModal"
                                type="button"
                                class="text-white transition-colors hover:text-gray-200">
                                <i class="text-2xl fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-6 space-y-4">
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">Admin Remark</label>
                            <textarea
                                wire:model="adminRemark"
                                rows="4"
                                style="text-transform: uppercase;"
                                class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                placeholder="Enter admin remark..."></textarea>
                            @error('adminRemark') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">Admin Attachment (Optional)</label>
                            <div class="relative border-2 border-dashed rounded-lg transition-all {{ $adminAttachment ? 'border-green-500 bg-gradient-to-br from-green-50 to-white border-solid' : 'border-gray-300 bg-gradient-to-br from-gray-50 to-white hover:border-green-500' }}" style="padding: 1rem; cursor: {{ $adminAttachment ? 'not-allowed' : 'pointer' }};">
                                <div style="text-align: center;">
                                    <p style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        @if($adminAttachment)
                                            File selected
                                        @else
                                            Click to upload or drag and drop
                                        @endif
                                    </p>
                                    <p style="font-size: 0.75rem; color: #9ca3af;">PDF, EXCEL, JPG (Max 10MB)</p>
                                </div>
                                @if(!$adminAttachment)
                                    <input
                                        type="file"
                                        wire:model="adminAttachment"
                                        accept=".pdf,.xlsx,.xls,.jpg,.jpeg,.png"
                                        style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                                @endif
                            </div>
                            @if($adminAttachment)
                                <div class="flex items-center gap-3 p-3 mt-3 bg-white border border-green-200 rounded-lg">
                                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg">
                                        <i class="text-lg text-white fas fa-file"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-green-700 break-all">{{ $adminAttachment->getClientOriginalName() }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($adminAttachment->getSize() / 1024, 2) }} KB</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="$set('adminAttachment', null)"
                                        class="text-red-600 transition-colors hover:text-red-800">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                            @error('adminAttachment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
                        <button
                            wire:click="closeCompleteModal"
                            type="button"
                            class="px-6 py-2.5 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition-all">
                            <i class="mr-2 fas fa-times"></i>Cancel
                        </button>
                        <button
                            wire:click="completeInquiry"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-teal-600 text-white font-semibold rounded-lg hover:from-green-700 hover:to-teal-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-check-circle"></i>Complete Task
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Reject Modal -->
    @if($showRejectModal && $inquiry)
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 10000;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div
                    wire:click="closeRejectModal"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <div class="inline-block w-full max-w-2xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-pink-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                <i class="mr-2 fas fa-times-circle"></i>Reject Inquiry
                            </h3>
                            <button
                                wire:click="closeRejectModal"
                                type="button"
                                class="text-white transition-colors hover:text-gray-200">
                                <i class="text-2xl fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-6">
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Reject Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                wire:model="rejectReason"
                                rows="4"
                                style="text-transform: uppercase;"
                                class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-red-500 focus:ring-2 focus:ring-red-200"
                                placeholder="Enter reason for rejection..."></textarea>
                            @error('rejectReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
                        <button
                            wire:click="closeRejectModal"
                            type="button"
                            class="px-6 py-2.5 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition-all">
                            <i class="mr-2 fas fa-times"></i>Cancel
                        </button>
                        <button
                            wire:click="rejectInquiry"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-pink-600 text-white font-semibold rounded-lg hover:from-red-700 hover:to-pink-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-times-circle"></i>Reject Inquiry
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
