<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ihre Onboarding-Dokumente – StationPilot</title>
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

        {{-- Haupt-Card --}}
        <tr>
          <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            {{-- Header --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:40px 40px 36px;text-align:center;">
                  <div style="font-size:40px;margin-bottom:12px;">📋</div>
                  <h1 style="color:#ffffff;margin:0 0 6px;font-size:22px;font-weight:700;">Ihre Onboarding-Dokumente</h1>
                  <p style="color:#bfdbfe;margin:0;font-size:14px;">Bitte alle Dokumente lesen und digital unterschreiben</p>
                </td>
              </tr>
            </table>

            {{-- Body --}}
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:36px 40px;">

                  <p style="margin:0 0 8px;font-size:16px;color:#64748b;">Guten Tag,</p>
                  <p style="margin:0 0 20px;font-size:20px;font-weight:700;color:#0f172a;">{{ $employee->first_name }} {{ $employee->last_name }}</p>

                  <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#475569;">
                    Im Rahmen Ihrer Anstellung wurden folgende Dokumente für Sie vorbereitet.
                    Bitte öffnen Sie jeden Link, lesen Sie das Dokument sorgfältig durch und unterschreiben Sie es digital.
                  </p>

                  {{-- Dokument-Liste --}}
                  @if(!empty($generatedDocuments))
                  <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Zu unterschreibende Dokumente</p>
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                    @foreach($generatedDocuments as $doc)
                    <tr>
                      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="padding-right:12px;">
                              <p style="margin:0;font-size:14px;font-weight:600;color:#1e293b;">{{ $doc->template->name ?? 'Dokument' }}</p>
                              @if($doc->signed_at)
                                <p style="margin:2px 0 0;font-size:12px;color:#059669;">✅ Bereits unterschrieben</p>
                              @else
                                <p style="margin:2px 0 0;font-size:12px;color:#d97706;">⏳ Unterschrift ausstehend</p>
                              @endif
                            </td>
                            <td style="white-space:nowrap;text-align:right;">
                              @if(!$doc->signed_at)
                              <a href="{{ route('document.sign', $doc->sign_token) }}"
                                 style="display:inline-block;background:#1e3a5f;color:#fff;text-decoration:none;padding:8px 18px;border-radius:6px;font-size:12px;font-weight:600;">
                                Unterschreiben →
                              </a>
                              @endif
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    @endforeach
                  </table>
                  @endif

                  {{-- Verträge --}}
                  @if(!empty($contracts))
                  <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Arbeitsvertrag</p>
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    @foreach($contracts as $contract)
                    <tr>
                      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="padding-right:12px;">
                              <p style="margin:0;font-size:14px;font-weight:600;color:#1e293b;">
                                Arbeitsvertrag — {{ $contract->contractTypeLabel() }}
                              </p>
                              @if($contract->employee_signed_at)
                                <p style="margin:2px 0 0;font-size:12px;color:#059669;">✅ Bereits unterschrieben</p>
                              @else
                                <p style="margin:2px 0 0;font-size:12px;color:#d97706;">⏳ Unterschrift ausstehend</p>
                              @endif
                            </td>
                            <td style="white-space:nowrap;text-align:right;">
                              @if(!$contract->employee_signed_at && $contract->employee_sign_token)
                              <a href="{{ route('contract.sign', $contract->employee_sign_token) }}"
                                 style="display:inline-block;background:#1e3a5f;color:#fff;text-decoration:none;padding:8px 18px;border-radius:6px;font-size:12px;font-weight:600;">
                                Unterschreiben →
                              </a>
                              @endif
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    @endforeach
                  </table>
                  @endif

                  {{-- Hinweis --}}
                  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                    <tr>
                      <td style="background-color:#eff6ff;border-left:4px solid #2563eb;border-radius:0 8px 8px 0;padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#1e40af;">
                          ℹ️ Diese Links sind personalisiert und gelten nur für Sie. Bitte teilen Sie sie nicht mit anderen.
                        </p>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0;font-size:15px;color:#475569;">Mit freundlichen Grüßen<br>
                    <strong style="color:#1e293b;">Ihr Arbeitgeber-Team</strong>
                  </p>

                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- Footer --}}
        <tr>
          <td style="padding:20px 40px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#94a3b8;">Diese E-Mail wurde automatisch von StationPilot generiert. &copy; {{ date('Y') }}</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
