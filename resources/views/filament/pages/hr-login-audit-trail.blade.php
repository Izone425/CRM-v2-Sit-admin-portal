<x-filament-panels::page>
    <div class="p-4 bg-white rounded-lg shadow-lg">
        <div class="flex items-center justify-end mb-4">
            <span class="text-sm font-medium text-gray-600">
                Total Records: <span class="font-bold text-gray-900">{{ number_format($this->getTableRecords()->total()) }}</span>
            </span>
        </div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
