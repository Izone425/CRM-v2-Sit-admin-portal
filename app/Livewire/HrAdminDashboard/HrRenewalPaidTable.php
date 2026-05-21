<?php

namespace App\Livewire\HrAdminDashboard;

class HrRenewalPaidTable extends HrRenewalBaseTable
{
    protected function getPaymentFilter(): ?string
    {
        return 'paid';
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-renewal-table');
    }
}
