@php
    $programLabel = strtoupper($application->partner_type);
    $businessTypeLabels = [
        'sole_proprietorship' => 'Sole Proprietorship',
        'partnership' => 'Partnership',
        'corporation' => 'Corporation',
    ];
    $yearsLabels = [
        '1_3' => '1 - 3 years',
        '4_5' => '4 - 5 years',
        '6_10' => '6 - 10 years',
        'more_than_10' => 'More than 10 years',
    ];
    $moduleLabels = [
        'attendance' => 'Attendance',
        'leave' => 'Leave',
        'claim' => 'Claim',
        'payroll' => 'Payroll',
    ];
    $modulesDisplay = collect($application->categories ?? [])
        ->map(fn ($k) => $moduleLabels[$k] ?? $k)
        ->implode(', ');

    $row = fn ($label, $value) =>
        '<div class="grid grid-cols-3 gap-3 py-1.5 text-sm">'
        . '<div class="text-gray-500">' . e($label) . '</div>'
        . '<div class="col-span-2 text-gray-900 font-medium">' . ($value === null || $value === '' ? '<span class="text-gray-400">—</span>' : e($value)) . '</div>'
        . '</div>';
@endphp

<div class="space-y-6 text-sm">
    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Program</h3>
        <div class="rounded-lg border border-gray-200 px-4 py-2 divide-y divide-gray-100">
            {!! $row('Program', $programLabel) !!}
            {!! $row('Modules', $modulesDisplay) !!}
            {!! $row('Headcount', $application->headcount) !!}
        </div>
    </section>

    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Company Information</h3>
        <div class="rounded-lg border border-gray-200 px-4 py-2 divide-y divide-gray-100">
            {!! $row('Company Name', $application->company_name) !!}
            {!! $row('Address', $application->address) !!}
            {!! $row('State', $application->state) !!}
            {!! $row('Postcode', $application->postcode) !!}
            {!! $row('Country', $application->country) !!}
            {!! $row('Telephone', $application->telephone) !!}
            {!! $row('Company Website', $application->company_website) !!}
            {!! $row('Business Type', $businessTypeLabels[$application->business_type] ?? $application->business_type) !!}
            {!! $row('Industry', $application->industry) !!}
            {!! $row('Years in Business', $yearsLabels[$application->years_in_business] ?? $application->years_in_business) !!}
        </div>
    </section>

    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Contact Information</h3>
        <div class="rounded-lg border border-gray-200 px-4 py-2 divide-y divide-gray-100">
            {!! $row('First Name', $application->first_name) !!}
            {!! $row('Last Name', $application->last_name) !!}
            {!! $row('Designation', $application->designation) !!}
            {!! $row('Email', $application->email) !!}
            {!! $row('Mobile Phone', $application->mobile_phone) !!}
            {!! $row('Password', '••••••••') !!}
            {!! $row('Existing FingerTec Reseller', $application->existing_fingertec_reseller ? 'Yes' : 'No') !!}
        </div>
    </section>

    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Agreement &amp; Consent</h3>
        <div class="rounded-lg border border-gray-200 px-4 py-2 divide-y divide-gray-100">
            {!! $row('Permission to set up account', $application->consent_setup_permission ? 'Yes' : 'No') !!}
            {!! $row('Marketing opt-in', $application->consent_marketing ? 'Yes' : 'No') !!}
        </div>
    </section>

    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Submission</h3>
        <div class="rounded-lg border border-gray-200 px-4 py-2 divide-y divide-gray-100">
            {!! $row('Submitted At', optional($application->created_at)->format('Y-m-d H:i')) !!}
            {!! $row('Status', ucfirst($application->status)) !!}
        </div>
    </section>
</div>
