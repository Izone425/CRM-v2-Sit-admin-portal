<div>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>

