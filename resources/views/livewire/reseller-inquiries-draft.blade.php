<div>
    <style>
        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .search-input-table {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input-table:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .custom-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        }

        .custom-table td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .inquiry-title {
            font-weight: 600;
            color: #111827;
        }

        .date-cell {
            color: #6b7280;
        }

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #9ca3af;
        }
    </style>

<div>
    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            wire:model.live="search"
            class="search-input-table"
            placeholder="Search by title, subscriber name, or description">
    </div>

    <!-- Inquiries Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Subscriber Name</th>
                    <th>Created At</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inquiries as $inquiry)
                    <tr>
                        <td>
                            <button
                                wire:click="openDetailModal({{ $inquiry->id }})"
                                class="text-sm font-semibold text-indigo-600 transition-colors cursor-pointer hover:text-indigo-800">
                                {{ $inquiry->formatted_id }}
                            </button>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-gray-900 inquiry-title" style="position: relative; cursor: pointer;"
                                 onmouseenter="this.querySelector('.custom-tooltip').style.display='block'"
                                 onmouseleave="this.querySelector('.custom-tooltip').style.display='none'">
                                {{ Str::limit($inquiry->title, 30) }}
                                <div class="custom-tooltip" style="display:none; position:absolute; bottom:100%; left:0; background:#111827; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; white-space:nowrap; z-index:50; margin-bottom:4px;">
                                    {{ $inquiry->title }}
                                </div>
                            </div>
                        </td>
                        <td>{{ $inquiry->subscriber_name }}</td>
                        <td class="date-cell">
                            {{ \Carbon\Carbon::parse($inquiry->created_at)->format('M d, Y H:i') }}
                        </td>
                        <td style="text-align: center;">
                            <button
                                wire:click="resubmitInquiry({{ $inquiry->id }})"
                                class="px-4 py-2 text-xs font-semibold text-white transition-all rounded-lg bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 hover:shadow-lg">
                                <i class="mr-1 fas fa-redo"></i>Resubmit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                                &nbsp;
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('livewire.partials.inquiry-detail-modal')
</div>
