<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Illuminate\Support\Facades\Auth;

class ResellerDatabaseCreationButton extends Component
{
    public $showModal = false;
    public $companyName = '';
    public $ssmNumber = '';
    public $taxIdentificationNumber = '';
    public $picName = '';
    public $picPhone = '';
    public $masterLoginEmail = '';
    public $modules = [];
    public $headcount = '';
    public $resellerRemark = '';
    public $draftId = null;

    protected $listeners = [
        'open-database-creation-modal-with-data' => 'openModalWithData'
    ];

    protected $rules = [
        'companyName' => 'required|string|max:255',
        'ssmNumber' => 'required|string|max:255',
        'taxIdentificationNumber' => 'required|string|max:255',
        'picName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
        'picPhone' => 'required|numeric',
        'masterLoginEmail' => 'required|email|max:255',
        'modules' => 'required|array|min:1',
        'headcount' => 'required|numeric|min:1',
        'resellerRemark' => 'nullable|string',
    ];

    protected $messages = [
        'picName.regex' => 'PIC Name must contain only letters and spaces.',
        'modules.required' => 'Please select at least one module.',
        'modules.min' => 'Please select at least one module.',
    ];

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openModalWithData($data)
    {
        // Extract data from event (handle both direct array and event detail format)
        if (is_array($data) && isset($data[0])) {
            $data = $data[0];
        }

        $this->companyName = $data['companyName'] ?? '';
        $this->ssmNumber = $data['ssmNumber'] ?? '';
        $this->taxIdentificationNumber = $data['taxIdentificationNumber'] ?? '';
        $this->picName = $data['picName'] ?? '';
        $this->picPhone = $data['picPhone'] ?? '';
        $this->masterLoginEmail = $data['masterLoginEmail'] ?? '';
        $this->modules = $data['modules'] ?? [];
        $this->headcount = $data['headcount'] ?? '';
        $this->resellerRemark = $data['resellerRemark'] ?? '';
        $this->draftId = $data['draftId'] ?? null;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->companyName = '';
        $this->ssmNumber = '';
        $this->taxIdentificationNumber = '';
        $this->picName = '';
        $this->picPhone = '';
        $this->masterLoginEmail = '';
        $this->modules = [];
        $this->headcount = '';
        $this->resellerRemark = '';
        $this->draftId = null;
        $this->resetValidation();
    }

    public function submit()
    {
        $this->validate();

        $reseller = Auth::guard('reseller')->user();

        $data = [
            'reseller_id' => $reseller->reseller_id,
            'reseller_name' => $reseller->name,
            'reseller_company_name' => $reseller->company_name,
            'company_name' => strtoupper($this->companyName),
            'ssm_number' => $this->ssmNumber ? strtoupper($this->ssmNumber) : null,
            'tax_identification_number' => $this->taxIdentificationNumber ? strtoupper($this->taxIdentificationNumber) : null,
            'pic_name' => strtoupper($this->picName),
            'pic_phone' => $this->picPhone,
            'master_login_email' => strtolower($this->masterLoginEmail),
            'modules' => $this->modules,
            'headcount' => $this->headcount,
            'reseller_remark' => $this->resellerRemark ? strtoupper($this->resellerRemark) : null,
            'status' => 'new',
        ];

        if ($this->draftId) {
            // Update existing draft
            $draft = ResellerDatabaseCreation::find($this->draftId);
            if ($draft && $draft->reseller_id === $reseller->reseller_id) {
                $draft->update($data);
            }
        } else {
            // Create new request
            ResellerDatabaseCreation::create($data);
        }

        $this->closeModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->draftId ? 'Database creation request resubmitted successfully!' : 'Database creation request submitted successfully!'
        ]);

        $this->dispatch('database-creation-updated');
    }

    public function render()
    {
        return view('livewire.reseller-database-creation-button');
    }
}
