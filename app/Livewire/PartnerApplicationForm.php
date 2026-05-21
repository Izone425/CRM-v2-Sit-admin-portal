<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\Industry;
use App\Models\PartnerApplication;
use App\Models\StateCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class PartnerApplicationForm extends Component
{
    // Program selection
    public string $partner_type = 'reseller';

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

    public function mount(): void
    {
        $this->countryOptions = $this->safePluck('countries', fn () => Country::query()->orderBy('name')->pluck('name', 'name'));
        $this->stateOptions = $this->safePluck('state_codes', fn () => StateCode::query()->orderBy('name')->pluck('name', 'name'));
        $this->industryOptions = $this->safePluck('industries', fn () => Industry::query()->where('is_active', true)->orderBy('name')->pluck('name', 'name'));
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
            'partner_type' => ['required', 'in:reseller,distributor'],

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

            'consent_setup_permission' => ['accepted'],
            'consent_marketing' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'consent_setup_permission.accepted' => 'You must confirm you have permission to set up this account.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();

        PartnerApplication::create([
            'partner_type' => $data['partner_type'],
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

        $this->reset([
            'company_name', 'address', 'state', 'postcode', 'country',
            'telephone', 'company_website', 'business_type', 'industry', 'years_in_business',
            'email', 'password', 'password_confirmation', 'mobile_phone',
            'first_name', 'last_name', 'designation', 'existing_fingertec_reseller',
            'consent_setup_permission', 'consent_marketing',
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.partner-application-form');
    }
}
