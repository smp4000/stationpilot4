<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\DocumentTemplateResource\Pages;
use App\Models\DocumentTemplate;
use App\Services\PlaceholderRegistry;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DocumentTemplateResource extends Resource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-document-duplicate';
    protected static \UnitEnum|string|null   $navigationGroup = 'Einstellungen';
    protected static ?string $navigationLabel = 'Dokumentvorlagen';
    protected static ?int    $navigationSort  = 85;

    protected static ?string $modelLabel       = 'Dokumentvorlage';
    protected static ?string $pluralModelLabel = 'Dokumentvorlagen';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('partner.document_templates.list') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('partner.document_templates.create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.document_templates.edit') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('partner.document_templates.delete') ?? false;
    }

    // ─── Form ────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(3)->schema([

                // ── Left: Typ & Name ─────────────────────────────────────
                Section::make('Vorlage')->columnSpan(2)->schema([

                    Grid::make(3)->schema([
                        Select::make('document_type')
                            ->label('Dokumenttyp')
                            ->options(PlaceholderRegistry::types())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('sub_type', null))
                            ->helperText('Bestimmt welche Platzhalter verfügbar sind.'),

                        Select::make('sub_type')
                            ->label('Untertyp')
                            ->options(fn (Get $get): array => PlaceholderRegistry::subTypes($get('document_type') ?? ''))
                            ->visible(fn (Get $get): bool => count(PlaceholderRegistry::subTypes($get('document_type') ?? '')) > 0)
                            ->live()
                            ->helperText('z.B. DSGVO-Einwilligung, Betriebsordnung, Schlüsselübergabe …'),

                        TextInput::make('name')
                            ->label('Name der Vorlage')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Abmahnung Standard'),
                    ]),

                    Textarea::make('description')
                        ->label('Beschreibung (optional)')
                        ->rows(2)
                        ->placeholder('Kurze Beschreibung für wen oder wann diese Vorlage verwendet wird.'),

                    RichEditor::make('body')
                        ->label('Vertragstext / Dokumentinhalt')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'orderedList', 'bulletList', 'undo', 'redo'])
                        ->extraInputAttributes(['style' => 'min-height:500px;'])
                        ->helperText('Platzhalter als {{name}} einfügen — werden beim PDF-Erstellen automatisch ersetzt.')
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->label('Vorlage aktiv')
                            ->default(true),

                        Toggle::make('requires_signature')
                            ->label('Mitarbeiter-Unterschrift erforderlich')
                            ->helperText('Beim Onboarding-Versand wird dieses Dokument mit Signing-Link gesendet.')
                            ->default(false),
                    ]),

                ])->columns(1),

                // ── Right: Eigene Platzhalter + Referenz ─────────────────
                Grid::make(1)->columnSpan(1)->schema([

                    Section::make('Eigene Platzhalter')
                        ->description('Definiere eigene {{schluessel}} — werden automatisch ersetzt.')
                        ->collapsible()
                        ->schema([
                            Repeater::make('custom_placeholders')
                                ->label('')
                                ->schema([
                                    TextInput::make('key')
                                        ->label('Platzhalter-Name')
                                        ->placeholder('z.B. firmen_website')
                                        ->helperText('Ohne {{ }} — wird zu {{firmen_website}}')
                                        ->required(),
                                    TextInput::make('label')
                                        ->label('Beschreibung')
                                        ->placeholder('z.B. Firmenwebsite'),
                                    TextInput::make('value')
                                        ->label('Wert')
                                        ->placeholder('z.B. www.meine-tankstelle.de'),
                                ])
                                ->columns(1)
                                ->addActionLabel('Platzhalter hinzufügen')
                                ->defaultItems(0),
                        ]),

                    Section::make('Verfügbare Platzhalter')
                        ->description('Kopiere einen Platzhalter direkt in den Text.')
                        ->collapsible()
                        ->collapsed(false)
                        ->schema([
                            Placeholder::make('placeholder_table')
                                ->label('')
                                ->content(function (Get $get): HtmlString {
                                    $type = $get('document_type');
                                    if (!$type) {
                                        return new HtmlString(
                                            '<p style="color:#94a3b8;font-size:13px;padding:8px 0;">Bitte zuerst Dokumenttyp wählen.</p>'
                                        );
                                    }

                                    $all = PlaceholderRegistry::placeholders($type);
                                    if (empty($all)) {
                                        return new HtmlString('<p style="color:#94a3b8;font-size:13px;">Keine Platzhalter verfügbar.</p>');
                                    }

                                    $rows = '';
                                    foreach ($all as $key => $desc) {
                                        $rows .= '<tr>'
                                            . '<td style="padding:4px 6px;font-family:monospace;font-size:11px;color:#2563eb;white-space:nowrap;">{{' . $key . '}}</td>'
                                            . '<td style="padding:4px 6px;font-size:11px;color:#475569;">' . $desc . '</td>'
                                            . '</tr>';
                                    }

                                    return new HtmlString(
                                        '<div style="max-height:400px;overflow-y:auto;">'
                                        . '<table style="width:100%;border-collapse:collapse;">'
                                        . '<thead><tr>'
                                        . '<th style="text-align:left;padding:4px 6px;font-size:11px;border-bottom:2px solid #e2e8f0;color:#64748b;">Platzhalter</th>'
                                        . '<th style="text-align:left;padding:4px 6px;font-size:11px;border-bottom:2px solid #e2e8f0;color:#64748b;">Beschreibung</th>'
                                        . '</tr></thead>'
                                        . '<tbody>' . $rows . '</tbody>'
                                        . '</table></div>'
                                    );
                                }),
                        ]),

                ]),

            ]),

        ]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $typeColors = [
            'mitarbeiter'    => 'info',
            'arbeitsvertrag' => 'success',
            'schluessel'     => 'warning',
            'tankstelle'     => 'danger',
        ];
        $typeLabels = PlaceholderRegistry::types();

        return $table
            ->query(
                DocumentTemplate::query()
                    ->where('tenant_id', session('tenant_id'))
                    ->orderBy('document_type')
                    ->orderByDesc('is_default')
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Vorlage')
                    ->searchable()
                    ->description(fn (DocumentTemplate $r): string => $r->is_default ? '★ Standard' : '')
                    ->weight(fn (DocumentTemplate $r) => $r->is_default ? 'bold' : 'normal'),

                TextColumn::make('document_type')
                    ->label('Dokumenttyp')
                    ->badge()
                    ->color(fn (string $state): string => $typeColors[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => $typeLabels[$state] ?? $state),

                TextColumn::make('sub_type')
                    ->label('Untertyp')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'unbefristet' => 'Unbefristet',
                        'befristet'   => 'Befristet',
                        'minijob'     => 'Minijob',
                        default       => $state ?? '—',
                    })
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                IconColumn::make('requires_signature')
                    ->label('Unterschrift')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('updated_at')
                    ->label('Zuletzt geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()->label('Bearbeiten'),

                Action::make('set_default')
                    ->label('Als Standard')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (DocumentTemplate $r): bool => !$r->is_default)
                    ->action(function (DocumentTemplate $record): void {
                        // Clear existing default for same type+sub_type
                        DocumentTemplate::where('tenant_id', $record->tenant_id)
                            ->where('document_type', $record->document_type)
                            ->where('sub_type', $record->sub_type)
                            ->where('id', '!=', $record->id)
                            ->update(['is_default' => false]);

                        $record->update(['is_default' => true]);

                        Notification::make()->title('Als Standard gesetzt.')->success()->send();
                    }),

                Action::make('reset_body')
                    ->label('Standard wiederherstellen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Vorlage auf Standard zurücksetzen?')
                    ->modalDescription('Der aktuelle Text wird überschrieben. Diese Aktion kann nicht rückgängig gemacht werden.')
                    ->visible(fn (DocumentTemplate $r): bool => $r->document_type === 'arbeitsvertrag' && $r->sub_type !== null)
                    ->action(function (DocumentTemplate $record): void {
                        $record->update([
                            'body' => DocumentTemplate::getDefaultBody($record->document_type, $record->sub_type),
                        ]);
                        Notification::make()->title('Vorlage zurückgesetzt.')->success()->send();
                    }),

                Action::make('delete')
                    ->label('Löschen')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (DocumentTemplate $r): bool => !$r->is_default)
                    ->action(function (DocumentTemplate $record): void {
                        $record->delete();
                        Notification::make()->title('Vorlage gelöscht.')->success()->send();
                    }),
            ])
            ->paginated(false);
    }

    // ─── Pages ───────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocumentTemplates::route('/'),
            'create' => Pages\CreateDocumentTemplate::route('/create'),
            'edit'   => Pages\EditDocumentTemplate::route('/{record}/edit'),
        ];
    }
}
