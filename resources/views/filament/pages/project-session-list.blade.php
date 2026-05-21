<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <style>
        .implementer-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .implementer-scroll::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        .implementer-scroll::-webkit-scrollbar-thumb {
            background: #c7d2fe;
            border-radius: 3px;
        }
        .implementer-scroll::-webkit-scrollbar-thumb:hover {
            background: #818cf8;
        }
    </style>

    {{-- Stats Boxes --}}
    <div style="display:flex; gap:16px; flex-wrap:nowrap; align-items:flex-start;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #10b981; border-radius:12px; padding:20px 24px; min-width:180px; flex-shrink:0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Kick Off Meeting</p>
            <p style="font-size:2rem; font-weight:700; color:#10b981; margin:4px 0;">{{ $stats['total_first_time_book'] }}</p>
            <p style="font-size:0.8rem; color:#6b7280; margin:0;">Activated, never had kick off meeting</p>
        </div>

        <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #f59e0b; border-radius:12px; padding:20px 24px; min-width:180px; flex-shrink:0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Review Session</p>
            <p style="font-size:2rem; font-weight:700; color:#f59e0b; margin:4px 0;">{{ $stats['total_waiting_to_book'] }}</p>
            <p style="font-size:0.8rem; color:#6b7280; margin:0;">Had kick off, waiting for review session</p>
        </div>

        <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #3b82f6; border-radius:12px; padding:20px 24px; flex:1; min-width:0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size:0.85rem; color:#6b7280; margin:0 0 12px 0; font-weight:600;">Implementer Ranking (Waiting to Book)</p>
            <div class="implementer-scroll" style="display:flex; gap:16px; overflow-x:auto; flex-wrap:nowrap; padding-bottom:6px;">
                @foreach($stats['implementer_ranking'] as $index => $rank)
                    <div style="text-align:center; min-width:120px; flex-shrink:0; padding:8px 12px; background:#f9fafb; border-radius:8px;">
                        <p style="font-size:0.85rem; color:#374151; margin:0; font-weight:600;">{{ $rank['name'] }}</p>
                        <p style="font-size:1.5rem; font-weight:700; color:#3b82f6; margin:4px 0;">{{ $rank['count'] }}</p>
                    </div>
                @endforeach
            </div>
            @if(empty($stats['implementer_ranking']))
                <p style="font-size:0.85rem; color:#9ca3af; margin:0;">No data</p>
            @endif
        </div>
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
