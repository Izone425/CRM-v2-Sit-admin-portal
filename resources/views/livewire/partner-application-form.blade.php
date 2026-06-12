<div class="min-h-screen py-12 px-4 bg-cover bg-center bg-no-repeat bg-fixed" style="background-image: url('/img/bg-login.jpg');">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-8 sm:p-10">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Become a TimeTec {{ $this->programLabel }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Apply to join TimeTec as a {{ $this->programLabel }}. Our team will review your submission and reach out to you.
                </p>
            </div>

            @if ($submitted)
                <div class="rounded-lg bg-green-50 border border-green-200 p-6">
                    <h2 class="text-base font-semibold text-green-900">Application received.</h2>
                    <p class="mt-2 text-sm text-green-800">
                        Thank you. We've recorded your application and our team will be in touch shortly.
                    </p>
                    <button
                        type="button"
                        wire:click="$set('submitted', false)"
                        class="mt-4 inline-flex items-center text-sm font-medium text-green-700 hover:text-green-900"
                    >
                        Submit another application
                    </button>
                </div>
            @else
                <form wire:submit.prevent="submit" class="space-y-10">

                    {{-- Company Information --}}
                    <section>
                        <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-5">Company Information</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-800 mb-1">Company Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="company_name" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-800 mb-1">Address <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="address" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">State <span class="text-red-500">*</span></label>
                                @if ($stateOptions->isNotEmpty())
                                    <select wire:model.defer="state" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                        <option value="">— Select state —</option>
                                        @foreach ($stateOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model.defer="state" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @endif
                                @error('state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Postcode <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="postcode" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('postcode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Country <span class="text-red-500">*</span></label>
                                @if ($countryOptions->isNotEmpty())
                                    <select wire:model.defer="country" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                        <option value="">— Select country —</option>
                                        @foreach ($countryOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model.defer="country" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @endif
                                @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Telephone <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="telephone" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('telephone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-800 mb-1">Company Website <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="company_website" placeholder="https://" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('company_website') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Business Type <span class="text-red-500">*</span></label>
                                <select wire:model.defer="business_type" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                    <option value="">— Select type —</option>
                                    <option value="sole_proprietorship">Sole Proprietorship</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="corporation">Corporation</option>
                                </select>
                                @error('business_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Industry <span class="text-red-500">*</span></label>
                                @if ($industryOptions->isNotEmpty())
                                    <select wire:model.defer="industry" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                        <option value="">— Select industry —</option>
                                        @foreach ($industryOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model.defer="industry" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @endif
                                @error('industry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-800 mb-1">Number of years of your business <span class="text-red-500">*</span></label>
                                <select wire:model.defer="years_in_business" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                    <option value="">— Select range —</option>
                                    <option value="1_3">1 - 3 years</option>
                                    <option value="4_5">4 - 5 years</option>
                                    <option value="6_10">6 - 10 years</option>
                                    <option value="more_than_10">More than 10 years</option>
                                </select>
                                @error('years_in_business') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Contact Information --}}
                    <section>
                        <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-5">Contact Information</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" wire:model.blur="email" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Mobile Phone <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="mobile_phone" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('mobile_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Password <span class="text-red-500">*</span></label>
                                <input type="password" wire:model.defer="password" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="password" wire:model.defer="password_confirmation" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">First Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="first_name" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="last_name" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-800 mb-1">Designation <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="designation" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border">
                                @error('designation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-2">Are you an existing FingerTec reseller? <span class="text-red-500">*</span></label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" wire:model.defer="existing_fingertec_reseller" value="1" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" wire:model.defer="existing_fingertec_reseller" value="0" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">No</span>
                                    </label>
                                </div>
                                @error('existing_fingertec_reseller') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-2">Modules <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" wire:model.defer="categories" value="attendance" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">Attendance</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" wire:model.defer="categories" value="leave" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">Leave</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" wire:model.defer="categories" value="claim" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">Claim</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" wire:model.defer="categories" value="payroll" class="text-indigo-600">
                                        <span class="text-sm text-gray-900">Payroll</span>
                                    </label>
                                </div>
                                @error('categories') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-800 mb-1">
                                        Headcount <span class="text-gray-500 text-xs font-normal">(max 100)</span> <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="100"
                                        wire:model.defer="headcount"
                                        class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm px-3 py-2 border"
                                    >
                                    @error('headcount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Agreement & Consent --}}
                    <section>
                        <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-5">Agreement &amp; Consent</h2>
                        <div class="space-y-3">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" wire:model.defer="consent_setup_permission" class="mt-1 text-indigo-600">
                                <span class="text-sm text-gray-800">
                                    I confirm that I have the permission to set up this account for my company. <span class="text-red-500">*</span>
                                </span>
                            </label>
                            @error('consent_setup_permission') <p class="text-xs text-red-600 ml-7">{{ $message }}</p> @enderror

                            <label class="flex items-start gap-3">
                                <input type="checkbox" wire:model.defer="consent_marketing" class="mt-1 text-indigo-600">
                                <span class="text-sm text-gray-800">
                                    I would like to receive updates and information on TimeTec news, events and promos.
                                </span>
                            </label>
                        </div>
                    </section>

                    <div>
                        @error('submit')
                            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                                class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submit">Submit application</span>
                            <span wire:loading wire:target="submit">Submitting…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <p class="mt-8 text-xs text-gray-500 text-center">
            &copy; {{ date('Y') }} TimeTec. All rights reserved.
        </p>
    </div>
</div>
