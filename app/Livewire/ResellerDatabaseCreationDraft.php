<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Illuminate\Support\Facades\Auth;

class ResellerDatabaseCreationDraft extends Component
{
    public $search = '';
    public $showDetailModal = false;
    public $selectedRequest = null;
    public $showResellerRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = [
        'database-creation-updated' => '$refresh'
    ];

    public function updatedSearch()
    {
        // Search updated
    }

    public function openDetailModal($requestId)
    {
        $this->selectedRequest = ResellerDatabaseCreation::find($requestId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedRequest = null;
    }

    public function resubmitRequest($requestId)
    {
        $request = ResellerDatabaseCreation::find($requestId);
        if ($request && $request->reseller_id === Auth::guard('reseller')->user()->reseller_id) {
            $this->dispatch('open-database-creation-modal-with-data', [
                'companyName' => $request->company_name,
                'ssmNumber' => $request->ssm_number,
                'taxIdentificationNumber' => $request->tax_identification_number,
                'picName' => $request->pic_name,
                'picPhone' => $request->pic_phone,
                'masterLoginEmail' => $request->master_login_email,
                'modules' => $request->modules,
                'headcount' => $request->headcount,
                'resellerRemark' => $request->reseller_remark,
                'draftId' => $request->id,
            ]);
        }
    }

    public function getRequestsProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerDatabaseCreation::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'draft');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('pic_name', 'like', '%' . $this->search . '%')
                  ->orWhere('master_login_email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.reseller-database-creation-draft', [
            'requests' => $this->requests
        ]);
    }
}
