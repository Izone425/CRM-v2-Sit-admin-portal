<?php
namespace App\Livewire\HrAdminDashboard;

class ArV2FollowUpOverduePp extends ArV2FollowUpBase
{
    protected string $renewalProgress = 'pending_payment';
    protected string $datePeriod = 'overdue';

    public function render() { return view('livewire.hr-admin-dashboard.ar-v2-follow-up-overdue-pp'); }
}
