<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
    @if ($showDrawer)
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }
            input.flatpickr-styled {
                width: 100%;
                padding: 12px 14px;
                background: #f9fafb;
                border: 1px solid #E5E7EB;
                border-radius: 10px;
                font-size: 14px;
                color: #111827;
            }
            input.flatpickr-styled:focus {
                outline: none;
                border-color: #6366F1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            }
        </style>
        @php
            $labelStyle = 'display: block; font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 8px;';
            $inputStyle = 'width: 100%; padding: 12px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827;';
            $selectStyle = 'appearance: none; -webkit-appearance: none; width: 100%; padding: 12px 36px 12px 14px; background: #fff url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%239CA3AF\' stroke-width=\'2\'><polyline points=\'6 9 12 15 18 9\'/></svg>") no-repeat right 14px center; background-size: 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827; cursor: pointer;';
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 560px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Create New Release</h2>
                    <button type="button" wire:click="closeDrawer" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="submit" style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 24px;">
                    <div style="display: grid; grid-template-columns: {{ $product_id ? '1fr 1fr' : '1fr' }}; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Product <span style="color: #DC2626;">*</span></label>
                            <select wire:model.live="product_id" style="{{ $selectStyle }}">
                                <option value="">Select a product</option>
                                @foreach ($this->products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                            </select>
                            @error('product_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        @if ($product_id)
                            <div>
                                <label style="{{ $labelStyle }}">Module <span style="color: #DC2626;">*</span></label>
                                <select wire:model.defer="module_id" style="{{ $selectStyle }}">
                                    <option value="">Select Module</option>
                                    @foreach ($this->modules as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                                </select>
                                @error('module_id') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Platform <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="platform" style="{{ $selectStyle }}">
                                <option value="">Select platform</option>
                                @foreach ($this->platformOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                            @error('platform') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Version <span style="color: #DC2626;">*</span> <span style="font-size: 12px; color: #9CA3AF; font-weight: 400;">(Auto-generated, editable)</span></label>
                            <input type="text" wire:model.defer="version" placeholder="Select product first" style="{{ $inputStyle }}">
                            @error('version') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Planned Live Date <span style="color: #DC2626;">*</span></label>
                        <input type="text" wire:model.defer="planned_live_date" x-init="const run = () => window.flatpickr ? flatpickr($el, { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', altInputClass: 'flatpickr-styled', allowInput: true }) : setTimeout(run, 50); run();" placeholder="dd/mm/yyyy" style="{{ $inputStyle }}">
                        @error('planned_live_date') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 16px 28px; border-top: 1px solid #E5E7EB;">
                    <button type="button" wire:click="closeDrawer" style="padding: 10px 24px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                    <button type="button" wire:click="submit" style="padding: 10px 28px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Create
                    </button>
                </div>
            </aside>
        </div>
    @endif
</div>
