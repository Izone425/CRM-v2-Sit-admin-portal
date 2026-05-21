<?php
namespace App\Livewire\HrAdminDashboard;

class ArV2FollowUpOverduePc extends ArV2FollowUpBase
{
    protected string $renewalProgress = 'pending_confirmation';
    protected string $datePeriod = 'overdue';

    public function render() { return view('livewire.hr-admin-dashboard.ar-v2-follow-up-overdue-pc'); }
}
