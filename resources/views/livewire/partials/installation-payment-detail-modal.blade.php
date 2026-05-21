<div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

@if($showDetailModal && $selectedPayment)
    <div style="position: fixed; inset: 0; overflow-y: auto; z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem 1rem 5rem; text-align: center;">
            <!-- Background overlay -->
            <div
                wire:click="closeDetailModal"
                style="position: fixed; inset: 0; background-color: rgba(107, 114, 128, 0.75); transition: opacity 0.3s;"
                aria-hidden="true"></div>

            <!-- Modal panel -->
            <div style="display: inline-block; width: 100%; max-width: 48rem; margin-top: 6rem; overflow: hidden; text-align: left; vertical-align: bottom; transition: all 0.3s; transform: translateY(0); background-color: white; border-radius: 0.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                <!-- Header -->
                <div style="padding: 1rem 1.5rem; background: linear-gradient(to right, #4f46e5, #9333ea);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: white; margin: 0;">
                            {{ $selectedPayment->formatted_id }}
                        </h3>
                        <button
                            wire:click="closeDetailModal"
                            type="button"
                            style="color: white; transition: color 0.3s; background: none; border: none; cursor: pointer; padding: 0;">
                            <i class="text-2xl fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div style="padding: 1.5rem 1.5rem;">
                    <!-- Reseller Name -->
                    <div style="display: flex; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563; min-width: 160px;">Reseller Name:</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #111827; text-transform: uppercase;">{{ $selectedPayment->reseller_name ?? 'N/A' }}</span>
                    </div>

                    <!-- Customer Name -->
                    <div style="display: flex; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563; min-width: 160px;">Customer Name:</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->customer_name ?? 'N/A' }}</span>
                    </div>

                    <!-- SalesPerson -->
                    <div style="display: flex; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563; min-width: 160px;">SalesPerson:</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->salesperson_name }}</span>
                    </div>

                    <!-- Installation Date -->
                    <div style="display: flex; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563; min-width: 160px;">Installation Date:</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->installation_date ? $selectedPayment->installation_date->format('M d, Y') : 'N/A' }}</span>
                    </div>

                    <!-- Installation Address -->
                    <div style="display: flex; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563; min-width: 160px;">Installation Address:</span>
                        <span style="font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->installation_address ?? 'N/A' }}</span>
                    </div>

                    <!-- Attachments Row -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 0.75rem;">
                        <!-- Quotation by Reseller -->
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Quotation by Reseller:</span>
                            @if($selectedPayment->quotation_path)
                                @php
                                    $quotations = is_array($selectedPayment->quotation_path)
                                        ? $selectedPayment->quotation_path
                                        : (is_array(json_decode($selectedPayment->quotation_path, true))
                                            ? json_decode($selectedPayment->quotation_path, true)
                                            : [$selectedPayment->quotation_path]);
                                @endphp

                                <div style="margin-top: 0.5rem;">
                                    @foreach($quotations as $index => $path)
                                        <div style="display: inline-block; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                            <a href="{{ Storage::url($path) }}" target="_blank"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: white; background-color: #4f46e5; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.3s;"
                                                onmouseover="this.style.backgroundColor='#3730a3'"
                                                onmouseout="this.style.backgroundColor='#4f46e5'">
                                                <i class="fas fa-file" style="font-size: 0.75rem;"></i>
                                                <span>File {{ $index + 1 }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">No attachment</span>
                            @endif
                        </div>

                        <!-- Invoice by Reseller -->
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Invoice by Reseller:</span>
                            @if($selectedPayment->invoice_path)
                                @php
                                    $invoices = is_array($selectedPayment->invoice_path)
                                        ? $selectedPayment->invoice_path
                                        : (is_array(json_decode($selectedPayment->invoice_path, true))
                                            ? json_decode($selectedPayment->invoice_path, true)
                                            : [$selectedPayment->invoice_path]);
                                @endphp

                                <div style="margin-top: 0.5rem;">
                                    @foreach($invoices as $index => $path)
                                        <div style="display: inline-block; margin-right: 0.5rem; margin-bottom: 0.5rem;">
                                            <a href="{{ Storage::url($path) }}" target="_blank"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: white; background-color: #9333ea; border-radius: 0.375rem; text-decoration: none; transition: background-color 0.3s;"
                                                onmouseover="this.style.backgroundColor='#7e22ce'"
                                                onmouseout="this.style.backgroundColor='#9333ea'">
                                                <i class="fas fa-file" style="font-size: 0.75rem;"></i>
                                                <span>File {{ $index + 1 }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span style="margin-left: 0.5rem; font-size: 0.875rem; color: #9ca3af;">No attachment</span>
                            @endif
                        </div>
                    </div>

                    <!-- Admin Remark -->
                    @if($selectedPayment->admin_remark)
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Remark by Admin:</span>
                            <div style="margin-top: 0.5rem; padding: 0.75rem; font-size: 0.875rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: #f9fafb; word-wrap: break-word;">
                                {!! nl2br(e($selectedPayment->admin_remark)) !!}
                            </div>
                        </div>
                    @endif

                    <!-- Dates -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; padding: 1rem; border-top: 2px solid #e5e7eb;">
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Submitted At:</span>
                            <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->created_at ? \Carbon\Carbon::parse($selectedPayment->created_at)->format('M d, Y H:i') : 'N/A' }}</span>
                        </div>
                        <div>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #4b5563;">Completed At:</span>
                            <span style="margin-left: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #111827;">{{ $selectedPayment->completed_at ? \Carbon\Carbon::parse($selectedPayment->completed_at)->format('M d, Y H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
