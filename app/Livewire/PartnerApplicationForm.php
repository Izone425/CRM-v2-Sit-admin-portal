<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\Industry;
use App\Models\PartnerApplication;
use App\Models\StateCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class PartnerApplicationForm extends Component
{
    // Program selection
    public string $partner_type = 'reseller';

    // Module selection
    public array $categories = [];

    // Headcount
    public ?int $headcount = null;

    // Company Information
    public string $company_name = '';
    public string $address = '';
    public string $state = '';
    public string $postcode = '';
    public string $country = '';
    public string $telephone = '';
    public string $company_website = '';
    public string $business_type = '';
    public string $industry = '';
    public string $years_in_business = '';

    // Contact Information
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $mobile_phone = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $designation = '';
    public ?string $existing_fingertec_reseller = null;

    // Agreement
    public bool $consent_setup_permission = false;
    public bool $consent_marketing = false;

    // Dropdown sources (populated in mount)
    public Collection $countryOptions;
    public Collection $stateOptions;
    public Collection $industryOptions;

    public bool $submitted = false;

    public function mount(string $partnerType): void
    {
        $this->partner_type = $partnerType;

        $this->countryOptions = $this->safePluck('countries', fn () => Country::query()->orderBy('name')->pluck('name', 'name'));
        $this->stateOptions = $this->safePluck('state_codes', fn () => StateCode::query()->orderBy('name')->pluck('name', 'name'));
        $this->industryOptions = $this->safePluck('industries', fn () => Industry::query()->where('is_active', true)->orderBy('name')->pluck('name', 'name'));
    }

    public function getProgramLabelProperty(): string
    {
        return $this->partner_type === 'distributor' ? 'Distributor' : 'Reseller';
    }

    public function updatedEmail(): void
    {
        $this->validateOnly('email');
    }

    private function safePluck(string $table, \Closure $resolver): Collection
    {
        try {
            if (! Schema::hasTable($table)) {
                return collect();
            }
            return $resolver();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'string', 'max:50'],
            'company_website' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:sole_proprietorship,partnership,corporation'],
            'industry' => ['required', 'string', 'max:255'],
            'years_in_business' => ['required', 'in:1_3,4_5,6_10,more_than_10'],

            'email' => ['required', 'email', 'max:255', 'unique:partner_applications,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'mobile_phone' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'existing_fingertec_reseller' => ['required', 'in:0,1'],

            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['in:attendance,leave,claim,payroll'],

            'headcount' => ['required', 'integer', 'min:1', 'max:100'],

            'consent_setup_permission' => ['accepted'],
            'consent_marketing' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'consent_setup_permission.accepted' => 'You must confirm you have permission to set up this account.',
            'password.confirmed' => 'Password confirmation does not match.',
            'categories.required' => 'Please select at least one module.',
            'categories.min' => 'Please select at least one module.',
            'headcount.max' => 'Headcount must be 100 or less.',
            'headcount.min' => 'Headcount must be at least 1.',
        ];
    }

    public function submit(): void
    {
        Log::info('Partner application submit() entered', [
            'partner_type' => $this->partner_type,
            'email' => $this->email,
            'has_categories' => count($this->categories),
            'has_headcount' => $this->headcount,
        ]);

        $data = $this->validate();

        try {
            $application = PartnerApplication::create([
                'partner_type' => $this->partner_type,
                'categories' => $data['categories'],
                'headcount' => $data['headcount'],
                'company_name' => $data['company_name'],
                'address' => $data['address'],
                'state' => $data['state'],
                'postcode' => $data['postcode'],
                'country' => $data['country'],
                'telephone' => $data['telephone'],
                'company_website' => $data['company_website'],
                'business_type' => $data['business_type'],
                'industry' => $data['industry'],
                'years_in_business' => $data['years_in_business'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'mobile_phone' => $data['mobile_phone'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'designation' => $data['designation'],
                'existing_fingertec_reseller' => (bool) $data['existing_fingertec_reseller'],
                'consent_setup_permission' => true,
                'consent_marketing' => (bool) $this->consent_marketing,
                'status' => 'pending',
            ]);

            Log::info('Partner application created', [
                'id' => $application->id,
                'partner_type' => $application->partner_type,
                'email' => $application->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Partner application submit failed', [
                'partner_type' => $this->partner_type,
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);

            $this->addError('submit', 'Sorry, something went wrong saving your application. Please try again or contact us.');
            return;
        }

        $this->reset([
            'company_name', 'address', 'state', 'postcode', 'country',
            'telephone', 'company_website', 'business_type', 'industry', 'years_in_business',
            'email', 'password', 'password_confirmation', 'mobile_phone',
            'first_name', 'last_name', 'designation', 'existing_fingertec_reseller',
            'consent_setup_permission', 'consent_marketing',
            'categories', 'headcount',
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.partner-application-form')
            ->layoutData(['title' => 'Become a TimeTec ' . $this->programLabel . ' — TimeTec']);
    }
}
