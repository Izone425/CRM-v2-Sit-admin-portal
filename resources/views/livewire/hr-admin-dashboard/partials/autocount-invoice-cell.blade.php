<div>
    @if($getRecord()->autocount_invoice_no)
        <span style="font-size: 0.875rem;">{{ $getRecord()->autocount_invoice_no }}</span>
    @else
        <input
            type="text"
            wire:change="updateAutocountInvoice({{ $getRecord()->id }}, $event.target.value)"
            placeholder="Enter Invoice No."
            style="font-size: 0.75rem; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%; max-width: 160px;"
        />
    @endif
</div>
