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
            $taskMap = $this->tasks->keyBy('id');
        @endphp

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999998; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 560px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;">Create Suggestion</h2>
                    <button type="button" wire:click="closeDrawer" style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="submit" style="flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 24px;">
                    <div style="display: grid; grid-template-columns: {{ $product_id ? '1fr 1fr' : '1fr' }}; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Product <span style="color: #DC2626;">*</span></label>
                            <select wire:model.live="product_id" style="{{ $selectStyle }}">
                                <option value="">Select Product</option>
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

                    <div>
                        <label style="{{ $labelStyle }}">Title <span style="color: #DC2626;">*</span></label>
                        <input type="text" wire:model.defer="title" placeholder="Enter a clear, descriptive title for your suggestion" style="{{ $inputStyle }}">
                        @error('title') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Description <span style="color: #DC2626;">*</span></label>
                        <textarea wire:model.defer="description" rows="5"
                                  placeholder="Describe your suggestion in detail. Include the problem it solves, expected benefits, and any implementation ideas."
                                  style="{{ $inputStyle }} resize: vertical; font-family: inherit;"></textarea>
                        @error('description') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="{{ $labelStyle }}">Priority <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="priority" style="{{ $selectStyle }}">
                                <option value="">Select Priority</option>
                                @foreach ($this->priorityOptions as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                            </select>
                            @error('priority') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label style="{{ $labelStyle }}">Category <span style="color: #DC2626;">*</span></label>
                            <select wire:model.defer="category" style="{{ $selectStyle }}">
                                <option value="">Select Category</option>
                                @foreach ($this->categoryOptions as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                            </select>
                            @error('category') <div style="color: #DC2626; font-size: 12px; margin-top: 6px;">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelMutedStyle }}">Related Task</label>
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" style="position: relative;">
                            <button type="button" @click="open = !open"
                                    style="width: 100%; min-height: 48px; padding: 8px 14px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; text-align: left;">
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; min-width: 0;">
                                    @if (empty($related_task_ids))
                                        <span style="color: #9CA3AF; font-size: 14px;">+ Add</span>
                                    @else
                                        @foreach ($related_task_ids as $tid)
                                            @if (isset($taskMap[$tid]))
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #EEF2FF; color: #4F46E5; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                                    {{ $taskMap[$tid]->task_id }}
                                                    <span @click.stop="$wire.call('removeRelatedTask', {{ $tid }})" style="cursor: pointer; line-height: 1; font-size: 14px; color: #4F46E5;">×</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition.opacity
                                 style="position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); z-index: 50; display: flex; flex-direction: column;">
                                <div style="padding: 10px; border-bottom: 1px solid #F3F4F6;">
                                    <input type="text" x-model="search" placeholder="Search tasks..." @click.stop
                                           style="width: 100%; padding: 8px 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; background: #F9FAFB;">
                                </div>
                                <ul style="max-height: 240px; overflow-y: auto; padding: 6px; margin: 0; list-style: none; display: flex; flex-direction: column;">
                                    @foreach ($this->tasks as $t)
                                        @php $isSel = in_array($t->id, $related_task_ids); $label = $t->task_id . ' — ' . \Illuminate\Support\Str::limit($t->title, 60); @endphp
                                        <li x-show="'{{ \Illuminate\Support\Str::lower(str_replace(["'", '"'], '', $label)) }}'.includes(search.toLowerCase())"
                                            wire:click="toggleRelatedTask({{ $t->id }})" @click.stop
                                            style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; cursor: pointer; border-radius: 8px; width: 100%;"
                                            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 2px solid {{ $isSel ? '#6366F1' : '#D1D5DB' }}; background: {{ $isSel ? '#6366F1' : 'white' }}; border-radius: 4px; flex-shrink: 0;">
                                                @if ($isSel)<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>@endif
                                            </span>
                                            <span style="font-size: 13px; color: #111827;">{{ $label }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Attachments</label>
                        <div style="background: #f9fafb; border: 1px dashed #E5E7EB; border-radius: 10px; padding: 32px; text-align: center;">
                            <input type="file" wire:model="attachments" multiple id="suggestionAttachments" style="display: none;">
                            <label for="suggestionAttachments" style="cursor: pointer; display: block;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5" style="display: inline-block; margin-bottom: 10px;"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5"/><path d="m7.5 11.25 4.5-4.5 4.5 4.5"/><path d="M12 6.75v10.5"/></svg>
                                <div style="font-size: 14px; color: #6B7280;">Drag and drop files here, or <span style="color: #6366F1; font-weight: 600;">browse</span></div>
                                <div style="font-size: 12px; color: #9CA3AF; margin-top: 6px;">Supported: PNG, JPG, GIF, PDF, DOC (Max 10MB each)</div>
                            </label>
                            @if (!empty($attachments))
                                <div style="margin-top: 12px; font-size: 12px; color: #4F46E5;">{{ count($attachments) }} file(s) selected</div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Reference Link</label>
                        <input type="url" wire:model.defer="reference_link" placeholder="https://example.com/reference" style="{{ $inputStyle }}">
                        <div style="font-size: 12px; color: #9CA3AF; margin-top: 6px;">Add a link to mockups, designs, documentation, or related resources</div>
                    </div>
                </form>

                <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 16px 28px; border-top: 1px solid #E5E7EB;">
                    <button type="button" wire:click="closeDrawer" style="padding: 10px 24px; background: white; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancel</button>
                    <button type="button" wire:click="submitAndAddAnother" style="padding: 10px 22px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Submit & Add Another
                    </button>
                    <button type="button" wire:click="submit" style="padding: 10px 28px; background: #6366F1; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Submit
                    </button>
                </div>
            </aside>
        </div>
    @endif
</div>
