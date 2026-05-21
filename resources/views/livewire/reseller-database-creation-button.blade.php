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
                    wire:click="closeModal"
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle" style="position: relative; z-index: 10000; margin-top: 2rem;">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                Request Trial Account
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
                        <!-- Row 1: Company Name & SSM Number -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Subscriber Company Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="companyName"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('companyName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Subscriber SSM Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="ssmNumber"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('ssmNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Subscriber TIN Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="taxIdentificationNumber"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('taxIdentificationNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Row 2: Tax ID & PIC Name -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    PIC Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="picName"
                                    style="text-transform: uppercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('picName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    PIC HP Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="picPhone"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('picPhone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Master Login Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    wire:model="masterLoginEmail"
                                    style="text-transform: lowercase;"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('masterLoginEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-semibold text-gray-700">
                                    Headcount <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    wire:model="headcount"
                                    placeholder="Enter headcount"
                                    min="1"
                                    class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                @error('headcount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Row 4: Modules & Headcount -->
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Modules <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-4 gap-4">
                                <label class="flex items-center gap-2 p-3 transition-all border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500">
                                    <input type="checkbox" wire:model="modules" value="Attendance" class="w-4 h-4 text-indigo-600">
                                    <span class="text-sm font-medium text-gray-700">Attendance</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 transition-all border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500">
                                    <input type="checkbox" wire:model="modules" value="Leave" class="w-4 h-4 text-indigo-600">
                                    <span class="text-sm font-medium text-gray-700">Leave</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 transition-all border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500">
                                    <input type="checkbox" wire:model="modules" value="Claim" class="w-4 h-4 text-indigo-600">
                                    <span class="text-sm font-medium text-gray-700">Claim</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 transition-all border-2 border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500">
                                    <input type="checkbox" wire:model="modules" value="Payroll" class="w-4 h-4 text-indigo-600">
                                    <span class="text-sm font-medium text-gray-700">Payroll</span>
                                </label>
                            </div>
                            @error('modules') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Row 5: Reseller Remark -->
                        <div>
                            <label class="block mb-2 text-sm font-semibold text-gray-700">
                                Reseller Remark
                            </label>
                            <textarea
                                wire:model="resellerRemark"
                                rows="2"
                                style="text-transform: uppercase;"
                                class="w-full px-4 py-2 text-sm transition-all border-2 border-gray-300 rounded-lg resize-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"></textarea>
                            @error('resellerRemark') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                            wire:click="submit"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-paper-plane"></i>Submit Request
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
