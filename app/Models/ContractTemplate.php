<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $fillable = ['tenant_id', 'contract_type', 'name', 'body', 'custom_placeholders', 'is_active', 'is_default'];

    protected $casts = [
        'is_active'           => 'boolean',
        'is_default'          => 'boolean',
        'custom_placeholders' => 'array',
    ];

    // ─────────────────────────────────────────────────────────
    // Placeholder rendering
    // ─────────────────────────────────────────────────────────

    public function render(array $placeholders): string
    {
        $body = $this->body;

        foreach ($placeholders as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $body);
        }

        foreach ($this->custom_placeholders ?? [] as $custom) {
            if (!empty($custom['key'])) {
                $body = str_replace('{{' . $custom['key'] . '}}', (string) ($custom['value'] ?? ''), $body);
            }
        }

        return $body;
    }

    // ─────────────────────────────────────────────────────────
    // Build placeholder array from a contract + employee
    // ─────────────────────────────────────────────────────────

    public static function buildPlaceholders(EmployeeContract $contract, Employee $employee): array
    {
        $d = $contract->contract_data ?? [];

        $wageAmount = (float) ($d['wage_amount'] ?? 0);
        $wageFormatted = number_format($wageAmount, 2, ',', '.');
        $wageType = $d['wage_type'] ?? 'hourly';

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
        $spLabels = ['holiday_pay' => 'Urlaubsgeld', 'christmas_pay' => 'Weihnachtsgeld', 'bonus' => 'Prämien', 'thirteenth_month' => '13. Monatsgehalt'];
        if (!empty($specialPayments)) {
            $list = implode(', ', array_map(fn($k) => $spLabels[$k] ?? $k, $specialPayments));
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

        return [
            // Mitarbeiter
            'mitarbeiter_name'         => trim($employee->first_name . ' ' . $employee->last_name),
            'mitarbeiter_vorname'      => $employee->first_name ?? '',
            'mitarbeiter_nachname'     => $employee->last_name ?? '',
            'mitarbeiter_adresse'      => trim(($employee->street ?? '') . ' ' . ($employee->house_number ?? '') . ', ' . ($employee->zip ?? '') . ' ' . ($employee->city ?? '')),
            'mitarbeiter_geburtsdatum' => $employee->date_of_birth ? Carbon::parse($employee->date_of_birth)->format('d.m.Y') : '',

            // Arbeitgeber
            'arbeitgeber_name'    => $contract->employer_name ?? '',
            'arbeitgeber_firma'   => $contract->employer_company ?? '',
            'arbeitgeber_adresse' => trim(($contract->employer_street ?? '') . ', ' . ($contract->employer_zip ?? '') . ' ' . ($contract->employer_city ?? '')),

            // Vertragsdaten
            'beginn_datum'   => !empty($d['employment_start']) ? Carbon::parse($d['employment_start'])->format('d.m.Y') : '',
            'ende_datum'     => !empty($d['employment_end']) ? Carbon::parse($d['employment_end'])->format('d.m.Y') : '',
            'job_titel'      => $d['job_title'] ?? '',
            'arbeitsort'     => $d['work_location'] ?? '',
            'wochenstunden'  => $d['weekly_hours'] ?? '',
            'urlaubstage'    => $d['vacation_days'] ?? '',
            'probezeit_monate' => $probationMonths,

            // Vergütung
            'stundenlohn'       => $wageFormatted,
            'monatslohn'        => $wageFormatted,
            'stundenlohn_worten' => $d['wage_in_words'] ?? '',

            // Unterzeichnung
            'unterschrift_ort' => $contract->signing_location ?? '',
            'datum_heute'      => now()->format('d.m.Y'),
            'vertragsart'      => $contract->contractTypeLabel(),

            // Computed Klauseln
            'vergutung_klausel'       => $vergutungKlausel,
            'sonderzahlungen_klausel' => $sonderzahlungenKlausel,
            'probezeit_klausel'       => $probationKlausel,
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Auto-seed defaults for a tenant
    // ─────────────────────────────────────────────────────────

    public static function seedDefaultsForTenant(string $tenantId): void
    {
        foreach (['unbefristet', 'befristet', 'minijob'] as $type) {
            static::firstOrCreate(
                ['tenant_id' => $tenantId, 'contract_type' => $type],
                ['name' => static::getDefaultName($type), 'body' => static::getDefaultBody($type)]
            );
        }
    }

    public static function forTenant(string $tenantId, string $contractType): static
    {
        // 1. Try default template for this type
        $template = static::where('tenant_id', $tenantId)
            ->where('contract_type', $contractType)
            ->where('is_default', true)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        // 2. Fall back to any active template for this type
        $template ??= static::where('tenant_id', $tenantId)
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        // 3. Auto-create default if none exists
        return $template ?? static::create([
            'tenant_id'    => $tenantId,
            'contract_type' => $contractType,
            'name'         => static::getDefaultName($contractType),
            'body'         => static::getDefaultBody($contractType),
            'is_active'    => true,
            'is_default'   => true,
        ]);
    }

    public static function getDefaultName(string $type): string
    {
        return match ($type) {
            'unbefristet' => 'Arbeitsvertrag — Unbefristet mit Arbeitszeitkonto',
            'befristet'   => 'Arbeitsvertrag — Befristet',
            'minijob'     => 'Arbeitsvertrag — Minijob / Geringfügig',
            default       => 'Arbeitsvertrag',
        };
    }

    // ─────────────────────────────────────────────────────────
    // Default template bodies (HTML, FTG-Stil)
    // ─────────────────────────────────────────────────────────

    public static function getDefaultBody(string $type): string
    {
        return match ($type) {
            'befristet' => static::defaultBefristet(),
            'minijob'   => static::defaultMinijob(),
            default     => static::defaultUnbefristet(),
        };
    }

    // ── Unbefristet (FTG A3a-Struktur) ───────────────────────

    private static function defaultUnbefristet(): string
    {
        return <<<'HTML'
<h2>§ 1 Beginn und Dauer des Arbeitsverhältnisses</h2>
<ol>
<li>Das Arbeitsverhältnis beginnt am <strong>{{beginn_datum}}</strong> und wird auf unbestimmte Zeit geschlossen.</li>
<li>Das Arbeitsverhältnis ist beidseitig kündbar nach Maßgabe der in diesem Vertrag vereinbarten und der gesetzlichen Bestimmungen.</li>
</ol>

<h2>§ 2 Probezeit</h2>
<ol>
<li>{{probezeit_klausel}}</li>
</ol>

<h2>§ 3 Tätigkeit und Arbeitsort</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin wird als <strong>{{job_titel}}</strong> eingestellt. Der Arbeitsort ist: <strong>{{arbeitsort}}</strong>.</li>
<li>Der Arbeitgeber ist berechtigt, dem Arbeitnehmer / der Arbeitnehmerin im Rahmen des Direktionsrechts (§ 106 GewO) andere gleichwertige Aufgaben zu übertragen sowie ihn / sie an einem anderen zumutbaren Ort einzusetzen.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin ist verpflichtet, auch Tätigkeiten zu übernehmen, die dem Berufsbild entsprechen, auch wenn diese nicht ausdrücklich in diesem Vertrag benannt sind.</li>
</ol>

<h2>§ 4 Vergütung</h2>
<ol>
<li>{{vergutung_klausel}}</li>
<li>{{sonderzahlungen_klausel}}</li>
<li>Änderungen des gesetzlichen Mindestlohns gelten unmittelbar, soweit die vereinbarte Vergütung darunter liegen sollte.</li>
</ol>

<h2>§ 5 Arbeitszeit und Arbeitszeitkonto</h2>
<ol>
<li>Die regelmäßige wöchentliche Arbeitszeit beträgt durchschnittlich <strong>{{wochenstunden}} Stunden</strong>. Die tatsächliche Arbeitszeit richtet sich nach dem Dienstplan und dem betrieblichen Bedarf.</li>
<li>Die gesetzliche Höchstarbeitszeit von 48 Stunden pro Woche (§ 3 ArbZG) darf nicht überschritten werden.</li>
<li>Es wird ein Arbeitszeitkonto eingerichtet. Mehr- und Minderarbeit gegenüber der vereinbarten Wochenarbeitszeit wird erfasst. Der Ausgleichszeitraum beträgt 12 Monate. Am Ende des Ausgleichszeitraums sind Guthaben in Freizeit abzubauen; Minusstunden werden nicht vergütet, sofern der Arbeitgeber die Minderarbeit nicht zu vertreten hat.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin ist verpflichtet, Nacht-, Wechselschicht-, Feiertags- und Sonntagsarbeit sowie Rufbereitschaft zu leisten, soweit dies aus betrieblichen Gründen erforderlich und gesetzlich zulässig ist.</li>
<li>Die geleisteten Arbeitszeiten werden aufgezeichnet. Der Arbeitgeber behält sich die Einführung elektronischer Zeiterfassungssysteme vor.</li>
</ol>

<h2>§ 6 Urlaub</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin hat Anspruch auf einen bezahlten Erholungsurlaub von <strong>{{urlaubstage}} Arbeitstagen</strong> pro Kalenderjahr (Basis 5-Tage-Woche).</li>
<li>Der Urlaub ist vor Antritt vom Arbeitgeber zu genehmigen. Bei dringendem betrieblichem Bedarf kann der Arbeitgeber bereits zugesagten Urlaub verschieben.</li>
<li>Der Urlaub ist grundsätzlich im laufenden Kalenderjahr zu nehmen. Eine Übertragung ist nur bei dringenden betrieblichen oder persönlichen Gründen bis zum 31. März des Folgejahres möglich. Danach verfällt nicht genommener Urlaub, soweit gesetzlich zulässig.</li>
</ol>

<h2>§ 7 Arbeitsverhinderung und Krankheit</h2>
<ol>
<li>Jede Arbeitsverhinderung ist dem Arbeitgeber unverzüglich, spätestens zu Beginn der Arbeitszeit, mitzuteilen und zu begründen.</li>
<li>Bei krankheitsbedingter Arbeitsunfähigkeit ist spätestens ab dem dritten Kalendertag eine ärztliche Arbeitsunfähigkeitsbescheinigung vorzulegen. Der Arbeitgeber ist berechtigt, die Vorlage ab dem ersten Tag zu verlangen.</li>
<li>Entgeltfortzahlung bei Krankheit richtet sich nach den gesetzlichen Bestimmungen (§ 3 EFZG — bis zu 6 Wochen).</li>
<li>Der Anspruch auf Entgeltfortzahlung wegen vorübergehender Verhinderung aus persönlichen Gründen gemäß § 616 BGB wird ausgeschlossen.</li>
</ol>

<h2>§ 8 Nebentätigkeit</h2>
<ol>
<li>Jede entgeltliche Nebenbeschäftigung sowie jede das Arbeitsverhältnis beeinträchtigende Tätigkeit bedarf der vorherigen schriftlichen Zustimmung des Arbeitgebers.</li>
<li>Eine Nebentätigkeit bei einem Konkurrenzunternehmen ist ausnahmslos untersagt.</li>
</ol>

<h2>§ 9 Verschwiegenheitspflicht und Datenschutz</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin verpflichtet sich, während des Arbeitsverhältnisses und nach dessen Beendigung über alle Betriebs- und Geschäftsgeheimnisse sowie vertraulichen Informationen Stillschweigen zu bewahren.</li>
<li>Personenbezogene Daten werden im Rahmen des Arbeitsverhältnisses gemäß DSGVO und BDSG verarbeitet. Verstöße gegen die Verschwiegenheitspflicht berechtigen zur außerordentlichen Kündigung und können Schadensersatzansprüche begründen.</li>
</ol>

<h2>§ 10 Nutzung von Arbeitsmitteln und EDV</h2>
<ol>
<li>Sämtliche Arbeitsmittel (einschließlich EDV-Geräte, Kasse, Kommunikationsmittel) sind ausschließlich für betriebliche Zwecke zu nutzen. Eine private Nutzung ist nicht gestattet.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin ist für die sorgfältige Behandlung der überlassenen Arbeitsmittel verantwortlich. Schäden durch unsachgemäße Behandlung sind zu ersetzen.</li>
</ol>

<h2>§ 11 Rückzahlungs- und Haftungsvereinbarungen</h2>
<ol>
<li>Überzahlungen sind vom Arbeitnehmer / von der Arbeitnehmerin unverzüglich zurückzuerstatten.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin haftet für vorsätzlich oder grob fahrlässig verursachte Schäden in vollem Umfang. Bei leichter Fahrlässigkeit ist die Haftung auf einen angemessenen Anteil beschränkt.</li>
</ol>

<h2>§ 12 Abtretung und Pfändung</h2>
<ol>
<li>Die Abtretung oder Verpfändung von Vergütungsansprüchen an Dritte ist nur mit vorheriger schriftlicher Zustimmung des Arbeitgebers zulässig.</li>
</ol>

<h2>§ 13 Altersbedingte Beendigung des Arbeitsverhältnisses</h2>
<ol>
<li>Das Arbeitsverhältnis endet ohne Kündigung mit Ablauf des Kalendermonats, in dem der Arbeitnehmer / die Arbeitnehmerin die Regelaltersgrenze in der gesetzlichen Rentenversicherung erreicht.</li>
</ol>

<h2>§ 14 Ordentliche Kündigung</h2>
<ol>
<li>Das Arbeitsverhältnis kann von beiden Seiten ordentlich gekündigt werden. Während der Probezeit beträgt die Kündigungsfrist zwei Wochen.</li>
<li>Nach Ablauf der Probezeit gelten die Kündigungsfristen gemäß § 15 dieses Vertrages.</li>
<li>Jede Kündigung bedarf zu ihrer Wirksamkeit der Schriftform; die elektronische Form ist ausgeschlossen (§ 623 BGB).</li>
<li>Am letzten Tätigkeitstag sind alle Unterlagen, Schlüssel und Gegenstände des Arbeitgebers zurückzugeben.</li>
</ol>

<h2>§ 15 Kündigungsfristen</h2>
<p>Nach Ablauf der Probezeit gelten für beide Vertragsparteien folgende Kündigungsfristen:</p>
<ol>
<li>In den ersten 2 Beschäftigungsjahren: 4 Wochen zum 15. oder zum Ende eines Kalendermonats.</li>
<li>Ab dem 3. Beschäftigungsjahr (nach 2 Jahren): 1 Monat zum Ende eines Kalendermonats.</li>
<li>Ab dem 6. Beschäftigungsjahr (nach 5 Jahren): 2 Monate zum Ende eines Kalendermonats.</li>
<li>Ab dem 9. Beschäftigungsjahr (nach 8 Jahren): 3 Monate zum Ende eines Kalendermonats.</li>
<li>Ab dem 11. Beschäftigungsjahr (nach 10 Jahren): 4 Monate zum Ende eines Kalendermonats.</li>
<li>Ab dem 13. Beschäftigungsjahr (nach 12 Jahren): 5 Monate zum Ende eines Kalendermonats.</li>
<li>Ab dem 16. Beschäftigungsjahr (nach 15 Jahren): 6 Monate zum Ende eines Kalendermonats.</li>
<li>Ab dem 21. Beschäftigungsjahr (nach 20 Jahren): 7 Monate zum Ende eines Kalendermonats.</li>
</ol>

<h2>§ 16 Außerordentliche Kündigung</h2>
<ol>
<li>Das Recht beider Parteien zur außerordentlichen fristlosen Kündigung aus wichtigem Grund (§ 626 BGB) bleibt unberührt.</li>
<li>Als wichtiger Grund gilt insbesondere: Diebstahl, Unterschlagung, unentschuldigtes Fernbleiben vom Arbeitsplatz, schwerwiegende Verstöße gegen die Verschwiegenheitspflicht sowie Tätlichkeiten gegenüber Vorgesetzten, Kollegen oder Kunden.</li>
</ol>

<h2>§ 17 Vertragsstrafe</h2>
<ol>
<li>Tritt der Arbeitnehmer / die Arbeitnehmerin die Arbeit nicht an oder verlässt er / sie das Arbeitsverhältnis ohne Einhaltung der vereinbarten Frist, ist eine Vertragsstrafe in Höhe eines Bruttomonatsgehalts zu zahlen. Bei Stundenlohnvereinbarung entspricht die Vertragsstrafe dem 173-fachen des vereinbarten Stundenlohns.</li>
<li>Weitergehende Schadensersatzansprüche des Arbeitgebers bleiben hiervon unberührt.</li>
</ol>

<h2>§ 18 Schriftformklausel und salvatorische Klausel</h2>
<ol>
<li>Änderungen und Ergänzungen dieses Vertrages sowie ein Abweichen vom Schriftformerfordernis selbst bedürfen zu ihrer Wirksamkeit der Schriftform. Mündliche Nebenabreden sind nicht getroffen.</li>
<li>Sollte eine Bestimmung dieses Vertrages unwirksam sein oder werden, wird die Wirksamkeit der übrigen Bestimmungen davon nicht berührt. An die Stelle der unwirksamen Bestimmung tritt die gesetzliche Regelung.</li>
</ol>

<h2>§ 19 Verfall- und Ausschlussfristen</h2>
<ol>
<li>Alle beiderseitigen Ansprüche aus dem Arbeitsverhältnis verfallen, wenn sie nicht innerhalb von 3 Monaten nach Fälligkeit gegenüber der anderen Vertragspartei schriftlich geltend gemacht werden.</li>
<li>Lehnt die andere Partei den Anspruch ab oder erklärt sich nicht innerhalb von 2 Wochen, verfällt der Anspruch, wenn er nicht innerhalb von weiteren 3 Monaten gerichtlich geltend gemacht wird.</li>
<li>Diese Ausschlussfristen gelten nicht für Ansprüche aus vorsätzlichem Handeln sowie für Ansprüche auf den gesetzlichen Mindestlohn.</li>
</ol>

<h2>§ 20 Sonstige Vereinbarungen und Vertragsaushändigung</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin bestätigt, dass alle Angaben im Einstellungsgespräch und Personalfragebogen wahrheitsgemäß und vollständig gemacht wurden. Falsche Angaben können zur Anfechtung dieses Vertrages führen.</li>
<li>Dieser Vertrag wird in zwei Ausfertigungen erstellt. Jede Partei erhält ein unterzeichnetes Exemplar. Der Arbeitnehmer / die Arbeitnehmerin bestätigt, vor Arbeitsaufnahme ein beidseitig unterzeichnetes Exemplar erhalten zu haben.</li>
<li>Mit Abschluss dieses Vertrages sind alle vorherigen mündlichen oder schriftlichen Absprachen durch diesen Vertrag ersetzt.</li>
</ol>
HTML;
    }

    // ── Befristet ─────────────────────────────────────────────

    private static function defaultBefristet(): string
    {
        return <<<'HTML'
<h2>§ 1 Beginn und Dauer des Arbeitsverhältnisses</h2>
<ol>
<li>Das Arbeitsverhältnis beginnt am <strong>{{beginn_datum}}</strong> und ist befristet bis zum <strong>{{ende_datum}}</strong>.</li>
<li>Das Arbeitsverhältnis endet mit Ablauf der Befristung, ohne dass es einer gesonderten Kündigung bedarf (§ 15 Abs. 1 TzBfG).</li>
<li>Sachgrund der Befristung: Vorübergehender betrieblicher Bedarf an der Arbeitsleistung (§ 14 Abs. 1 Nr. 1 TzBfG).</li>
</ol>

<h2>§ 2 Probezeit</h2>
<ol>
<li>{{probezeit_klausel}}</li>
</ol>

<h2>§ 3 Tätigkeit und Arbeitsort</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin wird als <strong>{{job_titel}}</strong> eingestellt. Der Arbeitsort ist: <strong>{{arbeitsort}}</strong>.</li>
<li>Der Arbeitgeber ist berechtigt, dem Arbeitnehmer / der Arbeitnehmerin im Rahmen des Direktionsrechts (§ 106 GewO) andere gleichwertige Aufgaben zu übertragen sowie ihn / sie an einem anderen zumutbaren Ort einzusetzen.</li>
</ol>

<h2>§ 4 Vergütung</h2>
<ol>
<li>{{vergutung_klausel}}</li>
<li>{{sonderzahlungen_klausel}}</li>
</ol>

<h2>§ 5 Arbeitszeit</h2>
<ol>
<li>Die regelmäßige wöchentliche Arbeitszeit beträgt durchschnittlich <strong>{{wochenstunden}} Stunden</strong>. Die tatsächliche Arbeitszeit richtet sich nach dem Dienstplan und dem betrieblichen Bedarf.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin ist verpflichtet, Nacht-, Wechselschicht-, Feiertags- und Sonntagsarbeit zu leisten, soweit dies aus betrieblichen Gründen erforderlich und gesetzlich zulässig ist.</li>
<li>Die geleisteten Arbeitszeiten werden aufgezeichnet.</li>
</ol>

<h2>§ 6 Urlaub</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin hat Anspruch auf einen anteiligen bezahlten Erholungsurlaub von <strong>{{urlaubstage}} Arbeitstagen</strong> pro Kalenderjahr (Basis 5-Tage-Woche). Da das Arbeitsverhältnis befristet ist, entsteht der Urlaubsanspruch nur zeitanteilig.</li>
<li>Der Urlaub ist vor Antritt vom Arbeitgeber zu genehmigen und grundsätzlich im laufenden Beschäftigungszeitraum zu nehmen.</li>
</ol>

<h2>§ 7 Arbeitsverhinderung und Krankheit</h2>
<ol>
<li>Jede Arbeitsverhinderung ist dem Arbeitgeber unverzüglich, spätestens zu Beginn der Arbeitszeit, mitzuteilen.</li>
<li>Bei krankheitsbedingter Arbeitsunfähigkeit ist spätestens ab dem dritten Kalendertag eine ärztliche Arbeitsunfähigkeitsbescheinigung vorzulegen. Der Arbeitgeber kann dies auch ab dem ersten Tag verlangen.</li>
<li>Entgeltfortzahlung bei Krankheit richtet sich nach den gesetzlichen Bestimmungen (§ 3 EFZG — bis zu 6 Wochen).</li>
<li>Der Anspruch auf Entgeltfortzahlung wegen vorübergehender Verhinderung aus persönlichen Gründen gemäß § 616 BGB wird ausgeschlossen.</li>
</ol>

<h2>§ 8 Nebentätigkeit</h2>
<ol>
<li>Jede entgeltliche Nebenbeschäftigung sowie jede das Arbeitsverhältnis beeinträchtigende Tätigkeit bedarf der vorherigen schriftlichen Zustimmung des Arbeitgebers.</li>
</ol>

<h2>§ 9 Verschwiegenheitspflicht und Datenschutz</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin verpflichtet sich, während des Arbeitsverhältnisses und nach dessen Beendigung über alle Betriebs- und Geschäftsgeheimnisse Stillschweigen zu bewahren.</li>
<li>Personenbezogene Daten werden gemäß DSGVO und BDSG verarbeitet.</li>
</ol>

<h2>§ 10 Nutzung von Arbeitsmitteln</h2>
<ol>
<li>Sämtliche Arbeitsmittel sind ausschließlich für betriebliche Zwecke zu nutzen. Schäden durch unsachgemäße Behandlung sind zu ersetzen.</li>
</ol>

<h2>§ 11 Ordentliche Kündigung und Verlängerung</h2>
<ol>
<li>Während der Probezeit kann das Arbeitsverhältnis von beiden Seiten mit einer Frist von zwei Wochen ordentlich gekündigt werden. Nach der Probezeit ist eine ordentliche Kündigung nur zulässig, wenn dies ausdrücklich vereinbart wurde.</li>
<li>Das Recht zur außerordentlichen fristlosen Kündigung aus wichtigem Grund (§ 626 BGB) bleibt unberührt.</li>
<li>Eine Verlängerung oder Entfristung des Arbeitsverhältnisses bedarf einer schriftlichen Vereinbarung vor Ablauf der Befristung.</li>
<li>Jede Kündigung bedarf zu ihrer Wirksamkeit der Schriftform (§ 623 BGB).</li>
</ol>

<h2>§ 12 Schriftformklausel und salvatorische Klausel</h2>
<ol>
<li>Änderungen und Ergänzungen dieses Vertrages bedürfen zu ihrer Wirksamkeit der Schriftform. Mündliche Nebenabreden sind nicht getroffen.</li>
<li>Sollte eine Bestimmung unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen davon unberührt.</li>
</ol>

<h2>§ 13 Verfall- und Ausschlussfristen</h2>
<ol>
<li>Alle beiderseitigen Ansprüche aus dem Arbeitsverhältnis verfallen, wenn sie nicht innerhalb von 3 Monaten nach Fälligkeit schriftlich geltend gemacht werden.</li>
<li>Diese Ausschlussfristen gelten nicht für Ansprüche aus vorsätzlichem Handeln sowie für Ansprüche auf den gesetzlichen Mindestlohn.</li>
</ol>

<h2>§ 14 Vertragsaushändigung</h2>
<ol>
<li>Dieser Vertrag wird in zwei Ausfertigungen erstellt. Jede Partei erhält ein unterzeichnetes Exemplar. Der Arbeitnehmer / die Arbeitnehmerin bestätigt, vor Arbeitsaufnahme ein beidseitig unterzeichnetes Exemplar erhalten zu haben.</li>
</ol>
HTML;
    }

    // ── Minijob ───────────────────────────────────────────────

    private static function defaultMinijob(): string
    {
        return <<<'HTML'
<h2>§ 1 Beginn und Art der Beschäftigung</h2>
<ol>
<li>Das Arbeitsverhältnis beginnt am <strong>{{beginn_datum}}</strong> und wird auf unbestimmte Zeit als geringfügige Beschäftigung im Sinne von § 8 Abs. 1 Nr. 1 SGB IV (Minijob) geschlossen.</li>
<li>Das monatliche Arbeitsentgelt überschreitet grundsätzlich nicht die jeweils gültige Geringfügigkeitsgrenze gemäß § 8 Abs. 1 Nr. 1 SGB IV.</li>
</ol>

<h2>§ 2 Probezeit</h2>
<ol>
<li>{{probezeit_klausel}}</li>
</ol>

<h2>§ 3 Tätigkeit und Arbeitsort</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin wird als <strong>{{job_titel}}</strong> eingestellt. Der Arbeitsort ist: <strong>{{arbeitsort}}</strong>.</li>
<li>Der Arbeitgeber ist berechtigt, dem Arbeitnehmer / der Arbeitnehmerin im Rahmen des Direktionsrechts (§ 106 GewO) andere gleichwertige Aufgaben zu übertragen.</li>
</ol>

<h2>§ 4 Vergütung und Sozialversicherung</h2>
<ol>
<li>{{vergutung_klausel}}</li>
<li>{{sonderzahlungen_klausel}}</li>
<li>Für das Beschäftigungsverhältnis besteht grundsätzlich Rentenversicherungspflicht nach § 1 SGB VI. Der Arbeitnehmer / die Arbeitnehmerin kann auf Antrag von der Rentenversicherungspflicht befreit werden (§ 6 Abs. 1b SGB VI).</li>
<li>Der Arbeitgeber führt Pauschalabgaben zur Kranken- und Rentenversicherung sowie die Einkommensteuer pauschal ab, soweit die gesetzlichen Voraussetzungen vorliegen.</li>
</ol>

<h2>§ 5 Arbeitszeit</h2>
<ol>
<li>Die Arbeitszeit richtet sich nach dem Dienstplan und dem betrieblichen Bedarf. Die durchschnittliche wöchentliche Arbeitszeit beträgt ca. <strong>{{wochenstunden}} Stunden</strong>.</li>
<li>Ein Anspruch auf eine bestimmte Anzahl von Arbeitsstunden pro Woche oder Monat besteht nicht, soweit nichts anderes vereinbart ist.</li>
<li>Der Arbeitnehmer / die Arbeitnehmerin ist verpflichtet, auch Nacht-, Wechselschicht-, Feiertags- und Sonntagsarbeit zu leisten, soweit dies betrieblich erforderlich und gesetzlich zulässig ist.</li>
</ol>

<h2>§ 6 Urlaub</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin hat Anspruch auf einen anteiligen bezahlten Erholungsurlaub von <strong>{{urlaubstage}} Arbeitstagen</strong> pro Kalenderjahr (Basis 5-Tage-Woche). Der Urlaubsanspruch berechnet sich entsprechend der tatsächlich geleisteten Arbeitstage.</li>
<li>Der Urlaub ist vor Antritt vom Arbeitgeber zu genehmigen und grundsätzlich im laufenden Kalenderjahr zu nehmen.</li>
</ol>

<h2>§ 7 Arbeitsverhinderung und Krankheit</h2>
<ol>
<li>Jede Arbeitsverhinderung ist dem Arbeitgeber unverzüglich, spätestens zu Beginn der Arbeitszeit, mitzuteilen.</li>
<li>Bei krankheitsbedingter Arbeitsunfähigkeit ist spätestens ab dem dritten Kalendertag eine ärztliche Arbeitsunfähigkeitsbescheinigung vorzulegen. Der Arbeitgeber kann dies ab dem ersten Tag verlangen.</li>
<li>Entgeltfortzahlung bei Krankheit richtet sich nach den gesetzlichen Bestimmungen (§ 3 EFZG — bis zu 6 Wochen).</li>
<li>Der Anspruch auf Entgeltfortzahlung wegen vorübergehender Verhinderung aus persönlichen Gründen gemäß § 616 BGB wird ausgeschlossen.</li>
</ol>

<h2>§ 8 Nebentätigkeit</h2>
<ol>
<li>Jede weitere Beschäftigung bei einem anderen Arbeitgeber ist dem Arbeitgeber unverzüglich mitzuteilen, da weitere geringfügige Beschäftigungen mit der Haupttätigkeit zusammengerechnet werden können und die Geringfügigkeitsgrenze gefährden.</li>
<li>Eine Nebentätigkeit bei einem Konkurrenzunternehmen ist nur mit vorheriger schriftlicher Zustimmung des Arbeitgebers zulässig.</li>
</ol>

<h2>§ 9 Verschwiegenheitspflicht</h2>
<ol>
<li>Der Arbeitnehmer / die Arbeitnehmerin verpflichtet sich, während des Arbeitsverhältnisses und nach dessen Beendigung über alle Betriebs- und Geschäftsgeheimnisse Stillschweigen zu bewahren.</li>
</ol>

<h2>§ 10 Kündigung</h2>
<ol>
<li>Während der Probezeit kann das Arbeitsverhältnis von beiden Seiten mit einer Frist von zwei Wochen gekündigt werden.</li>
<li>Nach Ablauf der Probezeit beträgt die Kündigungsfrist 4 Wochen zum 15. oder zum Ende eines Kalendermonats (§ 622 Abs. 1 BGB). Die gesetzlichen verlängerten Kündigungsfristen nach § 622 Abs. 2 BGB gelten entsprechend.</li>
<li>Das Recht zur außerordentlichen fristlosen Kündigung aus wichtigem Grund (§ 626 BGB) bleibt unberührt.</li>
<li>Jede Kündigung bedarf zu ihrer Wirksamkeit der Schriftform (§ 623 BGB).</li>
</ol>

<h2>§ 11 Schriftformklausel und salvatorische Klausel</h2>
<ol>
<li>Änderungen und Ergänzungen dieses Vertrages bedürfen zu ihrer Wirksamkeit der Schriftform. Mündliche Nebenabreden sind nicht getroffen.</li>
<li>Sollte eine Bestimmung unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen davon unberührt.</li>
</ol>

<h2>§ 12 Vertragsaushändigung</h2>
<ol>
<li>Dieser Vertrag wird in zwei Ausfertigungen erstellt. Der Arbeitnehmer / die Arbeitnehmerin bestätigt, vor Arbeitsaufnahme ein von beiden Seiten unterzeichnetes Exemplar erhalten zu haben.</li>
</ol>
HTML;
    }
}
