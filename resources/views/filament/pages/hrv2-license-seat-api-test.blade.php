<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Use the buttons above to test each API endpoint. The response will appear below.
        </div>

        @if($responseOutput)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">API Response</h3>
                    <button
                        wire:click="$set('responseOutput', null)"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        Clear
                    </button>
                </div>
                <pre class="text-xs font-mono bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ $responseOutput }}</pre>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">API Endpoints Reference</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 font-medium text-gray-500">Action</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-500">Method</th>
                            <th class="text-left py-2 px-3 font-medium text-gray-500">Endpoint</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 dark:text-gray-400">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3">Create Account</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs font-mono">POST</span></td>
                            <td class="py-2 px-3 font-mono">/api/crm/account</td>
                        </tr>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3">Add Buffer License</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs font-mono">POST</span></td>
                            <td class="py-2 px-3 font-mono">/api/crm/account/{'{accountId}'}/company/{'{companyId}'}/licenses/buffer</td>
                        </tr>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3">Update Buffer License</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 text-xs font-mono">PUT</span></td>
                            <td class="py-2 px-3 font-mono">/api/crm/account/{'{accountId}'}/company/{'{companyId}'}/licenses/buffer/{'{licenseSetId}'}</td>
                        </tr>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3">Add Paid App License</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs font-mono">POST</span></td>
                            <td class="py-2 px-3 font-mono">/api/crm/account/{'{accountId}'}/company/{'{companyId}'}/licenses/paid-app</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-3">Update Paid App License</td>
                            <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 text-xs font-mono">PUT</span></td>
                            <td class="py-2 px-3 font-mono">/api/crm/account/{'{accountId}'}/company/{'{companyId}'}/licenses/paid-app/{'{periodId}'}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
