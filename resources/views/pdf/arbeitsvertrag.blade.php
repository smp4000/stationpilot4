<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size:10.5px; color:#1a1a1a; line-height:1.6; }

  .header { padding:20px 30px 14px; border-bottom:2px solid #1e3a5f; margin-bottom:18px; }
  .header h1 { font-size:18px; font-weight:bold; color:#1e3a5f; }
  .header .sub { font-size:10px; color:#64748b; margin-top:3px; }

  .body { padding:0 30px; }

  .parties { margin-bottom:20px; }
  .parties table { width:100%; border-collapse:collapse; }
  .parties td { padding:3px 8px; vertical-align:top; }
  .parties td.label { width:140px; color:#5a6a82; font-size:9.5px; }
  .parties td.val { font-weight:500; }

  /* Contract body paragraphs from DB template */
  h2 { font-size:11px; font-weight:bold; color:#1e3a5f;
       border-bottom:1px solid #dde3ec; padding-bottom:3px;
       margin-top:14px; margin-bottom:6px; }
  h3 { font-size:10.5px; font-weight:bold; color:#334155;
       margin-top:10px; margin-bottom:4px; }
  p  { margin-bottom:5px; }
  ol { padding-left:18px; margin-bottom:8px; }
  ol li { margin-bottom:4px; }
  ul { padding-left:18px; margin-bottom:8px; }
  ul li { margin-bottom:3px; }
  strong { font-weight:bold; }

  .signature-block { margin-top:30px; page-break-inside:avoid; }
  .sig-table { width:100%; border-collapse:collapse; }
  .sig-table td { width:45%; vertical-align:bottom; padding-top:40px; }
  .sig-table td.gap { width:10%; }
  .sig-line { border-top:1px solid #1a1a1a; padding-top:5px; font-size:9px; color:#5a6a82; }
  .sig-img { max-height:50px; max-width:180px; display:block; margin-bottom:4px; }

  .footer { position:fixed; bottom:0; left:0; right:0; padding:6px 30px;
            border-top:1px solid #dde3ec; font-size:8px; color:#94a3b8;
            background:#fff; display:table; width:100%; }
  .footer .fl { display:table-cell; text-align:left; }
  .footer .fr { display:table-cell; text-align:right; }

  .page-break { page-break-before:always; }
</style>
</head>
<body>

<div class="footer">
  <div class="fl">Arbeitsvertrag — {{ $employee->first_name }} {{ $employee->last_name }} — Vertraulich</div>
  <div class="fr">Erstellt am {{ now()->format('d.m.Y') }}</div>
</div>

<div class="header">
  <h1>
    @if($contract->contract_type === 'minijob')
      Arbeitsvertrag für geringfügig entlohnte Beschäftigte
    @elseif($contract->contract_type === 'befristet')
      Arbeitsvertrag (befristet)
    @else
      Arbeitsvertrag
    @endif
  </h1>
  <div class="sub">{{ $contract->contractTypeLabel() }}</div>
</div>

<div class="body">

{{-- ── Vertragsparteien ──────────────────────────────────────── --}}
<div class="parties">
  <p style="margin-bottom:8px;">Zwischen</p>
  <table>
    <tr>
      <td class="label">Name:</td>
      <td class="val">{{ $contract->employer_name }}</td>
    </tr>
    <tr>
      <td class="label">Betrieb:</td>
      <td class="val">{{ $contract->employer_company }}</td>
    </tr>
    <tr>
      <td class="label">Adresse:</td>
      <td class="val">{{ $contract->employer_street }}, {{ $contract->employer_zip }} {{ $contract->employer_city }}</td>
    </tr>
  </table>
  <p style="margin:8px 0;color:#5a6a82;font-size:9.5px;">(nachfolgend „Arbeitgeber")</p>

  <p style="margin-bottom:8px;">und</p>
  <table>
    <tr>
      <td class="label">Name:</td>
      <td class="val">{{ $employee->first_name }} {{ $employee->last_name }}</td>
    </tr>
    @if($employee->street)
    <tr>
      <td class="label">Adresse:</td>
      <td class="val">{{ $employee->street }} {{ $employee->house_number }}, {{ $employee->zip }} {{ $employee->city }}</td>
    </tr>
    @endif
    @if($employee->date_of_birth)
    <tr>
      <td class="label">Geburtsdatum:</td>
      <td class="val">{{ \Carbon\Carbon::parse($employee->date_of_birth)->format('d.m.Y') }}</td>
    </tr>
    @endif
  </table>
  <p style="margin-top:8px;color:#5a6a82;font-size:9.5px;">(nachfolgend „Arbeitnehmer" / „Arbeitnehmerin")</p>
  <p style="margin-top:10px;margin-bottom:16px;">wird folgender Arbeitsvertrag geschlossen:</p>
</div>

{{-- ── Vertragsinhalt aus DB-Vorlage ────────────────────────── --}}
{!! $bodyHtml !!}

{{-- ── Unterschriften ─────────────────────────────────────────── --}}
<div class="signature-block">
  <table class="sig-table">
    <tr>
      <td>
        @if($contract->employee_signed_at)
          <p style="font-size:9px;color:#059669;margin-bottom:4px;">
            ✓ Digital unterzeichnet am {{ $contract->employee_signed_at->format('d.m.Y H:i') }} Uhr
          </p>
          @if($contract->employee_signature)
            <p style="font-size:10px;font-style:italic;margin-bottom:4px;">{{ $contract->employee_signature }}</p>
          @endif
        @endif
        <div class="sig-line">
          {{ $contract->signing_location }}, den
          {{ $contract->employee_signed_at ? $contract->employee_signed_at->format('d.m.Y') : '________________' }}
          <br><br>
          {{ $employee->first_name }} {{ $employee->last_name }} (Arbeitnehmer/in)
        </div>
      </td>
      <td class="gap"></td>
      <td>
        @if($contract->employer_signed_at)
          <p style="font-size:9px;color:#059669;margin-bottom:4px;">
            ✓ Digital unterzeichnet am {{ $contract->employer_signed_at->format('d.m.Y H:i') }} Uhr
          </p>
          @if($contract->employer_signature)
            <p style="font-size:10px;font-style:italic;margin-bottom:4px;">{{ $contract->employer_signature }}</p>
          @endif
        @endif
        <div class="sig-line">
          {{ $contract->signing_location }}, den
          {{ $contract->employer_signed_at ? $contract->employer_signed_at->format('d.m.Y') : '________________' }}
          <br><br>
          {{ $contract->employer_name }} (Arbeitgeber)
        </div>
      </td>
    </tr>
  </table>
</div>

</div>{{-- /body --}}

{{-- ── Anlage 1: Stellenbeschreibung ──────────────────────── --}}
@php $d = $contract->contract_data ?? []; @endphp
@if(($d['job_description_type'] ?? 'none') !== 'none')
<div class="page-break"></div>
<div class="header">
  <h1>Anlage 1 zum Arbeitsvertrag</h1>
  <div class="sub">Stellenbeschreibung — {{ $employee->first_name }} {{ $employee->last_name }}</div>
</div>
<div class="body">
  <p style="margin-bottom:10px;"><strong>Position:</strong> {{ $d['job_title'] ?? '' }}</p>
  <p style="margin-bottom:10px;"><strong>Arbeitgeber:</strong> {{ $contract->employer_company }}, {{ $contract->employer_name }}</p>
  <p style="margin-bottom:14px;"><strong>Arbeitsort:</strong> {{ $d['work_location'] ?? '' }}</p>

  @if(($d['job_description_type'] ?? '') === 'custom' && !empty($d['job_description_custom']))
    <h2>Tätigkeitsbeschreibung</h2>
    <p>{{ $d['job_description_custom'] }}</p>
  @else
    <h2>Hauptaufgaben</h2>
    <ol>
      <li>Bedienung der Registrierkasse und Durchführung aller Kassenvorgänge; Annahme von Bar-, EC- und Kreditkartenzahlungen</li>
      <li>Freundliche und kompetente Kundenberatung und -betreuung; Bearbeitung von Reklamationen</li>
      <li>Überwachung des Tankstellenbetriebs; Freischaltung der Zapfsäulen; Kontrolle der Kraftstoffbestände</li>
      <li>Verkauf von Waren; Warenannahme und Einlagerung; Preisauszeichnung und Warenpräsentation</li>
      <li>Zubereitung und Verkauf von Speisen nach Hygienevorschriften (HACCP)</li>
      <li>Tägliche Kassenabrechnung; Sauberkeit im Verkaufsbereich; Öffnung und Schließung nach Dienstplan</li>
    </ol>
    <h2>Sicherheit und Compliance</h2>
    <ol>
      <li>Einhaltung aller Sicherheitsvorschriften im Tankstellenbetrieb</li>
      <li>Beachtung der Jugendschutzbestimmungen beim Verkauf (Alkohol, Tabak, Lotto)</li>
      <li>Kassenverantwortung: Entstandene Schäden sind zu ersetzen</li>
      <li>Schutz vor Trickbetrug: Cash-Codes über das E-Pay Terminal dürfen nie telefonisch herausgegeben werden</li>
    </ol>
  @endif

  <p style="margin-top:14px;font-size:9px;color:#64748b;">
    Diese Stellenbeschreibung ist Bestandteil des Arbeitsvertrags und kann bei betrieblichen Erfordernissen nach Absprache angepasst werden.
  </p>
</div>
@endif

</body>
</html>
