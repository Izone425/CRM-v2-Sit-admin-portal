<x-filament-panels::page>
    <style>
        /* === WRAPPER === */
        .qc-form-wrapper {
            max-width: 960px;
            margin: 0 auto;
        }

        .qc-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #E5E7EB;
        }
        .qc-form-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qc-form-header h2 .qc-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            background: #EEF2FF;
            color: #4338CA;
        }
        .qc-form-header h2 .qc-pill.edit {
            background: #FEF3C7;
            color: #92400E;
        }
        .qc-back-link {
            font-size: 13px;
            color: #4F46E5;
            text-decoration: none;
            font-weight: 600;
        }
        .qc-back-link:hover { text-decoration: underline; }

        /* === SECTIONS === */
        .qc-section {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .qc-section-header {
            padding: 14px 20px;
            background: linear-gradient(180deg, #F9FAFB 0%, #F3F4F6 100%);
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .qc-section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1F2937;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qc-section-title .icon {
            width: 20px;
            height: 20px;
            background: #EEF2FF;
            color: #4338CA;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .qc-section-body { padding: 18px 20px; }

        /* === FIELDS === */
        .qc-field { margin-bottom: 14px; }
        .qc-field:last-child { margin-bottom: 0; }
        .qc-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .qc-field .qc-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .qc-mini-link-btn {
            background: transparent;
            border: 0;
            color: #4F46E5;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }
        .qc-mini-link-btn:hover { text-decoration: underline; }

        .qc-field input,
        .qc-field select,
        .qc-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 13px;
            color: #111827;
            background: #fff;
            box-sizing: border-box;
            transition: all 0.15s;
        }
        .qc-field input:focus,
        .qc-field select:focus,
        .qc-field textarea:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .qc-field select:disabled,
        .qc-field input:disabled,
        .qc-field textarea:disabled,
        .qc-field textarea[readonly] {
            background: #F3F4F6;
            color: #6B7280;
            cursor: not-allowed;
        }
        .qc-field textarea { resize: vertical; min-height: 110px; font-family: inherit; }

        .qc-locked-note {
            display: inline-block;
            margin-left: 6px;
            font-size: 10px;
            padding: 1px 8px;
            background: #F3F4F6;
            color: #6B7280;
            border-radius: 9999px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .qc-locked-pill {
            display: inline-block;
            margin-left: 10px;
            padding: 2px 10px;
            background: #D1FAE5;
            color: #065F46;
            font-size: 10px;
            font-weight: 700;
            border-radius: 9999px;
            letter-spacing: 0.04em;
        }

        .qc-prompt-row.locked {
            background: #F9FAFB;
            border-color: #D1FAE5;
        }
        .qc-prompt-row.locked textarea[readonly] {
            background: #F9FAFB;
            border-color: #E5E7EB;
            color: #6B7280;
        }
        .qc-prompt-number.locked {
            background: linear-gradient(135deg, #10B981 0%, #065F46 100%);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
        }

        .qc-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .qc-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

        /* Inline create option */
        .qc-create-option-row {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            align-items: center;
        }
        .qc-create-option-row input {
            flex: 1;
            padding: 7px 10px !important;
            font-size: 12px !important;
        }
        .qc-create-option-btn {
            padding: 7px 12px;
            background: #6366F1;
            color: #fff;
            border: 0;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .qc-create-option-btn:hover { background: #4F46E5; }
        .qc-cancel-option-btn {
            padding: 7px 10px;
            background: #fff;
            color: #6B7280;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        /* === PROMPT CARDS === */
        .qc-prompt-row {
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 14px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 14px 16px 14px 14px;
            margin-bottom: 12px;
            background: #fff;
            transition: all 0.15s;
        }
        .qc-prompt-row:hover {
            border-color: #C7D2FE;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);
        }
        .qc-prompt-row:focus-within {
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .qc-prompt-number {
            grid-row: span 2;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366F1 0%, #4338CA 100%);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
        }

        .qc-prompt-body {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .qc-prompt-row-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            gap: 10px;
        }
        .qc-prompt-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            color: #9CA3AF;
        }
        .qc-prompt-meta strong {
            color: #4338CA;
            font-weight: 700;
        }

        .qc-prompt-row textarea {
            min-height: 160px;
            font-size: 14px;
            line-height: 1.6;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            background: #FAFAFA;
        }
        .qc-prompt-row textarea:focus {
            background: #fff;
        }

        .qc-remove-btn {
            background: transparent;
            border: 0;
            color: #B91C1C;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
        }
        .qc-remove-btn:hover { background: #FEF2F2; }
        .qc-remove-btn:disabled { color: #D1D5DB; cursor: not-allowed; }
        .qc-remove-btn:disabled:hover { background: transparent; }

        .qc-add-prompt {
            background: #EEF2FF;
            border: 1.5px dashed #A5B4FC;
            color: #4338CA;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .qc-add-prompt:hover {
            background: #E0E7FF;
            border-color: #6366F1;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.15);
        }
        .qc-add-prompt .qc-plus-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #6366F1;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* === FOOTER === */
        .qc-form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 16px 0 4px 0;
            margin-top: 6px;
        }
        .qc-footer-hint {
            font-size: 12px;
            color: #6B7280;
        }
        .qc-footer-actions {
            display: flex;
            gap: 10px;
        }

        .qc-btn-primary {
            background: #6366F1; color: #fff; border: 0;
            padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
        }
        .qc-btn-primary:hover { background: #4F46E5; }
        .qc-btn-primary:disabled { background: #A5B4FC; cursor: not-allowed; }
        .qc-btn-secondary {
            background: #fff; color: #374151; border: 1px solid #D1D5DB;
            padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .qc-btn-secondary:hover { background: #F9FAFB; }
        .qc-err { color: #DC2626; font-size: 11px; margin-top: 4px; }
    </style>

    <form wire:submit.prevent="save">
        <div class="qc-form-wrapper">

            {{-- Header --}}
            <div class="qc-form-header">
                <h2>
                    {{ $isEdit ? 'Edit Prompt Task' : 'Create Prompt Task' }}
                    <span class="qc-pill {{ $isEdit ? 'edit' : '' }}">
                        {{ $isEdit ? 'Edit Mode' : 'New' }}
                    </span>
                </h2>
                <a class="qc-back-link" href="{{ \App\Filament\Pages\QCPromptTask::getUrl() }}">&larr; Back to list</a>
            </div>

            {{-- Task Details --}}
            <div class="qc-section">
                <div class="qc-section-header">
                    <h3 class="qc-section-title">
                        <span class="icon">①</span> Task Details
                    </h3>
                </div>
                <div class="qc-section-body">

                    <div class="qc-row-2">
                        <div class="qc-field">
                            <label for="qc-version">HR Version</label>
                            <select id="qc-version" wire:model.live="hrVersion" @if($isEdit) disabled @endif>
                                <option value="v1">HR Version 1</option>
                                <option value="v2">HR Version 2</option>
                            </select>
                            @error('hrVersion') <div class="qc-err">{{ $message }}</div> @enderror
                        </div>

                        <div class="qc-field">
                            <label for="qc-module">Module</label>
                            <select id="qc-module" wire:model.live="module" @if($isEdit) disabled @endif>
                                @foreach($this->availableModules as $mod)
                                    <option value="{{ $mod }}">{{ $mod }}</option>
                                @endforeach
                            </select>
                            @error('module') <div class="qc-err">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="qc-field">
                        <label for="qc-title">Title</label>
                        <input type="text"
                               id="qc-title"
                               wire:model.defer="taskTitle"
                               x-data
                               x-on:input="const s=$el.selectionStart, e=$el.selectionEnd; $el.value=$el.value.toUpperCase(); $el.setSelectionRange(s,e);"
                               style="text-transform:uppercase;"
                               placeholder="TASK TITLE">
                        @error('taskTitle') <div class="qc-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Label Tiers --}}
            <div class="qc-section">
                <div class="qc-section-header">
                    <h3 class="qc-section-title">
                        <span class="icon">②</span> Label Tiers
                    </h3>
                </div>
                <div class="qc-section-body">
                    <div class="qc-row-3">
                        @php
                            $tiers = [
                                ['key' => 'tier1', 'label' => 'Label Tier 1', 'model' => 'labelTier1', 'current' => $labelTier1],
                                ['key' => 'tier2', 'label' => 'Label Tier 2', 'model' => 'labelTier2', 'current' => $labelTier2],
                                ['key' => 'tier3', 'label' => 'Label Tier 3', 'model' => 'labelTier3', 'current' => $labelTier3],
                            ];
                        @endphp

                        @foreach($tiers as $t)
                            <div class="qc-field">
                                <div class="qc-label-row">
                                    <label for="qc-{{ $t['key'] }}" style="margin-bottom:0;">{{ $t['label'] }}</label>
                                    @if($creatingTier !== $t['key'])
                                        <button type="button"
                                                class="qc-mini-link-btn"
                                                wire:click="showCreateOption('{{ $t['key'] }}')">
                                            + Create Option
                                        </button>
                                    @endif
                                </div>

                                <select id="qc-{{ $t['key'] }}" wire:model.defer="{{ $t['model'] }}">
                                    <option value="">— None —</option>
                                    @foreach($this->labelOptions[$t['key']] as $opt)
                                        <option value="{{ $opt }}" @if($t['current'] === $opt) selected @endif>{{ $opt }}</option>
                                    @endforeach
                                </select>

                                @if($creatingTier === $t['key'])
                                    <div class="qc-create-option-row">
                                        <input type="text"
                                               wire:model.defer="newOptionValue"
                                               x-data
                                               x-on:input="const s=$el.selectionStart, e=$el.selectionEnd; $el.value=$el.value.toUpperCase(); $el.setSelectionRange(s,e);"
                                               style="text-transform:uppercase;"
                                               placeholder="ENTER NEW OPTION..."
                                               wire:keydown.enter.prevent="saveNewOption">
                                        <button type="button" class="qc-create-option-btn" wire:click="saveNewOption">Save</button>
                                        <button type="button" class="qc-cancel-option-btn" wire:click="cancelCreateOption">Cancel</button>
                                    </div>
                                @endif

                                @error($t['model']) <div class="qc-err">{{ $message }}</div> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Prompts --}}
            <div class="qc-section">
                <div class="qc-section-header">
                    <h3 class="qc-section-title">
                        <span class="icon">③</span> Prompts
                    </h3>
                    <span style="font-size:12px; color:#6B7280;">{{ count($prompts) }} prompt(s)</span>
                </div>
                <div class="qc-section-body">

                    @foreach($prompts as $index => $promptText)
                        @php $isLocked = $promptLocks[$index] ?? false; @endphp
                        <div class="qc-prompt-row {{ $isLocked ? 'locked' : '' }}" wire:key="prompt-row-{{ $index }}">
                            <div class="qc-prompt-number {{ $isLocked ? 'locked' : '' }}">
                                @if($isLocked)
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>

                            <div class="qc-prompt-body">
                                <div class="qc-prompt-row-toolbar">
                                    <div class="qc-prompt-meta">
                                        <strong>Prompt #{{ $index + 1 }}</strong>
                                    </div>
                                    @if(!$isLocked)
                                        <button type="button"
                                                class="qc-remove-btn"
                                                wire:click="removePrompt({{ $index }})"
                                                @if(count($prompts) <= 1) disabled @endif>
                                            ✕ Remove
                                        </button>
                                    @endif
                                </div>
                                <textarea wire:model.defer="prompts.{{ $index }}"
                                          placeholder="Write the AI prompt here..."
                                          @if($isLocked) readonly @endif></textarea>
                                @error('prompts.' . $index) <div class="qc-err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endforeach

                    <button type="button" class="qc-add-prompt" wire:click="addPrompt">
                        <span class="qc-plus-icon">+</span>
                        Add another prompt
                    </button>
                </div>
            </div>

            {{-- Footer --}}
            <div class="qc-form-footer">
                <span class="qc-footer-hint">
                    @if($isEdit)
                        Editing an existing task. Changes to prompts keep their status/attachment when possible.
                    @else
                        All prompts will be created with status "Pending".
                    @endif
                </span>
                <div class="qc-footer-actions">
                    <a href="{{ \App\Filament\Pages\QCPromptTask::getUrl() }}" class="qc-btn-secondary">Cancel</a>
                    <button type="submit" class="qc-btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            {{ $isEdit ? 'Save Changes' : 'Create Task' }}
                        </span>
                        <span wire:loading wire:target="save">
                            {{ $isEdit ? 'Saving...' : 'Creating...' }}
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </form>
</x-filament-panels::page>
