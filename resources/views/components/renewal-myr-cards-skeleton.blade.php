<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
    @for ($i = 0; $i < 5; $i++)
        <div style="background-color: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); overflow: hidden;">
            <div style="padding: 1.25rem 0.75rem;">
                <div style="display: flex; align-items: center;">
                    <div style="flex-shrink: 0; padding: 0.75rem; border-radius: 0.375rem; background-color: #f3f4f6;">
                        <div class="animate-pulse" style="width: 1.5rem; height: 1.5rem; background-color: #e5e7eb; border-radius: 0.25rem;"></div>
                    </div>
                    <div style="flex: 1; margin-left: 0.5rem;">
                        <div class="animate-pulse" style="height: 0.875rem; width: 70%; background-color: #e5e7eb; border-radius: 0.25rem; margin-bottom: 0.5rem;"></div>
                        <div class="animate-pulse" style="height: 0.75rem; width: 90%; background-color: #e5e7eb; border-radius: 0.25rem; margin-bottom: 0.35rem;"></div>
                        <div class="animate-pulse" style="height: 0.75rem; width: 80%; background-color: #e5e7eb; border-radius: 0.25rem; margin-bottom: 0.35rem;"></div>
                        <div class="animate-pulse" style="height: 0.75rem; width: 75%; background-color: #e5e7eb; border-radius: 0.25rem; margin-bottom: 0.5rem;"></div>
                        <div class="animate-pulse" style="height: 1.25rem; width: 60%; background-color: #d1d5db; border-radius: 0.25rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endfor
</div>
