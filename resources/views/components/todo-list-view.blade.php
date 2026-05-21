<style>
    .todo-view-container {
        padding: 1rem;
    }

    .todo-view-content {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .todo-view-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .todo-view-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        display: block;
        margin-bottom: 0.25rem;
    }

    .todo-view-id {
        font-size: 1.125rem;
        font-weight: 700;
        color: #2563eb;
    }

    .todo-view-text {
        font-size: 1rem;
        color: #111827;
    }

    .todo-view-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 9999px;
    }

    .badge-completed {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-pending {
        background-color: #fef3c7;
        color: #854d0e;
    }

    .badge-overdue {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-today {
        background-color: #fef3c7;
        color: #854d0e;
    }

    .badge-future {
        background-color: #dcfce7;
        color: #166534;
    }

    .todo-view-remark-box {
        padding: 0.75rem;
        margin-top: 0.25rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }

    .todo-view-remark-text {
        font-size: 1rem;
        white-space: pre-wrap;
        color: #111827;
        margin: 0;
    }

    .todo-view-footer {
        padding-top: 0.5rem;
        font-size: 0.75rem;
        color: #6b7280;
    }

    .todo-view-footer p {
        margin: 0;
    }
</style>

<div class="todo-view-container">
    <div class="todo-view-content">
        <div class="todo-view-grid">
            <div>
                <label class="todo-view-label">Company Name</label>
                <p class="todo-view-text">
                    @if($record->lead_id)
                        <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($record->lead_id)) }}"
                           target="_blank"
                           style="color: #338cf0; text-decoration: none;">
                            {{ $record->company_name }}
                        </a>
                    @else
                        {{ $record->company_name }}
                    @endif
                </p>
            </div>
            <div>
                <label class="todo-view-label">Status</label>
                <p>
                    <span class="todo-view-badge {{ $record->status === 'completed' ? 'badge-completed' : 'badge-pending' }}">
                        {{ ucfirst($record->status) }}
                    </span>
                </p>
            </div>
        </div>

        <div class="todo-view-grid">
            <div>
                <label class="todo-view-label">Reminder Date</label>
                <p class="todo-view-text">{{ $record->reminder_date->format('d M Y') }}</p>
            </div>
            @if($record->status === 'pending')
                <div>
                    <label class="todo-view-label">Days Left</label>
                    <p class="todo-view-text">
                        <span class="todo-view-badge
                            {{ $record->days_left < 0 ? 'badge-overdue' : ($record->days_left === 0 ? 'badge-today' : 'badge-future') }}">
                            @if($record->days_left < 0)
                                {{ abs($record->days_left) }} days overdue
                            @elseif($record->days_left === 0)
                                Today
                            @else
                                {{ $record->days_left }} days left
                            @endif
                        </span>
                    </p>
                </div>
            @else
                <div>
                    <label class="todo-view-label">Completed At</label>
                    <p class="todo-view-text">{{ $record->completed_at->format('d M Y H:i') }}</p>
                </div>
            @endif
        </div>

        <div>
            <label class="todo-view-label">Remark</label>
            <div class="todo-view-remark-box">
                <p class="todo-view-remark-text">{{ $record->remark }}</p>
            </div>
        </div>

        <div class="todo-view-footer">
            <p>Created: {{ $record->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>
