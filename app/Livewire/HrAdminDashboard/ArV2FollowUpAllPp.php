<?php
namespace App\Livewire\HrAdminDashboard;

class ArV2FollowUpAllPp extends ArV2FollowUpBase
{
    protected string $renewalProgress = 'pending_payment';
    protected string $datePeriod = 'all';

    public function render() { return view('livewire.hr-admin-dashboard.ar-v2-follow-up-all-pp'); }
}
