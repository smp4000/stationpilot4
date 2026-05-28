<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmployeeContractResource\Pages;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class EmployeeContractResource extends Resource
{
    protected static ?string $model = EmployeeContract::class;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.contracts.list') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('partner.contracts.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.contracts.edit') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.contracts.delete') ?? false;
    }

    public static function shouldRegisterNavigation(): bool { return false; }

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup  = 'Personal';
    protected static ?int $navigationSort                     = 3;
    protected static ?string $label                           = 'Arbeitsvertrag';
    protected static ?string $pluralLabel                     = 'Arbeitsverträge';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Mitarbeiter')
                    ->getStateUsing(fn (EmployeeContract $r): string => $r->employee->fullName())
                    ->searchable(['employees.first_name', 'employees.last_name'])
                    ->sortable(),
                TextColumn::make('contract_type')
                    ->label('Vertragsart')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unbefristet' => 'success',
                        'befristet'   => 'warning',
                        'minijob'     => 'info',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'unbefristet' => 'Unbefristet',
                        'befristet'   => 'Befristet',
                        'minijob'     => 'Minijob',
                        default       => $state,
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'           => 'gray',
                        'sent'            => 'warning',
                        'employee_signed' => 'info',
                        'completed'       => 'success',
                        'cancelled'       => 'danger',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'           => 'Entwurf',
                        'sent'            => 'Versendet',
                        'employee_signed' => 'Unterschrieben',
                        'completed'       => 'Abgeschlossen',
                        'cancelled'       => 'Abgebrochen',
                        default           => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('employee_signed_at')
                    ->label('Unterschrieben am')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('contract_type')
                    ->label('Vertragsart')
                    ->options([
                        'unbefristet' => 'Unbefristet',
                        'befristet'   => 'Befristet',
                        'minijob'     => 'Minijob',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'           => 'Entwurf',
                        'sent'            => 'Versendet',
                        'employee_signed' => 'Unterschrieben',
                        'completed'       => 'Abgeschlossen',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('Anzeigen')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EmployeeContract $r): string => static::getUrl('view', ['record' => $r->id])),
                Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (EmployeeContract $r): string => route('pdf.contract.download', $r->id))
                    ->openUrlInNewTab()
                    ->visible(fn (EmployeeContract $r): bool => (bool) $r->pdf_path),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmployeeContracts::route('/'),
            'create' => Pages\CreateEmployeeContract::route('/create'),
            'view'   => Pages\ViewEmployeeContract::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', session('tenant_id'))
            ->with('employee');
    }
}
