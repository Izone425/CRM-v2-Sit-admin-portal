<div style="padding:24px;">

    {{-- Search/Filter Bar --}}
    <table style="width: 100%; border-spacing: 8px; border-collapse: separate; margin-bottom:32px;">
        <tr>
            <td style="width: 30%;">
                <input type="text"
                       wire:model.defer="search"
                       wire:keydown.enter="searchCustomers"
                       placeholder="Search by name..."
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 15%;">
                <select wire:model.defer="statusFilter"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; background: white;">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </td>
            <td style="width: 15%;">
                <input type="date"
                       wire:model.defer="startDate"
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 15%;">
                <input type="date"
                       wire:model.defer="endDate"
                       style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none;" />
            </td>
            <td style="width: 10%;">
                <button wire:click="searchCustomers"
                        wire:loading.attr="disabled"
                        style="width: 100%; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap;">
                    Search
                </button>
            </td>
            <td style="width: 10%;">
                <button wire:click="resetFilters"
                        style="width: 100%; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap;">
                    Reset
                </button>
            </td>
        </tr>
    </table>

    @if ($showResellersSection)
    {{-- Resellers Section --}}
    <div style="margin-bottom:32px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
            <h3 style="font-size:1.125rem; font-weight:700; color:#1f2937;">Resellers</h3>
            <span style="font-size:0.875rem; color:#16a34a; font-weight:500;">Active: {{ $resellerActiveCount }}</span>
            <span style="font-size:0.875rem; color:#6b7280;">| Inactive: {{ $resellerInactiveCount }}</span>
        </div>
        <div style="height:16px;"></div>
        <div style="overflow-x:auto; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);">
            <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(to bottom, #f3f4f6, #e5e7eb);">
                        <th style="width: 15%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:left; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Reseller Id
                        </th>
                        <th style="width: 40%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:left; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Reseller Name
                        </th>
                        <th style="width: 25%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:center; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Joined Date
                        </th>
                        <th style="width: 20%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:center; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $index => $reseller)
                        <tr style="background:{{ $index % 2 === 0 ? '#fff' : '#f9fafb' }}; border-bottom:1px solid #e5e7eb;"
                            onmouseenter="this.style.background='#f3f4f6'" onmouseleave="this.style.background='{{ $index % 2 === 0 ? '#fff' : '#f9fafb' }}'">
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap;">
                                @if($reseller['software_handover_id'])
                                    <a href="{{ url('/admin/hr-company-license-details?' . http_build_query([
                                        'hrAccountId' => $reseller['hr_account_id'] ?? null,
                                        'hrCompanyId' => $reseller['hr_company_id'] ?? null,
                                    ])) }}"
                                       style="font-size:0.875rem; color:#2563eb; font-weight:500; text-decoration:none;"
                                       onmouseenter="this.style.color='#1e40af';this.style.textDecoration='underline'" onmouseleave="this.style.color='#2563eb';this.style.textDecoration='none'">
                                        {{ $reseller['id'] }}
                                    </a>
                                @else
                                    <span style="font-size:0.875rem; color:#111827;">{{ $reseller['id'] }}</span>
                                @endif
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; font-size:0.875rem; color:#111827;">
                                {{ $reseller['name'] }}
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap; font-size:0.875rem; color:#374151; text-align:center;">
                                {{ $reseller['joined_date'] }}
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap; text-align:center;">
                                @if(strtolower($reseller['status']) === 'active')
                                    <span style="font-size:0.875rem; font-weight:500; color:#16a34a;">Active</span>
                                @else
                                    <span style="font-size:0.875rem; font-weight:500; color:#6b7280;">{{ $reseller['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:16px; text-align:center;">
                                <p style="color:#6b7280; font-size:0.8rem;">No resellers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="height:16px;"></div>
    @endif
    {{-- Customers (Subscriber) Section --}}
    <div>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
            <h3 style="font-size:1.125rem; font-weight:700; color:#1f2937;">Customers (Subscriber)</h3>
            <span style="font-size:0.875rem; color:#16a34a; font-weight:500;">Active: {{ $subscriberActiveCount }}</span>
            <span style="font-size:0.875rem; color:#6b7280;">| Inactive: {{ $subscriberInactiveCount }}</span>
        </div>
        <div style="height:16px;"></div>
        <div style="overflow-x:auto; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);">
            <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(to bottom, #f3f4f6, #e5e7eb);">
                        <th style="width: 15%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:left; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Customer Id
                        </th>
                        <th style="width: 40%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:left; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Customer Name
                        </th>
                        <th style="width: 25%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:center; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Joined Date
                        </th>
                        <th style="width: 20%; padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; text-align:center; font-size:0.75rem; font-weight:600; color:#374151; letter-spacing:0.05em;">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($subscribers as $index => $subscriber)
                        <tr style="background:{{ $index % 2 === 0 ? '#fff' : '#f9fafb' }}; border-bottom:1px solid #e5e7eb;"
                            onmouseenter="this.style.background='#f3f4f6'" onmouseleave="this.style.background='{{ $index % 2 === 0 ? '#fff' : '#f9fafb' }}'">
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap;">
                                @if($subscriber['software_handover_id'])
                                    <a href="{{ url('/admin/hr-company-license-details?' . http_build_query([
                                        'hrAccountId' => $subscriber['hr_account_id'] ?? null,
                                        'hrCompanyId' => $subscriber['hr_company_id'] ?? null,
                                    ])) }}"
                                       style="font-size:0.875rem; color:#2563eb; font-weight:500; text-decoration:none;"
                                       onmouseenter="this.style.color='#1e40af';this.style.textDecoration='underline'" onmouseleave="this.style.color='#2563eb';this.style.textDecoration='none'">
                                        {{ $subscriber['id'] }}
                                    </a>
                                @else
                                    <span style="font-size:0.875rem; color:#111827;">{{ $subscriber['id'] }}</span>
                                @endif
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; font-size:0.875rem; color:#111827;">
                                {{ $subscriber['name'] }}
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap; font-size:0.875rem; color:#374151; text-align:center;">
                                {{ $subscriber['joined_date'] }}
                            </td>
                            <td style="padding-left:16px; padding-right:16px; padding-top:10px; padding-bottom:10px; white-space:nowrap; text-align:center;">
                                @if(strtolower($subscriber['status']) === 'active')
                                    <span style="font-size:0.875rem; font-weight:500; color:#16a34a;">Active</span>
                                @else
                                    <span style="font-size:0.875rem; font-weight:500; color:#6b7280;">{{ $subscriber['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:16px; text-align:center;">
                                <p style="color:#6b7280; font-size:0.8rem;">No subscribers found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
