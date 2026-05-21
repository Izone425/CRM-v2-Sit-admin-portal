<?php

namespace App\Livewire\HrAdminDashboard;

class HrRenewalAllTable extends HrRenewalBaseTable
{
    protected function getPaymentFilter(): ?string
    {
        return null; // No filter - show all
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-renewal-table');
    }
}
