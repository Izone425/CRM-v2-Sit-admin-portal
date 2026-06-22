<div class="p-6 space-y-4 bg-gray-100">
    {{-- 2-column grid for assignment forms — mirrors Profile tab's layout rhythm --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Assign to Reseller (left column, taller — 3 fields) --}}
        <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
            <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Assign to Reseller
            </h4>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <input type="text" value="{{ $companyData['company_name'] ?? '-' }}" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor/Reseller</label>
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <button type="button" @click="open = !open"
                            class="w-full text-left px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-white flex justify-between items-center focus:ring-blue-500 focus:border-blue-500">
                        <span class="{{ $this->getSelectedDealerLabel() ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $this->getSelectedDealerLabel() ?? 'Select Distributor/Reseller' }}
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                        <input type="text"
                               wire:model.live.debounce.150ms="dealerSearch"
                               placeholder="Search by name..."
                               class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 focus:border-blue-500 text-sm" />
                        <ul class="max-h-64 overflow-y-auto">
                            @if($dealerId)
                                <li>
                                    <button type="button"
                                            wire:click="selectDealer(null)"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 text-sm text-gray-500 italic">
                                        — Clear selection —
                                    </button>
                                </li>
                            @endif
                            @forelse($this->getDealerOptions() as $id => $name)
                                <li>
                                    <button type="button"
                                            wire:click="selectDealer({{ $id }})"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 text-sm {{ $dealerId === $id ? 'bg-blue-50 font-medium' : '' }}">
                                        {{ $name }}
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-sm text-gray-500">No matches</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Commission Rate --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate</label>
                <select wire:model.live="commissionRate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    @foreach ([10, 15, 20, 25, 30, 35, 40, 50] as $rate)
                        <option value="{{ $rate }}">{{ $rate }}%</option>
                    @endforeach
                </select>
            </div>

            {{-- Billing Method --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Method</label>
                <select wire:model.live="billingMethod" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Billing Method</option>
                    <option value="direct">Direct Billing</option>
                    <option value="reseller">Through Reseller</option>
                </select>
            </div>
        </div>

        {{-- Right column: Referral + Sales Person stacked (≈ matching left height) --}}
        <div class="space-y-4">
            {{-- Assign Customer to Referral --}}
            <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Assign Customer to Referral
                </h4>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <input type="text" value="{{ $companyData['company_name'] ?? '-' }}" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referral</label>
                    <select wire:model.live="referralId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Referral</option>
                    </select>
                </div>
            </div>

            {{-- Assign Sales Person — re-laid out label-above-input to match siblings --}}
            <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                <h4 class="mb-4 text-sm font-semibold text-gray-900 uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Assign Sales Person
                </h4>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Sales Person</label>
                    <div class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-900">
                        {{ $companyData['sales_person'] ?? '-' }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Sales Person</label>
                    <select wire:model.live="salesPersonId" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Sales Person</option>
                        @foreach($this->getSalesPersonOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
