<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Zugang zu StationPilot</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 16px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

        {{-- Logo --}}
        <tr>
          <td align="center" style="padding-bottom:24px;">
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="background-color:#1e3a8a;border-radius:12px;padding:10px 20px;">
                  <span style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:1px;">&#9981; StationPilot</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Haupt-Card --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            {{-- Header-Gradient --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:40px 40px 36px;text-align:center;">
                  <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:50%;margin:0 auto 16px;display:inline-block;line-height:64px;font-size:28px;">&#128274;</div>
                  <h1 style="color:#ffffff;margin:0 0 6px;font-size:24px;font-weight:700;">Ihr Zugang zu StationPilot</h1>
                  <p style="color:#bfdbfe;margin:0;font-size:14px;">Mitarbeiter-Portal</p>
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
                    Ihr Zugang zum <strong style="color:#1e3a8a;">StationPilot Mitarbeiter-Portal</strong> wurde eingerichtet.
                    Mit den folgenden Zugangsdaten können Sie sich jederzeit einloggen.
                  </p>

                  {{-- Zugangsdaten --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#f8fafc;border-radius:10px;padding:20px 24px;border:1px solid #e2e8f0;">
                        <p style="margin:0 0 16px;font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Ihre Zugangsdaten</p>

                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="padding:6px 0;font-size:13px;color:#64748b;width:140px;">Login-Adresse:</td>
                            <td style="padding:6px 0;">
                              <a href="{{ route('employee.portal.login') }}" style="color:#2563eb;font-size:14px;font-weight:600;text-decoration:none;">{{ route('employee.portal.login') }}</a>
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:6px 0;font-size:13px;color:#64748b;">Benutzername:</td>
                            <td style="padding:6px 0;font-size:14px;font-weight:600;color:#0f172a;">{{ $employee->email }}</td>
                          </tr>
                          <tr>
                            <td style="padding:6px 0;font-size:13px;color:#64748b;">Temporäres Passwort:</td>
                            <td style="padding:6px 0;">
                              <span style="background:#1e3a8a;color:#ffffff;font-size:15px;font-weight:700;letter-spacing:2px;padding:4px 12px;border-radius:6px;font-family:monospace;">{{ $plainPassword }}</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  {{-- CTA-Button --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td align="center">
                        <a href="{{ route('employee.portal.login') }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);color:#ffffff;text-decoration:none;padding:16px 48px;border-radius:10px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(37,99,235,0.4);">
                          Jetzt einloggen &rarr;
                        </a>
                      </td>
                    </tr>
                  </table>

                  {{-- Hinweis Passwort ändern --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="background-color:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#9a3412;">
                          <strong>Wichtig:</strong> Bitte ändern Sie Ihr Passwort nach dem ersten Login.
                          Sie werden automatisch dazu aufgefordert.
                        </p>
                      </td>
                    </tr>
                  </table>

                  {{-- Kein Ablauf-Hinweis --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="background-color:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#166534;">
                          Ihr Zugang ist dauerhaft gültig und läuft nicht ab.
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

        {{-- Footer --}}
        <tr>
          <td style="padding:20px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:12px;color:#94a3b8;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese Nachricht.</p>
            <p style="margin:0;font-size:12px;color:#cbd5e1;">&copy; {{ date('Y') }} StationPilot &middot; Alle Rechte vorbehalten</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
