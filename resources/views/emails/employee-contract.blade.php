<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ihr Arbeitsvertrag – StationPilot</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

        {{-- ── Logo ──────────────────────────────────────────────── --}}
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="background-color:#1e3a8a;border-radius:12px;padding:10px 20px;">
                  <span style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:1px;">⛽ StationPilot</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- ── Haupt-Card ──────────────────────────────────────────── --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            {{-- Header --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:40px 40px 36px;text-align:center;">
                  <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:50%;margin:0 auto 16px;line-height:64px;font-size:30px;display:inline-block;">📄</div>
                  <h1 style="color:#ffffff;margin:0 0 6px;font-size:24px;font-weight:700;">Ihr Arbeitsvertrag</h1>
                  <p style="color:#bfdbfe;margin:0;font-size:14px;">{{ $contract->employer_company }}</p>
                </td>
              </tr>
            </table>

            {{-- Body --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:36px 40px;">

                  <p style="margin:0 0 8px;font-size:16px;color:#64748b;">Guten Tag,</p>
                  <p style="margin:0 0 24px;font-size:22px;font-weight:700;color:#0f172a;">{{ $employee->first_name }} {{ $employee->last_name }}</p>

                  <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                    Ihr Arbeitsvertrag wurde erstellt und steht zur digitalen Unterzeichnung bereit.
                    Bitte öffnen Sie den Link, lesen Sie den Vertrag sorgfältig und unterschreiben Sie ihn digital.
                  </p>

                  {{-- Vertragsdetails --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#f8fafc;border-radius:10px;padding:20px;border:1px solid #e2e8f0;">
                        <p style="margin:0 0 14px;font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Vertragsdetails</p>
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="padding:5px 0;font-size:13px;color:#94a3b8;width:44%;">Vertragsart</td>
                            <td style="padding:5px 0;font-size:14px;font-weight:600;color:#1e293b;">{{ $contract->contractTypeLabel() }}</td>
                          </tr>
                          <tr>
                            <td style="padding:5px 0;font-size:13px;color:#94a3b8;">Arbeitgeber</td>
                            <td style="padding:5px 0;font-size:14px;font-weight:600;color:#1e293b;">{{ $contract->employer_company }}</td>
                          </tr>
                          @if($contract->employment_start)
                          <tr>
                            <td style="padding:5px 0;font-size:13px;color:#94a3b8;">Beginn</td>
                            <td style="padding:5px 0;font-size:14px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($contract->employment_start)->format('d.m.Y') }}</td>
                          </tr>
                          @endif
                          @if($contract->employment_end)
                          <tr>
                            <td style="padding:5px 0;font-size:13px;color:#94a3b8;">Befristet bis</td>
                            <td style="padding:5px 0;font-size:14px;font-weight:600;color:#1e293b;">{{ \Carbon\Carbon::parse($contract->employment_end)->format('d.m.Y') }}</td>
                          </tr>
                          @endif
                          <tr>
                            <td style="padding:5px 0;font-size:13px;color:#94a3b8;">Erstellt am</td>
                            <td style="padding:5px 0;font-size:14px;font-weight:600;color:#1e293b;">{{ $contract->created_at->format('d.m.Y') }}</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  {{-- CTA-Button --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td align="center">
                        <a href="{{ $signUrl }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);color:#ffffff;text-decoration:none;padding:16px 48px;border-radius:10px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(37,99,235,0.35);">
                          Vertrag ansehen &amp; unterschreiben →
                        </a>
                      </td>
                    </tr>
                  </table>

                  {{-- Hinweis-Box --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="background-color:#eff6ff;border-left:4px solid #2563eb;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#1e40af;">
                          ℹ️ Dieser Link ist personalisiert und gilt nur für Sie. Bitte teilen Sie ihn nicht mit anderen Personen.
                        </p>
                      </td>
                    </tr>
                  </table>

                  {{-- Fallback-Link --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#fafafa;border:1px dashed #cbd5e1;border-radius:8px;padding:14px 16px;">
                        <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:</p>
                        <p style="margin:0;font-size:12px;color:#2563eb;word-break:break-all;">{{ $signUrl }}</p>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0 0 4px;font-size:15px;color:#475569;">Mit freundlichen Grüßen</p>
                  <p style="margin:0;font-size:15px;font-weight:600;color:#1e293b;">{{ $contract->employer_name }}<br>
                    <span style="font-weight:400;color:#64748b;">{{ $contract->employer_company }}</span>
                  </p>

                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- ── Footer ─────────────────────────────────────────────── --}}
        <tr>
          <td style="padding:20px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Nachricht.</p>
            <p style="margin:0;font-size:12px;color:#cbd5e1;">&copy; {{ date('Y') }} StationPilot &middot; Alle Rechte vorbehalten</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
