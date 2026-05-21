<div style="padding:24px;">
    <style>
        .license-tooltip {
            position: relative;
            display: inline-block;
        }
        .license-tooltip .tooltip-content {
            visibility: hidden;
            position: absolute;
            z-index: 50;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            white-space: nowrap;
            margin-bottom: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .license-tooltip .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1f2937 transparent transparent transparent;
        }
        .license-tooltip:hover .tooltip-content {
            visibility: visible;
        }
        .tooltip-active {
            color: #22c55e;
            font-weight: 700;
        }
        .tooltip-inactive {
            color: #ef4444;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        .license-row:hover {
            background: #f0f9ff !important;
        }
    </style>

    {{-- Total License Card --}}
    <div style="margin-bottom:24px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);">
        <div style="background:linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding:14px 20px; display:flex; align-items:center; gap:10px;">
            <svg style="width:20px; height:20px; color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            <h4 style="font-size:1rem; font-weight:700; color:#fff; margin:0;">Total License</h4>
        </div>
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
            <thead>
                <tr style="background:#f0f4ff;">
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Attendance</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Leave</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Claim</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Payroll</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Onboarding</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Recruitment</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Appraisal</th>
                    <th style="width:12.5%; padding:10px 8px; font-size:0.7rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#4b5563; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Training</th>
                </tr>
            </thead>
            <tbody style="background:#fff;">
                <tr>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['attendance_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['attendance_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['attendance_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['leave_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['leave_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['leave_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['claim_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['claim_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['claim_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['payroll_user']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['payroll_user']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['payroll_user']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['onboarding_offboarding']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['onboarding_offboarding']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['onboarding_offboarding']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['recruitment']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['recruitment']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['recruitment']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['appraisal']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['appraisal']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['appraisal']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:8px 8px 16px 8px; text-align:center;">
                        <div class="license-tooltip">
                            <span style="font-size:1.5rem; font-weight:700; color:#2563eb; cursor:help; border-bottom:1px dashed #60a5fa;">{{ $productData['training']['total'] }}</span>
                            <div class="tooltip-content">
                                <div class="tooltip-active">Active: {{ $productData['training']['active'] }}</div>
                                <div class="tooltip-inactive">Inactive: {{ $productData['training']['inactive'] }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- License Table (Grouped by Invoice) --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:28px; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #7c3aed, #8b5cf6); display:flex; align-items:center; justify-content:center;">
                <svg style="width:20px; height:20px; color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <h4 style="font-size:1.125rem; font-weight:700; color:#111827; margin:0;">License Details</h4>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            @if($isSelectionMode)
                <button type="button" wire:click="exitSelectionMode"
                    style="display:inline-flex; align-items:center; padding:8px 16px; font-size:0.875rem; font-weight:500; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:6px; box-shadow:0 1px 2px rgba(0,0,0,0.05); cursor:pointer; transition:background-color 0.15s;"
                    onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='#fff'">
                    Cancel
                </button>
                <button type="button" wire:click="openBulkEditModal"
                    style="display:inline-flex; align-items:center; padding:8px 16px; font-size:0.875rem; font-weight:600; color:#fff; background:linear-gradient(135deg, #2563eb, #1d4ed8); border:none; border-radius:8px; box-shadow:0 2px 4px rgba(37,99,235,0.3); cursor:pointer; transition:all 0.15s; {{ count($selectedLicenseNos) === 0 ? 'opacity:0.5; cursor:not-allowed;' : '' }}"
                    onmouseenter="this.style.boxShadow='0 4px 8px rgba(37,99,235,0.4)'; this.style.transform='translateY(-1px)'" onmouseleave="this.style.boxShadow='0 2px 4px rgba(37,99,235,0.3)'; this.style.transform='translateY(0)'"
                    @if(count($selectedLicenseNos) === 0) disabled @endif>
                    <svg style="width:16px; height:16px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Selected ({{ count($selectedLicenseNos) }})
                </button>
            @else
                <button type="button" wire:click="enterSelectionMode"
                    style="display:inline-flex; align-items:center; padding:8px 16px; font-size:0.875rem; font-weight:600; color:#fff; background:linear-gradient(135deg, #2563eb, #1d4ed8); border:none; border-radius:8px; box-shadow:0 2px 4px rgba(37,99,235,0.3); cursor:pointer; transition:all 0.15s;"
                    onmouseenter="this.style.boxShadow='0 4px 8px rgba(37,99,235,0.4)'; this.style.transform='translateY(-1px)'" onmouseleave="this.style.boxShadow='0 2px 4px rgba(37,99,235,0.3)'; this.style.transform='translateY(0)'">
                    <svg style="width:16px; height:16px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Bulk Edit License
                </button>
            @endif
        </div>
    </div>

    {{-- License Filter Bar --}}
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
        <table style="width:100%; border-spacing:8px; border-collapse:separate;">
            <tr>
                <td style="width:18%;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">Start Date</label>
                    <input type="date"
                           wire:model.defer="filterStartDate"
                           style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none; background:#fff; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='#7c3aed'; this.style.boxShadow='0 0 0 3px rgba(124,58,237,0.1)'"
                           onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" />
                </td>
                <td style="width:18%;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">End Date</label>
                    <input type="date"
                           wire:model.defer="filterEndDate"
                           style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none; background:#fff; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='#7c3aed'; this.style.boxShadow='0 0 0 3px rgba(124,58,237,0.1)'"
                           onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" />
                </td>
                <td style="width:15%;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">Type</label>
                    <select wire:model.defer="filterType"
                            style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none; background:#fff; cursor:pointer;">
                        <option value="all">All Types</option>
                        <option value="PAID">Paid</option>
                        <option value="TRIAL">Trial</option>
                    </select>
                </td>
                <td style="width:15%;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">Status</label>
                    <select wire:model.defer="filterStatus"
                            style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none; background:#fff; cursor:pointer;">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </td>
                <td style="width:15%;">
                    <label style="display:block; font-size:0.7rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">Product</label>
                    <select wire:model.defer="filterProduct"
                            style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; outline:none; background:#fff; cursor:pointer;">
                        <option value="all">All Products</option>
                        <option value="TimeTec TA">TimeTec TA</option>
                        <option value="TimeTec Leave">TimeTec Leave</option>
                        <option value="TimeTec Claim">TimeTec Claim</option>
                        <option value="TimeTec Payroll">TimeTec Payroll</option>
                    </select>
                </td>
                <td style="width:9%; vertical-align:bottom;">
                    <button wire:click="applyFilters"
                            wire:loading.attr="disabled"
                            style="width:100%; padding:8px 16px; background:linear-gradient(135deg, #7c3aed, #6d28d9); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap; box-shadow:0 2px 4px rgba(124,58,237,0.3); transition:all 0.15s;"
                            onmouseenter="this.style.boxShadow='0 4px 8px rgba(124,58,237,0.4)'" onmouseleave="this.style.boxShadow='0 2px 4px rgba(124,58,237,0.3)'">
                        Search
                    </button>
                </td>
                <td style="width:9%; vertical-align:bottom;">
                    <button wire:click="resetLicenseFilters"
                            style="width:100%; padding:8px 16px; background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; white-space:nowrap; transition:all 0.15s;"
                            onmouseenter="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af'" onmouseleave="this.style.background='#fff'; this.style.borderColor='#d1d5db'">
                        Reset
                    </button>
                </td>
            </tr>
        </table>
    </div>

    <div style="overflow-x:auto; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
            <thead>
                <tr style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);">
                    @if($isSelectionMode)
                        <th style="width:4%; padding:12px; text-align:center; border-bottom:3px solid #7c3aed;">
                            <input type="checkbox"
                                wire:click="toggleSelectAll"
                                @checked(count($selectedLicenseNos) === count($licenseRecords) && count($licenseRecords) > 0)
                                style="height:16px; width:16px; cursor:pointer;">
                        </th>
                    @endif
                    <th style="width:{{ $isSelectionMode ? '4%' : '5%' }}; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:left; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">No</th>
                    <th style="width:{{ $isSelectionMode ? '20%' : '22%' }}; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:left; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">License Type</th>
                    <th style="width:10%; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">User Limit</th>
                    <th style="width:8%; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">Month</th>
                    <th style="width:12%; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">Start Date</th>
                    <th style="width:12%; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">End Date</th>
                    <th style="width:8%; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">Status</th>
                    <th style="width:{{ $isSelectionMode ? '15%' : '16%' }}; padding:12px 16px; font-size:0.75rem; font-weight:600; letter-spacing:0.05em; text-align:center; color:#e2e8f0; text-transform:uppercase; border-bottom:3px solid #7c3aed;">Action</th>
                </tr>
            </thead>
            <tbody style="background:#fff;" x-data="{ inv: {}, sub: {} }">
                @php
                    $trialSections = $groupedLicenseRecords['trials'] ?? [];
                    $invoiceSections = $groupedLicenseRecords['invoices'] ?? [];
                    $hasAnyRecords = count($trialSections) > 0 || count($invoiceSections) > 0;
                    $today = now()->startOfDay();
                @endphp

                {{-- TRIAL sub-groups (flat, always rendered on top) --}}
                @foreach($trialSections as $trialIndex => $subGroup)
                    @php
                        $subProducts = $subGroup['products'];
                        $subTotalUser = collect($subProducts)->sum('user_limit');
                        $subStartDate = collect($subProducts)->min('start_date');
                        $subEndDate = collect($subProducts)->max('end_date');
                        $subActiveCount = collect($subProducts)->filter(function($p) {
                            return strtolower($p['status'] ?? 'enabled') === 'enabled';
                        })->count();
                        $subIsActive = $subActiveCount >= (count($subProducts) - $subActiveCount);
                        $subAlpineKey = 'trial_' . $trialIndex;
                        $typeBadge = 'background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; border:1px solid #fcd34d;';
                    @endphp

                    <tr style="background:#f3f4f6; border-top:1px solid #d1d5db; cursor:pointer;"
                        onmouseenter="this.style.background='#e5e7eb'" onmouseleave="this.style.background='#f3f4f6'"
                        @click="sub['{{ $subAlpineKey }}'] = !sub['{{ $subAlpineKey }}']"
                        x-transition>
                        @if($isSelectionMode)
                            <td style="padding:8px 12px; text-align:center;" @click.stop></td>
                        @endif
                        <td style="padding:8px 12px; font-size:0.75rem;">
                            <span style="display:inline-flex; align-items:center;">
                                <svg style="width:14px; height:14px; margin-right:6px; color:#6b7280; transition:transform 0.2s; flex-shrink:0;" :style="sub['{{ $subAlpineKey }}'] ? 'transform: rotate(90deg)' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <span style="display:inline-flex; padding:2px 8px; font-weight:700; border-radius:6px; font-size:0.65rem; letter-spacing:0.05em; {{ $typeBadge }} box-shadow:0 1px 2px rgba(0,0,0,0.05);">{{ $subGroup['type'] }}</span>
                            </span>
                        </td>
                        <td style="padding:8px 12px; font-size:0.75rem; color:#4b5563;">
                            <span style="font-weight:600; color:#374151;">{{ $subGroup['year'] }}</span>
                            <span style="margin-left:8px; color:#9ca3af;">({{ count($subProducts) }} items)</span>
                        </td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; font-weight:600; color:#4b5563;">{{ $subTotalUser }}</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">-</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">{{ \Carbon\Carbon::parse($subStartDate)->format('d/m/Y') }}</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">{{ \Carbon\Carbon::parse($subEndDate)->format('d/m/Y') }}</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center;">
                            <span style="display:inline-flex; align-items:center; justify-content:center;">
                                <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $subIsActive ? '#22c55e' : '#ef4444' }};"></span>
                            </span>
                        </td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center;">
                            @if(!$isSelectionMode)
                                <button type="button" wire:click="openEditBufferGroupModal('{{ $subGroup['invoice_no'] }}')" @click.stop style="color:#2563eb; cursor:pointer; background:none; border:none; font-size:0.75rem; font-weight:600;" onmouseenter="this.style.color='#1e40af'; this.style.textDecoration='underline'" onmouseleave="this.style.color='#2563eb'; this.style.textDecoration='none'">Edit Set</button>
                            @endif
                        </td>
                    </tr>

                    @foreach($subProducts as $product)
                        @php
                            $startDate = \Carbon\Carbon::parse($product['start_date'])->startOfDay();
                            $endDate = \Carbon\Carbon::parse($product['end_date'])->endOfDay();
                            $isEnabled = strtolower($product['status'] ?? 'enabled') === 'enabled';
                        @endphp
                        <tr style="border-bottom:1px solid #f3f4f6;"
                            onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background=''"
                            x-show="sub['{{ $subAlpineKey }}']"
                            x-transition>
                            @if($isSelectionMode)
                                <td style="padding:12px; text-align:center;"></td>
                            @endif
                            <td style="padding:12px; {{ $isSelectionMode ? '' : 'padding-left:40px;' }} font-size:0.875rem; color:#111827;"></td>
                            <td style="padding:12px; font-size:0.875rem; color:#111827; padding-left:48px;">{{ $product['license_type'] }}</td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ $product['user_limit'] ?? $product['total_user'] ?? 0 }}</td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ $product['month'] }}</td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ \Carbon\Carbon::parse($product['start_date'])->format('d/m/Y') }}</td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ \Carbon\Carbon::parse($product['end_date'])->format('d/m/Y') }}</td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center;">
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:9999px; font-size:0.7rem; font-weight:600; {{ $isEnabled ? 'background:#dcfce7; color:#15803d;' : 'background:#fef2f2; color:#dc2626;' }}">
                                    <span style="width:8px; height:8px; border-radius:50%; display:inline-block; background-color:{{ $isEnabled ? '#22c55e' : '#ef4444' }};"></span>
                                    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td style="padding:12px; font-size:0.875rem; text-align:center;"></td>
                        </tr>
                    @endforeach
                @endforeach

                {{-- PAID invoice groups (Tier 1 invoice header → Tier 2 type+year → Tier 3 products) --}}
                @foreach($invoiceSections as $groupIndex => $group)
                    @php
                        $groupTotalUser = collect($group['products'])->sum('user_limit');
                        $groupStartDate = collect($group['products'])->min('start_date');
                        $groupEndDate = collect($group['products'])->max('end_date');
                        $groupActiveCount = collect($group['products'])->filter(function($p) {
                            return strtolower($p['status'] ?? 'enabled') === 'enabled';
                        })->count();
                        $groupInactiveCount = count($group['products']) - $groupActiveCount;
                        $groupIsActive = $groupActiveCount >= $groupInactiveCount;
                        $groupNos = collect($group['products'])->where('type', '!=', 'TRIAL')->pluck('no')->toArray();
                        $allGroupSelected = count($groupNos) > 0 && count(array_intersect($selectedLicenseNos, $groupNos)) === count($groupNos);
                        $displayInvoiceNo = $group['invoice_no'];
                        if (!str_starts_with($displayInvoiceNo, 'TTC') && $displayInvoiceNo !== '-') {
                            $displayInvoiceNo = 'TTC' . $displayInvoiceNo;
                        }
                    @endphp

                    {{-- Tier 1: Invoice No Header (PAID only) --}}
                    <tr style="background:#f3f4f6; border-top:1px solid #d1d5db; cursor:pointer;"
                        onmouseenter="this.style.background='#e5e7eb'" onmouseleave="this.style.background='#f3f4f6'"
                        @click="inv[{{ $groupIndex }}] = inv[{{ $groupIndex }}] === false">
                        @if($isSelectionMode)
                            <td style="padding:8px 12px; text-align:center;" @click.stop>
                                <input type="checkbox"
                                    wire:click="toggleGroupSelection('{{ $group['invoice_no'] }}')"
                                    @checked($allGroupSelected)
                                    style="height:16px; width:16px; cursor:pointer;">
                            </td>
                        @endif
                        <td colspan="2" style="padding:8px 12px; font-size:0.75rem;">
                            <span style="display:inline-flex; align-items:center; gap:6px;">
                                <svg style="width:16px; height:16px; color:#6b7280; transition:transform 0.2s; flex-shrink:0;" :style="inv[{{ $groupIndex }}] !== false ? 'transform: rotate(90deg)' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <a href="{{ url('/admin/view-sales-invoice?invoiceNo=' . $displayInvoiceNo . '&softwareHandoverId=' . ($group['software_handover_id'] ?? $softwareHandoverId) . '&from=products') }}"
                                   @click.stop
                                   style="font-weight:600; color:#2563eb; text-decoration:underline; cursor:pointer;"
                                   onmouseenter="this.style.color='#1e40af'" onmouseleave="this.style.color='#2563eb'">
                                    {{ $displayInvoiceNo }}
                                </a>
                            </span>
                        </td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">-</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">-</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; font-weight:500; color:#4b5563;">{{ $groupStartDate ? \Carbon\Carbon::parse($groupStartDate)->format('d/m/Y') : '-' }}</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center; font-weight:500; color:#4b5563;">{{ $groupEndDate ? \Carbon\Carbon::parse($groupEndDate)->format('d/m/Y') : '-' }}</td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center;">
                            <span style="display:inline-flex; align-items:center; justify-content:center;" title="{{ $groupActiveCount }} Enabled, {{ $groupInactiveCount }} Disabled">
                                <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $groupIsActive ? '#22c55e' : '#ef4444' }};"></span>
                            </span>
                        </td>
                        <td style="padding:8px 12px; font-size:0.75rem; text-align:center;"></td>
                    </tr>

                    {{-- Tier 2: Type + Year Sub-groups (PAID only) --}}
                    @foreach($group['sub_groups'] ?? [] as $subKey => $subGroup)
                        @php
                            $subProducts = $subGroup['products'];
                            $subTotalUser = collect($subProducts)->sum('user_limit');
                            $subStartDate = collect($subProducts)->min('start_date');
                            $subEndDate = collect($subProducts)->max('end_date');
                            $subActiveCount = collect($subProducts)->filter(function($p) {
                                return strtolower($p['status'] ?? 'enabled') === 'enabled';
                            })->count();
                            $subIsActive = $subActiveCount >= (count($subProducts) - $subActiveCount);
                            $subAlpineKey = $groupIndex . '_' . \Illuminate\Support\Str::slug($subKey);
                            $typeBadge = 'background:linear-gradient(135deg, #dcfce7, #bbf7d0); color:#166534; border:1px solid #86efac;';
                        @endphp

                        <tr style="background:#f9fafb; border-top:1px solid #e5e7eb; cursor:pointer;"
                            onmouseenter="this.style.background='#f3f4f6'" onmouseleave="this.style.background='#f9fafb'"
                            x-show="inv[{{ $groupIndex }}] !== false"
                            @click="sub['{{ $subAlpineKey }}'] = !sub['{{ $subAlpineKey }}']"
                            x-transition>
                            @if($isSelectionMode)
                                <td style="padding:8px 12px; text-align:center;" @click.stop></td>
                            @endif
                            <td style="padding:8px 12px; font-size:0.75rem; padding-left:32px;">
                                <span style="display:inline-flex; align-items:center;">
                                    <svg style="width:12px; height:12px; margin-right:4px; color:#9ca3af; transition:transform 0.2s;" :style="sub['{{ $subAlpineKey }}'] ? 'transform: rotate(90deg)' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span style="display:inline-flex; padding:2px 8px; font-weight:700; border-radius:6px; font-size:0.65rem; letter-spacing:0.05em; {{ $typeBadge }} box-shadow:0 1px 2px rgba(0,0,0,0.05);">{{ $subGroup['type'] }}</span>
                                </span>
                            </td>
                            <td style="padding:8px 12px; font-size:0.75rem; color:#4b5563; padding-left:24px;">
                                <span style="font-weight:600; color:#374151;">{{ $subGroup['year'] }}</span>
                                <span style="margin-left:8px; color:#9ca3af;">({{ count($subProducts) }} items)</span>
                            </td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center; font-weight:600; color:#4b5563;">{{ $subTotalUser }}</td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">-</td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">{{ \Carbon\Carbon::parse($subStartDate)->format('d/m/Y') }}</td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center; color:#6b7280;">{{ \Carbon\Carbon::parse($subEndDate)->format('d/m/Y') }}</td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center;">
                                <span style="display:inline-flex; align-items:center; justify-content:center;">
                                    <span style="width: 12px; height: 12px; border-radius: 50%; display: inline-block; background-color: {{ $subIsActive ? '#22c55e' : '#ef4444' }};"></span>
                                </span>
                            </td>
                            <td style="padding:8px 12px; font-size:0.75rem; text-align:center;"></td>
                        </tr>

                        {{-- Tier 3: Product Detail Rows --}}
                        @foreach($subProducts as $product)
                            @php
                                $startDate = \Carbon\Carbon::parse($product['start_date'])->startOfDay();
                                $endDate = \Carbon\Carbon::parse($product['end_date'])->endOfDay();
                                $isEnabled = strtolower($product['status'] ?? 'enabled') === 'enabled';
                            @endphp
                            <tr style="border-bottom:1px solid #f3f4f6; {{ $isSelectionMode && in_array($product['no'], $selectedLicenseNos) ? 'background:#eff6ff;' : '' }}"
                                onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='{{ $isSelectionMode && in_array($product['no'], $selectedLicenseNos) ? '#eff6ff' : '' }}'"
                                x-show="inv[{{ $groupIndex }}] !== false && sub['{{ $subAlpineKey }}']"
                                x-transition>
                                @if($isSelectionMode)
                                    <td style="padding:12px; text-align:center;">
                                        @if($endDate >= $today)
                                            <input type="checkbox"
                                                wire:click="toggleLicenseSelection({{ $product['no'] }})"
                                                @checked(in_array($product['no'], $selectedLicenseNos))
                                                style="height:16px; width:16px; cursor:pointer;">
                                        @endif
                                    </td>
                                @endif
                                <td style="padding:12px; {{ $isSelectionMode ? '' : 'padding-left:40px;' }} font-size:0.875rem; color:#111827;"></td>
                                <td style="padding:12px; font-size:0.875rem; color:#111827; padding-left:48px;">{{ $product['license_type'] }}</td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ $product['user_limit'] ?? $product['total_user'] ?? 0 }}</td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ $product['month'] }}</td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ \Carbon\Carbon::parse($product['start_date'])->format('d/m/Y') }}</td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center; color:#111827;">{{ \Carbon\Carbon::parse($product['end_date'])->format('d/m/Y') }}</td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center;">
                                    <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:9999px; font-size:0.7rem; font-weight:600; {{ $isEnabled ? 'background:#dcfce7; color:#15803d;' : 'background:#fef2f2; color:#dc2626;' }}">
                                        <span style="width:8px; height:8px; border-radius:50%; display:inline-block; background-color:{{ $isEnabled ? '#22c55e' : '#ef4444' }};"></span>
                                        {{ $isEnabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td style="padding:12px; font-size:0.875rem; text-align:center;">
                                    @if($isSelectionMode)
                                        <span style="color:#9ca3af; cursor:not-allowed;">Edit</span>
                                    @elseif($endDate >= $today)
                                        <button type="button" wire:click="openEditModal({{ $product['no'] }})" @click.stop style="color:#2563eb; cursor:pointer; background:none; border:none; font-size:0.875rem;" onmouseenter="this.style.color='#1e40af'; this.style.textDecoration='underline'" onmouseleave="this.style.color='#2563eb'; this.style.textDecoration='none'">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @endforeach

                @if(!$hasAnyRecords)
                    <tr>
                        <td colspan="{{ $isSelectionMode ? '10' : '9' }}" style="padding:20px 12px; text-align:center; color:#6b7280; font-size:0.8rem;">
                            <svg style="width:18px; height:18px; color:#9ca3af; margin:0 auto 4px auto; display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            No license records found
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Edit License Modal --}}
    @if($showEditModal)
        <div style="position:fixed; top:0; right:0; bottom:0; left:0; z-index:50; overflow-y:auto;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div style="display:flex; align-items:flex-end; justify-content:center; min-height:100vh; padding:16px 16px 80px 16px; text-align:center;">
                {{-- Background overlay --}}
                <div style="position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(107,114,128,0.75); transition:opacity 0.15s;" wire:click="closeEditModal"></div>

                {{-- Modal panel --}}
                <div style="display:inline-block; vertical-align:bottom; background:#fff; border-radius:8px; text-align:left; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); transform:translateY(0); transition:all 0.15s; margin:32px auto; max-width:512px; width:100%; vertical-align:middle;">
                    <form wire:submit="saveLicense">
                        {{-- Modal Header --}}
                        <div style="background:#f9fafb; padding:16px; border-bottom:1px solid #e5e7eb;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <h3 style="font-size:1.125rem; font-weight:600; color:#111827;" id="modal-title">
                                    Edit License
                                </h3>
                                <button type="button" wire:click="closeEditModal" style="color:#9ca3af; cursor:pointer; background:none; border:none;" onmouseenter="this.style.color='#6b7280'" onmouseleave="this.style.color='#9ca3af'">
                                    <svg style="height:24px; width:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p style="margin-top:4px; font-size:0.875rem; color:#6b7280;">{{ $editingLicenseType }}</p>
                        </div>

                        {{-- Modal Body --}}
                        <div style="background:#fff; padding:24px;">
                            <div>
                                {{-- User Limit --}}
                                <div>
                                    <label for="edit_total_user" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">User Limit</label>
                                    <input type="number" id="edit_total_user" wire:model="editForm.total_user"
                                        style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem;"
                                        min="1" required>
                                    @error('editForm.total_user') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Billing Cycle in Month --}}
                                <div style="margin-top:16px;">
                                    <label for="edit_month" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Billing Cycle in Month</label>
                                    <input type="number" id="edit_month" wire:model.live="editForm.month"
                                        style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem;"
                                        min="1" max="36" required>
                                    @error('editForm.month') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Start Date --}}
                                <div style="margin-top:16px;" x-data="{
                                    iso: '{{ $editForm['start_date'] ?? '' }}',
                                    display: '{{ $editForm['start_date'] ? \Carbon\Carbon::parse($editForm['start_date'])->format('d/m/Y') : '' }}',
                                    toISO(val) { if(!val) return ''; const p=val.split('/'); return p[2]+'-'+p[1]+'-'+p[0]; },
                                    toDisplay(val) { if(!val) return ''; const p=val.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; },
                                    syncFromCalendar() {
                                        this.display = this.toDisplay(this.iso);
                                        $wire.set('editForm.start_date', this.iso);
                                    },
                                    syncFromText() {
                                        if (this.display.length === 10) {
                                            this.iso = this.toISO(this.display);
                                            $wire.set('editForm.start_date', this.iso);
                                        }
                                    }
                                }">
                                    <label for="edit_start_date_display" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Start Date</label>
                                    <div style="margin-top:4px; display:flex; gap:8px; align-items:center;">
                                        <input type="text" id="edit_start_date_display" x-model="display"
                                            placeholder="dd/mm/yyyy" maxlength="10"
                                            x-on:input="
                                                let v = $el.value.replace(/[^0-9]/g,'');
                                                if(v.length>2) v = v.slice(0,2)+'/'+v.slice(2);
                                                if(v.length>5) v = v.slice(0,5)+'/'+v.slice(5);
                                                if(v.length>10) v = v.slice(0,10);
                                                display = v;
                                                syncFromText();
                                            "
                                            style="display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem;"
                                            required>

                                        <input type="date" x-ref="nativeDate" x-model="iso" x-on:change="syncFromCalendar()"
                                            style="position:absolute; opacity:0; pointer-events:none; width:1px; height:1px;">

                                        <button type="button"
                                            x-on:click="$refs.nativeDate.showPicker ? $refs.nativeDate.showPicker() : $refs.nativeDate.click()"
                                            style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:38px; border:1px solid #d1d5db; border-radius:6px; background:#fff; cursor:pointer; color:#4b5563;">
                                            <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('editForm.start_date') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                </div>

                                {{-- End Date --}}
                                <div style="margin-top:16px;" x-data="{
                                    display: '{{ $editForm['end_date'] ? \Carbon\Carbon::parse($editForm['end_date'])->format('d/m/Y') : '' }}'
                                }" x-effect="display = (() => { let v = $wire.editForm.end_date; if(!v) return ''; const p=v.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; })()">
                                    <label for="edit_end_date_display" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">End Date</label>
                                    <input type="text" id="edit_end_date_display" x-model="display"
                                        readonly
                                        style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem; background:#f9fafb; cursor:not-allowed;"
                                        required>
                                    @error('editForm.end_date') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Status --}}
                                <div style="margin-top:16px;">
                                    <label for="edit_status" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Status</label>
                                    <select id="edit_status" wire:model="editForm.status"
                                        style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem;">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('editForm.status') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div style="background:#f9fafb; padding:16px 24px; display:flex; flex-direction:row; justify-content:flex-end; gap:12px; border-top:1px solid #e5e7eb;">
                            <button type="button" wire:click="closeEditModal"
                                style="display:inline-flex; justify-content:center; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 24px; background:#fff; font-size:0.875rem; font-weight:500; color:#374151; cursor:pointer;"
                                onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='#fff'">
                                Cancel
                            </button>
                            <button type="submit"
                                style="display:inline-flex; justify-content:center; border-radius:8px; border:none; box-shadow:0 2px 4px rgba(37,99,235,0.3); padding:9px 24px; background:linear-gradient(135deg, #2563eb, #1d4ed8); font-size:0.875rem; font-weight:600; color:#fff; cursor:pointer; transition:all 0.15s;"
                                onmouseenter="this.style.background='#1d4ed8'" onmouseleave="this.style.background='#2563eb'">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Edit License Modal --}}
    @if($showBulkEditModal)
        <div style="position:fixed; top:0; right:0; bottom:0; left:0; z-index:50; overflow-y:auto;" aria-labelledby="bulk-modal-title" role="dialog" aria-modal="true">
            <div style="display:flex; align-items:flex-end; justify-content:center; min-height:100vh; padding:16px 16px 80px 16px; text-align:center;">
                {{-- Background overlay --}}
                <div style="position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(107,114,128,0.75); transition:opacity 0.15s;" wire:click="closeBulkEditModal"></div>

                {{-- Modal panel --}}
                <div style="display:inline-block; vertical-align:bottom; background:#fff; border-radius:8px; text-align:left; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); transform:translateY(0); transition:all 0.15s; margin:32px auto; max-width:512px; width:100%; vertical-align:middle;">
                    <form wire:submit="saveBulkEdit">
                        {{-- Modal Header --}}
                        <div style="background:#f9fafb; padding:16px; border-bottom:1px solid #e5e7eb;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div>
                                    <h3 style="font-size:1.125rem; font-weight:600; color:#111827;" id="bulk-modal-title">
                                        Bulk Edit License
                                    </h3>
                                    <p style="margin-top:4px; font-size:0.875rem; color:#6b7280;">Update multiple licenses at once. Check the fields you want to modify.</p>
                                </div>
                                <button type="button" wire:click="closeBulkEditModal" style="color:#9ca3af; cursor:pointer; background:none; border:none;" onmouseenter="this.style.color='#6b7280'" onmouseleave="this.style.color='#9ca3af'">
                                    <svg style="height:24px; width:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal Body --}}
                        <div style="background:#fff; padding:24px;">
                            <div>
                                {{-- Info Banner --}}
                                <div style="display:flex; align-items:flex-start; padding:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px;">
                                    <svg style="width:20px; height:20px; color:#3b82f6; margin-top:2px; margin-right:8px; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div style="font-size:0.875rem; color:#1d4ed8; flex:1;">
                                        <p style="font-weight:500;">Editing {{ count($selectedLicenseNos) }} selected license(s):</p>
                                        <div style="margin-top:8px; max-height:128px; overflow-y:auto;">
                                            <table style="width:100%; font-size:0.75rem;">
                                                <thead>
                                                    <tr style="text-align:left; color:#1e40af;">
                                                        <th style="padding-bottom:4px; padding-right:12px;">License</th>
                                                        <th style="padding-bottom:4px; padding-right:12px; white-space:nowrap; width:80px;">Start Date</th>
                                                        <th style="padding-bottom:4px; white-space:nowrap; width:80px;">End Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($this->getSelectedLicenseDetails() as $license)
                                                        <tr>
                                                            <td style="padding:2px 12px 2px 0;">{{ $license['name'] }}</td>
                                                            <td style="padding:2px 12px 2px 0; white-space:nowrap;">{{ $license['start_date'] }}</td>
                                                            <td style="padding:2px 0; white-space:nowrap;">{{ $license['end_date'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p style="margin-top:8px; font-size:0.75rem; color:#2563eb;">Only checked fields below will be modified.</p>
                                    </div>
                                </div>

                                {{-- Total User --}}
                                <div style="display:flex; align-items:flex-start; gap:16px; margin-top:16px;">
                                    <div style="display:flex; align-items:center; height:36px; margin-top:24px;">
                                        <input type="checkbox" id="bulk_enable_total_user" wire:model.live="bulkEditEnabled.total_user"
                                            style="height:16px; width:16px; cursor:pointer;">
                                    </div>
                                    <div style="flex:1;">
                                        <label for="bulk_total_user" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Total User</label>
                                        <input type="number" id="bulk_total_user" wire:model="bulkEditForm.total_user"
                                            style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem; {{ !$bulkEditEnabled['total_user'] ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}"
                                            min="1" placeholder="Enter total users"
                                            @if(!$bulkEditEnabled['total_user']) disabled @endif>
                                        @error('bulkEditForm.total_user') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Start Date --}}
                                <div style="display:flex; align-items:flex-start; gap:16px; margin-top:16px;">
                                    <div style="display:flex; align-items:center; height:36px; margin-top:24px;">
                                        <input type="checkbox" id="bulk_enable_start_date" wire:model.live="bulkEditEnabled.start_date"
                                            style="height:16px; width:16px; cursor:pointer;">
                                    </div>
                                    <div style="flex:1;">
                                        <label for="bulk_start_date" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Start Date</label>
                                        <input type="date" id="bulk_start_date" wire:model="bulkEditForm.start_date"
                                            style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem; {{ !$bulkEditEnabled['start_date'] ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}"
                                            @if(!$bulkEditEnabled['start_date']) disabled @endif>
                                        @error('bulkEditForm.start_date') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- End Date --}}
                                <div style="display:flex; align-items:flex-start; gap:16px; margin-top:16px;">
                                    <div style="display:flex; align-items:center; height:36px; margin-top:24px;">
                                        <input type="checkbox" id="bulk_enable_end_date" wire:model.live="bulkEditEnabled.end_date"
                                            style="height:16px; width:16px; cursor:pointer;">
                                    </div>
                                    <div style="flex:1;">
                                        <label for="bulk_end_date" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">End Date</label>
                                        <input type="date" id="bulk_end_date" wire:model="bulkEditForm.end_date"
                                            style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem; {{ !$bulkEditEnabled['end_date'] ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}"
                                            @if(!$bulkEditEnabled['end_date']) disabled @endif>
                                        @error('bulkEditForm.end_date') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div style="display:flex; align-items:flex-start; gap:16px; margin-top:16px;">
                                    <div style="display:flex; align-items:center; height:36px; margin-top:24px;">
                                        <input type="checkbox" id="bulk_enable_status" wire:model.live="bulkEditEnabled.status"
                                            style="height:16px; width:16px; cursor:pointer;">
                                    </div>
                                    <div style="flex:1;">
                                        <label for="bulk_status" style="display:block; font-size:0.875rem; font-weight:500; color:#374151;">Status</label>
                                        <select id="bulk_status" wire:model="bulkEditForm.status"
                                            style="margin-top:4px; display:block; width:100%; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 12px; font-size:0.875rem; {{ !$bulkEditEnabled['status'] ? 'background:#f3f4f6; cursor:not-allowed;' : '' }}"
                                            @if(!$bulkEditEnabled['status']) disabled @endif>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        @error('bulkEditForm.status') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div style="background:#f9fafb; padding:16px 24px; display:flex; flex-direction:row; justify-content:flex-end; gap:12px; border-top:1px solid #e5e7eb;">
                            <button type="button" wire:click="closeBulkEditModal"
                                style="display:inline-flex; justify-content:center; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 24px; background:#fff; font-size:0.875rem; font-weight:500; color:#374151; cursor:pointer;"
                                onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='#fff'">
                                Cancel
                            </button>
                            <button type="submit"
                                style="display:inline-flex; justify-content:center; border-radius:8px; border:none; box-shadow:0 2px 4px rgba(37,99,235,0.3); padding:9px 24px; background:linear-gradient(135deg, #2563eb, #1d4ed8); font-size:0.875rem; font-weight:600; color:#fff; cursor:pointer; transition:all 0.15s;"
                                onmouseenter="this.style.background='#1d4ed8'" onmouseleave="this.style.background='#2563eb'">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Proforma Invoice Modal --}}
    @if($showPiModal)
        <div style="position:fixed; top:0; right:0; bottom:0; left:0; z-index:50; overflow-y:auto;" aria-labelledby="pi-modal-title" role="dialog" aria-modal="true">
            <div style="display:flex; align-items:flex-end; justify-content:center; min-height:100vh; padding:16px 16px 80px 16px; text-align:center;">
                {{-- Background overlay --}}
                <div style="position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(107,114,128,0.75); transition:opacity 0.15s;" wire:click="closePiModal"></div>

                {{-- Modal panel --}}
                <div style="display:inline-block; vertical-align:bottom; background:#fff; border-radius:16px; text-align:left; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05); transform:translateY(0); transition:all 0.15s; margin:32px auto; max-width:896px; width:100%; vertical-align:middle;">
                    {{-- Modal Header --}}
                    <div style="background:linear-gradient(to right, #0891b2, #2563eb); padding:16px 24px;">
                        <div style="display:flex; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:16px;">
                                <img src="{{ asset('img/logo-ttc.png') }}" alt="TimeTec" style="height:40px; width:auto;">
                                <div>
                                    <h3 style="font-size:1.25rem; font-weight:700; color:#fff;" id="pi-modal-title">
                                        PROFORMA INVOICE
                                    </h3>
                                </div>
                            </div>
                            <button type="button" wire:click="closePiModal" style="color:#fff; cursor:pointer; background:none; border:none;" onmouseenter="this.style.color='#a5f3fc'" onmouseleave="this.style.color='#fff'">
                                <svg style="height:24px; width:24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div style="background:#fff; padding:24px; max-height:75vh; overflow-y:auto;">
                        {{-- Loading State --}}
                        @if($piLoading)
                            <div style="display:flex; align-items:center; justify-content:center; padding:48px 0;">
                                <div style="text-align:center;">
                                    <svg class="animate-spin" style="height:40px; width:40px; color:#0891b2; margin:0 auto 16px auto;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p style="color:#6b7280;">Loading proforma invoice...</p>
                                </div>
                            </div>
                        @elseif($piError)
                            {{-- Error State --}}
                            <div style="text-align:center; padding:20px 0;">
                                <svg style="width:20px; height:20px; color:#ef4444; margin:0 auto 6px auto; display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p style="color:#6b7280; font-size:0.8rem;">{{ $piError }}</p>
                            </div>
                        @elseif(!empty($apiPiData))
                            {{-- API-based PI Display (TimeTec Backend Format) --}}
                            @php
                                $pi = $apiPiData;
                                $billTo = $pi['bill_to'] ?? [];
                                $items = $pi['items'] ?? [];
                                $currency = $pi['currency'] ?? 'MYR';
                                $subtotal = $pi['subtotal'] ?? 0;
                                $discount = $pi['discount'] ?? 0;
                                $sst = $pi['sst'] ?? 0;
                                $sstRate = $pi['sst_rate'] ?? 8;
                                $totalAmount = $pi['total_amount'] ?? $pi['amount_due'] ?? 0;
                                $status = $pi['status'] ?? 'Pending';
                            @endphp

                            {{-- Bill To & Invoice Info Section --}}
                            <table style="margin-bottom:24px; width:100%;">
                                <tr>
                                    <td style="font-size:13px; text-align:left; vertical-align:top; padding:0 0 0 6px;">
                                        <strong>Bill To:</strong><br>
                                        {{ $billTo['company_name'] ?? ($companyData['company_name'] ?? '-') }}<br>
                                        @if(!empty($billTo['email'])){{ $billTo['email'] }}<br>@endif
                                        @if(!empty($billTo['registration_no'])){{ $billTo['registration_no'] }}<br>@endif
                                        {{ $billTo['address'] ?? '-' }}<br>
                                        Malaysia
                                    </td>
                                    <td style="font-size:13px; text-align:left; vertical-align:top; padding:0;">
                                        <div>
                                            P. Invoice No: {{ $selectedInvoiceNo }}<br>
                                            Date: {{ $pi['date'] ?? $pi['invoice_date'] ?? '-' }}<br>
                                            Status:
                                            @if($status === 'PAID')
                                                <font style="color:#16a34a;"><strong>PAID</strong></font>
                                            @elseif($status === 'Cancel')
                                                <font style="color:#dc2626;"><strong>CANCELLED</strong></font>
                                            @else
                                                <font style="color:#dc2626;"><strong>UNPAID</strong></font>
                                            @endif
                                            <br>
                                            TRX Rate (RM): {{ $pi['trx_rate'] ?? '1' }}
                                            <h2 style="margin-bottom:0; margin-top:12px; text-align:left; color:#000;">
                                                <strong>Amount Due: {{ $currency }} {{ number_format($totalAmount, 2) }}</strong>
                                            </h2>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- Items Table --}}
                            <div style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; margin-bottom:24px;">
                                <table style="min-width:100%; border-collapse:collapse;">
                                    <thead style="background:#f3f4f6;">
                                        <tr>
                                            <th style="padding:12px 16px; text-align:left; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:48px;">No.</th>
                                            <th style="padding:12px 16px; text-align:left; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase;">Description</th>
                                            <th style="padding:12px 16px; text-align:center; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:64px;">Qty</th>
                                            <th style="padding:12px 16px; text-align:right; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:80px;">Price</th>
                                            <th style="padding:12px 16px; text-align:center; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:96px;">Billing Cycle</th>
                                            <th style="padding:12px 16px; text-align:center; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:80px;">Discount</th>
                                            <th style="padding:12px 16px; text-align:right; font-size:0.75rem; font-weight:600; color:#4b5563; text-transform:uppercase; width:96px;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background:#fff;">
                                        @php
                                            $groupedByYear = collect($items)->groupBy(function($item) {
                                                if (isset($item['year'])) return $item['year'];
                                                if (!empty($item['period'])) {
                                                    return substr($item['period'], 6, 4);
                                                }
                                                return 'Other';
                                            });
                                            $itemCounter = 0;
                                        @endphp
                                        @forelse($groupedByYear as $yearLabel => $yearItems)
                                            {{-- Year Header Row --}}
                                            <tr style="background:#eff6ff;">
                                                <td colspan="7" style="padding:8px 16px; font-size:0.875rem; font-weight:600; color:#1e40af;">
                                                    Year {{ $yearLabel }}
                                                </td>
                                            </tr>
                                            {{-- Items for this year --}}
                                            @foreach($yearItems as $item)
                                                @php $itemCounter++; @endphp
                                                <tr style="background:{{ $itemCounter % 2 === 0 ? '#f9fafb' : '#fff' }}; border-bottom:1px solid #e5e7eb;">
                                                    <td style="padding:12px 16px; font-size:0.875rem; color:#374151;">{{ $itemCounter }}.</td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; color:#111827;">
                                                        {{ $item['description'] ?? '-' }}
                                                        @if(!empty($item['period']))
                                                            <br><span style="font-size:0.75rem; color:#6b7280;">[{{ $item['period'] }}]</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; text-align:center; color:#374151;">{{ $item['qty'] ?? $item['quantity'] ?? 0 }}</td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; text-align:right; color:#374151;">{{ number_format($item['price'] ?? $item['unit_price'] ?? 0, 2) }}</td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; text-align:center; color:#374151;">{{ $item['billing_cycle'] ?? $item['month'] ?? '-' }}</td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; text-align:center; color:#374151;">{{ $item['discount'] ?? '0%' }}</td>
                                                    <td style="padding:12px 16px; font-size:0.875rem; text-align:right; font-weight:500; color:#111827;">{{ number_format($item['amount'] ?? 0, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="7" style="padding:24px 16px; text-align:center; color:#6b7280; font-size:0.875rem;">No items found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Totals Section --}}
                            <div style="display:flex; justify-content:flex-end; margin-bottom:24px;">
                                <div style="width:288px; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                    <table style="width:100%; border-collapse:collapse;">
                                        <tbody>
                                            <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                                <td style="padding:8px 16px; font-size:0.875rem; color:#4b5563; text-align:right;">Discount:</td>
                                                <td style="padding:8px 16px; font-size:0.875rem; font-weight:500; text-align:right; width:112px;">-{{ number_format($discount, 2) }}</td>
                                            </tr>
                                            <tr style="border-bottom:1px solid #e5e7eb;">
                                                <td style="padding:8px 16px; font-size:0.875rem; color:#4b5563; text-align:right;">Subtotal:</td>
                                                <td style="padding:8px 16px; font-size:0.875rem; font-weight:500; text-align:right;">{{ number_format($subtotal, 2) }}</td>
                                            </tr>
                                            <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                                                <td style="padding:8px 16px; font-size:0.875rem; color:#4b5563; text-align:right;">SST ({{ $sstRate }}%):</td>
                                                <td style="padding:8px 16px; font-size:0.875rem; font-weight:500; text-align:right;">{{ number_format($sst, 2) }}</td>
                                            </tr>
                                            <tr style="border-bottom:1px solid #e5e7eb;">
                                                <td style="padding:8px 16px; font-size:0.875rem; color:#4b5563; text-align:right;">Total sales incl SST:</td>
                                                <td style="padding:8px 16px; font-size:0.875rem; font-weight:500; text-align:right;">{{ number_format($subtotal + $sst, 2) }}</td>
                                            </tr>
                                            <tr style="background:#ecfeff;">
                                                <td style="padding:12px 16px; font-size:0.875rem; font-weight:600; color:#155e75; text-align:right;">Amount Due ({{ $currency }}):</td>
                                                <td style="padding:12px 16px; font-size:1rem; font-weight:700; color:#0e7490; text-align:right;">{{ number_format($totalAmount, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Terms & Conditions --}}
                            <div style="border-top:1px solid #e5e7eb; padding-top:16px;">
                                <h5 style="font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:8px;">Terms & Conditions:</h5>
                                <ol style="font-size:0.75rem; color:#6b7280; list-style-type:decimal; list-style-position:inside; padding:0; margin:0;">
                                    <li style="margin-bottom:4px;">Please keep this Invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)</li>
                                    <li style="margin-bottom:4px;">All purchases with TimeTec Cloud Sdn Bhd are bound by the Terms & Conditions.</li>
                                    <li style="margin-bottom:4px;">Questions about your invoice, email us at info@timeteccloud.com.</li>
                                    <li>Bank Account Details (for TT payment): TimeTec Cloud Sdn Bhd (832542-W), United Overseas Bank (M) Bhd, Puchong branch</li>
                                </ol>
                            </div>

                            {{-- View Full Page Button --}}
                            @if($softwareHandoverId)
                                <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e5e7eb; display:flex; justify-content:center;">
                                    <a href="{{ route('pdf.license-proforma-invoice', ['softwareHandover' => $softwareHandoverId, 'invoiceNo' => $selectedInvoiceNo]) }}"
                                       target="_blank"
                                       style="display:inline-flex; align-items:center; padding:10px 24px; font-size:0.875rem; font-weight:500; color:#fff; background:#0891b2; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.05); text-decoration:none; transition:background-color 0.15s;"
                                       onmouseenter="this.style.background='#0e7490'" onmouseleave="this.style.background='#0891b2'">
                                        <svg style="width:20px; height:20px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        View Full Page
                                    </a>
                                </div>
                            @endif
                        @elseif(count($piData) > 0)
                            {{-- Local Quotation-based PI Display (Fallback) --}}
                            @foreach($piData as $index => $pi)
                                <div style="margin-bottom:24px; {{ $index > 0 ? 'padding-top:24px; border-top:1px solid #e5e7eb;' : '' }}">
                                    {{-- PI Header --}}
                                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                                        <div>
                                            <h4 style="font-size:1rem; font-weight:600; color:#111827;">{{ $pi['pi_reference_no'] }}</h4>
                                            <p style="font-size:0.875rem; color:#6b7280;">{{ $pi['company_name'] }}</p>
                                        </div>
                                        <a href="{{ url('/generate-proforma-invoice-pdf/' . $pi['id']) }}"
                                           target="_blank"
                                           style="display:inline-flex; align-items:center; padding:6px 12px; font-size:0.875rem; font-weight:500; color:#0891b2; background:#ecfeff; border-radius:6px; text-decoration:none; transition:background-color 0.15s;"
                                           onmouseenter="this.style.background='#cffafe'" onmouseleave="this.style.background='#ecfeff'">
                                            <svg style="width:16px; height:16px; margin-right:4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            View PDF
                                        </a>
                                    </div>

                                    {{-- PI Info Grid --}}
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; font-size:0.875rem;">
                                        <div>
                                            <span style="color:#6b7280;">Date:</span>
                                            <span style="margin-left:8px; font-weight:500; color:#111827;">{{ $pi['quotation_date'] }}</span>
                                        </div>
                                        <div>
                                            <span style="color:#6b7280;">Currency:</span>
                                            <span style="margin-left:8px; font-weight:500; color:#111827;">{{ $pi['currency'] }}</span>
                                        </div>
                                        <div>
                                            <span style="color:#6b7280;">Salesperson:</span>
                                            <span style="margin-left:8px; font-weight:500; color:#111827;">{{ $pi['salesperson'] }}</span>
                                        </div>
                                        <div>
                                            <span style="color:#6b7280;">Total:</span>
                                            <span style="margin-left:8px; font-weight:500; color:#16a34a;">{{ $pi['currency'] }} {{ number_format($pi['total_amount'], 2) }}</span>
                                        </div>
                                    </div>

                                    {{-- PI Items Table --}}
                                    @if(count($pi['items']) > 0)
                                        <div style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">
                                            <table style="min-width:100%; border-collapse:collapse;">
                                                <thead style="background:#f9fafb;">
                                                    <tr>
                                                        <th style="padding:8px 16px; text-align:left; font-size:0.75rem; font-weight:500; color:#6b7280; text-transform:uppercase;">Description</th>
                                                        <th style="padding:8px 16px; text-align:center; font-size:0.75rem; font-weight:500; color:#6b7280; text-transform:uppercase;">Qty</th>
                                                        <th style="padding:8px 16px; text-align:right; font-size:0.75rem; font-weight:500; color:#6b7280; text-transform:uppercase;">Unit Price</th>
                                                        <th style="padding:8px 16px; text-align:right; font-size:0.75rem; font-weight:500; color:#6b7280; text-transform:uppercase;">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody style="background:#fff;">
                                                    @foreach($pi['items'] as $item)
                                                        <tr style="border-bottom:1px solid #e5e7eb;">
                                                            <td style="padding:8px 16px; font-size:0.875rem; color:#111827;">{{ $item['description'] }}</td>
                                                            <td style="padding:8px 16px; font-size:0.875rem; text-align:center; color:#111827;">{{ $item['quantity'] }}</td>
                                                            <td style="padding:8px 16px; font-size:0.875rem; text-align:right; color:#111827;">{{ number_format($item['unit_price'], 2) }}</td>
                                                            <td style="padding:8px 16px; font-size:0.875rem; text-align:right; color:#111827;">{{ number_format($item['amount'], 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot style="background:#f9fafb;">
                                                    <tr>
                                                        <td colspan="3" style="padding:8px 16px; font-size:0.875rem; font-weight:600; color:#111827; text-align:right;">Total:</td>
                                                        <td style="padding:8px 16px; font-size:0.875rem; font-weight:600; color:#16a34a; text-align:right;">{{ $pi['currency'] }} {{ number_format($pi['total_amount'], 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @else
                                        <p style="font-size:0.875rem; color:#6b7280; font-style:italic;">No items found in this PI.</p>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            {{-- No Data Found --}}
                            <div style="text-align:center; padding:20px 0;">
                                <svg style="width:18px; height:18px; color:#9ca3af; margin:0 auto 4px auto; display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p style="color:#6b7280; font-size:0.8rem;">No Proforma Invoice Found</p>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div style="background:#f9fafb; padding:16px 24px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e5e7eb;">
                        <div style="font-size:0.75rem; color:#6b7280;">
                            Invoice No: {{ $selectedInvoiceNo }}
                        </div>
                        <button type="button" wire:click="closePiModal"
                            style="display:inline-flex; justify-content:center; border-radius:6px; border:1px solid #d1d5db; box-shadow:0 1px 2px rgba(0,0,0,0.05); padding:8px 24px; background:#fff; font-size:0.875rem; font-weight:500; color:#374151; cursor:pointer;"
                            onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background='#fff'">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
