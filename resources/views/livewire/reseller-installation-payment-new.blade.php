
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

        .success-message {
            position: fixed;
            top: 120px;
            right: 20px;
            z-index: 99999;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>

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
                placeholder="Search by customer name or address">
        </div>

        <!-- Payments Table -->
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Installation Date</th>
                        <th>Attention To</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <button
                                    wire:click="openDetailModal({{ $payment->id }})"
                                    class="text-sm font-semibold text-indigo-600 transition-colors cursor-pointer hover:text-indigo-800">
                                    {{ $payment->formatted_id }}
                                </button>
                            </td>
                            <td>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $payment->customer_name }}
                                </div>
                            </td>
                            <td>{{ $payment->installation_date->format('d M Y') }}</td>
                            <td>{{ $payment->salesperson_name }}</td>
                            <td style="color: #6b7280;">
                                {{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y H:i') }}
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

        <!-- Success Notification -->
        <div
            x-show="showNotification"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-10"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="success-message"
            :class="{
                'bg-green-500': notificationType === 'success',
                'bg-red-500': notificationType === 'error',
                'bg-blue-500': notificationType === 'info'
            }"
            style="padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); color: white; font-weight: 600;">
            <div class="flex items-center gap-3">
                <i class="text-lg fas" :class="{
                    'fa-check-circle': notificationType === 'success',
                    'fa-exclamation-circle': notificationType === 'error',
                    'fa-info-circle': notificationType === 'info'
                }"></i>
                <span x-text="notificationMessage"></span>
            </div>
        </div>
    </div>

    @include('livewire.partials.installation-payment-detail-modal')
</div>
