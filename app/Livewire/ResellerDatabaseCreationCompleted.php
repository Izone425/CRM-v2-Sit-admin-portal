<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerDatabaseCreation;
use Illuminate\Support\Facades\Auth;

class ResellerDatabaseCreationCompleted extends Component
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

    public function getRequestsProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerDatabaseCreation::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed');

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
        return view('livewire.reseller-database-creation-completed', [
            'requests' => $this->requests
        ]);
    }
}
