<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

  .header { background: #1e3a5f; color: #fff; padding: 16px 24px; margin-bottom: 20px; }
  .header h1 { font-size: 16px; font-weight: bold; letter-spacing: 0.5px; }
  .header .sub { font-size: 10px; opacity: 0.8; margin-top: 4px; }
  .header .meta { font-size: 9px; opacity: 0.7; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 6px; }

  .section { margin: 0 24px 16px; border: 1px solid #dde3ec; border-radius: 4px; overflow: hidden; }
  .section-title { background: #eef2f8; color: #1e3a5f; font-weight: bold; font-size: 10px;
                   padding: 6px 10px; border-bottom: 1px solid #dde3ec; text-transform: uppercase;
                   letter-spacing: 0.5px; }
  .section-body { padding: 10px; }

  table.fields { width: 100%; border-collapse: collapse; }
  table.fields td { padding: 4px 6px; vertical-align: top; }
  table.fields td.label { color: #5a6a82; width: 38%; font-size: 9px; }
  table.fields td.value { color: #1a1a1a; font-weight: 500; }
  table.fields tr:nth-child(even) td { background: #f8fafc; }

  .cols2 { display: table; width: 100%; }
  .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
  .col:last-child { padding-right: 0; padding-left: 10px; }

  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .badge-green  { background: #d1fae5; color: #065f46; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-gray   { background: #f1f5f9; color: #475569; }
  .badge-blue   { background: #dbeafe; color: #1e40af; }
  .badge-amber  { background: #fef3c7; color: #92400e; }

  .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 24px;
            border-top: 1px solid #dde3ec; font-size: 8px; color: #94a3b8;
            background: #fff; display: table; width: 100%; }
  .footer .fl { display: table-cell; text-align: left; }
  .footer .fr { display: table-cell; text-align: right; }

  .no-value { color: #94a3b8; font-style: italic; }
  .encrypted { color: #64748b; }
</style>
</head>
<body>

<div class="footer">
  <div class="fl">Vertraulich — nur für internen Gebrauch</div>
  <div class="fr">Erstellt am {{ now()->format('d.m.Y H:i') }} Uhr</div>
</div>

<div class="header">
  <h1>Mitarbeiterdaten</h1>
  <div class="sub">{{ $employee->first_name }} {{ $employee->last_name }}{{ $employee->birth_name ? ' (geb. ' . $employee->birth_name . ')' : '' }}</div>
  <div class="meta">
    Station: {{ $employee->station?->name ?? '—' }}
    &nbsp;·&nbsp;
    Status:
    @switch($employee->status)
      @case('aktiv') Aktiv @break
      @case('inaktiv') Inaktiv @break
      @case('eingeladen') Eingeladen @break
      @default Neu
    @endswitch
    &nbsp;·&nbsp;
    Eintrittsdatum: {{ $employee->employment_start?->format('d.m.Y') ?? '—' }}
  </div>
</div>

{{-- ── Stammdaten ────────────────────────────────────────────── --}}
<div class="section">
  <div class="section-title">Stammdaten</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Vorname</td>
        <td class="value">{{ $employee->first_name }}</td>
        <td class="label">Nachname</td>
        <td class="value">{{ $employee->last_name }}</td>
      </tr>
      @if($employee->birth_name)
      <tr>
        <td class="label">Geburtsname</td>
        <td class="value" colspan="3">{{ $employee->birth_name }}</td>
      </tr>
      @endif
      <tr>
        <td class="label">Geburtsdatum</td>
        <td class="value">{{ $employee->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('d.m.Y') : '—' }}</td>
        <td class="label">Geburtsort</td>
        <td class="value">{{ $employee->place_of_birth ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Geburtsland</td>
        <td class="value">{{ $employee->country_of_birth ?? '—' }}</td>
        <td class="label">Staatsangehörigkeit</td>
        <td class="value">{{ $employee->nationality ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Geschlecht</td>
        <td class="value">
          @php $gender = ['maennlich'=>'Männlich','weiblich'=>'Weiblich','divers'=>'Divers','keine_angabe'=>'Keine Angabe']; @endphp
          {{ $gender[$employee->gender] ?? ($employee->gender ?? '—') }}
        </td>
        <td class="label">Familienstand</td>
        <td class="value">
          @php $ms = ['ledig'=>'Ledig','verheiratet'=>'Verheiratet','geschieden'=>'Geschieden','verwitwet'=>'Verwitwet','eingetragene_partnerschaft'=>'Eingetr. Partnerschaft']; @endphp
          {{ $ms[$employee->marital_status] ?? ($employee->marital_status ?? '—') }}
        </td>
      </tr>
      @if($employee->severely_disabled)
      <tr>
        <td class="label">Schwerbehinderung</td>
        <td class="value" colspan="3">Ja{{ $employee->disability_degree ? ' — Grad: ' . $employee->disability_degree . '%' : '' }}</td>
      </tr>
      @endif
    </table>
  </div>
</div>

{{-- ── Anschrift & Kontakt ───────────────────────────────────── --}}
<div class="section">
  <div class="section-title">Anschrift &amp; Kontakt</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Straße / Hausnr.</td>
        <td class="value" colspan="3">
          {{ $employee->street ?? '—' }}{{ $employee->house_number ? ' ' . $employee->house_number : '' }}
        </td>
      </tr>
      <tr>
        <td class="label">PLZ / Ort</td>
        <td class="value">{{ $employee->zip ?? '' }} {{ $employee->city ?? '—' }}</td>
        <td class="label">Land</td>
        <td class="value">{{ $employee->country ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Telefon privat</td>
        <td class="value">{{ $employee->phone_private ?? '—' }}</td>
        <td class="label">Mobil</td>
        <td class="value">{{ $employee->phone_mobile ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">E-Mail</td>
        <td class="value" colspan="3">{{ $employee->email ?? '—' }}</td>
      </tr>
    </table>
  </div>
</div>

{{-- ── Beschäftigung ─────────────────────────────────────────── --}}
<div class="section">
  <div class="section-title">Beschäftigung</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Station</td>
        <td class="value">{{ $employee->station?->name ?? '—' }}</td>
        <td class="label">Berufsbezeichnung</td>
        <td class="value">{{ $employee->job_title ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Beschäftigungsart</td>
        <td class="value">
          @php $et = ['vollzeit'=>'Vollzeit','teilzeit'=>'Teilzeit','minijob'=>'Minijob','kurzfristig'=>'Kurzfristig','azubi'=>'Azubi','praktikum'=>'Praktikum','werkstudent'=>'Werkstudent']; @endphp
          {{ $et[$employee->employment_type] ?? ($employee->employment_type ?? '—') }}
        </td>
        <td class="label">Berufsstatus</td>
        <td class="value">{{ $employee->employee_status ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Eintrittsdatum</td>
        <td class="value">{{ $employee->employment_start?->format('d.m.Y') ?? '—' }}</td>
        <td class="label">Austrittsdatum</td>
        <td class="value">{{ $employee->employment_end?->format('d.m.Y') ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Wochenstunden</td>
        <td class="value">{{ $employee->weekly_hours ? $employee->weekly_hours . ' Std.' : '—' }}</td>
        <td class="label">Urlaubstage</td>
        <td class="value">{{ $employee->vacation_days ? $employee->vacation_days . ' Tage' : '—' }}</td>
      </tr>
      @if($employee->cost_center)
      <tr>
        <td class="label">Kostenstelle</td>
        <td class="value" colspan="3">{{ $employee->cost_center }}</td>
      </tr>
      @endif
    </table>
  </div>
</div>

{{-- ── Steuer & Sozialversicherung ──────────────────────────── --}}
<div class="section">
  <div class="section-title">Steuer &amp; Sozialversicherung</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Steuer-ID</td>
        <td class="value">{{ $employee->tax_id ?? '—' }}</td>
        <td class="label">Steuerklasse</td>
        <td class="value">{{ $employee->tax_class ? 'Klasse ' . $employee->tax_class : '—' }}</td>
      </tr>
      <tr>
        <td class="label">Kinderfreibeträge</td>
        <td class="value">{{ $employee->tax_child_allowance ?? '—' }}</td>
        <td class="label">Konfession</td>
        <td class="value">{{ $employee->church_tax ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">SV-Nummer</td>
        <td class="value">{{ $employee->social_security_number ?? '—' }}</td>
        <td class="label">Krankenkasse</td>
        <td class="value">{{ $employee->health_insurance_name ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">KV-Art</td>
        <td class="value">{{ $employee->health_insurance_type ?? '—' }}</td>
        <td class="label">RV / ALV</td>
        <td class="value">
          RV: {{ $employee->pension_insurance ? 'Ja' : 'Nein' }}
          &nbsp;·&nbsp;
          ALV: {{ $employee->unemployment_insurance ? 'Ja' : 'Nein' }}
        </td>
      </tr>
    </table>
  </div>
</div>

{{-- ── Vergütung & Bank ──────────────────────────────────────── --}}
<div class="section">
  <div class="section-title">Vergütung &amp; Bankverbindung</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Lohnart</td>
        <td class="value">{{ $employee->wage_type ?? '—' }}</td>
        <td class="label">Betrag</td>
        <td class="value">{{ $employee->wage_amount ? number_format((float)$employee->wage_amount, 2, ',', '.') . ' €' : '—' }}</td>
      </tr>
      <tr>
        <td class="label">Zahlungsweise</td>
        <td class="value">{{ $employee->payment_interval ? ucfirst($employee->payment_interval) : '—' }}</td>
        <td class="label">Kontoinhaber</td>
        <td class="value">{{ $employee->account_holder ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">IBAN</td>
        <td class="value">{{ $employee->iban ?? '—' }}</td>
        <td class="label">BIC</td>
        <td class="value">{{ $employee->bic ?? '—' }}</td>
      </tr>
      <tr>
        <td class="label">Geldinstitut</td>
        <td class="value" colspan="3">{{ $employee->bank_name ?? '—' }}</td>
      </tr>
    </table>
  </div>
</div>

{{-- ── Ausbildung & Führerschein ─────────────────────────────── --}}
<div class="section">
  <div class="section-title">Ausbildung &amp; Führerschein</div>
  <div class="section-body">
    <table class="fields">
      <tr>
        <td class="label">Schulabschluss</td>
        <td class="value">{{ $employee->education_level ?? '—' }}</td>
        <td class="label">Berufsausbildung</td>
        <td class="value">{{ $employee->vocational_training ?? '—' }}</td>
      </tr>
      @if($employee->vocational_title)
      <tr>
        <td class="label">Berufsbezeichnung Ausb.</td>
        <td class="value" colspan="3">{{ $employee->vocational_title }}</td>
      </tr>
      @endif
      <tr>
        <td class="label">Führerschein</td>
        <td class="value">
          @if($employee->has_driving_license)
            Ja — Klassen: {{ $employee->driving_license_classes ? implode(', ', $employee->driving_license_classes) : '—' }}
          @else
            Nein
          @endif
        </td>
        <td class="label">Führerschein-Nr.</td>
        <td class="value">{{ $employee->driving_license_number ?? '—' }}</td>
      </tr>
      @if($employee->has_driving_license)
      <tr>
        <td class="label">Ausgestellt</td>
        <td class="value">{{ $employee->driving_license_issued?->format('d.m.Y') ?? '—' }}</td>
        <td class="label">Gültig bis</td>
        <td class="value">{{ $employee->driving_license_expires?->format('d.m.Y') ?? '—' }}</td>
      </tr>
      @endif
    </table>
  </div>
</div>

{{-- ── Genehmigungen ─────────────────────────────────────────── --}}
@if($employee->residence_permit_type || $employee->work_permit_granted)
<div class="section">
  <div class="section-title">Genehmigungen</div>
  <div class="section-body">
    <table class="fields">
      @if($employee->residence_permit_type)
      <tr>
        <td class="label">Aufenthaltstitel</td>
        <td class="value">{{ $employee->residence_permit_type }}</td>
        <td class="label">Gültig bis</td>
        <td class="value">{{ $employee->residence_permit_expires?->format('d.m.Y') ?? '—' }}</td>
      </tr>
      @endif
      @if($employee->work_permit_granted)
      <tr>
        <td class="label">Arbeitserlaubnis</td>
        <td class="value">Erteilt</td>
        <td class="label">Ablaufdatum</td>
        <td class="value">{{ $employee->work_permit_expires?->format('d.m.Y') ?? '—' }}</td>
      </tr>
      @endif
    </table>
  </div>
</div>
@endif

{{-- ── Notfallkontakte ───────────────────────────────────────── --}}
@if($employee->emergencyContacts->isNotEmpty())
<div class="section">
  <div class="section-title">Notfallkontakte</div>
  <div class="section-body">
    <table class="fields">
      @foreach($employee->emergencyContacts as $contact)
      <tr>
        <td class="label">{{ $loop->iteration }}. Kontakt</td>
        <td class="value">{{ $contact->name }} ({{ $contact->relationship ?? '—' }})</td>
        <td class="label">Telefon</td>
        <td class="value">{{ $contact->phone }}{{ $contact->phone_mobile ? ' · ' . $contact->phone_mobile : '' }}</td>
      </tr>
      @endforeach
    </table>
  </div>
</div>
@endif

{{-- ── Schlüssel & Zugangsmedien ─────────────────────────────── --}}
<div class="section">
  <div class="section-title">Schlüssel &amp; Zugangsmedien — Übergabeprotokoll</div>
  <div class="section-body">
    @if($employee->keyHandovers->isNotEmpty())
    <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
      <thead>
        <tr style="background: #f1f5f9;">
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Art</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Bezeichnung</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Nummer</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Ausgegeben</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Zurückgegeben</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Empfang bestätigt</th>
          <th style="padding: 5px 6px; text-align: left; border-bottom: 1px solid #dde3ec; color: #5a6a82; font-weight: 600;">Rückgabe bestätigt</th>
        </tr>
      </thead>
      <tbody>
        @foreach($employee->keyHandovers->sortByDesc('handed_out_at') as $handover)
        @php
          $typeMap = ['schluessel'=>'Schlüssel','chip'=>'Chip (Alarm/Funk)','karte'=>'Zugangskarte','code'=>'Code/PIN','sonstiges'=>'Sonstiges'];
          $type = $typeMap[$handover->key?->type] ?? ($handover->key?->type ?? '—');
        @endphp
        <tr style="{{ $loop->even ? 'background:#f8fafc;' : '' }}">
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">{{ $type }}</td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9; font-weight: 500;">{{ $handover->key?->name ?? '—' }}</td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">{{ $handover->key?->key_number ?? '—' }}</td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">{{ $handover->handed_out_at?->format('d.m.Y H:i') ?? '—' }}</td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">
            @if($handover->returned_at)
              {{ $handover->returned_at->format('d.m.Y H:i') }}
            @elseif($handover->employee_returned_at)
              {{ $handover->employee_returned_at->format('d.m.Y') }}
            @else
              <span style="color:#dc2626; font-weight:600;">Noch ausgegeben</span>
            @endif
          </td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">
            @if($handover->receipt_signature)
              <img src="{{ $handover->receipt_signature }}" style="height:28px; max-width:90px; display:block;">
              <span style="color:#059669; font-size:8px;">{{ $handover->employee_confirmed_at?->format('d.m.Y') ?? '' }}</span>
            @elseif($handover->employee_confirmed_at)
              <span style="color:#059669;">✓ {{ $handover->employee_confirmed_at->format('d.m.Y') }}</span>
            @else
              <span style="color:#94a3b8;">—</span>
            @endif
          </td>
          <td style="padding: 4px 6px; border-bottom: 1px solid #f1f5f9;">
            @if($handover->return_signature)
              <img src="{{ $handover->return_signature }}" style="height:28px; max-width:90px; display:block;">
              <span style="color:#059669; font-size:8px;">{{ $handover->employee_returned_at?->format('d.m.Y') ?? '' }}</span>
            @elseif($handover->employee_returned_at)
              <span style="color:#059669;">✓ {{ $handover->employee_returned_at->format('d.m.Y') }}</span>
            @else
              <span style="color:#94a3b8;">—</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    {{-- Unterschrift Schlüsselübergabe --}}
    <div style="margin-top: 20px;">
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td style="width: 45%; padding-right: 20px; padding-top: 30px; vertical-align: bottom;">
            <div style="border-top: 1px solid #1a1a1a; padding-top: 6px; font-size: 9px; color: #5a6a82;">
              Ort, Datum &amp; Unterschrift Arbeitnehmer (Schlüsselempfang)
            </div>
          </td>
          <td style="width: 10%;"></td>
          <td style="width: 45%; padding-left: 20px; padding-top: 30px; vertical-align: bottom;">
            <div style="border-top: 1px solid #1a1a1a; padding-top: 6px; font-size: 9px; color: #5a6a82;">
              Ort, Datum &amp; Unterschrift Arbeitgeber (Schlüsselausgabe)
            </div>
          </td>
        </tr>
      </table>
    </div>
    @else
      <p style="color: #94a3b8; font-style: italic; font-size: 9px; padding: 4px 0;">Keine Schlüssel / Zugangsmedien hinterlegt.</p>
    @endif
  </div>
</div>

{{-- ── Unterschriften (Stammdaten) ───────────────────────────── --}}
<div style="margin: 20px 24px; padding-top: 16px;">
  <div style="font-size: 9px; color: #5a6a82; margin-bottom: 30px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
    Bestätigung Stammdaten
  </div>
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td style="width: 45%; padding-right: 20px; vertical-align: bottom;">
        <div style="border-top: 1px solid #1a1a1a; padding-top: 6px; font-size: 9px; color: #5a6a82;">
          Ort, Datum &amp; Unterschrift Arbeitnehmer
        </div>
      </td>
      <td style="width: 10%;"></td>
      <td style="width: 45%; padding-left: 20px; vertical-align: bottom;">
        <div style="border-top: 1px solid #1a1a1a; padding-top: 6px; font-size: 9px; color: #5a6a82;">
          Ort, Datum &amp; Unterschrift Arbeitgeber
        </div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
