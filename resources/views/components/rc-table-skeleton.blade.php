<div class="p-4 bg-white rounded-lg shadow-lg">
    <div class="flex items-center space-x-3 mb-5">
        <div class="h-6 w-72 bg-gray-200 rounded animate-pulse"></div>
    </div>

    <div class="flex justify-between items-center mb-3">
        <div class="flex space-x-2">
            <div class="h-9 w-28 bg-gray-200 rounded animate-pulse"></div>
            <div class="h-9 w-28 bg-gray-200 rounded animate-pulse"></div>
        </div>
        <div class="h-9 w-52 bg-gray-200 rounded animate-pulse"></div>
    </div>

    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-100 px-4 py-3 grid grid-cols-6 gap-4">
            @for ($i = 0; $i < 6; $i++)
                <div class="h-4 bg-gray-300 rounded animate-pulse"></div>
            @endfor
        </div>
        @for ($i = 0; $i < 10; $i++)
            <div class="px-4 py-3 grid grid-cols-6 gap-4 border-t border-gray-100 {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                @for ($j = 0; $j < 6; $j++)
                    <div class="h-3 bg-gray-200 rounded animate-pulse" style="width: {{ 50 + (($i * 3 + $j * 7) % 45) }}%"></div>
                @endfor
            </div>
        @endfor
    </div>

    <div class="flex justify-between items-center mt-4">
        <div class="h-4 w-36 bg-gray-200 rounded animate-pulse"></div>
        <div class="flex space-x-1">
            @for ($i = 0; $i < 5; $i++)
                <div class="h-8 w-8 bg-gray-200 rounded animate-pulse"></div>
            @endfor
        </div>
    </div>
</div>
