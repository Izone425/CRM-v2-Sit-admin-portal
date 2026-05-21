<div>
    <button
        wire:click="toggleAutoRenewal({{ $getRecord()->id }})"
        style="position: relative; display: inline-flex; height: 24px; width: 44px; flex-shrink: 0; cursor: pointer; border-radius: 9999px; border: 2px solid transparent; transition: background-color 0.2s ease-in-out; background-color: {{ $getRecord()->is_enabled ? '#10b981' : '#d1d5db' }};"
        type="button"
        role="switch"
        aria-checked="{{ $getRecord()->is_enabled ? 'true' : 'false' }}"
    >
        <span
            style="pointer-events: none; display: inline-block; height: 20px; width: 20px; border-radius: 9999px; background-color: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.2s ease-in-out; transform: translateX({{ $getRecord()->is_enabled ? '20px' : '0px' }});"
        ></span>
    </button>
</div>
