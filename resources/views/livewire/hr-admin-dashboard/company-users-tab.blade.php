<div class="p-6">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Users</h3>
            <p class="text-sm text-gray-500">All users associated with this company account</p>
        </div>
        <button wire:click="openLoginLogModal" type="button"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; font-size:0.875rem; font-weight:500; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:6px; cursor:pointer;"
            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Login Audit Log
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200" style="table-layout: fixed;">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 50px;">No</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 120px;">Backend User Id</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 160px;">Full Name</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Login Id</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 140px;">Password</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 100px;">Role</th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 100px;">Status</th>
                    {{-- Hidden for now: product subscription columns
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Attendance">TA</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Leave">TL</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Claim">TC</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Payroll">TP</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Onboarding & Offboarding">TO</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Recruitment">TR</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Appraisal">TAP</span></th>
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase"><span class="border-b border-gray-400 border-dashed cursor-help" title="TimeTec Training">TT</span></th>
                    --}}
                    <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase" style="width: 200px;">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $index => $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $user['backend_user_id'] }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $user['full_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $user['login_id'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $user['password'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded {{ $user['role'] === 'OWNER' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $user['role'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded {{ $user['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user['status'] }}
                            </span>
                        </td>
                        {{-- Hidden for now: product subscription cells
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['ta'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tl'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tc'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tp'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['to'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tr'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tap'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if(($user['role'] ?? '') === 'OWNER' || ($user['tt'] ?? false))
                                <i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>
                            @endif
                        </td>
                        --}}
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($user['role'] === 'OWNER' && ($companyData['hr_user_id'] ?? null) && ($companyData['hr_company_id'] ?? null))
                                    <form method="POST" action="{{ route('hr.crm-auth.login-as-user') }}" target="_blank" class="inline">
                                        @csrf
                                        <input type="hidden" name="email" value="{{ $user['login_id'] }}">
                                        <input type="hidden" name="userId" value="{{ $companyData['hr_user_id'] }}">
                                        <input type="hidden" name="companyId" value="{{ $companyData['hr_company_id'] }}">
                                        <button type="submit" style="display:inline-flex; align-items:center; padding:4px 12px; font-size:0.875rem; font-weight:500; color:#fff; background:#2563eb; border:none; border-radius:4px; cursor:pointer;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                            Login as User
                                        </button>
                                    </form>
                                @endif
                                {{-- <button wire:click="openEditDrawer('{{ $user['login_id'] }}')" style="display:inline-flex; align-items:center; padding:4px 12px; font-size:0.875rem; font-weight:500; color:#000; background:#eab308; border:none; border-radius:4px; cursor:pointer;" onmouseover="this.style.background='#ca8a04'" onmouseout="this.style.background='#eab308'">
                                    Edit
                                </button> --}}
                                @if(($user['status'] ?? '') === 'Inactive')
                                    <button style="display:inline-flex; align-items:center; padding:4px 12px; font-size:0.875rem; font-weight:500; color:#000; background:#16a34a; border:none; border-radius:4px; cursor:pointer;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                                        Enable
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                            <svg class="w-6 h-6 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p class="mt-1 text-sm">No users found for this company</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Total Users: <span class="font-semibold">{{ $users->count() }}</span>
    </div>

    {{-- Edit Password Slide-Over Drawer --}}
    @if($showEditDrawer)
        {{-- Backdrop --}}
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(107, 114, 128, 0.75); z-index: 9998;" wire:click="closeEditDrawer"></div>

        {{-- Drawer Panel - right side --}}
        <div style="position: fixed; top: 0; right: 0; bottom: 0; width: 28rem; max-width: 100vw; z-index: 9999;">
            <div class="flex flex-col h-full bg-white shadow-xl">
                {{-- Header --}}
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
                            <p class="text-sm text-gray-500">System will update if password is not empty</p>
                        </div>
                        <button wire:click="closeEditDrawer" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex-1 px-6 py-6 overflow-y-auto">
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Login ID</label>
                            <p class="px-3 py-2 text-sm text-gray-900 border rounded bg-gray-50">{{ $editLoginId }}</p>
                        </div>

                        <div>
                            <label for="newPassword" class="block mb-1 text-sm font-medium text-gray-700">New Password <span class="text-red-500">*</span></label>
                            <div style="position: relative;" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" id="newPassword" wire:model="newPassword"
                                    class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('newPassword') border-red-500 @enderror"
                                    style="padding: 0.5rem 2.5rem 0.5rem 0.75rem;"
                                    placeholder="Enter new password">
                                <button type="button" @click="show = !show" style="position: absolute; top: 50%; right: 0.75rem; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 0;">
                                    <svg x-show="!show" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('newPassword')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="confirmPassword" class="block mb-1 text-sm font-medium text-gray-700">Confirm New Password <span class="text-red-500">*</span></label>
                            <div style="position: relative;" x-data="{ show: false }">
                                <input :type="show ? 'text' : 'password'" id="confirmPassword" wire:model="confirmPassword"
                                    class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm @error('confirmPassword') border-red-500 @enderror"
                                    style="padding: 0.5rem 2.5rem 0.5rem 0.75rem;"
                                    placeholder="Confirm new password">
                                <button type="button" @click="show = !show" style="position: absolute; top: 50%; right: 0.75rem; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; padding: 0;">
                                    <svg x-show="!show" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('confirmPassword')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button wire:click="closeEditDrawer" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="updatePassword" class="px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                        Update
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Login as User Audit Log Modal --}}
    @if($showLoginLogModal)
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(107, 114, 128, 0.75); z-index: 9998;" wire:click="closeLoginLogModal"></div>

        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 64rem; max-width: 95vw; max-height: 85vh; z-index: 9999;">
            <div class="flex flex-col bg-white shadow-xl rounded-lg" style="max-height: 85vh;">
                {{-- Header --}}
                <div class="px-6 py-4 border-b bg-gray-50 rounded-t-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Login as User Audit Log</h2>
                            <p class="text-sm text-gray-500">Showing the latest 100 entries for this company</p>
                        </div>
                        <button wire:click="closeLoginLogModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto">
                    @if($loginLogs->isEmpty())
                        <div class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-3l-2-2H10L8 5H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm">No login-as-user activity recorded for this company yet.</p>
                        </div>
                    @else
                        <table class="w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">When</th>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">CRM User</th>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Logged in as</th>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">HR User ID</th>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">IP</th>
                                    <th class="px-4 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($loginLogs as $log)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ \Carbon\Carbon::parse($log->created_at)->format('d-m-Y H:i:s') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-900">{{ $log->causer?->name ?? $log->causer_name ?? '-' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ $log->target_email }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ $log->hr_user_id }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @php
                                                $color = match($log->status) {
                                                    'success' => 'background:#dcfce7; color:#166534;',
                                                    'failed' => 'background:#fee2e2; color:#991b1b;',
                                                    default => 'background:#fef3c7; color:#92400e;',
                                                };
                                            @endphp
                                            <span style="display:inline-flex; padding:2px 8px; font-size:0.75rem; font-weight:500; border-radius:9999px; {{ $color }}" title="{{ $log->error_message ?? '' }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex justify-end px-6 py-3 border-t bg-gray-50 rounded-b-lg">
                    <button wire:click="closeLoginLogModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
