<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

/**
 * Überschreibt den Standard-AccountWidget:
 * Für Mitarbeiter ausgeblendet (die haben das MitarbeiterWillkommenWidget).
 */
class AccountWidget extends BaseAccountWidget
{
    public static function canView(): bool
    {
        return ! (auth()->user()?->isEmployee() ?? false);
    }
}
