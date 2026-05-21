<?php
namespace App\Livewire\HrAdminDashboard;

class ArV2FollowUpTodayPc extends ArV2FollowUpBase
{
    protected string $renewalProgress = 'pending_confirmation';
    protected string $datePeriod = 'today';

    public function render() { return view('livewire.hr-admin-dashboard.ar-v2-follow-up-today-pc'); }
}
