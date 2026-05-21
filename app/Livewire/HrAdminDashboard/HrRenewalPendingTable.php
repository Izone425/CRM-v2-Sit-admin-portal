<?php

namespace App\Livewire\HrAdminDashboard;

class HrRenewalPendingTable extends HrRenewalBaseTable
{
    protected function getPaymentFilter(): ?string
    {
        return 'pending';
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-renewal-table');
    }
}
