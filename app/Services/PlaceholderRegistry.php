<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\KeyHandover;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PlaceholderRegistry
{
    // ─── Supported document types ────────────────────────────────────────────

    public static function types(): array
    {
        return [
            'mitarbeiter'    => 'Mitarbeiter',
            'arbeitsvertrag' => 'Arbeitsvertrag',
            'schluessel'     => 'Schlüssel / Übergabe-Protokoll',
            'tankstelle'     => 'Tankstelle',
        ];
    }

    public static function subTypes(string $documentType): array
    {
        return match ($documentType) {
            'arbeitsvertrag' => [
                'unbefristet' => 'Unbefristet',
                'befristet'   => 'Befristet',
                'minijob'     => 'Minijob / Geringfügig',
            ],
            'mitarbeiter' => [
                'datenschutz'     => 'DSGVO-Einwilligung',
                'betriebsordnung' => 'Betriebsordnung',
                'nda'             => 'Geheimhaltungsvereinbarung',
            ],
            'schluessel' => [
                'uebergabe' => 'Schlüsselübergabe-Protokoll',
            ],
            default => [],
        };
    }

    // ─── Available placeholders per type (for UI reference) ─────────────────

    public static function placeholders(string $type): array
    {
        $employee = [
            'mitarbeiter_name'           => 'Vollständiger Name (Vor- + Nachname)',
            'mitarbeiter_vorname'        => 'Vorname',
            'mitarbeiter_nachname'       => 'Nachname',
            'mitarbeiter_adresse'        => 'Vollständige Adresse',
            'mitarbeiter_strasse'        => 'Straße + Hausnummer',
            'mitarbeiter_plz'            => 'PLZ',
            'mitarbeiter_ort'            => 'Ort',
            'mitarbeiter_geburtsdatum'   => 'Geburtsdatum',
            'mitarbeiter_email'          => 'E-Mail-Adresse',
            'mitarbeiter_telefon'        => 'Telefon',
            'mitarbeiter_stelle'         => 'Stellenbezeichnung',
            'mitarbeiter_eintrittsdatum' => 'Eintrittsdatum',
            'station_name'               => 'Stationsname (aktuelle Station)',
            'arbeitgeber_name'           => 'Name des Arbeitgebers',
            'arbeitgeber_firma'          => 'Firma / Betrieb',
            'arbeitgeber_adresse'        => 'Adresse Arbeitgeber',
            'unterschrift_ort'           => 'Unterschriftsort',
            'datum_heute'                => 'Heutiges Datum (z.B. 27. Mai 2026)',
        ];

        $contract = [
            'beginn_datum'            => 'Arbeitsbeginn',
            'ende_datum'              => 'Befristung bis (leer wenn unbefristet)',
            'wochenstunden'           => 'Wochenstunden',
            'urlaubstage'             => 'Urlaubstage pro Jahr',
            'stundenlohn'             => 'Stundenlohn brutto (z.B. 14,50 €)',
            'monatslohn'              => 'Monatslohn brutto (z.B. 2.200,00 €)',
            'stundenlohn_worten'      => 'Lohnbetrag in Worten',
            'probezeit_monate'        => 'Probezeit in Monaten',
            'arbeitsort'              => 'Arbeitsort',
            'job_titel'               => 'Stellenbezeichnung (aus Vertrag)',
            'vertragsart'             => 'Vertragsart (Unbefristet / Befristet / Minijob)',
            'vergutung_klausel'       => 'Vergütungsklausel (automatisch generiert)',
            'probezeit_klausel'       => 'Probezeitklausel (automatisch generiert)',
            'sonderzahlungen_klausel' => 'Sonderzahlungsklausel (automatisch generiert)',
        ];

        $key = [
            'schluessel_name'      => 'Bezeichnung des Schlüssels',
            'schluessel_typ'       => 'Typ / Kategorie',
            'ausgabe_datum'        => 'Ausgabedatum',
            'ausgabe_uhrzeit'      => 'Ausgabeuhrzeit',
            'rueckgabe_datum'      => 'Rückgabedatum (leer wenn noch nicht zurückgegeben)',
            'mitarbeiter_name'     => 'Vollständiger Name des Mitarbeiters',
            'mitarbeiter_vorname'  => 'Vorname des Mitarbeiters',
            'mitarbeiter_nachname' => 'Nachname des Mitarbeiters',
            'station_name'         => 'Stationsname',
            'datum_heute'          => 'Heutiges Datum',
        ];

        $station = [
            'station_name'     => 'Name der Tankstelle',
            'station_strasse'  => 'Straße + Hausnummer',
            'station_plz'      => 'PLZ',
            'station_ort'      => 'Ort',
            'station_adresse'  => 'Vollständige Adresse',
            'inhaber_name'     => 'Vollständiger Name des Inhabers',
            'inhaber_vorname'  => 'Vorname Inhaber',
            'inhaber_nachname' => 'Nachname Inhaber',
            'inhaber_firma'    => 'Firmenname',
            'station_telefon'  => 'Telefonnummer Station',
            'station_email'    => 'E-Mail Station',
            'datum_heute'      => 'Heutiges Datum',
        ];

        return match ($type) {
            'mitarbeiter'    => $employee,
            'arbeitsvertrag' => array_merge($employee, $contract),
            'schluessel'     => $key,
            'tankstelle'     => $station,
            default          => [],
        };
    }

    // ─── Resolve actual values from model ───────────────────────────────────

    public static function resolve(string $type, Model $model): array
    {
        return match ($type) {
            'mitarbeiter'    => static::fromEmployee($model),
            'arbeitsvertrag' => static::fromContract($model),
            'schluessel'     => static::fromKeyHandover($model),
            'tankstelle'     => static::fromStation($model),
            default          => [],
        };
    }

    // ─── Per-type resolvers ──────────────────────────────────────────────────

    public static function fromEmployee(Employee $e): array
    {
        $station = $e->station;
        $today   = Carbon::today()->format('d.m.Y');

        return [
            'mitarbeiter_name'           => trim($e->first_name . ' ' . $e->last_name),
            'mitarbeiter_vorname'        => $e->first_name ?? '',
            'mitarbeiter_nachname'       => $e->last_name ?? '',
            'mitarbeiter_adresse'        => trim(($e->address ?? '') . ' ' . ($e->house_number ?? '') . ', ' . ($e->zip ?? '') . ' ' . ($e->city ?? '')),
            'mitarbeiter_strasse'        => trim(($e->address ?? '') . ' ' . ($e->house_number ?? '')),
            'mitarbeiter_plz'            => $e->zip ?? '',
            'mitarbeiter_ort'            => $e->city ?? '',
            'mitarbeiter_geburtsdatum'   => $e->date_of_birth ? Carbon::parse($e->date_of_birth)->format('d.m.Y') : '',
            'mitarbeiter_email'          => $e->email ?? '',
            'mitarbeiter_telefon'        => $e->phone ?? $e->phone_mobile ?? '',
            'mitarbeiter_stelle'         => $e->job_title ?? '',
            'mitarbeiter_eintrittsdatum' => $e->employment_start ? Carbon::parse($e->employment_start)->format('d.m.Y') : '',
            'station_name'               => $station?->name ?? '',
            'arbeitgeber_name'           => trim(($station?->contact_first_name ?? '') . ' ' . ($station?->contact_last_name ?? '')),
            'arbeitgeber_firma'          => $station?->name ?? '',
            'arbeitgeber_adresse'        => trim(($station?->street ?? '') . ' ' . ($station?->house_number ?? '') . ', ' . ($station?->zip ?? '') . ' ' . ($station?->city ?? '')),
            'unterschrift_ort'           => $station?->city ?? '',
            'datum_heute'                => $today,
        ];
    }

    public static function fromContract(EmployeeContract $c): array
    {
        $employee = $c->employee;
        $d        = $c->contract_data ?? [];

        $base = static::fromEmployee($employee);

        // Override arbeitgeber from contract-level fields
        $base['arbeitgeber_name']    = $c->employer_name ?? $base['arbeitgeber_name'];
        $base['arbeitgeber_firma']   = $c->employer_company ?? $base['arbeitgeber_firma'];
        $base['arbeitgeber_adresse'] = trim(($c->employer_street ?? '') . ', ' . ($c->employer_zip ?? '') . ' ' . ($c->employer_city ?? ''));
        $base['unterschrift_ort']    = $c->signing_location ?? $base['unterschrift_ort'];

        $wageAmount    = (float) ($d['wage_amount'] ?? 0);
        $wageFormatted = number_format($wageAmount, 2, ',', '.');
        $wageType      = $d['wage_type'] ?? 'hourly';

        // Computed: Vergütungsklausel
        if ($wageType === 'hourly') {
            $vergutungKlausel = 'Der Arbeitnehmer / die Arbeitnehmerin erhält eine Vergütung von <strong>' . $wageFormatted . ' EUR brutto pro Arbeitsstunde</strong>'
                . (!empty($d['wage_in_words']) ? ' (in Worten: ' . $d['wage_in_words'] . ')' : '')
                . '. Die Vergütung erfolgt auf Basis der tatsächlich geleisteten Arbeitsstunden. Die monatliche Abrechnung wird zum Ende des Kalendermonats auf das benannte Konto überwiesen.';
        } else {
            $vergutungKlausel = 'Der Arbeitnehmer / die Arbeitnehmerin erhält ein monatliches Bruttogehalt von <strong>' . $wageFormatted . ' EUR</strong>. Die Zahlung erfolgt jeweils zum Ende des Monats.';
        }

        // Computed: Sonderzahlungsklausel
        $specialPayments = $d['special_payments'] ?? [];
        $spLabels        = ['holiday_pay' => 'Urlaubsgeld', 'christmas_pay' => 'Weihnachtsgeld', 'bonus' => 'Prämien', 'thirteenth_month' => '13. Monatsgehalt'];
        if (!empty($specialPayments)) {
            $list                  = implode(', ', array_map(fn ($k) => $spLabels[$k] ?? $k, $specialPayments));
            $sonderzahlungenKlausel = 'Der Arbeitgeber kann freiwillig und ohne rechtliche Verpflichtung folgende Sonderzahlungen gewähren: ' . $list . '. Eine mehrmalige Zahlung begründet keinen Rechtsanspruch (Freiwilligkeitsvorbehalt).';
        } else {
            $sonderzahlungenKlausel = 'Ein Anspruch auf Zuschläge, Zulagen oder Sonderzahlungen (Gratifikationen, Prämien, 13. Gehalt, Weihnachtsgeld, Urlaubsgeld) besteht nicht. Sollte der Arbeitgeber dennoch eine solche Zahlung leisten, geschieht dies freiwillig ohne rechtliche Verpflichtung.';
        }

        // Computed: Probezeit
        $probationMonths = (int) ($d['probation_months'] ?? 0);
        if ($probationMonths > 0) {
            $probationKlausel = 'Die ersten <strong>' . $probationMonths . ' Monate</strong> des Arbeitsverhältnisses gelten als Probezeit. Während der Probezeit kann das Arbeitsverhältnis von beiden Seiten mit einer Frist von zwei Wochen gekündigt werden.';
        } else {
            $probationKlausel = 'Eine Probezeit ist nicht vereinbart.';
        }

        $typeMap = ['unbefristet' => 'Unbefristet', 'befristet' => 'Befristet', 'minijob' => 'Minijob / Geringfügig'];

        return array_merge($base, [
            'beginn_datum'            => !empty($d['employment_start']) ? Carbon::parse($d['employment_start'])->format('d.m.Y') : '',
            'ende_datum'              => !empty($d['employment_end']) ? Carbon::parse($d['employment_end'])->format('d.m.Y') : '',
            'wochenstunden'           => $d['weekly_hours'] ?? '',
            'urlaubstage'             => $d['vacation_days'] ?? '',
            'stundenlohn'             => $wageType === 'hourly' ? $wageFormatted : '',
            'monatslohn'              => $wageType === 'monthly' ? $wageFormatted : '',
            'stundenlohn_worten'      => $d['wage_in_words'] ?? '',
            'probezeit_monate'        => (string) $probationMonths,
            'arbeitsort'              => $d['work_location'] ?? '',
            'job_titel'               => $d['job_title'] ?? '',
            'vertragsart'             => $typeMap[$c->contract_type] ?? $c->contract_type,
            'vergutung_klausel'       => $vergutungKlausel,
            'sonderzahlungen_klausel' => $sonderzahlungenKlausel,
            'probezeit_klausel'       => $probationKlausel,
        ]);
    }

    public static function fromKeyHandover(KeyHandover $h): array
    {
        $key      = $h->key;
        $employee = $h->employee;
        $station  = $key?->station;

        $returned = $h->returned_at ?? $h->employee_returned_at;

        return [
            'schluessel_name'      => $key?->name ?? '',
            'schluessel_typ'       => $key?->type ?? '',
            'ausgabe_datum'        => $h->handed_out_at ? Carbon::parse($h->handed_out_at)->format('d.m.Y') : '',
            'ausgabe_uhrzeit'      => $h->handed_out_at ? Carbon::parse($h->handed_out_at)->format('H:i') . ' Uhr' : '',
            'rueckgabe_datum'      => $returned ? Carbon::parse($returned)->format('d.m.Y') : '',
            'mitarbeiter_name'     => trim(($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '')),
            'mitarbeiter_vorname'  => $employee?->first_name ?? '',
            'mitarbeiter_nachname' => $employee?->last_name ?? '',
            'station_name'         => $station?->name ?? '',
            'datum_heute'          => Carbon::today()->format('d.m.Y'),
        ];
    }

    public static function fromStation(Station $s): array
    {
        return [
            'station_name'     => $s->name ?? '',
            'station_strasse'  => trim(($s->street ?? '') . ' ' . ($s->house_number ?? '')),
            'station_plz'      => $s->zip ?? '',
            'station_ort'      => $s->city ?? '',
            'station_adresse'  => trim(($s->street ?? '') . ' ' . ($s->house_number ?? '') . ', ' . ($s->zip ?? '') . ' ' . ($s->city ?? '')),
            'inhaber_name'     => trim(($s->contact_first_name ?? '') . ' ' . ($s->contact_last_name ?? '')),
            'inhaber_vorname'  => $s->contact_first_name ?? '',
            'inhaber_nachname' => $s->contact_last_name ?? '',
            'inhaber_firma'    => $s->name ?? '',
            'station_telefon'  => $s->phone ?? '',
            'station_email'    => $s->email ?? '',
            'datum_heute'      => Carbon::today()->format('d.m.Y'),
        ];
    }
}
