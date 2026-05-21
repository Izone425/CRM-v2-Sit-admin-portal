<div x-data="{
    showNotification: false,
    notificationMessage: '',
    notificationType: 'success'
}"
@notify.window="
    showNotification = true;
    notificationMessage = $event.detail.message || $event.detail[0]?.message || 'Success';
    notificationType = $event.detail.type || $event.detail[0]?.type || 'success';
    setTimeout(() => showNotification = false, 3000);
">

    <!-- Trigger Button -->
    <div class="flex justify-end mb-2">
        <button
            wire:click="openModal"
            type="button"
            class="flex items-center gap-2 px-5 py-3 text-sm font-semibold text-white transition-all rounded-lg shadow-md bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 hover:shadow-lg">
            <i class="text-base fas fa-plus"></i>
            <span>Submit</span>
        </button>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle" style="position: relative; z-index: 10000; margin-top: 2rem;">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                Installation Payment
                            </h3>
                            <button
                                wire:click="closeModal"
                                type="button"
                                class="text-white transition-colors hover:text-gray-200">
                                <i class="text-2xl fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Field 1: Attention To (Salesperson) -->
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    SalesPerson <span class="text-red-500">*</span>
                                </label>
                                <select
                                    wire:model="attentionTo"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                    <option value="">Select SalesPerson</option>
                                    @foreach($salespersons as $sp)
                                        <option value="{{ $sp['id'] }}">{{ $sp['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('attentionTo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Field 2: Customer Name -->
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Customer Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="customerName"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('customerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Field 3: Installation Date -->
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Installation Date <span class="text-red-500">*</span>
                                </label>
                                <div wire:ignore
                                     x-data
                                     x-init="$nextTick(() => {
                                         flatpickr($refs.datepicker, {
                                             dateFormat: 'd/m/Y',
                                             allowInput: false,
                                             onChange: function(selectedDates, dateStr) {
                                                 $wire.set('installationDate', dateStr);
                                             }
                                         });
                                     })">
                                    <input
                                        type="text"
                                        x-ref="datepicker"
                                        placeholder="dd/mm/yyyy"
                                        readonly
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                </div>
                                @error('installationDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Field 4: Installation Address -->
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Installation Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="installationAddress"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('installationAddress') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Field 5: Quotation by Reseller (Multiple Files) -->
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">Quotation by Reseller <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Upload area -->
                                <div class="relative transition-all border-2 border-gray-300 border-dashed rounded-lg bg-gradient-to-br from-gray-50 to-white hover:border-indigo-500 hover:bg-gradient-to-br hover:from-indigo-50 hover:to-white" style="padding: 0.5rem; cursor: pointer;">
                                    <div style="text-align: center;">
                                        <p style="font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                            Click to upload (Multiple files)
                                        </p>
                                        <p style="font-size: 0.625rem; color: #9ca3af;">PDF, EXCEL, JPG (Max 10MB)</p>
                                    </div>
                                    <input
                                        type="file"
                                        wire:model="quotations"
                                        accept=".pdf,.xlsx,.xls,.jpg,.jpeg,.png"
                                        multiple
                                        style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                                </div>
                                <!-- File list -->
                                <div>
                                    @if(!empty($quotations))
                                        <div class="space-y-1 overflow-y-auto" style="max-height: 120px;">
                                            @foreach($quotations as $index => $file)
                                                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem; background: white; border-radius: 4px; border: 1px solid #d1fae5;">
                                                    <i class="text-green-600 fas fa-file" style="font-size: 0.875rem;"></i>
                                                    <div style="flex: 1; min-width: 0;">
                                                        <p style="font-size: 0.75rem; font-weight: 600; color: #059669; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $file->getClientOriginalName() }}</p>
                                                    </div>
                                                    <span style="font-size: 0.625rem; color: #6b7280;">{{ number_format($file->getSize() / 1024, 1) }}KB</span>
                                                    <button type="button" wire:click="removeQuotation({{ $index }})"
                                                        style="padding: 0.15rem; border-radius: 4px; border: none; cursor: pointer;">
                                                        <i class="fas fa-times" style="font-size: 0.625rem;"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-full p-4 text-xs text-gray-400">
                                            No files uploaded
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @error('quotations') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('quotations.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Field 6: Invoice by Reseller (Multiple Files) -->
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">Invoice by Reseller <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Upload area -->
                                <div class="relative transition-all border-2 border-gray-300 border-dashed rounded-lg bg-gradient-to-br from-gray-50 to-white hover:border-indigo-500 hover:bg-gradient-to-br hover:from-indigo-50 hover:to-white" style="padding: 0.5rem; cursor: pointer;">
                                    <div style="text-align: center;">
                                        <p style="font-size: 0.75rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                            Click to upload (Multiple files)
                                        </p>
                                        <p style="font-size: 0.625rem; color: #9ca3af;">PDF, EXCEL, JPG (Max 10MB)</p>
                                    </div>
                                    <input
                                        type="file"
                                        wire:model="invoices"
                                        accept=".pdf,.xlsx,.xls,.jpg,.jpeg,.png"
                                        multiple
                                        style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                                </div>
                                <!-- File list -->
                                <div>
                                    @if(!empty($invoices))
                                        <div class="space-y-1 overflow-y-auto" style="max-height: 120px;">
                                            @foreach($invoices as $index => $file)
                                                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem; background: white; border-radius: 4px; border: 1px solid #d1fae5;">
                                                    <i class="text-green-600 fas fa-file" style="font-size: 0.875rem;"></i>
                                                    <div style="flex: 1; min-width: 0;">
                                                        <p style="font-size: 0.75rem; font-weight: 600; color: #059669; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $file->getClientOriginalName() }}</p>
                                                    </div>
                                                    <span style="font-size: 0.625rem; color: #6b7280;">{{ number_format($file->getSize() / 1024, 1) }}KB</span>
                                                    <button type="button" wire:click="removeInvoice({{ $index }})"
                                                        style="padding: 0.15rem; border-radius: 4px; border: none; cursor: pointer;">
                                                        <i class="fas fa-times" style="font-size: 0.625rem;"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-full p-4 text-xs text-gray-400">
                                            No files uploaded
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @error('invoices') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('invoices.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
                        <button
                            wire:click="closeModal"
                            type="button"
                            class="px-6 py-2.5 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition-all">
                            <i class="mr-2 fas fa-times"></i>Cancel
                        </button>
                        <button
                            wire:click="submitPayment"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-paper-plane"></i>Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success/Notification Message -->
    <div
        x-show="showNotification"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="z-index: 99999; position: fixed; top: 120px; right: 20px;"
        :class="{
            'bg-green-500': notificationType === 'success',
            'bg-red-500': notificationType === 'error',
            'bg-blue-500': notificationType === 'info'
        }"
        class="px-6 py-4 text-white rounded-lg shadow-2xl">
        <div class="flex items-center gap-2">
            <i class="fas" :class="{
                'fa-check-circle': notificationType === 'success',
                'fa-exclamation-circle': notificationType === 'error',
                'fa-info-circle': notificationType === 'info'
            }"></i>
            <span x-text="notificationMessage"></span>
        </div>
    </div>
</div>
