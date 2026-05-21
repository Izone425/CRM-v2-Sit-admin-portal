<div style="padding:24px;">
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .invoice-row-hover:hover { background: #eef2ff !important; }
    </style>
    {{-- Section Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #06b6d4, #0891b2); display:flex; align-items:center; justify-content:center;">
                <svg style="width:20px; height:20px; color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 style="font-size:1.125rem; font-weight:700; color:#111827; margin:0;">Proforma Invoice</h3>
        </div>
    </div>

    {{-- Search and Controls Section --}}
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px;">
        <div style="display:flex; flex-direction:row; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            {{-- Search Box --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative;">
                    <svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        wire:model.defer="search"
                        wire:keydown.enter="searchInvoices"
                        placeholder="Search invoice..."
                        style="width:220px; padding:9px 14px 9px 34px; font-size:0.875rem; border:1px solid #d1d5db; border-radius:8px; outline:none; background:#fff; transition:border-color 0.15s;"
                        onfocus="this.style.borderColor='#06b6d4'; this.style.boxShadow='0 0 0 3px rgba(6,182,212,0.1)'"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                    >
                </div>
                <button
                    wire:click="searchInvoices"
                    wire:loading.attr="disabled"
                    style="background:linear-gradient(135deg, #06b6d4, #0891b2); color:#fff; padding:9px 18px; font-size:14px; font-weight:600; border-radius:8px; border:none; cursor:pointer; box-shadow:0 2px 4px rgba(6,182,212,0.3); transition:all 0.15s;"
                    onmouseenter="this.style.boxShadow='0 4px 8px rgba(6,182,212,0.4)'; this.style.transform='translateY(-1px)'"
                    onmouseleave="this.style.boxShadow='0 2px 4px rgba(6,182,212,0.3)'; this.style.transform='translateY(0)'"
                >
                    <span wire:loading.remove wire:target="searchInvoices">Search</span>
                    <span wire:loading wire:target="searchInvoices">
                        <svg style="display:inline; width:16px; height:16px; animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>

            {{-- Pagination Controls & Total Records --}}
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                {{-- Per Page Selector --}}
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:0.8rem; color:#6b7280;">Show</span>
                    <select wire:model.live="perPage" style="padding:6px 8px; font-size:0.8rem; border:1px solid #d1d5db; border-radius:6px; background:#fff; outline:none; cursor:pointer;">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <span style="font-size:0.8rem; color:#6b7280;">entries</span>
                </div>

                {{-- Pagination Navigation --}}
                @php
                    $totalPages = $this->totalPages();
                    $prevDisabled = $currentPage <= 1;
                    $nextDisabled = $currentPage >= $totalPages;
                @endphp
                <div style="display:flex; align-items:center; gap:0; overflow:hidden; border:1px solid #d1d5db; border-radius:8px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <button
                        wire:click="previousPage"
                        {{ $prevDisabled ? 'disabled' : '' }}
                        style="padding:7px 12px; font-size:0.875rem; font-weight:500; color:{{ $prevDisabled ? '#d1d5db' : '#374151' }}; border:none; border-right:1px solid #e5e7eb; background:transparent; cursor:{{ $prevDisabled ? 'not-allowed' : 'pointer' }}; transition:background 0.15s;"
                        onmouseenter="{{ $prevDisabled ? '' : "this.style.background='#f3f4f6'" }}"
                        onmouseleave="{{ $prevDisabled ? '' : "this.style.background='transparent'" }}"
                    >
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span style="padding:7px 14px; font-size:0.8rem; color:#374151; background:#f9fafb; font-weight:500; white-space:nowrap;">
                        Page <strong>{{ $currentPage }}</strong> of <strong>{{ $totalPages }}</strong>
                    </span>
                    <button
                        wire:click="nextPage"
                        {{ $nextDisabled ? 'disabled' : '' }}
                        style="padding:7px 12px; font-size:0.875rem; font-weight:500; color:{{ $nextDisabled ? '#d1d5db' : '#374151' }}; border:none; border-left:1px solid #e5e7eb; background:transparent; cursor:{{ $nextDisabled ? 'not-allowed' : 'pointer' }}; transition:background 0.15s;"
                        onmouseenter="{{ $nextDisabled ? '' : "this.style.background='#f3f4f6'" }}"
                        onmouseleave="{{ $nextDisabled ? '' : "this.style.background='transparent'" }}"
                    >
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                {{-- Total Records Count --}}
                <div style="font-size:0.8rem; color:#6b7280; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; padding:5px 12px;">
                    Total: <span style="font-weight:700; color:#16a34a;">{{ $totalRecords }}</span> record(s)
                </div>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    @if($isLoading)
        <div style="display:flex; align-items:center; justify-content:center; padding:24px 0;">
            <svg style="width:20px; height:20px; animation:spin 1s linear infinite; color:#06b6d4;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle style="opacity:0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity:0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span style="margin-left:8px; color:#6b7280; font-size:0.875rem;">Loading invoices...</span>
        </div>
    @elseif($hasError)
        <div style="display:flex; align-items:center; justify-content:center; padding:24px 0; gap:8px;">
            <svg style="width:20px; height:20px; color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span style="color:#6b7280; font-size:0.875rem;">{{ $errorMessage }}</span>
            <button wire:click="refreshInvoices" style="background:#06b6d4; color:#fff; padding:4px 12px; font-size:0.75rem; border-radius:4px; border:none; cursor:pointer; margin-left:8px;">Retry</button>
        </div>
    @else
        {{-- Invoice List --}}
        <div style="display:flex; flex-direction:column; gap:12px;">
            @forelse($invoices as $index => $invoice)
                @php
                    $status = strtolower($invoice['status'] ?? 'pending');
                    $statusConfig = match($status) {
                        'paid' => ['bg' => '#dcfce7', 'color' => '#15803d', 'border' => '#86efac', 'label' => 'Paid'],
                        'cancel', 'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => ucfirst($invoice['status'] ?? 'Cancel')],
                        'unpaid' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Unpaid'],
                        'active' => ['bg' => '#dcfce7', 'color' => '#15803d', 'border' => '#86efac', 'label' => 'Active'],
                        default => ['bg' => '#fffbeb', 'color' => '#d97706', 'border' => '#fde68a', 'label' => ucfirst($invoice['status'] ?? 'Pending')],
                    };
                @endphp
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; transition:all 0.15s; box-shadow:0 1px 3px rgba(0,0,0,0.04);"
                    onmouseenter="this.style.borderColor='#06b6d4'; this.style.boxShadow='0 2px 8px rgba(6,182,212,0.12)'"
                    onmouseleave="this.style.borderColor='#e5e7eb'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)'">

                    {{-- Left: Icon + Invoice Info --}}
                    <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:0;">
                        {{-- PDF Icon --}}
                        <div style="width:42px; height:42px; border-radius:10px; background:linear-gradient(135deg, #fef2f2, #fee2e2); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg style="width:22px; height:22px; color:#ef4444;" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zM14 3.5L18.5 8H14V3.5zM12 18c-.55 0-1-.45-1-1v-2H9.5c-.28 0-.5-.22-.5-.5s.22-.5.5-.5H11v-1h-1c-.55 0-1-.45-1-1s.45-1 1-1h1V10c0-.55.45-1 1-1s1 .45 1 1v1h1c.55 0 1 .45 1 1s-.45 1-1 1h-1v1h1.5c.28 0 .5.22.5.5s-.22.5-.5.5H13v2c0 .55-.45 1-1 1z"/>
                            </svg>
                        </div>

                        {{-- Invoice Details --}}
                        <div style="min-width:0; flex:1;">
                            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <span wire:click="viewInvoice({{ $invoice['id'] ?? 0 }})"
                                    style="font-size:0.9rem; font-weight:700; color:#0891b2; cursor:pointer; text-decoration:none; transition:all 0.15s;"
                                    onmouseenter="this.style.textDecoration='underline'; this.style.color='#0e7490'"
                                    onmouseleave="this.style.textDecoration='none'; this.style.color='#0891b2'">
                                    {{ $invoice['invoice_no'] ?? '-' }}
                                </span>
                                <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:9999px; font-size:0.7rem; font-weight:600; background:{{ $statusConfig['bg'] }}; color:{{ $statusConfig['color'] }}; border:1px solid {{ $statusConfig['border'] }};">
                                    {{ $statusConfig['label'] }}
                                </span>
                                @if(!empty($invoice['or_no']))
                                    <a href="{{ url('/admin/view-official-receipt?orNo=' . $invoice['or_no'] . '&softwareHandoverId=' . ($this->softwareHandoverId ?? '')) }}"
                                        style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:9999px; font-size:0.7rem; font-weight:600; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; text-decoration:none; cursor:pointer; transition:all 0.15s;"
                                        onmouseenter="this.style.background='#2563eb'; this.style.color='#fff'"
                                        onmouseleave="this.style.background='#eff6ff'; this.style.color='#2563eb'">
                                        <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ $invoice['or_no'] }}
                                    </a>
                                @endif
                            </div>
                            <div style="display:flex; align-items:center; gap:16px; margin-top:4px; flex-wrap:wrap;">
                                <span style="font-size:0.8rem; color:#6b7280;">
                                    {{ isset($invoice['invoice_date']) ? \Carbon\Carbon::parse($invoice['invoice_date'])->format('d M Y') : '-' }}
                                </span>
                                <span style="font-size:0.8rem; color:#9ca3af;">|</span>
                                <span style="font-size:0.8rem; color:#6b7280; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:300px;" title="{{ strip_tags($invoice['description'] ?? '') }}">
                                    {{ strip_tags($invoice['description'] ?? 'TimeTec License') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Amount + View Link --}}
                    <div style="display:flex; align-items:center; gap:16px; flex-shrink:0;">
                        <div style="text-align:right;">
                            <span style="font-size:0.95rem; font-weight:700; color:#0f172a;">{{ $invoice['currency'] ?? 'MYR' }} {{ number_format($invoice['total'] ?? 0, 2) }}</span>
                        </div>
                        <span wire:click="viewInvoice({{ $invoice['id'] ?? 0 }})"
                            style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background:#ecfeff; color:#0891b2; cursor:pointer; transition:all 0.15s; border:1px solid #cffafe;"
                            onmouseenter="this.style.background='#0891b2'; this.style.color='#fff'"
                            onmouseleave="this.style.background='#ecfeff'; this.style.color='#0891b2'"
                            title="View Invoice">
                            <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            @empty
                <div style="padding:24px 16px; text-align:center;">
                    <svg style="width:20px; height:20px; color:#9ca3af; margin:0 auto 6px auto; display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p style="font-size:0.8rem; color:#6b7280;">No invoices found</p>
                </div>
            @endforelse
        </div>

        {{-- Bottom Pagination --}}
        @if($totalRecords > $perPage)
            @php
                $totalPages = $this->totalPages();
                $prevDisabled = $currentPage <= 1;
                $nextDisabled = $currentPage >= $totalPages;
            @endphp
            <div style="display:flex; align-items:center; justify-content:flex-end; margin-top:16px; gap:12px;">
                <span style="font-size:0.8rem; color:#6b7280;">
                    Showing {{ (($currentPage - 1) * $perPage) + 1 }} - {{ min($currentPage * $perPage, $totalRecords) }} of {{ $totalRecords }}
                </span>
                <div style="display:flex; align-items:center; gap:0; overflow:hidden; border:1px solid #d1d5db; border-radius:8px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <button
                        wire:click="previousPage"
                        {{ $prevDisabled ? 'disabled' : '' }}
                        style="padding:7px 12px; font-size:0.875rem; color:{{ $prevDisabled ? '#d1d5db' : '#374151' }}; border:none; border-right:1px solid #e5e7eb; background:transparent; cursor:{{ $prevDisabled ? 'not-allowed' : 'pointer' }};"
                        onmouseenter="{{ $prevDisabled ? '' : "this.style.background='#f3f4f6'" }}"
                        onmouseleave="{{ $prevDisabled ? '' : "this.style.background='transparent'" }}"
                    >
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span style="padding:7px 14px; font-size:0.8rem; color:#374151; background:#f9fafb; font-weight:500;">
                        {{ $currentPage }} / {{ $totalPages }}
                    </span>
                    <button
                        wire:click="nextPage"
                        {{ $nextDisabled ? 'disabled' : '' }}
                        style="padding:7px 12px; font-size:0.875rem; color:{{ $nextDisabled ? '#d1d5db' : '#374151' }}; border:none; border-left:1px solid #e5e7eb; background:transparent; cursor:{{ $nextDisabled ? 'not-allowed' : 'pointer' }};"
                        onmouseenter="{{ $nextDisabled ? '' : "this.style.background='#f3f4f6'" }}"
                        onmouseleave="{{ $nextDisabled ? '' : "this.style.background='transparent'" }}"
                    >
                        <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
