<?php

namespace App\Filament\App\Resources\EmployeeContractResource\Pages;

use App\Filament\App\Resources\EmployeeContractResource;
use App\Models\DocumentTemplate;
use App\Services\PlaceholderRegistry;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Station;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateEmployeeContract extends CreateRecord
{
    protected static string $resource = EmployeeContractResource::class;

    public function mount(): void
    {
        parent::mount();

        $employeeId = (int) request('employee_id');
        if (!$employeeId) {
            return;
        }

        $employee = Employee::with('station')->find($employeeId);
        $station  = $employee?->station ?? Station::where('tenant_id', session('tenant_id'))->first();

        $this->form->fill([
            'employee_id'      => $employeeId,
            'contract_type'    => 'unbefristet',
            'employment_type'  => $employee?->employment_type ?? 'vollzeit',
            'employment_start' => $employee?->employment_start?->format('Y-m-d'),
            'employment_end'   => $employee?->employment_end?->format('Y-m-d'),
            'pension_insurance_waiver' => false,

            'station_id'       => $station?->id,
            'employer_name'    => trim(($station?->contact_first_name ?? '') . ' ' . ($station?->contact_last_name ?? '')),
            'employer_company' => $station?->name ?? '',
            'employer_street'  => ($station?->street ?? '') . ($station?->house_number && !str_contains($station->street ?? '', $station->house_number) ? ' ' . $station->house_number : ''),
            'employer_zip'     => $station?->zip ?? '',
            'employer_city'    => $station?->city ?? '',
            'signing_location' => $station?->city ?? '',
            'work_location'    => $station ? ($station->name . ', ' . $station->street . ', ' . $station->zip . ' ' . $station->city) : '',

            'job_title'         => $employee?->job_title ?? 'Verkäufer/in im Einzelhandel / Kassier/in (Tankstelle)',
            'weekly_hours'      => $employee?->weekly_hours ?? 40,
            'wage_type'         => 'hourly',
            'wage_amount'       => $employee?->wage_amount ?? '',
            'wage_in_words'     => '',
            'overtime_included' => true,
            'special_payments'  => [],

            'probation_months' => 6,
            'vacation_days'    => $employee?->vacation_days ?? 24,
            'notice_period'    => 'gesetzlich',

            'clauses'                => ['confidentiality', 'side_jobs', 'overtime', 'edv'],
            'custom_clause'          => '',
            'job_description_type'   => 'standard',
            'job_description_custom' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->components([
                Hidden::make('employee_id'),

                Wizard::make([

                    // ── Step 1: Vertragsart ────────────────────────────────
                    Step::make('Vertragsart')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('contract_type')
                                    ->label('Vertragsart')
                                    ->options([
                                        'unbefristet' => 'Unbefristet',
                                        'befristet'   => 'Befristet',
                                        'minijob'     => 'Minijob / Geringfügig',
                                    ])
                                    ->required()
                                    ->live(),
                                Select::make('employment_type')
                                    ->label('Beschäftigungsart')
                                    ->options([
                                        'vollzeit'    => 'Vollzeit',
                                        'teilzeit'    => 'Teilzeit',
                                        'minijob'     => 'Minijob',
                                        'kurzfristig' => 'Kurzfristig',
                                        'azubi'       => 'Azubi',
                                    ]),
                            ]),
                            Grid::make(2)->schema([
                                DatePicker::make('employment_start')
                                    ->label('Arbeitsbeginn')
                                    ->required(),
                                DatePicker::make('employment_end')
                                    ->label('Befristet bis')
                                    ->visible(fn (Get $get): bool => $get('contract_type') === 'befristet')
                                    ->required(fn (Get $get): bool => $get('contract_type') === 'befristet'),
                            ]),
                            Toggle::make('pension_insurance_waiver')
                                ->label('Befreiung von der Rentenversicherungspflicht (§ 6 SGB VI) beantragt')
                                ->visible(fn (Get $get): bool => $get('contract_type') === 'minijob'),
                        ]),

                    // ── Step 2: Arbeitgeber & Arbeitsort ──────────────────
                    Step::make('Arbeitgeber')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Select::make('station_id')
                                ->label('Station')
                                ->options(fn (): array => \App\Models\Station::where('tenant_id', session('tenant_id'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray())
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set): void {
                                    if (!$state) return;
                                    $station = \App\Models\Station::find($state);
                                    if (!$station) return;
                                    $set('employer_name',    trim(($station->contact_first_name ?? '') . ' ' . ($station->contact_last_name ?? '')));
                                    $set('employer_company', $station->name);
                                    $set('employer_street',  $station->street ?? '');
                                    $set('employer_zip',     $station->zip ?? '');
                                    $set('employer_city',    $station->city ?? '');
                                    $set('signing_location', $station->city ?? '');
                                    $set('work_location',    $station->name . ', ' . $station->street . ', ' . $station->zip . ' ' . $station->city);
                                })
                                ->helperText('Station wählen → Felder werden automatisch befüllt'),
                            Grid::make(2)->schema([
                                TextInput::make('employer_name')
                                    ->label('Name des Arbeitgebers (Person)')
                                    ->required(),
                                TextInput::make('employer_company')
                                    ->label('Firmenname / Betrieb')
                                    ->required(),
                            ]),
                            Grid::make(3)->schema([
                                TextInput::make('employer_street')
                                    ->label('Straße & Hausnummer')
                                    ->required(),
                                TextInput::make('employer_zip')
                                    ->label('PLZ')
                                    ->required(),
                                TextInput::make('employer_city')
                                    ->label('Ort')
                                    ->required(),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('work_location')
                                    ->label('Arbeitsort (für Vertrag)')
                                    ->required(),
                                TextInput::make('signing_location')
                                    ->label('Unterzeichnungsort')
                                    ->required()
                                    ->helperText('Z.B. "Fulda" — erscheint bei „Fulda, den …"'),
                            ]),
                        ]),

                    // ── Step 3: Vergütung ──────────────────────────────────
                    Step::make('Vergütung')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('job_title')
                                    ->label('Stellenbezeichnung')
                                    ->required(),
                                TextInput::make('weekly_hours')
                                    ->label('Wochenstunden (Ø)')
                                    ->numeric()
                                    ->required(),
                            ]),
                            Grid::make(3)->schema([
                                Select::make('wage_type')
                                    ->label('Vergütungsart')
                                    ->options([
                                        'hourly'  => 'Stundenlohn',
                                        'monthly' => 'Monatslohn (fest)',
                                    ])
                                    ->required()
                                    ->live(),
                                TextInput::make('wage_amount')
                                    ->label(fn (Get $get): string => $get('wage_type') === 'hourly' ? 'Stundenlohn (brutto €)' : 'Monatslohn (brutto €)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->prefix('€')
                                    ->live(debounce: 600)
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if (filled($state) && is_numeric($state) && $state > 0) {
                                            $set('wage_in_words', static::numberToGermanWords((float) $state));
                                        }
                                    }),
                                TextInput::make('wage_in_words')
                                    ->label('Betrag in Worten')
                                    ->helperText('z.B. "vierzehn Euro"')
                                    ->visible(fn (Get $get): bool => $get('wage_type') === 'hourly'),
                            ]),
                            Toggle::make('overtime_included')
                                ->label('Überstunden werden zum regulären Stundensatz vergütet')
                                ->default(true),
                            CheckboxList::make('special_payments')
                                ->label('Sonderzahlungen (freiwillig, ohne Rechtsanspruch)')
                                ->options([
                                    'holiday_pay'      => 'Urlaubsgeld',
                                    'christmas_pay'    => 'Weihnachtsgeld',
                                    'bonus'            => 'Prämien',
                                    'thirteenth_month' => '13. Monatsgehalt',
                                ])
                                ->columns(2),
                        ]),

                    // ── Step 4: Konditionen ────────────────────────────────
                    Step::make('Konditionen')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('probation_months')
                                    ->label('Probezeit')
                                    ->options([
                                        0 => 'Keine Probezeit',
                                        1 => '1 Monat',
                                        2 => '2 Monate',
                                        3 => '3 Monate',
                                        4 => '4 Monate',
                                        5 => '5 Monate',
                                        6 => '6 Monate',
                                    ])
                                    ->default(6)
                                    ->required(),
                                TextInput::make('vacation_days')
                                    ->label('Urlaubsanspruch (Arbeitstage/Jahr)')
                                    ->numeric()
                                    ->required(),
                            ]),
                            Select::make('notice_period')
                                ->label('Kündigungsfristen nach Probezeit')
                                ->options([
                                    'gesetzlich' => 'Gesetzliche Kündigungsfristen (§ 622 BGB)',
                                    '4_wochen'   => '4 Wochen zum 15. oder Monatsende',
                                    '1_monat'    => '1 Monat zum Monatsende',
                                ])
                                ->default('gesetzlich'),
                        ]),

                    // ── Step 5: Klauseln & Stellenbeschreibung ─────────────
                    Step::make('Klauseln')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            CheckboxList::make('clauses')
                                ->label('Vertragsklauseln')
                                ->options([
                                    'confidentiality' => '§ Verschwiegenheitspflicht',
                                    'side_jobs'       => '§ Nebentätigkeit (nur mit Zustimmung)',
                                    'non_compete'     => '§ Wettbewerbsverbot',
                                    'overtime'        => '§ Überstunden-Pflicht bei betrieblichem Bedarf',
                                    'edv'             => '§ EDV-/Internetnutzung (nur dienstlich)',
                                    'vehicle'         => '§ Kfz-Nutzung des Arbeitgebers',
                                ])
                                ->columns(2)
                                ->default(['confidentiality', 'side_jobs', 'overtime', 'edv']),
                            Textarea::make('custom_clause')
                                ->label('Zusatzvereinbarung (Freitext, optional)')
                                ->rows(3)
                                ->placeholder('Individuelle Klausel …'),
                            Select::make('job_description_type')
                                ->label('Stellenbeschreibung (Anlage 1)')
                                ->options([
                                    'none'     => 'Keine Anlage 1',
                                    'standard' => 'Standard Tankstelle (vorgefertigt)',
                                    'custom'   => 'Eigener Text',
                                ])
                                ->default('standard')
                                ->live(),
                            Textarea::make('job_description_custom')
                                ->label('Eigene Stellenbeschreibung')
                                ->rows(8)
                                ->visible(fn (Get $get): bool => $get('job_description_type') === 'custom'),
                        ]),

                ])
                ->submitAction($this->getSubmitFormAction())
                ->cancelAction($this->getCancelFormAction())
                ->alpineSubmitHandler('$wire.create()')
                ->contained(false)
                ->columnSpanFull(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Employer-Felder auf Toplevel heben
        return [
            'tenant_id'        => session('tenant_id'),
            'employee_id'      => $data['employee_id'],
            'created_by'       => auth()->id(),
            'contract_type'    => $data['contract_type'],
            'status'           => 'draft',
            'employer_name'    => $data['employer_name'],
            'employer_company' => $data['employer_company'],
            'employer_street'  => $data['employer_street'],
            'employer_zip'     => $data['employer_zip'],
            'employer_city'    => $data['employer_city'],
            'signing_location' => $data['signing_location'],
            'contract_data'    => $data, // Alle Felder als JSON
        ];
    }

    protected function afterCreate(): void
    {
        $contract = $this->getRecord();
        $employee = Employee::find($contract->employee_id);
        $this->generatePdf($contract, $employee);
    }

    public function generatePdf(EmployeeContract $contract, Employee $employee): void
    {
        $template = DocumentTemplate::forTenant($contract->tenant_id, 'arbeitsvertrag', $contract->contract_type);
        $bodyHtml = $template->render(PlaceholderRegistry::fromContract($contract));

        $pdf  = Pdf::loadView('pdf.arbeitsvertrag', compact('contract', 'employee', 'bodyHtml'))
                    ->setPaper('a4', 'portrait');
        $path = 'contracts/' . $contract->id . '_' . Str::slug($employee->last_name . '_' . $employee->first_name) . '.pdf';
        Storage::disk('local')->put($path, $pdf->output());
        $contract->update(['pdf_path' => $path]);
    }

    protected function getRedirectUrl(): string
    {
        return EmployeeContractResource::getUrl('view', ['record' => $this->getRecord()->id]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Arbeitsvertrag erstellt & PDF generiert';
    }

    public static function numberToGermanWords(float $amount): string
    {
        $euros = (int) floor($amount);
        $cents = (int) round(($amount - $euros) * 100);

        $result = ucfirst(static::intToGermanWords($euros)) . ' Euro';
        if ($cents > 0) {
            $result .= ' und ' . static::intToGermanWords($cents) . ' Cent';
        }

        return $result;
    }

    private static function intToGermanWords(int $n): string
    {
        if ($n === 0) return 'null';
        if ($n < 0)  return 'minus ' . static::intToGermanWords(-$n);

        $ones = [
            '', 'ein', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun',
            'zehn', 'elf', 'zwölf', 'dreizehn', 'vierzehn', 'fünfzehn', 'sechzehn',
            'siebzehn', 'achtzehn', 'neunzehn',
        ];
        $tens = [
            '', '', 'zwanzig', 'dreißig', 'vierzig', 'fünfzig',
            'sechzig', 'siebzig', 'achtzig', 'neunzig',
        ];

        if ($n < 20) {
            return $ones[$n];
        }

        if ($n < 100) {
            $unit = $n % 10;
            $ten  = intdiv($n, 10);
            return $unit > 0
                ? $ones[$unit] . 'und' . $tens[$ten]
                : $tens[$ten];
        }

        if ($n < 1000) {
            $h    = intdiv($n, 100);
            $rest = $n % 100;
            return $ones[$h] . 'hundert' . ($rest > 0 ? static::intToGermanWords($rest) : '');
        }

        if ($n < 10000) {
            $k    = intdiv($n, 1000);
            $rest = $n % 1000;
            return static::intToGermanWords($k) . 'tausend' . ($rest > 0 ? static::intToGermanWords($rest) : '');
        }

        return (string) $n;
    }
}
