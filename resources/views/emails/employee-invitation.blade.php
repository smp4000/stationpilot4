<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einladung zur Dateneingabe – StationPilot</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

        {{-- ── Logo-Bereich ────────────────────────────────────── --}}
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

        {{-- ── Haupt-Card ──────────────────────────────────────── --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            {{-- Header-Gradient --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:40px 40px 36px;text-align:center;">
                  <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:50%;margin:0 auto 16px;display:inline-block;line-height:64px;font-size:28px;">👤</div>
                  <h1 style="color:#ffffff;margin:0 0 6px;font-size:24px;font-weight:700;">Willkommen bei StationPilot</h1>
                  <p style="color:#bfdbfe;margin:0;font-size:14px;">{{ $employee->station->name ?? 'Ihre Tankstelle' }}</p>
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
                    Sie wurden von <strong style="color:#1e3a8a;">{{ $employee->station->name ?? 'Ihrer Tankstelle' }}</strong>
                    eingeladen, Ihre Mitarbeiterdaten im StationPilot-System zu erfassen.
                    Der Vorgang dauert nur wenige Minuten.
                  </p>

                  {{-- Info-Kacheln --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#f8fafc;border-radius:10px;padding:16px;border:1px solid #e2e8f0;">
                        <p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Was wird abgefragt?</p>
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="50%" style="padding:4px 0;font-size:14px;color:#334155;">✔&nbsp; Persönliche Stammdaten</td>
                            <td width="50%" style="padding:4px 0;font-size:14px;color:#334155;">✔&nbsp; Anschrift</td>
                          </tr>
                          <tr>
                            <td style="padding:4px 0;font-size:14px;color:#334155;">✔&nbsp; Kontaktdaten</td>
                            <td style="padding:4px 0;font-size:14px;color:#334155;">✔&nbsp; Bankverbindung</td>
                          </tr>
                          <tr>
                            <td colspan="2" style="padding:4px 0;font-size:14px;color:#334155;">✔&nbsp; Sozialversicherungsnummer</td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  {{-- CTA-Button --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td align="center">
                        <a href="{{ route('employee.invitation.show', $employee->invitation_token) }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);color:#ffffff;text-decoration:none;padding:16px 48px;border-radius:10px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(37,99,235,0.4);">
                          Jetzt Daten eingeben →
                        </a>
                      </td>
                    </tr>
                  </table>

                  {{-- Fallback-Link --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#fafafa;border:1px dashed #cbd5e1;border-radius:8px;padding:14px 16px;">
                        <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:</p>
                        <p style="margin:0;font-size:12px;color:#2563eb;word-break:break-all;">{{ route('employee.invitation.show', $employee->invitation_token) }}</p>
                      </td>
                    </tr>
                  </table>

                  {{-- Ablauf-Hinweis --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="background-color:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#9a3412;">
                          ⏱ Dieser Link ist <strong>7 Tage gültig</strong> und läuft am
                          <strong>{{ $employee->invitation_expires_at?->format('d.m.Y') }}</strong> ab.
                        </p>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0 0 4px;font-size:15px;color:#475569;">Mit freundlichen Grüßen</p>
                  <p style="margin:0;font-size:15px;font-weight:600;color:#1e293b;">Ihr StationPilot-Team</p>

                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- ── Footer ──────────────────────────────────────────── --}}
        <tr>
          <td style="padding:20px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese Nachricht.</p>
            <p style="margin:0;font-size:12px;color:#cbd5e1;">&copy; {{ date('Y') }} StationPilot · Alle Rechte vorbehalten</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
