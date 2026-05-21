<div>
    @if ($showDrawer)
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }
        </style>
        @php
            $labelStyle = 'display: block; font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 8px;';
            $labelMutedStyle = 'display: block; font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;';
            $inputStyle = 'width: 100%; padding: 12px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827;';
            $selectStyle = 'appearance: none; -webkit-appearance: none; width: 100%; padding: 12px 36px 12px 14px; background: #fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827; cursor: pointer;';
            $fileBtnStyle = 'display: inline-flex; align-items: center; gap: 6px; padding: 10px 14px; background: white; color: #6366F1; border: 1px solid #E0E7FF; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;';
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999998; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 640px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Create Ticket</h2>
                    <button type="button" wire:click="closeDrawer" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="submit" style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Priority <span style="color: #DC2626;">*</span></label>
                            <select wire:model.live="priority_id" style="{{ $selectStyle }}">
                                <option value="">Select Priority</option>
                                @foreach ($this->priorities as $p)<option value="{{ $p->id }}">{{ $p->label }}</option>@endforeach
                            </select>
                            @error('priority_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Product <span style="color: #DC2626;">*</span></label>
                            <select wire:model.live="product_id" style="{{ $selectStyle }}">
                                <option value="">Select Product</option>
                                @foreach ($this->productOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                            @error('product_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Module <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="module_id" style="{{ $selectStyle }}" @if(!$product_id) disabled @endif>
                                <option value="">{{ $product_id ? 'Select Module' : 'Select product first' }}</option>
                                @foreach ($this->modules as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                            </select>
                            @error('module_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if ($this->isP4b)
                        <div>
                            <label style="{{ $labelStyle }}">RFQ Customization Ticket <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="parent_ticket_id" style="{{ $selectStyle }}">
                                <option value="">Select RFQ Customization Ticket</option>
                                @foreach ($this->parentTickets as $t)<option value="{{ $t->id }}">{{ $t->ticket_id }} - {{ \Illuminate\Support\Str::limit($t->title, 60) }}</option>@endforeach
                            </select>
                            @error('parent_ticket_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    @if ($this->deviceTypeVisible)
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                            <div>
                                <label style="{{ $labelStyle }}">Device Type @if($this->isBackEndAssistance)<span style="color: #DC2626;">*</span>@endif</label>
                                <select wire:model.live="device_type" style="{{ $selectStyle }}">
                                    <option value="">Select Device</option>
                                    <option value="Mobile">Mobile</option>
                                    <option value="Browser">Browser</option>
                                </select>
                                @error('device_type') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                            </div>

                            @if ($this->mobileFieldsVisible)
                                <div>
                                    <label style="{{ $labelStyle }}">Mobile Type <span style="color: #DC2626;">*</span></label>
                                    <select wire:model.defer="mobile_type" style="{{ $selectStyle }}">
                                        <option value="">Select Mobile Type</option>
                                        <option value="iOS">iOS</option>
                                        <option value="Android">Android</option>
                                        <option value="Huawei">Huawei</option>
                                    </select>
                                    @error('mobile_type') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label style="{{ $labelStyle }}">Version Screenshot <span style="color: #DC2626;">*</span></label>
                                    <label for="ticket_version_screenshot" style="{{ $fileBtnStyle }} width: 100%; justify-content: center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        {{ $version_screenshot ? 'Change File' : 'Upload' }}
                                    </label>
                                    <input id="ticket_version_screenshot" type="file" wire:model="version_screenshot" accept="image/*" style="display: none;">
                                    @if ($version_screenshot && method_exists($version_screenshot, 'getClientOriginalName'))
                                        <div style="margin-top: 6px; font-size: 12px; color: #4B5563;">{{ $version_screenshot->getClientOriginalName() }}</div>
                                    @endif
                                    @error('version_screenshot') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                                </div>
                            @elseif ($this->browserFieldsVisible)
                                <div style="grid-column: span 2;">
                                    <label style="{{ $labelStyle }}">Browser Type <span style="color: #DC2626;">*</span></label>
                                    <select wire:model.defer="browser_type" style="{{ $selectStyle }}">
                                        <option value="">Select Browser</option>
                                        <option value="Chrome">Chrome</option>
                                        <option value="Firefox">Firefox</option>
                                        <option value="Safari">Safari</option>
                                        <option value="Edge">Edge</option>
                                        <option value="Opera">Opera</option>
                                    </select>
                                    @error('browser_type') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Zoho Ticket Number <span style="color: #DC2626;">*</span></label>
                            <input type="text" wire:model.defer="zoho_id" placeholder="e.g. ZH-1234" style="{{ $inputStyle }} text-transform: uppercase;">
                            @error('zoho_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Company Name <span style="color: #DC2626;">*</span></label>
                            @php
                                $companyMap = $this->companyOptions->keyBy('value')->toArray();
                                $selectedCompany = $company_name && isset($companyMap[$company_name]) ? $companyMap[$company_name] : null;
                            @endphp
                            <div x-data="{ open: false, search: '' }" @click.away="open = false" style="position: relative;">
                                <button type="button" @click="open = !open"
                                        style="{{ $selectStyle }} display: flex; align-items: center; justify-content: space-between; text-align: left; gap: 8px;">
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; {{ $selectedCompany ? 'color: #111827;' : 'color: #9CA3AF;' }}">
                                        {{ $selectedCompany ? $selectedCompany['label'] : 'Select Company' }}
                                    </span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                <div x-show="open" x-transition x-cloak
                                     style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); z-index: 50; overflow: hidden;">
                                    <div style="padding: 8px; border-bottom: 1px solid #F3F4F6;">
                                        <input type="text" x-model="search" placeholder="Search company..."
                                               @click.stop
                                               style="width: 100%; padding: 8px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #111827;">
                                    </div>
                                    <div style="max-height: 240px; overflow-y: auto;">
                                        <button type="button" @click="$wire.set('company_name', ''); open = false; search = ''"
                                                style="display: block; width: 100%; text-align: left; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #9CA3AF; cursor: pointer; border-bottom: 1px solid #F3F4F6;"
                                                onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">Clear selection</button>
                                        @foreach ($this->companyOptions as $c)
                                            <button type="button"
                                                    x-show="search === '' || '{{ e(strtolower($c['label'])) }}'.includes(search.toLowerCase())"
                                                    @click="$wire.set('company_name', '{{ e($c['value']) }}'); open = false; search = ''"
                                                    style="display: block; width: 100%; text-align: left; padding: 8px 14px; border: none; background: #fff; font-size: 13px; color: #111827; cursor: pointer; border-bottom: 1px solid #F3F4F6;"
                                                    onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
                                                {{ $c['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('company_name') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if ($this->isP4b)
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <label style="{{ $labelStyle }}">Invoice <span style="color: #DC2626;">*</span></label>
                                <label for="ticket_invoice" style="{{ $fileBtnStyle }} width: 100%; justify-content: center;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    {{ $invoice ? 'Change File' : 'Upload Invoice' }}
                                </label>
                                <input id="ticket_invoice" type="file" wire:model="invoice" accept="application/pdf,image/*" style="display: none;">
                                @if ($invoice && method_exists($invoice, 'getClientOriginalName'))
                                    <div style="margin-top: 6px; font-size: 12px; color: #4B5563;">{{ $invoice->getClientOriginalName() }}</div>
                                @endif
                                @error('invoice') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label style="{{ $labelStyle }}">Payment Slip <span style="color: #DC2626;">*</span></label>
                                <label for="ticket_payment_slip" style="{{ $fileBtnStyle }} width: 100%; justify-content: center;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    {{ $payment_slip ? 'Change File' : 'Upload Slip' }}
                                </label>
                                <input id="ticket_payment_slip" type="file" wire:model="payment_slip" accept="application/pdf,image/*" style="display: none;">
                                @if ($payment_slip && method_exists($payment_slip, 'getClientOriginalName'))
                                    <div style="margin-top: 6px; font-size: 12px; color: #4B5563;">{{ $payment_slip->getClientOriginalName() }}</div>
                                @endif
                                @error('payment_slip') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label style="{{ $labelStyle }}">Title <span style="color: #DC2626;">*</span></label>
                        <input type="text" wire:model.defer="title" placeholder="Enter ticket title" style="{{ $inputStyle }} text-transform: uppercase;">
                        @error('title') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Description <span style="color: #DC2626;">*</span></label>
                        {{ $this->descriptionForm }}
                        @error('description') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 16px 28px; border-top: 1px solid #E5E7EB;">
                    <button type="button" wire:click="closeDrawer" style="padding: 10px 24px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                    <button type="button" wire:click="submit" style="padding: 10px 28px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Create Ticket</button>
                </div>
            </aside>
        </div>
    @endif
</div>
