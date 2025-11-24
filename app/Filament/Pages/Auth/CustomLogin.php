<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class CustomLogin extends BaseLogin
{
    public function getFooter(): string | Htmlable | View
    {
        return view('filament.pages.auth.google-button');
    }
}
