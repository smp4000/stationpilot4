<?php

namespace App\Filament\App\Pages;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MeineVertraege extends Page
{
    protected string $view = 'filament.app.pages.meine-vertraege';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Meine Verträge';

    protected static ?string $title = 'Meine Verträge';

    protected static ?string $slug = 'meine-vertraege';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isEmployee() ?? false;
    }

    public function getContracts(): Collection
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (!$employee) {
            return collect();
        }

        return EmployeeContract::where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
