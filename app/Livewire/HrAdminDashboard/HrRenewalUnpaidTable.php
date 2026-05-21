<?php

namespace App\Livewire\HrAdminDashboard;

class HrRenewalUnpaidTable extends HrRenewalBaseTable
{
    protected function getPaymentFilter(): ?string
    {
        return 'unpaid';
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-renewal-table');
    }
}
