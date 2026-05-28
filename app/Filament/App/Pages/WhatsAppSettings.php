<?php

namespace App\Filament\App\Pages;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * WhatsApp-Widget Einstellungen für Partner.
 * Konfiguriert die Ansprechpartner die im Floating-Widget angezeigt werden.
 * Gespeichert unter tenants.settings['whatsapp'].
 */
class WhatsAppSettings extends Page
{
    protected string $view = 'filament.app.pages.whatsapp-settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static \UnitEnum|string|null   $navigationGroup = 'Einstellungen';
    protected static ?string $navigationLabel = 'WhatsApp-Widget';
    protected static ?string $title           = 'WhatsApp-Widget';
    protected static ?string $slug            = 'whatsapp-settings';
    protected static ?int    $navigationSort  = 92;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.settings.edit') ?? false;
    }

    // ── Livewire state ───────────────────────────────────────────────────────

    public array $data = [];

    // ── Mount ────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $tenant   = $this->getTenant();
        $settings = $tenant?->settings ?? [];
        $whatsapp = $settings['whatsapp'] ?? [];

        $this->form->fill([
            'enabled' => (bool) ($whatsapp['enabled'] ?? false),
            'agents'  => $whatsapp['agents']  ?? [],
        ]);
    }

    // ── Schema ───────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([

                Section::make('Widget aktivieren')
                    ->description('Das WhatsApp-Widget erscheint als grüner Button unten rechts auf jeder Seite.')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('WhatsApp-Widget anzeigen')
                            ->helperText('Wenn aktiv, sehen alle Benutzer dieses Panels den Widget-Button.')
                            ->onColor('success')
                            ->offColor('gray'),
                    ]),

                Section::make('Ansprechpartner')
                    ->description('Lege die Kontakte fest, die im Widget-Popup auswählbar sind. Die Reihenfolge entspricht der Anzeige.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Repeater::make('agents')
                            ->label('')
                            ->schema([

                                TextInput::make('name')
                                    ->label('Name')
                                    ->placeholder('z.B. Max Mustermann')
                                    ->required()
                                    ->maxLength(100),

                                TextInput::make('phone')
                                    ->label('Telefonnummer (international)')
                                    ->placeholder('z.B. +4915112345678')
                                    ->helperText('Mit Ländervorwahl, ohne Leerzeichen oder Bindestriche.')
                                    ->required()
                                    ->tel()
                                    ->maxLength(30),

                                TextInput::make('description')
                                    ->label('Bezeichnung / Funktion')
                                    ->placeholder('z.B. Stationsleiter, Buchhaltung, Support …')
                                    ->maxLength(100),

                                Textarea::make('message')
                                    ->label('Vorausgefüllte Nachricht')
                                    ->placeholder('Hallo, ich habe eine Frage zu meiner Tankstelle.')
                                    ->helperText('Wird beim Öffnen des Chats automatisch in das Textfeld eingefügt.')
                                    ->rows(2)
                                    ->maxLength(500),

                            ])
                            ->columns(2)
                            ->addActionLabel('Kontakt hinzufügen')
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ]),

                Actions::make([
                    Action::make('save')
                        ->label('Einstellungen speichern')
                        ->icon('heroicon-o-check')
                        ->color('primary')
                        ->action('save'),
                ]),

            ]);
    }

    // ── Action-Handler ───────────────────────────────────────────────────────

    public function save(): void
    {
        $data = $this->form->getState();

        $tenant = $this->getTenant();
        if (! $tenant) {
            Notification::make()->title('Mandant nicht gefunden.')->danger()->send();
            return;
        }

        $settings             = $tenant->settings ?? [];
        $settings['whatsapp'] = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'agents'  => array_values($data['agents'] ?? []),
        ];

        $tenant->update(['settings' => $settings]);

        Notification::make()->title('WhatsApp-Einstellungen gespeichert.')->success()->send();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function getTenant(): ?Tenant
    {
        $tenantId = (int) session('tenant_id', 0);
        if (! $tenantId) {
            return null;
        }
        return Tenant::find($tenantId);
    }
}
