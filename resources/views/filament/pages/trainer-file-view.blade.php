<x-filament-panels::page>
    <style>
        .trainer-file-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.25rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
        }

        .trainer-file-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
        }

        /* Tabs styling */
        .file-tabs {
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.375rem;
            border-radius: 0.75rem;
        }

        .file-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .file-tab:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .file-tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .file-tab svg {
            width: 1.125rem;
            height: 1.125rem;
        }

        /* Responsive: stack on smaller screens */
        @media (max-width: 900px) {
            .trainer-file-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .file-tabs {
                overflow-x: auto;
                max-width: 100%;
            }
        }
    </style>

    <div class="trainer-file-header">
        <h2>
            {{ ucfirst(strtolower($trainingType)) }} Training Files
        </h2>
        <div class="file-tabs">
            @foreach($fileTabs as $tabKey => $tabInfo)
                <button
                    type="button"
                    wire:click="switchTab('{{ $tabKey }}')"
                    class="file-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
                >
                    @if($tabInfo['icon'] === 'presentation')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                    @elseif($tabInfo['icon'] === 'document')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    @elseif($tabInfo['icon'] === 'book')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif($tabInfo['icon'] === 'video')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    @endif
                    {{ $tabInfo['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
