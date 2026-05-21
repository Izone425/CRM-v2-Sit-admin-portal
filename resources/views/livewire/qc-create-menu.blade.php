<div style="display: inline-flex; gap: 8px; order: -1; margin-right: 8px; align-items: center;">

    <button
        type="button"
        @click="$dispatch('openCreateBugModal')"
        style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #6366F1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
        onmouseover="this.style.background='#4F46E5'"
        onmouseout="this.style.background='#6366F1'"
    >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Create Bug</span>
    </button>

</div>
