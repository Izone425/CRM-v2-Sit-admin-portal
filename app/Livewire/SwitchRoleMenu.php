<?php

namespace App\Livewire;

use Livewire\Component;

class SwitchRoleMenu extends Component
{
    public ?string $activeRole = null;

    // Define which sidebar sections each switched role can see
    public static array $roleSections = [
        'qc' => ['handover', 'ticketing', 'system-portal', 'implementer', 'support', 'internal', 'external'],
        'implementer' => ['handover', 'ticketing', 'system-portal', 'implementer', 'internal', 'external'],
        'support' => ['handover', 'ticketing', 'system-portal', 'support', 'internal', 'external'],
        'pdt' => ['handover', 'ticketing', 'system-portal', 'implementer', 'support', 'internal', 'external'],
    ];

    // Define the default landing page for each role
    public static array $roleLandingPages = [
        'qc' => 'filament.admin.pages.qc-ticketing.dashboard',
        'implementer' => 'filament.admin.pages.future-enhancement',
        'support' => 'filament.admin.pages.future-enhancement',
        'pdt' => 'filament.admin.pages.pdt-ticketing.dashboard',
    ];

    // Per-user role visibility overrides. Users not listed here get the default set.
    public static array $userAllowedRoles = [
        24 => ['qc', 'pdt'],
        43 => ['qc', 'pdt'],
    ];

    public static array $defaultAllowedRoles = ['qc', 'implementer', 'support'];

    public function mount(): void
    {
        $this->activeRole = session('switched_role');
    }

    public static function allowedRolesFor(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }
        // role_id === 3 sees every role defined in $roleSections.
        $user = \App\Models\User::find($userId);
        if ($user && (int) $user->role_id === 3) {
            return array_keys(self::$roleSections);
        }
        return self::$userAllowedRoles[$userId] ?? self::$defaultAllowedRoles;
    }

    public function getAllowedRolesProperty(): array
    {
        return self::allowedRolesFor(auth()->id());
    }

    public function switchTo(string $role): void
    {
        if (!in_array($role, $this->allowedRoles, true)) {
            return;
        }

        if ($this->activeRole === $role) {
            // Clicking the same role again clears the switch (back to normal)
            session()->forget('switched_role');
            $this->activeRole = null;
            $this->redirect(route('filament.admin.pages.dashboard-form'));
            return;
        }

        session(['switched_role' => $role]);
        $this->activeRole = $role;

        $route = self::$roleLandingPages[$role] ?? 'filament.admin.pages.dashboard-form';
        $this->redirect(route($route));
    }

    public function render()
    {
        return view('livewire.switch-role-menu');
    }
}
