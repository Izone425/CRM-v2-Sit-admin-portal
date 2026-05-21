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

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #9ca3af;
        }
    </style>

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
            placeholder="Search by company name, PIC name, or email">
    </div>

    <!-- Requests Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company Name</th>
                    <th>Headcount</th>
                    <th>Rejected At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $request)
                    <tr>
                        <td>
                            <button
                                wire:click="openDetailModal({{ $request->id }})"
                                class="text-sm font-semibold text-red-600 transition-colors cursor-pointer hover:text-red-800">
                                {{ $request->formatted_id }}
                            </button>
                        </td>
                        <td class="font-medium">{{ $request->company_name }}</td>
                        <td>{{ $request->headcount ?? 'N/A' }}</td>
                        <td class="text-gray-500">
                            {{ \Carbon\Carbon::parse($request->rejected_at)->format('M d, Y H:i') }}
                        </td>
                        <td>
                            <button
                                wire:click="convertToDraft({{ $request->id }})"

                                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="mr-1 fas fa-redo"></i> Convert to Draft
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                                &nbsp;
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Detail Modal -->
    @include('components.database-creation-detail-modal')
</div>
