<div style="position: relative;">
    <!-- Title -->
    <div class="title-section">
        <h2>Dealer Information</h2>
        <p>View your dealer companies and their subscribers</p>
    </div>

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search dealer company name..."
            class="search-input"
        >
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>
                        <button wire:click="sortBy('f_company_name')">
                            Dealer Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'f_company_name')
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
                        <button wire:click="sortBy('f_reg_date')">
                            Registered Date
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'f_reg_date')
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
                        <button wire:click="sortBy('subscriber_count')">
                            Subscribers
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_count')
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
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($dealers as $dealer)
                    <tr wire:click="toggleDealer('{{ $dealer->f_id }}')"
                        wire:key="dealer-{{ $dealer->f_id }}"
                        class="{{ $expandedDealer == $dealer->f_id ? 'row-expanded' : '' }}"
                        style="cursor: pointer;">
                        <td>{{ $loop->iteration }}</td>
                        <td class="company-name">{{ strtoupper($dealer->f_company_name) }}</td>
                        <td class="date-cell">{{ $dealer->f_reg_date ? date('Y-m-d', strtotime($dealer->f_reg_date)) : '-' }}</td>
                        <td>
                            <span class="status-badge status-green">
                                {{ $dealer->subscriber_count }} Subscriber{{ $dealer->subscriber_count != 1 ? 's' : '' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="expand-arrow {{ $expandedDealer == $dealer->f_id ? 'rotated' : '' }}">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </td>
                    </tr>

                    {{-- Tier 2: Subscriber list --}}
                    @if($expandedDealer == $dealer->f_id)
                        <tr wire:key="dealer-details-{{ $dealer->f_id }}">
                            <td colspan="5" style="padding: 0;">
                                <div class="details-section">
                                    @if(!empty($subscriberList))
                                        <table class="custom-table" style="background: white; border-radius: 10px; overflow: hidden;">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Company Name</th>
                                                    <th>Expiry Date</th>
                                                    <th>Days Until Expiry</th>
                                                    <th>Renewal Status</th>
                                                    <th style="width: 60px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($subscriberList as $subIndex => $sub)
                                                    <tr wire:click.stop="toggleSubscriber('{{ $sub['f_id'] }}')"
                                                        wire:key="sub-{{ $sub['f_id'] }}"
                                                        class="{{ $expandedSubscriber == $sub['f_id'] ? 'row-expanded' : '' }}"
                                                        style="cursor: pointer;">
                                                        <td>{{ $subIndex + 1 }}</td>
                                                        <td class="company-name">{{ strtoupper($sub['f_company_name']) }}</td>
                                                        <td class="date-cell">
                                                            {{ $sub['earliest_expiry'] ? date('Y-m-d', strtotime($sub['earliest_expiry'])) : '-' }}
                                                        </td>
                                                        <td>
                                                            @if($sub['days_until_expiry'] !== null)
                                                                <span class="status-badge
                                                                    @if($sub['days_until_expiry'] == 0) status-red
                                                                    @elseif($sub['days_until_expiry'] < 7) status-orange
                                                                    @elseif($sub['days_until_expiry'] < 14) status-yellow
                                                                    @else status-green
                                                                    @endif">
                                                                    {{ $sub['days_until_expiry'] }} days
                                                                </span>
                                                            @else
                                                                <span class="status-badge" style="background: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb;">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td style="text-align: left;">
                                                            @if(($sub['renewal_status'] ?? 'pending') === 'done')
                                                                <span class="renewal-badge renewal-done">
                                                                    <i class="fas fa-check-circle"></i> Done Renewal
                                                                </span>
                                                            @elseif(($sub['renewal_status'] ?? 'pending') === 'done_expiring')
                                                                <span class="renewal-badge renewal-done-expiring">
                                                                    <i class="fas fa-exclamation-circle"></i> Renewed (Expiring Soon)
                                                                </span>
                                                            @else
                                                                <span class="renewal-badge renewal-pending">
                                                                    Pending
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <span class="expand-arrow {{ $expandedSubscriber == $sub['f_id'] ? 'rotated' : '' }}">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </span>
                                                        </td>
                                                    </tr>

                                                    {{-- Tier 3: License details (same as expiring license) --}}
                                                    @if($expandedSubscriber == $sub['f_id'])
                                                        <tr wire:key="sub-details-{{ $sub['f_id'] }}">
                                                            <td colspan="6" style="padding: 0;">
                                                                <div class="details-section">
                                                                    @if(!empty($invoiceDetails) && count($invoiceDetails) > 1)
                                                                        {{-- License Summary Table --}}
                                                                        @if(isset($invoiceDetails['_summary']))
                                                                            <div class="license-summary-table">
                                                                                <table>
                                                                                    <thead>
                                                                                        <tr>
                                                                                            <th class="module-col attendance-module">ATTENDANCE</th>
                                                                                            <th class="headcount-col attendance-count">{{ $invoiceDetails['_summary']['attendance'] }}</th>
                                                                                            <th class="module-col leave-module">LEAVE</th>
                                                                                            <th class="headcount-col leave-count">{{ $invoiceDetails['_summary']['leave'] }}</th>
                                                                                            <th class="module-col claim-module">CLAIM</th>
                                                                                            <th class="headcount-col claim-count">{{ $invoiceDetails['_summary']['claim'] }}</th>
                                                                                            <th class="module-col payroll-module">PAYROLL</th>
                                                                                            <th class="headcount-col payroll-count">{{ $invoiceDetails['_summary']['payroll'] }}</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                </table>
                                                                            </div>
                                                                        @endif

                                                                        @foreach($invoiceDetails as $invoiceNo => $group)
                                                                            @if($invoiceNo === '_summary') @continue @endif
                                                                            <div class="invoice-card">
                                                                                <div class="invoice-header">
                                                                                    Invoice: {{ $invoiceNo }}
                                                                                </div>

                                                                                <table class="product-table">
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
                                                                                        @foreach($group['products'] as $product)
                                                                                            @php
                                                                                                $productClass = '';
                                                                                                if (strpos($product['f_name'], 'TimeTec TA') !== false) {
                                                                                                    $productClass = 'product-row-ta';
                                                                                                } elseif (strpos($product['f_name'], 'TimeTec Leave') !== false) {
                                                                                                    $productClass = 'product-row-leave';
                                                                                                } elseif (strpos($product['f_name'], 'TimeTec Claim') !== false) {
                                                                                                    $productClass = 'product-row-claim';
                                                                                                } elseif (strpos($product['f_name'], 'TimeTec Payroll') !== false) {
                                                                                                    $productClass = 'product-row-payroll';
                                                                                                }
                                                                                            @endphp
                                                                                            <tr class="{{ $productClass }}">
                                                                                                <td>{{ $product['f_name'] }}</td>
                                                                                                <td>{{ $product['f_total_user'] }}</td>
                                                                                                <td>{{ $product['billing_cycle'] }}</td>
                                                                                                <td>{{ $product['f_start_date'] ? date('Y-m-d', strtotime($product['f_start_date'])) : '-' }}</td>
                                                                                                <td>{{ $product['f_expiry_date'] ? date('Y-m-d', strtotime($product['f_expiry_date'])) : '-' }}</td>
                                                                                            </tr>
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem; text-align: center; padding: 1.5rem;">No active licenses found for this subscriber.</p>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem; text-align: center; padding: 1.5rem;">No subscribers found under this dealer.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            No dealers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
