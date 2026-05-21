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
    <button
        wire:click="openModal"
        id="request-quotation-tab"
        onclick="activateRequestQuotation()"
        type="button"
        class="flex items-center gap-2 px-5 py-3 text-sm font-semibold text-white transition-all rounded-lg shadow-md bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 hover:shadow-lg">
        <i class="text-base fas fa-plus"></i>
        <span>Submit</span>
    </button>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 overflow-y-auto" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    aria-hidden="true"></div>

                <!-- Modal panel -->
                <div class="inline-block w-full max-w-4xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle" style="position: relative; z-index: 10000; margin-top: 4rem;">
                    <!-- Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white">
                                Request Quotation
                            </h3>
                            <button
                                wire:click="closeModal"
                                class="text-white transition-colors hover:text-gray-200">
                                <i class="text-2xl fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-3 space-y-5">
                        <!-- Status Toggle -->
                        <div class="flex gap-3 mb-2">
                            <button
                                wire:click="$set('subscriberStatus', 'active')"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $subscriberStatus === 'active' ? 'bg-green-500 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                <i class="mr-2 fas fa-check-circle"></i>Active
                            </button>
                            <button
                                wire:click="$set('subscriberStatus', 'inactive')"
                                class="flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $subscriberStatus === 'inactive' ? 'bg-red-500 text-white shadow-lg' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                <i class="mr-2 fas fa-times-circle"></i>InActive
                            </button>
                        </div>

                        <!-- Subscriber Search -->
                        <div class="relative">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-sm font-semibold text-gray-700">
                                    Select Subscriber <span class="text-red-500">*</span>
                                </label>
                                @if($selectedSubscriber)
                                    <button
                                        type="button"
                                        wire:click="viewLicense"
                                        class="px-3 py-1 text-xs font-semibold text-indigo-600 transition-all border border-indigo-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-700">
                                        <i class="mr-1 fas fa-eye"></i>View License
                                    </button>
                                @endif
                            </div>

                            <div class="relative">
                                <div class="relative">
                                    <input
                                        type="text"
                                        wire:model.live.debounce.300ms="search"
                                        placeholder="Search subscriber name"
                                        class="w-full px-4 py-2 pr-20 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                        {{ $selectedSubscriber ? 'readonly' : '' }}>

                                    @if($selectedSubscriber)
                                        <button
                                            type="button"
                                            wire:click="clearSubscriber"
                                            class="absolute inset-y-0 flex items-center pr-3 text-gray-400 transition-colors right-10 hover:text-red-500">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif

                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="text-gray-400 fas fa-chevron-down"></i>
                                    </div>
                                </div>

                                @if(!$selectedSubscriber && $subscribers->count() > 0)
                                    <div class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-lg max-h-60">
                                        @foreach($subscribers as $subscriber)
                                            <button
                                                type="button"
                                                wire:click="selectSubscriber('{{ $subscriber->f_id }}', '{{ $subscriber->f_company_name }}')"
                                                class="flex items-center justify-between w-full px-4 py-2 text-left transition-colors border-b border-gray-100 hover:bg-indigo-50 last:border-b-0">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-800" style="text-transform: uppercase;">{{ $subscriber->f_company_name }}</div>
                                                    <div class="text-xs text-gray-500">ID: {{ $subscriber->f_id }}</div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif(!$selectedSubscriber && strlen($search) > 0 && $subscribers->count() == 0)
                                    <div class="absolute z-10 w-full p-3 mt-1 text-center text-gray-500 bg-white border border-gray-300 rounded-lg shadow-lg">
                                        <i class="mr-2 fas fa-search"></i>No subscribers found
                                    </div>
                                @endif
                            </div>
                            @error('selectedSubscriber')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category Selection -->
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Select Category <span class="text-red-500">*</span>
                            </label>
                            <select
                                wire:model="category"
                                class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                                <option value="">-- Select Category --</option>
                                <option value="renewal_subscription">Renewal Subscription</option>
                                <option value="addon_headcount">AddOn Headcount</option>
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Product Quantities Grid -->
                        <div>
                            <div class="grid grid-cols-5 gap-4">
                                <!-- Attendance -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Attendance
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="attendance"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                        placeholder="0">
                                    @error('attendance')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Leave -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Leave
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="leave"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                        placeholder="0">
                                    @error('leave')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Claim -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Claim
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="claim"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200"
                                        placeholder="0">
                                    @error('claim')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Payroll -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        Payroll
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="payroll"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                                        placeholder="0">
                                    @error('payroll')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- QF Master -->
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-600">
                                        QF Master
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="qf_master"
                                        min="0"
                                        class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg focus:border-pink-500 focus:ring-2 focus:ring-pink-200"
                                        placeholder="0">
                                    @error('qf_master')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            @if($headcountError)
                                <div class="p-3 mb-3 border border-red-200 rounded-lg bg-red-50">
                                    <p class="flex items-center text-sm text-red-600">
                                        <i class="mr-2 fas fa-exclamation-circle"></i>
                                        {{ $headcountError }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Reseller Remark -->
                        <div>
                            <label class="block mb-1 text-sm font-semibold text-gray-700">
                                Reseller Remark
                            </label>
                            <textarea
                                wire:model="resellerRemark"
                                rows="4"
                                maxlength="1000"
                                style="text-transform: uppercase;"
                                class="w-full px-4 py-2 transition-all border-2 border-gray-300 rounded-lg resize-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                ></textarea>
                            @error('resellerRemark')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-3 bg-gray-50">
                        <button
                            wire:click="closeModal"
                            type="button"
                            class="px-6 py-2.5 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition-all">
                            <i class="mr-2 fas fa-times"></i>Cancel
                        </button>
                        <button
                            wire:click="submitRequest"
                            type="button"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="mr-2 fas fa-paper-plane"></i>Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- View License Modal -->
    @if($showLicenseModal && !empty($licenseDetails))
        <style>
            .license-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
                animation: licenseModalFadeIn 0.2s ease-out;
            }
            @keyframes licenseModalFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes licenseModalSlideUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .license-modal-content {
                background: white;
                border-radius: 12px;
                max-width: 850px;
                width: 90%;
                max-height: 85vh;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                animation: licenseModalSlideUp 0.3s ease-out;
            }
            .license-modal-header {
                padding: 1.25rem 1.5rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px 12px 0 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .license-modal-header h3 {
                margin: 0;
                color: white;
                font-size: 1.125rem;
                font-weight: 600;
            }
            .license-modal-header .close-btn {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 32px;
                height: 32px;
                border-radius: 8px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.125rem;
                transition: background 0.2s;
            }
            .license-modal-header .close-btn:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            .license-modal-body {
                padding: 1.5rem;
            }
            .license-summary-table {
                margin-bottom: 1.5rem;
            }
            .license-summary-table table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            .license-summary-table th {
                padding: 12px 8px;
                text-align: center;
                border: 1px solid #e5e7eb;
                vertical-align: middle;
                font-weight: 600;
                font-size: 14px;
            }
            .license-module-col {
                width: 18.75% !important;
                text-align: center !important;
                padding-left: 12px !important;
            }
            .license-headcount-col {
                width: 6.25% !important;
                text-align: center !important;
                font-weight: bold !important;
            }
            .license-attendance-module {
                background-color: rgba(34, 197, 94, 0.1) !important;
                color: rgba(34, 197, 94, 1) !important;
            }
            .license-attendance-count {
                background-color: rgba(34, 197, 94, 1) !important;
                color: white !important;
            }
            .license-leave-module {
                background-color: rgba(37, 99, 235, 0.1) !important;
                color: rgba(37, 99, 235, 1) !important;
            }
            .license-leave-count {
                background-color: rgba(37, 99, 235, 1) !important;
                color: white !important;
            }
            .license-claim-module {
                background-color: rgba(124, 58, 237, 0.1) !important;
                color: rgba(124, 58, 237, 1) !important;
            }
            .license-claim-count {
                background-color: rgba(124, 58, 237, 1) !important;
                color: white !important;
            }
            .license-payroll-module {
                background-color: rgba(249, 115, 22, 0.1) !important;
                color: rgba(249, 115, 22, 1) !important;
            }
            .license-payroll-count {
                background-color: rgba(249, 115, 22, 1) !important;
                color: white !important;
            }
            .license-invoice-card {
                background: white;
                padding: 1.5rem;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                margin-bottom: 1rem;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }
            .license-invoice-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transform: translateY(-2px);
            }
            .license-invoice-header {
                font-weight: 600;
                color: #667eea;
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 2px solid #e5e7eb;
            }
            .license-product-table {
                width: 100%;
                border-collapse: collapse;
            }
            .license-product-table thead {
                background: #f9fafb;
            }
            .license-product-table th {
                padding: 0.625rem 1rem;
                text-align: left;
                font-size: 0.75rem;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                border-bottom: 1px solid #e5e7eb;
            }
            .license-product-table td {
                padding: 0.625rem 1rem;
                font-size: 0.8125rem;
                color: #374151;
                border-bottom: 1px solid #f3f4f6;
            }
            .license-product-table tbody tr:hover {
                background: #f9fafb;
            }
            .license-product-row-ta {
                background-color: rgba(34, 197, 94, 0.1) !important;
            }
            .license-product-row-leave {
                background-color: rgba(37, 99, 235, 0.1) !important;
            }
            .license-product-row-claim {
                background-color: rgba(124, 58, 237, 0.1) !important;
            }
            .license-product-row-payroll {
                background-color: rgba(249, 115, 22, 0.1) !important;
            }
        </style>

        <div class="license-modal-overlay" wire:click.self="closeLicenseModal">
            <div class="license-modal-content">
                <div class="license-modal-header">
                    <h3>Active Licenses - {{ $licenseCompanyName }}</h3>
                    <button class="close-btn" wire:click="closeLicenseModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="license-modal-body">
                    <!-- License Summary Table -->
                    @if(isset($licenseDetails['_summary']))
                        <div class="license-summary-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="license-module-col license-attendance-module">ATTENDANCE</th>
                                        <th class="license-headcount-col license-attendance-count">{{ $licenseDetails['_summary']['attendance'] }}</th>
                                        <th class="license-module-col license-leave-module">LEAVE</th>
                                        <th class="license-headcount-col license-leave-count">{{ $licenseDetails['_summary']['leave'] }}</th>
                                        <th class="license-module-col license-claim-module">CLAIM</th>
                                        <th class="license-headcount-col license-claim-count">{{ $licenseDetails['_summary']['claim'] }}</th>
                                        <th class="license-module-col license-payroll-module">PAYROLL</th>
                                        <th class="license-headcount-col license-payroll-count">{{ $licenseDetails['_summary']['payroll'] }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    @endif

                    @php $hasLicenses = false; @endphp
                    @foreach($licenseDetails as $invoiceNo => $invoice)
                        @if($invoiceNo === '_summary') @continue @endif
                        @php $hasLicenses = true; @endphp
                        <div class="license-invoice-card">
                            <div class="license-invoice-header">
                                Invoice: {{ $invoiceNo }}
                            </div>

                            <table class="license-product-table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th style="width: 20%;">Total User</th>
                                        <th style="width: 12%;">Cycle</th>
                                        <th style="width: 20%;">Start Date</th>
                                        <th style="width: 20%;">Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice['products'] as $product)
                                        @php
                                            $productClass = '';
                                            if (strpos($product['f_name'], 'TimeTec TA') !== false) $productClass = 'license-product-row-ta';
                                            elseif (strpos($product['f_name'], 'TimeTec Leave') !== false) $productClass = 'license-product-row-leave';
                                            elseif (strpos($product['f_name'], 'TimeTec Claim') !== false) $productClass = 'license-product-row-claim';
                                            elseif (strpos($product['f_name'], 'TimeTec Payroll') !== false) $productClass = 'license-product-row-payroll';
                                        @endphp
                                        <tr class="{{ $productClass }}">
                                            <td>{{ $product['f_name'] }}</td>
                                            <td>{{ $product['f_total_user'] }}</td>
                                            <td>{{ $product['billing_cycle'] }}</td>
                                            <td>{{ date('Y-m-d', strtotime($product['f_start_date'])) }}</td>
                                            <td>{{ date('Y-m-d', strtotime($product['f_expiry_date'])) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    @if(!$hasLicenses)
                        <div style="padding: 3rem 1.5rem; text-align: center; color: #9ca3af;">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                            <p>No active licenses found for this subscriber.</p>
                        </div>
                    @endif
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
