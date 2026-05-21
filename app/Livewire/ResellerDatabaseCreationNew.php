<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Illuminate\Support\Facades\Auth;

class ResellerDatabaseCreationNew extends Component
{
    public $search = '';
    public $countOnly = false;
    public $showDetailModal = false;
    public $selectedRequest = null;
    public $showResellerRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = [
        'database-creation-updated' => '$refresh'
    ];

    public function mount($countOnly = false)
    {
        $this->countOnly = $countOnly;
    }

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

    public function getRequestsProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerDatabaseCreation::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('pic_name', 'like', '%' . $this->search . '%')
                  ->orWhere('master_login_email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getNewCountProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return 0;
        }

        return ResellerDatabaseCreation::where('reseller_id', $reseller->reseller_id)
            ->whereIn('status', ['rejected', 'draft'])
            ->count();
    }

    public function render()
    {
        if ($this->countOnly) {
            return view('livewire.reseller-database-creation-new-count', [
                'count' => $this->newCount
            ]);
        }

        return view('livewire.reseller-database-creation-new', [
            'requests' => $this->requests
        ]);
    }
}
