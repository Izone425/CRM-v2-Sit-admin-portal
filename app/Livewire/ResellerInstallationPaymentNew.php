<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerInstallationPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResellerInstallationPaymentNew extends Component
{
    public $search = '';
    public $countOnly = false;
    public $showDetailModal = false;
    public $selectedPayment = null;

    protected $listeners = [
        'installation-payment-updated' => '$refresh'
    ];

    public function mount($countOnly = false)
    {
        $this->countOnly = $countOnly;
    }

    public function getPaymentsProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerInstallationPayment::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('installation_address', 'like', '%' . $this->search . '%');
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

        return ResellerInstallationPayment::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'new')
            ->count();
    }

    public function openDetailModal($paymentId)
    {
        $this->selectedPayment = ResellerInstallationPayment::find($paymentId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedPayment = null;
    }

    public function render()
    {
        if ($this->countOnly) {
            return view('livewire.reseller-installation-payment-new-count', [
                'count' => $this->newCount
            ]);
        }

        return view('livewire.reseller-installation-payment-new', [
            'payments' => $this->payments
        ]);
    }
}
