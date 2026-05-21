<div>
    @if ($showDrawer)
        <style>
            @keyframes drawerSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
            @keyframes drawerOverlayFade { from { opacity: 0; } to { opacity: 1; } }
        </style>

        <div x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999997; display: flex; justify-content: flex-end; animation: drawerOverlayFade 0.2s ease-out;">
            <aside style="width: 100%; max-width: 520px; height: calc(100vh - 32px); margin: 16px 16px 16px 0; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); display: flex; flex-direction: column; border-radius: 16px; animation: drawerSlideIn 0.28s cubic-bezier(0.22, 1, 0.36, 1);">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #E5E7EB;">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.04em;">
                            {{ $type === 'bug' ? 'Bugs' : 'Tasks' }} · {{ $status }}
                        </div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 4px 0 0 0;">
                            {{ $this->items->count() }} {{ \Illuminate\Support\Str::plural($type, $this->items->count()) }}
                        </h2>
                    </div>
                    <button type="button" wire:click="closeDrawer"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div style="flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 10px;">
                    @forelse ($this->items as $item)
                        <button
                            type="button"
                            wire:click="$dispatch('{{ $type === 'bug' ? 'openBugModal' : 'openTaskModal' }}', [{{ $item->id }}])"
                            style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; cursor: pointer; text-align: left; transition: border-color 0.15s, box-shadow 0.15s;"
                            onmouseover="this.style.borderColor='#C7D2FE'; this.style.boxShadow='0 2px 8px rgba(99,102,241,0.08)';"
                            onmouseout="this.style.borderColor='#E5E7EB'; this.style.boxShadow='none';">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #4F46E5; background: #EEF2FF; border-radius: 4px;">
                                        {{ $type === 'bug' ? $item->bug_id : $item->task_id }}
                                    </span>
                                    @if ($type === 'bug' && $item->severity)
                                        <span style="font-size: 11px; font-weight: 600; color: #6B7280; text-transform: uppercase;">{{ $item->severity }}</span>
                                    @endif
                                </div>
                                <div style="font-size: 14px; font-weight: 500; color: #111827; line-height: 1.4; word-break: break-word;">
                                    {{ \Illuminate\Support\Str::limit($item->title, 120) }}
                                </div>
                                <div style="margin-top: 6px; font-size: 12px; color: #6B7280; display: flex; gap: 8px; flex-wrap: wrap;">
                                    @if ($item->product)
                                        <span>{{ $item->product->name }}</span>
                                    @endif
                                    @if ($type === 'task' && $item->due_date)
                                        <span>· Due {{ \Illuminate\Support\Carbon::parse($item->due_date)->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="flex-shrink: 0;"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @empty
                        <div style="padding: 48px 16px; text-align: center; color: #9CA3AF; font-size: 13px;">
                            No {{ $type === 'bug' ? 'bugs' : 'tasks' }} with status "{{ $status }}".
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    @endif
</div>
