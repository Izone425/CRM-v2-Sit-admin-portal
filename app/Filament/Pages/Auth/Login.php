<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament.layouts.auth-split';

    public function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
