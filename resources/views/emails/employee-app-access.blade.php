<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Zugang zum App-Panel – StationPilot</title>
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
                  <span style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:1px;">⛽ StationPilot</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Karte --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            {{-- Header --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:40px 40px 36px;text-align:center;">
                  <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:50%;margin:0 auto 16px;display:inline-block;line-height:64px;font-size:28px;">🖥️</div>
                  <h1 style="color:#ffffff;margin:0 0 6px;font-size:24px;font-weight:700;">App-Panel Zugang</h1>
                  <p style="color:#bfdbfe;margin:0;font-size:14px;">StationPilot Mitarbeiter-Portal</p>
                </td>
              </tr>
            </table>

            {{-- Body --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:36px 40px;">

                  <p style="margin:0 0 8px;font-size:16px;color:#64748b;">Guten Tag,</p>
                  <p style="margin:0 0 24px;font-size:22px;font-weight:700;color:#0f172a;">
                    {{ $user->first_name }} {{ $user->last_name }}
                  </p>

                  <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                    Sie haben Zugang zum <strong style="color:#1e3a8a;">StationPilot App-Panel</strong> erhalten.
                    Mit Ihren Zugangsdaten können Sie sich ab sofort einloggen.
                  </p>

                  {{-- Zugangsdaten-Box --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;padding:20px 24px;">
                        <p style="margin:0 0 14px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;">Ihre Zugangsdaten</p>
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="padding:6px 0;font-size:14px;color:#64748b;width:130px;">🌐 Login-Adresse:</td>
                            <td style="padding:6px 0;font-size:14px;color:#1e3a8a;">
                              <a href="{{ url('/app') }}" style="color:#2563eb;">{{ url('/app') }}</a>
                            </td>
                          </tr>
                          <tr>
                            <td style="padding:6px 0;font-size:14px;color:#64748b;">📧 E-Mail:</td>
                            <td style="padding:6px 0;font-size:14px;font-weight:600;color:#0f172a;">{{ $user->email }}</td>
                          </tr>
                          <tr>
                            <td style="padding:6px 0;font-size:14px;color:#64748b;">🔑 Passwort:</td>
                            <td style="padding:6px 0;">
                              <span style="font-family:monospace;font-size:15px;font-weight:700;background:#fef9c3;padding:4px 10px;border-radius:6px;color:#854d0e;letter-spacing:1px;">{{ $plainPassword }}</span>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  {{-- CTA --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td align="center">
                        <a href="{{ url('/app') }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);color:#ffffff;text-decoration:none;padding:16px 48px;border-radius:10px;font-size:16px;font-weight:700;box-shadow:0 4px 12px rgba(37,99,235,.4);">
                          Jetzt einloggen →
                        </a>
                      </td>
                    </tr>
                  </table>

                  {{-- Sicherheitshinweis --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    <tr>
                      <td style="background-color:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#9a3412;">
                          ⚠️ Bitte ändern Sie Ihr Passwort nach dem ersten Login. Geben Sie Ihre Zugangsdaten nicht weiter.
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
            <p style="margin:0;font-size:12px;color:#cbd5e1;">&copy; {{ date('Y') }} StationPilot · Alle Rechte vorbehalten</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
