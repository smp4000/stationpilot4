<?php

namespace App\Filament\App\Widgets;

use App\Models\Employee;
use Filament\Widgets\Widget;

/**
 * Willkommens-Widget für Mitarbeiter im App-Panel.
 * Wird nur für User vom Typ 'employee' angezeigt.
 */
class MitarbeiterWillkommenWidget extends Widget
{
    protected string $view = 'filament.app.widgets.mitarbeiter-willkommen';

    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    public function getEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }
}
