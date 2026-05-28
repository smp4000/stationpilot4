<?php

namespace App\Models;

use App\Services\PlaceholderRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'document_type', 'sub_type', 'name', 'description',
        'body', 'custom_placeholders', 'is_active', 'is_default', 'requires_signature',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'is_default'          => 'boolean',
        'requires_signature'  => 'boolean',
        'custom_placeholders' => 'array',
    ];

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }

    // ─── Rendering ───────────────────────────────────────────────────────────

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

    // ─── Lookup ──────────────────────────────────────────────────────────────

    public static function forTenant(string $tenantId, string $documentType, ?string $subType = null): static
    {
        $query = static::where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($subType) {
            $query->where(function ($q) use ($subType) {
                $q->where('sub_type', $subType)->orWhereNull('sub_type');
            })->orderByRaw("CASE WHEN sub_type = ? THEN 0 ELSE 1 END", [$subType]);
        }

        $template = $query->orderByDesc('is_default')->first();

        return $template ?? static::createDefault($tenantId, $documentType, $subType);
    }

    public static function seedDefaultsForTenant(string $tenantId): void
    {
        $seeds = [
            ['arbeitsvertrag', 'unbefristet', false],
            ['arbeitsvertrag', 'befristet',   false],
            ['arbeitsvertrag', 'minijob',     false],
            ['mitarbeiter',    'datenschutz',     true],
            ['mitarbeiter',    'betriebsordnung', true],
            ['mitarbeiter',    'nda',             true],
            ['schluessel',     'uebergabe',       true],
        ];

        foreach ($seeds as [$type, $subType, $requiresSig]) {
            if (!static::where('tenant_id', $tenantId)
                       ->where('document_type', $type)
                       ->where('sub_type', $subType)
                       ->exists()) {
                static::create([
                    'tenant_id'          => $tenantId,
                    'document_type'      => $type,
                    'sub_type'           => $subType,
                    'name'               => static::getDefaultName($type, $subType),
                    'body'               => static::getDefaultBody($type, $subType),
                    'is_active'          => true,
                    'is_default'         => true,
                    'requires_signature' => $requiresSig,
                ]);
            }
        }
    }

    private static function createDefault(string $tenantId, string $documentType, ?string $subType): static
    {
        return static::create([
            'tenant_id'     => $tenantId,
            'document_type' => $documentType,
            'sub_type'      => $subType,
            'name'          => static::getDefaultName($documentType, $subType),
            'body'          => static::getDefaultBody($documentType, $subType),
            'is_active'     => true,
            'is_default'    => true,
        ]);
    }

    // ─── Defaults ────────────────────────────────────────────────────────────

    public static function getDefaultName(string $type, ?string $subType = null): string
    {
        return match ([$type, $subType]) {
            ['arbeitsvertrag', 'unbefristet'] => 'Arbeitsvertrag — Unbefristet mit Arbeitszeitkonto',
            ['arbeitsvertrag', 'befristet']   => 'Arbeitsvertrag — Befristet',
            ['arbeitsvertrag', 'minijob']     => 'Arbeitsvertrag — Minijob / Geringfügig',
            ['mitarbeiter',    'datenschutz']     => 'DSGVO-Einwilligung',
            ['mitarbeiter',    'betriebsordnung'] => 'Betriebsordnung',
            ['mitarbeiter',    'nda']             => 'Geheimhaltungsvereinbarung',
            ['schluessel',     'uebergabe']        => 'Schlüsselübergabe-Protokoll',
            default => ($type === 'arbeitsvertrag' ? 'Arbeitsvertrag' : (PlaceholderRegistry::types()[$type] ?? $type) . ' — Standard'),
        };
    }

    public static function getDefaultBody(string $type, ?string $subType = null): string
    {
        if ($type === 'arbeitsvertrag') {
            return ContractTemplate::getDefaultBody($subType ?? 'unbefristet');
        }

        if ($type === 'mitarbeiter') {
            return match ($subType) {
                'datenschutz'     => static::bodyDatenschutz(),
                'betriebsordnung' => static::bodyBetriebsordnung(),
                'nda'             => static::bodyNda(),
                default           => '<p>Sehr geehrte/r <strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong>,</p><p></p><p>Mit freundlichen Grüßen<br><strong>{{arbeitgeber_firma}}</strong></p>',
            };
        }

        if ($type === 'schluessel') {
            return static::bodySchluesseluebergabe();
        }

        return match ($type) {
            'tankstelle' => '<h2>{{station_name}}</h2><p>{{station_adresse}}</p><p>Inhaber: {{inhaber_name}}</p>',
            default      => '<p>Vorlage bearbeiten …</p>',
        };
    }

    // ── Template-Texte ────────────────────────────────────────────────────────

    private static function bodyDatenschutz(): string
    {
        return <<<'HTML'
<h2>Einwilligung zur Verarbeitung personenbezogener Daten</h2>
<p>Sehr geehrte/r <strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong>,</p>
<p>im Rahmen Ihres Beschäftigungsverhältnisses bei <strong>{{arbeitgeber_firma}}</strong> verarbeiten wir Ihre personenbezogenen Daten. Wir informieren Sie hiermit gemäß Art. 13 DSGVO über Art und Zweck der Verarbeitung.</p>

<h2>1. Verantwortlicher</h2>
<p>{{arbeitgeber_firma}}<br>{{arbeitgeber_adresse}}<br>Ansprechpartner: {{arbeitgeber_name}}</p>

<h2>2. Verarbeitete Daten und Zweck</h2>
<p>Wir verarbeiten folgende Datenkategorien ausschließlich für die Durchführung des Arbeitsverhältnisses:</p>
<ul>
<li><strong>Stammdaten</strong> (Name, Anschrift, Geburtsdatum, Kontaktdaten) — Personalverwaltung</li>
<li><strong>Bankverbindung</strong> — Lohn- und Gehaltsauszahlung</li>
<li><strong>Steuer- und Sozialversicherungsdaten</strong> — gesetzliche Melde- und Abführungspflichten</li>
<li><strong>Arbeitszeitdaten</strong> — Vertragserfüllung und Abrechnung</li>
<li><strong>Gesundheitsdaten</strong> (z.B. AU-Bescheinigungen) — soweit gesetzlich erforderlich</li>
</ul>

<h2>3. Rechtsgrundlage</h2>
<ul>
<li>Art. 6 Abs. 1 lit. b DSGVO — Vertragserfüllung</li>
<li>Art. 6 Abs. 1 lit. c DSGVO — Erfüllung rechtlicher Verpflichtungen</li>
<li>§ 26 BDSG — Datenverarbeitung für Zwecke des Beschäftigungsverhältnisses</li>
</ul>

<h2>4. Speicherdauer</h2>
<p>Ihre Daten werden für die Dauer des Arbeitsverhältnisses gespeichert und danach entsprechend der gesetzlichen Aufbewahrungsfristen archiviert (i.d.R. 6–10 Jahre für steuerrelevante Unterlagen).</p>

<h2>5. Ihre Rechte</h2>
<p>Sie haben das Recht auf Auskunft (Art. 15), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18) und Widerspruch (Art. 21 DSGVO). Beschwerden richten Sie an die zuständige Datenschutzaufsichtsbehörde.</p>

<h2>6. Bestätigung</h2>
<p>Ich, <strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong>, bestätige, die Datenschutzhinweise erhalten und verstanden zu haben.</p>
<br>
<p>{{unterschrift_ort}}, den <strong>{{datum_heute}}</strong></p>
<br><br>
<p>________________________________<br><strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong><br>Unterschrift Mitarbeiter/in</p>
HTML;
    }

    private static function bodyBetriebsordnung(): string
    {
        return <<<'HTML'
<h2>Betriebsordnung</h2>
<p>Gültig für alle Mitarbeiterinnen und Mitarbeiter der <strong>{{arbeitgeber_firma}}</strong> ab dem ersten Arbeitstag.</p>

<h2>§ 1 — Allgemeines</h2>
<p>Diese Betriebsordnung regelt das Miteinander am Arbeitsplatz und dient einem geordneten, sicheren Betriebsablauf. Alle Mitarbeiterinnen und Mitarbeiter sind verpflichtet, die Regelungen einzuhalten.</p>

<h2>§ 2 — Arbeitszeit und Pünktlichkeit</h2>
<p>Die Arbeitszeiten richten sich nach dem Arbeitsvertrag. Beginn, Ende und Pausen sind einzuhalten. Über- und Mehrarbeit ist vorab mit der Betriebsleitung abzustimmen und wird entsprechend erfasst.</p>

<h2>§ 3 — Abwesenheit und Krankmeldung</h2>
<p>Bei Abwesenheit wegen Krankheit ist die Betriebsleitung spätestens zum Beginn der Arbeitszeit telefonisch zu informieren. Ab dem dritten Krankheitstag ist eine ärztliche Bescheinigung einzureichen.</p>

<h2>§ 4 — Verhalten am Arbeitsplatz</h2>
<p>Ein respektvoller, kollegialer Umgang ist selbstverständlich. Diskriminierung, Belästigung und Mobbing werden nicht toleriert und haben arbeitsrechtliche Konsequenzen.</p>

<h2>§ 5 — Kundenumgang</h2>
<p>Alle Kundinnen und Kunden sind freundlich, zuvorkommend und professionell zu behandeln. Reklamationen und Beschwerden sind sachlich entgegenzunehmen und ggf. an die Betriebsleitung weiterzuleiten.</p>

<h2>§ 6 — Kassenführung</h2>
<p>Die Kasse ist sorgfältig und ordnungsgemäß zu führen. Fehlbeträge sind unverzüglich der Betriebsleitung zu melden. Eigenmächtige Entnahmen führen zur sofortigen Kündigung und strafrechtlichen Verfolgung.</p>

<h2>§ 7 — Rauchverbot und Brandschutz</h2>
<p>Auf dem gesamten Betriebsgelände besteht striktes Rauchverbot. Verstöße stellen eine schwerwiegende Sicherheitsgefährdung dar und werden disziplinarisch geahndet. Notausgänge und Feuerlöscher sind stets freizuhalten.</p>

<h2>§ 8 — Umgang mit Kraftstoffen und Gefahrstoffen</h2>
<p>Alle Sicherheitsvorschriften beim Umgang mit Kraftstoffen, Schmier- und Reinigungsmitteln sind strikt einzuhalten. Schutzkleidung ist zu tragen. Unfälle und Beinahe-Unfälle sind sofort zu melden.</p>

<h2>§ 9 — Mobiltelefon und private Nutzung</h2>
<p>Die private Nutzung von Mobiltelefonen ist auf Pausenzeiten beschränkt. Im Umgang mit Kraftstoffen ist die Nutzung aus Sicherheitsgründen generell verboten. Der private Gebrauch von Betriebsmitteln ist nicht gestattet.</p>

<h2>§ 10 — Vertraulichkeit</h2>
<p>Über betriebliche Angelegenheiten, Preise, Kundendaten und interne Vorgänge ist Stillschweigen zu wahren — auch nach Beendigung des Arbeitsverhältnisses.</p>

<h2>§ 11 — Verstöße</h2>
<p>Verstöße gegen diese Betriebsordnung können — je nach Schwere — zu einer schriftlichen Abmahnung oder zur fristlosen Kündigung führen.</p>
<br>
<p>Ich habe die Betriebsordnung gelesen, verstanden und erkläre mich damit einverstanden.</p>
<br>
<p>{{unterschrift_ort}}, den <strong>{{datum_heute}}</strong></p>
<br><br>
<p>________________________________<br><strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong><br>Unterschrift Mitarbeiter/in</p>
HTML;
    }

    private static function bodyNda(): string
    {
        return <<<'HTML'
<h2>Geheimhaltungsvereinbarung</h2>
<p>zwischen</p>
<p><strong>{{arbeitgeber_firma}}</strong><br>{{arbeitgeber_adresse}}<br>(nachfolgend „Arbeitgeber")</p>
<p>und</p>
<p><strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong><br>{{mitarbeiter_adresse}}<br>(nachfolgend „Mitarbeiter/in")</p>

<h2>§ 1 — Gegenstand</h2>
<p>Die/Der Mitarbeiter/in verpflichtet sich, alle im Rahmen des Beschäftigungsverhältnisses erlangten vertraulichen Informationen streng vertraulich zu behandeln und weder an Dritte weiterzugeben noch zu eigenem Vorteil zu nutzen.</p>

<h2>§ 2 — Vertrauliche Informationen</h2>
<p>Als vertraulich gelten insbesondere:</p>
<ul>
<li>Kundendaten, Kundenbeziehungen und Kundenkonditionen</li>
<li>Preise, Rabatte, Lieferantenbeziehungen und Geschäftsstrategien</li>
<li>Betriebliche Abläufe, Systeme und Zugangsdaten</li>
<li>Personaldaten anderer Mitarbeiterinnen und Mitarbeiter</li>
<li>Finanzielle Informationen und Umsatzdaten</li>
<li>Alle als „vertraulich" oder „intern" gekennzeichneten Informationen</li>
</ul>

<h2>§ 3 — Dauer der Verpflichtung</h2>
<p>Die Geheimhaltungspflicht gilt sowohl während als auch nach Beendigung des Arbeitsverhältnisses, zeitlich unbegrenzt.</p>

<h2>§ 4 — Rückgabe und Löschung</h2>
<p>Bei Beendigung des Arbeitsverhältnisses sind alle vertraulichen Unterlagen und Datenträger vollständig zurückzugeben oder datenschutzkonform zu vernichten.</p>

<h2>§ 5 — Rechtsfolgen bei Verstößen</h2>
<p>Verstöße gegen diese Vereinbarung berechtigen den Arbeitgeber zur außerordentlichen Kündigung und zur Geltendmachung von Schadensersatzansprüchen gemäß § 17 UWG (Geschäftsgeheimnis) sowie §§ 823 ff. BGB.</p>

<h2>§ 6 — Salvatorische Klausel</h2>
<p>Sollten einzelne Bestimmungen dieser Vereinbarung unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen davon unberührt.</p>
<br>
<p>{{unterschrift_ort}}, den <strong>{{datum_heute}}</strong></p>
<br>
<table width="100%" style="border:none;">
<tr>
<td width="48%" style="border:none;vertical-align:bottom;">
<p>________________________________<br><strong>{{arbeitgeber_name}}</strong><br>{{arbeitgeber_firma}}<br>(Arbeitgeber)</p>
</td>
<td width="4%" style="border:none;">&nbsp;</td>
<td width="48%" style="border:none;vertical-align:bottom;">
<p>________________________________<br><strong>{{mitarbeiter_vorname}} {{mitarbeiter_nachname}}</strong><br>Mitarbeiter/in</p>
</td>
</tr>
</table>
HTML;
    }

    private static function bodySchluesseluebergabe(): string
    {
        return <<<'HTML'
<h2>Schlüsselübergabe-Protokoll</h2>

<table width="100%" style="border-collapse:collapse;margin-bottom:20px;">
<tr>
<td style="padding:6px 12px;background:#f1f5f9;font-weight:bold;width:35%;border:1px solid #cbd5e1;">Mitarbeiter/in:</td>
<td style="padding:6px 12px;border:1px solid #cbd5e1;"><strong>{{mitarbeiter_name}}</strong></td>
</tr>
<tr>
<td style="padding:6px 12px;background:#f1f5f9;font-weight:bold;border:1px solid #cbd5e1;">Station:</td>
<td style="padding:6px 12px;border:1px solid #cbd5e1;">{{station_name}}</td>
</tr>
<tr>
<td style="padding:6px 12px;background:#f1f5f9;font-weight:bold;border:1px solid #cbd5e1;">Datum der Ausgabe:</td>
<td style="padding:6px 12px;border:1px solid #cbd5e1;"><strong>{{ausgabe_datum}}</strong> um {{ausgabe_uhrzeit}}</td>
</tr>
<tr>
<td style="padding:6px 12px;background:#f1f5f9;font-weight:bold;border:1px solid #cbd5e1;">Rückgabedatum:</td>
<td style="padding:6px 12px;border:1px solid #cbd5e1;">{{rueckgabe_datum}}</td>
</tr>
</table>

<h2>Übergebener Schlüssel</h2>

<table width="100%" style="border-collapse:collapse;margin-bottom:20px;">
<thead>
<tr style="background:#1e3a5f;color:#ffffff;">
<th style="padding:8px 12px;text-align:left;border:1px solid #1e3a5f;">Bezeichnung</th>
<th style="padding:8px 12px;text-align:left;border:1px solid #1e3a5f;">Typ / Kategorie</th>
<th style="padding:8px 12px;text-align:center;border:1px solid #1e3a5f;">Anzahl</th>
</tr>
</thead>
<tbody>
<tr>
<td style="padding:8px 12px;border:1px solid #cbd5e1;"><strong>{{schluessel_name}}</strong></td>
<td style="padding:8px 12px;border:1px solid #cbd5e1;">{{schluessel_typ}}</td>
<td style="padding:8px 12px;text-align:center;border:1px solid #cbd5e1;">1</td>
</tr>
</tbody>
</table>

<h2>Vereinbarungen</h2>
<ul>
<li>Die/Der Mitarbeiter/in bestätigt den ordnungsgemäßen Erhalt des oben genannten Schlüssels.</li>
<li>Der Schlüssel ist ausschließlich für dienstliche Zwecke zu verwenden.</li>
<li>Der Schlüssel ist sorgfältig zu behandeln und vor Verlust oder Diebstahl zu schützen.</li>
<li>Bei Verlust oder Diebstahl ist die Betriebsleitung <strong>unverzüglich</strong> zu informieren. Die Kosten für notwendige Schlossaustausche und neue Schlüssel trägt die/der Mitarbeiter/in.</li>
<li>Der Schlüssel ist nicht zu kopieren und Dritten nicht zu überlassen.</li>
<li>Bei Beendigung des Arbeitsverhältnisses ist der Schlüssel am letzten Arbeitstag zurückzugeben.</li>
</ul>

<p>{{unterschrift_ort}}, den <strong>{{datum_heute}}</strong></p>
<br>
<table width="100%" style="border:none;">
<tr>
<td width="48%" style="border:none;vertical-align:bottom;">
<p>________________________________<br><strong>{{arbeitgeber_name}}</strong><br>{{arbeitgeber_firma}}<br>(Übergeber/in)</p>
</td>
<td width="4%" style="border:none;">&nbsp;</td>
<td width="48%" style="border:none;vertical-align:bottom;">
<p>________________________________<br><strong>{{mitarbeiter_name}}</strong><br>Empfänger/in</p>
</td>
</tr>
</table>
HTML;
    }
}
