<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" style="position: relative; display: inline-block; order: -1; margin-right: 8px;">
    <button
        type="button"
        @click="open = !open"
        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: {{ $activeRole ? '#EEF2FF' : 'white' }}; color: #4F46E5; border: 1px solid #C7D2FE; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
        onmouseover="this.style.background='#EEF2FF'"
        onmouseout="this.style.background='{{ $activeRole ? '#EEF2FF' : 'white' }}'"
    >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M21 3l-7 7"/><path d="M3 3l7 7"/><path d="M16 21h5v-5"/><path d="M8 21H3v-5"/><path d="M21 21l-7-7"/><path d="M3 21l7-7"/></svg>
        <span>{{ $activeRole ? ucfirst($activeRole) : 'Switch Role' }}</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open ? 'transform: rotate(180deg); transition: transform 0.15s;' : 'transition: transform 0.15s;'"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.away="open = false"
        x-transition.origin.top.right
        style="position: absolute; top: calc(100% + 8px); right: 0; min-width: 200px; background: white; border: 1px solid #E5E7EB; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 8px; z-index: 1000;"
    >
        @foreach (collect([
            ['key' => 'qc', 'label' => 'QC', 'color' => '#6366F1'],
            ['key' => 'implementer', 'label' => 'Implementer', 'color' => '#F59E0B'],
            ['key' => 'support', 'label' => 'Support', 'color' => '#10B981'],
            ['key' => 'pdt', 'label' => 'PDT', 'color' => '#EC4899'],
        ])->whereIn('key', $this->allowedRoles)->values() as $item)
            <button
                type="button"
                wire:click="switchTo('{{ $item['key'] }}')"
                @click="open = false"
                style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; background: {{ $activeRole === $item['key'] ? '#F3F4F6' : 'transparent' }}; border: none; border-radius: 6px; font-size: 14px; color: #374151; cursor: pointer; text-align: left; transition: background 0.15s;"
                onmouseover="this.style.background='#F3F4F6'"
                onmouseout="this.style.background='{{ $activeRole === $item['key'] ? '#F3F4F6' : 'transparent' }}'"
            >
                <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $item['color'] }}; flex-shrink: 0;"></span>
                <span>{{ $item['label'] }}</span>
                @if($activeRole === $item['key'])
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="3" style="margin-left: auto;"><polyline points="20 6 9 17 4 12"/></svg>
                @endif
            </button>
        @endforeach

        @if($activeRole)
            <div style="border-top: 1px solid #E5E7EB; margin: 4px 0;"></div>
            <button
                type="button"
                wire:click="switchTo('{{ $activeRole }}')"
                @click="open = false"
                style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px; background: transparent; border: none; border-radius: 6px; font-size: 14px; color: #EF4444; cursor: pointer; text-align: left; transition: background 0.15s;"
                onmouseover="this.style.background='#FEF2F2'"
                onmouseout="this.style.background='transparent'"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span>Reset to Default</span>
            </button>
        @endif
    </div>
</div>
