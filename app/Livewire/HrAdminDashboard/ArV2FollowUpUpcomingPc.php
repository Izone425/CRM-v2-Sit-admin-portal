<?php
namespace App\Livewire\HrAdminDashboard;

class ArV2FollowUpUpcomingPc extends ArV2FollowUpBase
{
    protected string $renewalProgress = 'pending_confirmation';
    protected string $datePeriod = 'upcoming';

    public function render() { return view('livewire.hr-admin-dashboard.ar-v2-follow-up-upcoming-pc'); }
}
