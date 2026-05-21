@php
    $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');
    $formattedId = 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);

    // Get related hardware handovers
    $relatedHandovers = [];
    if ($record->related_hardware_handovers) {
        $handoverIds = is_string($record->related_hardware_handovers)
            ? json_decode($record->related_hardware_handovers, true)
            : $record->related_hardware_handovers;

        if (is_array($handoverIds) && !empty($handoverIds)) {
            foreach ($handoverIds as $id) {
                $hw = \App\Models\HardwareHandoverV2::find($id);
                $hwYear = $hw && $hw->created_at ? $hw->created_at->format('y') : now()->format('y');
                $relatedHandovers[] = 'HW_' . $hwYear . str_pad($id, 4, '0', STR_PAD_LEFT);
            }
        }
    }

    // Ensure arrays for file fields
    $ensureArray = function($value) {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    };

    $quotationReseller = $ensureArray($record->quotation_by_reseller);
    $invoiceReseller = $ensureArray($record->invoice_by_reseller);
    $invoiceCustomer = $ensureArray($record->invoice_by_customer);
    $paymentCustomer = $ensureArray($record->payment_by_customer);
    $productQuotation = $ensureArray($record->product_quotation);
    $paymentSlip = $ensureArray($record->payment_slip);
@endphp

<style>
    .fn-label { font-weight: 600; color: #1f2937; display: inline-flex; justify-content: space-between; min-width: 13rem; margin-right: 0.25rem; }
    .fn-label::after { content: ':'; }
    .fn-value { color: #374151; }
    .fn-view-link { font-weight: 500; color: #2563eb; text-decoration: none; cursor: pointer; }
    .fn-view-link:hover { text-decoration: underline; }
    .fn-not-available { font-style: italic; color: #6b7280; }
</style>

<div>
    {{-- Header --}}
    <div style="margin-bottom: 0.75rem;">
        <div><span class="fn-label" style="min-width:10rem;">Finance ID</span> <span class="fn-value">{{ $formattedId }}</span></div>
        <div><span class="fn-label" style="min-width:10rem;">SalesPerson</span> <span class="fn-value">{{ $record->creator?->name ?? 'N/A' }}</span></div>
        <div><span class="fn-label" style="min-width:10rem;">Company Name</span> <span class="fn-value">{{ $record->lead?->companyDetail?->company_name ?? $record->lead?->name ?? 'N/A' }}</span></div>
        <div>
            <span class="fn-label" style="min-width:10rem;">Reseller Name</span>
            @if($record->reseller && $record->reseller->company_name)
                <span class="fn-value" style="color: #dc2626; font-weight: bold;">{{ $record->reseller->company_name }}</span>
            @else
                <span class="fn-not-available">N/A</span>
            @endif
        </div>
    </div>

    {{-- Box with two columns --}}
    <div style="border: 1px solid #252525; border-radius: 0.25rem; padding: 1rem;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <td style="width: 50%; padding-bottom: 0.25rem; font-weight: 600;">Reseller Details</td>
                    <td style="width: 50%; padding-bottom: 0.25rem; font-weight: 600;">Document Details</td>
                </tr>
                <tr>
                    <td style="padding-bottom: 0.5rem;"><hr style="border: none; border-top: 1px solid #d1d5db; margin: 0; width: 95%;"></td>
                    <td style="padding-bottom: 0.5rem;"><hr style="border: none; border-top: 1px solid #d1d5db; margin: 0; width: 95%;"></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="vertical-align: top; padding-right: 1rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <div>
                                <span class="fn-label">Hardware Handover ID</span>
                                @if(!empty($relatedHandovers))
                                    <span class="fn-value">{{ implode(', ', $relatedHandovers) }}</span>
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Reseller Invoice No</span>
                                <span class="fn-value">{{ $record->reseller_invoice_number ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="fn-label">Name</span>
                                <span class="fn-value">{{ $record->pic_name ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="fn-label">Hp Number</span>
                                <span class="fn-value">{{ $record->pic_phone ?? 'N/A' }}</span>
                            </div>
                            <div x-data="{ showEmail: false }">
                                <span class="fn-label">Email Address</span>
                                @if($record->pic_email)
                                    <a href="#" @click.prevent="showEmail = true" class="fn-view-link">View</a>
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif

                                @if($record->pic_email)
                                <div x-show="showEmail" x-cloak x-transition @click.outside="showEmail = false" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; overflow: auto; padding: 1rem;">
                                    <div @click.away="showEmail = false" style="position: relative; width: 100%; max-width: 30rem; padding: 1.5rem; margin: auto; background-color: white; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); margin-top: 8rem;">
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem;">
                                            <h3 style="font-size: 1.125rem; font-weight: 500; color: #111827;">Email Address</h3>
                                            <button type="button" @click="showEmail = false" style="color: #9ca3af; background-color: transparent; border: none; border-radius: 0.375rem; padding: 0.375rem; margin-left: auto; display: inline-flex; align-items: center; cursor: pointer;">
                                                <svg style="width: 1.25rem; height: 1.25rem;" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div style="padding: 1rem; border-radius: 0.5rem; background-color: #f9fafb;">
                                            <span style="color: #1f2937; word-break: break-all;">{{ $record->pic_email }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <hr style="border: none; border-top: 1px solid #d1d5db; margin: 0.25rem 0; width: 95%;">

                            <div>
                                <span class="fn-label">Payment Method</span>
                                @if($record->payment_method === 'bank_transfer')
                                    <span class="fn-value">Via Bank Transfer</span>
                                @elseif($record->payment_method === 'hrdf')
                                    <span class="fn-value">Via HRDF</span>
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Payment Slip by Finance</span>
                                @if(!empty($paymentSlip))
                                    @foreach($paymentSlip as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="vertical-align: top;">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <div>
                                <span class="fn-label">Quotation by Reseller</span>
                                @if(!empty($quotationReseller))
                                    @foreach($quotationReseller as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Invoice by Reseller</span>
                                @if(!empty($invoiceReseller))
                                    @foreach($invoiceReseller as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Invoice by Customer</span>
                                @if(!empty($invoiceCustomer))
                                    @foreach($invoiceCustomer as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Payment by Customer</span>
                                @if(!empty($paymentCustomer))
                                    @foreach($paymentCustomer as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>
                            <div>
                                <span class="fn-label">Product Quotation</span>
                                @if(!empty($productQuotation))
                                    @foreach($productQuotation as $index => $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="fn-view-link">View</a>
                                        @if(!$loop->last) / @endif
                                    @endforeach
                                @else
                                    <span class="fn-not-available">N/A</span>
                                @endif
                            </div>

                            <hr style="border: none; border-top: 1px solid #d1d5db; margin: 0.25rem 0; width: 95%;">

                            <div><span class="fn-label">Date Submit</span> <span class="fn-value">{{ $record->submitted_at ? $record->submitted_at->format('d M Y g:ia') : 'N/A' }}</span></div>
                            <div><span class="fn-label">Date Completed</span> <span class="fn-value">{{ $record->completed_at ? $record->completed_at->format('d M Y g:ia') : 'N/A' }}</span></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
